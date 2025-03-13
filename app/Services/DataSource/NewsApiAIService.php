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

class NewsApiAIService extends DataAggregatorService implements DataSourceContract
{
    protected string $identifier = 'news-api-ai';

    public function __construct()
    {
        $this->http = Http::baseUrl(config('aggregators.news_api_ai.base_url'))
            ->withHeaders([
                'Accept' => 'application/json',
            ])->withQueryParameters([
                'apiKey' => config('aggregators.news_api_ai.api_key'),
            ]);
    }

    public function getNews(DataSource $dataSource, array $parameters = []): ?array
    {
        if ($dataSource->last_published_at) {
            $parameters['dateStart'] = $dataSource->last_published_at->format('Y-m-d');
            $parameters['dateEnd'] = now()->format('Y-m-d');
        }

        $response = $this->http->get($dataSource->uri, $parameters)->json();

        if (! isset($response['articles']['results'])) {
            Log::error('Failed to retrieve news from News API AI.', $response);

            return null;
        }

        return $response['articles']['results'];
    }

    public function processNews($page = 1): void
    {
        $dataSource = $this->getModel();
        $response = $this->getNews($dataSource, $this->resolveParameters($dataSource));

        if (! $response) {
            return;
        }

        $this->storeToDatabase($dataSource, $response);
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

            $articles = collect($news)->map(function (array $item) use ($dataSource) {
                return [
                    'id' => (string) Str::ulid(),
                    'data_source_id' => $dataSource->id,
                    'data_source_identifier' => $item['uri'] ?? null,
                    'category' => $dataSource['filters']['keyword'] ?? null,
                    'author' => implode(', ', array_column($item['authors'] ?? [], 'name')), // compute authors
                    'source' => $item['source']['uri'] ?? null,
                    'title' => $item['title'] ?? '',
                    'description' => mb_convert_encoding(substr($item['body'] ?? '', 0, 100), 'UTF-8', 'auto'),
                    'story_url' => $item['url'] ?? null,
                    'image_url' => $item['image'] ?? null,
                    'content' => mb_convert_encoding($item['body'] ?? '', 'UTF-8', 'auto'),
                    'published_at' => Carbon::parse($item['2025-03-13T06:30:14'] ?? Carbon::now()),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->filter(fn ($article) => ! in_array($article['story_url'], $duplicates) && $article['content'] !== null)
                ->unique('story_url')
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
