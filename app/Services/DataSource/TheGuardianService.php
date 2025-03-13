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

class TheGuardianService extends DataAggregatorService implements DataSourceContract {
    protected string $identifier = 'the-guardian';

    public function __construct() {
        $this->http = Http::baseUrl(config('aggregators.the_guardian.base_url'))
            ->withHeaders([
                'Accept' => 'application/json',
            ])->withQueryParameters([
                'api-key' => config('aggregators.the_guardian.api_key'),
            ]);
    }

    public function getNews(DataSource $dataSource, array $parameters = [])
    : ?array {
        if ($dataSource->last_published_at) {
            $parameters['from'] = $dataSource->last_published_at->addSecond()->format('Y-m-d H:i:s');
            $parameters['to']   = now()->format('Y-m-d H:i:s');
        }

        $response = $this->http->get($dataSource->uri, $parameters)->json();

        if (($response['response']['status'] ?? null) !== 'ok') {
            Log::info($response['message'] ?? 'An error occurred while fetching news The Guardian API');

            return $response;
        }

        return $response;
    }

    public function processNews($page = 1)
    : void {
        $dataSource         = $this->getModel();
        $this->processLimit = $dataSource->max_article_per_sync;
        $parameters         = [...$this->resolveParameters($dataSource), 'page' => $page];
        $response           = $this->getNews($dataSource, $parameters);

        if (!$response
        ) {
            return;
        }

        $this->storeToDatabase($dataSource, $response['response']['results']);

        $totalPages = $response['response']['pages'];

        for ($i = 2; $i <= $totalPages; $i++) {
            $parameters['page'] = $i;
            $response           = $this->getNews($dataSource, $parameters);

            if (!$response || ($this->processLimit && ($this->processLimit <= $this->processCount))) {
                break;
            }

            $this->storeToDatabase($dataSource, $response['response']['results']);
            $this->processLimit += count($response['response']['results']);
        }

    }

    public function storeToDatabase(DataSource $dataSource, array $news)
    : void {
        if (empty($news)) {
            return;
        }

        DB::transaction(function () use ($dataSource, $news) {
            // get duplicates in db by data source id and url
            $duplicates = Article::where('data_source_id', $dataSource->id)
                ->whereIn('data_source_identifier', array_column($news, 'id'))
                ->get('story_url')
                ->pluck('story_url')
                ->toArray();

            $articles = collect($news)->map(function ($item) use ($dataSource) {
                return [
                    'id'                     => (string)Str::ulid(),
                    'data_source_id'         => $dataSource->id,
                    'data_source_identifier' => $item['id'] ?? null,
                    'author'                 => $item['author'] ?? null,
                    'source'                 => 'The Guardian',
                    'category'               => $item['sectionName'] ?? null,
                    'title'                  => $item['webTitle'] ?? '',
                    'description'            => $item['fields']['trailText'] ?? null,
                    'story_url'              => $item['webUrl'] ?? '',
                    'image_url'              => $this->extractCoverImage($item),
                    'content'                => mb_convert_encoding($item['fields']['body'] ?? null, 'UTF-8', 'auto'),
                    'published_at'           => Carbon::parse($item['webPublicationDate'] ?? Carbon::now()),
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];
            })->filter(fn($article) => !in_array($article['story_url'], $duplicates) && $article['content'] !== null)
                ->unique('story_url')
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

    public function getNewsContent(string $url)
    : ?string {
        $response = Http::withQueryParameters([
            'api-key' => config('aggregators.the_guardian.api_key'),
        ])->get($url);

        if (!$response->successful()) {
            return 'Failed to retrieve content.';
        }

        $html = $response->body();

        return $html;
    }

    public function extractCoverImage(array $item)
    : ?string {
        $src = $this->extractImageSrc($item['fields']['main'] ?? '');

        if (empty($src)) {
            $src = self::extractCoverImageFromBody($item['webUrl']);
        }

        return $src;
    }

    public function extractImageSrc(string $html)
    : string {
        if (empty($html)) {
            return '';
        }

        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        return $xpath->evaluate('string(//img/@src)');
    }

    public function extractCoverImageFromBody(string $url)
    : ?string {
        // look  for picture tag with itemprop="contentUrl"

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0',
        ])
            ->get($url);

        if (!$response->successful()) {
            return null;
        }

        $html = $response->body();
        $dom  = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        return $xpath->evaluate("string(//picture[@itemprop='contentUrl']/img/@src)");
    }
}
