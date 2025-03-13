<?php

namespace App\Services\DataSource;

use App\Contracts\DataSourceContract;
use App\Models\Article;
use App\Models\DataSource;
use App\Services\DataAggregatorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsAPIService extends DataAggregatorService implements DataSourceContract
{
    protected string $identifier = 'news-api';

    public function __construct()
    {
        $this->http = Http::baseUrl(config('aggregators.news_api.base_url'))
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Api-Key' => config('aggregators.news_api.api_key'),
            ]);
    }

    public function getNews(DataSource $dataSource, array $parameters = []): ?array
    {
        if($dataSource->last_published_at){
            $parameters['from-date'] = $dataSource->last_published_at->addSecond()->format('Y-m-d');
        }

        $response = $this->http->get($dataSource->uri, $parameters)->json();

        if ($response['status'] !== 'ok') {
            Log::info($response['message'] ?? 'An error occurred while fetching news');

            return null;
        }

        return $response;
    }

    public function processNews($page = 1): void
    {
        $dataSource = $this->getModel();
        $parameters = [...$this->resolveParameters($dataSource), 'page' => $page];
        $response = $this->getNews($dataSource, $parameters);

        if (! $response
        ) {
            return;
        }

        $this->storeToDatabase($dataSource, $response['articles']);

        $perPage = $parameters['pageSize'];
        $totalResults = $response['totalResults'];
        $totalPages = ceil($totalResults / $perPage);

        for ($i = 2; $i <= $totalPages; $i++) {
            $parameters['page'] = $i;
            $response = $this->getNews($dataSource, $parameters);

            if (! $response || ($this->processLimit && ($this->processLimit <= $this->processCount))) {
                break;
            }

            $this->storeToDatabase($dataSource, $response['articles']);
            $this->processLimit += count($response['articles']);
        }

    }

    public function storeToDatabase(DataSource $dataSource, array $news): void
    {
        DB::transaction(function () use ($dataSource, $news) {
            // get duplicates in db by data source id and url
            $duplicates = Article::where('data_source_id', $dataSource->id)
                ->whereIn('story_url', array_column($news, 'url'))
                ->get('story_url')
                ->pluck('story_url')
                ->toArray();

            $articles = collect($news)->map(function ($item) use ($dataSource) {
                return [
                    'id' => (string) Str::ulid(),
                    'data_source_id' => $dataSource->id,
                    'category' => $dataSource->fiters['category']['default'] ?? null,
                    'author' => $item['author'] ?? null,
                    'source' => $item['source']['name'] ?? null,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? null,
                    'story_url' => $item['url'] ?? '',
                    'image_url' => $item['urlToImage'] ?? null,
                    'content' => self::getNewsContent($item['url']),
                    'published_at' => Carbon::parse($item['publishedAt'] ?? Carbon::now()),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->filter(fn ($article) => ! in_array($article['story_url'], $duplicates) && $article['content'] !== null)
                ->all();

            Article::insert($articles);

            $mostRecentArticle = Article::where('data_source_id', $dataSource->id)
                ->orderByDesc('published_at')
                ->first('published_at');

            $dataSource->update([
                'last_sync_at' => now(),
                'last_published_at' => $mostRecentArticle?->published_at ?? now(),
            ]);
        });
    }
}
