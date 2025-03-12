<?php

namespace App\Services\DataSource;

use App\Contracts\DataSourceContract;
use App\Models\Article;
use App\Models\DataSource;
use App\Services\DataAggregatorService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewYorkTimesService extends DataAggregatorService implements DataSourceContract {
    protected string $identifier = 'new-york-times';

    public function __construct()
    {
        $this->http = Http::baseUrl(config('aggregators.new_york_times.base_url'))
            ->withHeaders([
                'Accept' => 'application/json',
            ])->withQueryParameters([
                'api-key' => config('aggregators.new_york_times.api_key'),
            ]);

        $this->articleQueries = [
            '//section[contains(@name, "articleBody")]//p',
            '//div[contains(@class, "StoryBodyCompanionColumn")]//p', // NYT specific content wrapper
            '//div[contains(@class, "css-1yxu27x")]//p', // NYT article paragraphs
            '//div[contains(@class, "css-1r7ky0e")]//p' // Another common NYT content block
        ];
    }

    public function getNews(DataSource $dataSource, array $parameters = [])
    : ?array {
        $response = $this->http->get($dataSource->uri, [])->json();

//        if (($response['status'] ?? null) !== 'Ok') {
//            Log::info($response['message'] ?? 'An error occurred while fetching news, New York Times');
//
//            return null;
//        }

        return $response;
    }

    public function processNews($page = 1)
    : void {
        $dataSource = $this->getModel();
        $response   = $this->getNews($dataSource);


        if (!$response
        ) {
            return;
        }

        $this->storeToDatabase($dataSource, $response['results']);
    }

    public function storeToDatabase(DataSource $dataSource, array $news)
    : void {
        Log::info("here");
        DB::transaction(function () use ($dataSource, $news) {
            // get duplicates in db by data source id and url
            $duplicates = Article::where('data_source_id', $dataSource->id)
                ->whereIn('story_url', array_column($news, 'url'))
                ->get('story_url')
                ->pluck('story_url')
                ->toArray();

            $articles = collect($news)->map(function ($item) use ($dataSource) {
                return [
                    'id'             => (string)Str::ulid(),
                    'data_source_id' => $dataSource->id,
                    'category'       => $item['section'] ?? null,
                    'author'         => $item['byline'] ?? null,
                    'source'         => 'The New York Times',
                    'title'          => $item['title'] ?? '',
                    'description'    => $item['abstract'] ?? null,
                    'story_url'      => $item['url'],
                    'image_url'      => $item['multimedia'][0]['url'] ?? null,
                    'content'        => self::getNewsContent($item['url']),
                    'published_at'   => Carbon::parse($item['published_date'] ?? Carbon::now()),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            })->filter(fn($article) => !in_array($article['story_url'], $duplicates) && $article['content'] !== null)
                ->all();

            Article::insert($articles);

            $mostRecentArticle = Article::where('data_source_id', $dataSource->id)
                ->orderByDesc('published_at')
                ->first('published_at');

            $dataSource->update([
                'last_sync_at'      => now(),
                'last_published_at' => $mostRecentArticle?->published_at ?? now(),
            ]);
        });
    }

}
