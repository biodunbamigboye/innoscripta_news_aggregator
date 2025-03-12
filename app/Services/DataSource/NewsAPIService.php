<?php

namespace App\Services\DataSource;

use App\Contracts\DataSourceContract;
use App\Models\Article;
use App\Models\DataSource;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsAPIService implements DataSourceContract
{
    private PendingRequest $http;

    private string $identifier = 'news-api';

    public function __construct()
    {
        $this->http = Http::baseUrl(config('aggregators.news_api.base_url'))
            ->withHeaders([
                'X-Api-Key' => config('aggregators.news_api.api_key'),
                'Accept' => 'application/json',
            ]);
    }

    public function getNews(DataSource $dataSource, array $parameters = []): ?array
    {
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

        dump($totalResults, $totalPages);

        for ($i = 2; $i <= $totalPages; $i++) {
            sleep(5);
            dump("Loop $i");
            $parameters['page'] = $i;
            $response = $this->getNews($dataSource, $parameters);

            if (! $response) {
                break;
            }

            $this->storeToDatabase($dataSource, $response['articles']);
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
                    'author' => $item['author'] ?? null,
                    'source' => $item['source']['name'] ?? null,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? null,
                    'story_url' => $item['url'] ?? '',
                    'image_url' => $item['urlToImage'] ?? null,
                    'content' => $item['content'] ?? null,
                    'html_content' => self::getNewsContent($item['url']),
                    'published_at' => Carbon::parse($item['publishedAt'] ?? Carbon::now()),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->filter(fn ($article) => ! in_array($article['story_url'], $duplicates) && $article['html_content'] !== null)
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

    public function resolveParameters(DataSource $dataSource): array
    {
        $parameters = [];

        if ($dataSource->filters && is_array($dataSource->filters)) {
            $parameters = collect($dataSource->filters)
                ->map(function ($filter) {
                    return isset($filter['parameters']) ? $filter['default'] : $filter;
                })
                ->filter(fn ($filter) => $filter !== null)
                ->toArray();
        }

        if ($dataSource->last_published_at) {
            $parameters['from'] = $dataSource->last_published_at->addSecond()->format('Y-m-d H:i:s');
            $parameters['to'] = now()->format('Y-m-d H:i:s');
        }

        return $parameters;
    }

    public function getModel(): DataSource
    {
        return DataSource::where('identifier', $this->identifier)->first();
    }

    public static function getNewsContent(string $url): ?string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0',
        ])->get($url);

        if (! $response->successful()) {
            return 'Failed to retrieve content.';
        }

        $html = $response->body();

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        // Try to extract the article title
        $titleQuery = '//h1';
        $titleNode = $xpath->query($titleQuery);
        $title = $titleNode->length ? self::cleanHtml($titleNode->item(0)->nodeValue) : 'No title found';

        // Try to extract article content from common article tags
        $articleQueries = [
            '//article//p', // BBC, CNN, NYTimes, etc.
            '//div[contains(@class, "article-body")]//p',
            '//div[contains(@class, "story-body")]//p',
            '//div[contains(@class, "content")]//p',
        ];

        $content = '';
        foreach ($articleQueries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes->length) {
                foreach ($nodes as $node) {
                    $content .= '<p>'.self::cleanHtml($node->nodeValue).'</p>';
                }
                break;
            }
        }

        if (empty($content)) {
            return null;
        }

        return "<section><h1>$title</h1><div>$content</div></section>";
    }

    public static function cleanHtml(string $html): string
    {
        $text = trim($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // Decode special HTML entities
        $text = preg_replace('/[\x00-\x1F\x7F-\x9F\xAD\xA0]/u', ' ', $text); // Remove non-printable characters
        $text = preg_replace('/\s+/u', ' ', $text); // Normalize excessive whitespace
        $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252'); // Fix encoding issues
        $text = str_replace('™', "'", $text);
        $text = str_replace(['Ã¢â‚¬â„¢', 'Ã¢â‚¬', 'Ã¢', 'â€œ', 'â€', 'â€™', 'â€“', 'â€¦', '™'], '', $text); // Replace encoding artifacts

        return $text;
    }
}
