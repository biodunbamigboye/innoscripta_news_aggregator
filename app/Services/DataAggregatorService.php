<?php

namespace App\Services;

use App\Models\DataSource;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class DataAggregatorService
{
    protected PendingRequest $http;

    protected int $processCount = 0;
    protected ?int $processLimit = null;

    protected array $articleQueries;

//    public function __construct(?array $articleQueries = null)
//    {
//        $this->articleQueries = $articleQueries;
//    }

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
            $parameters['from-date'] = $dataSource->last_published_at->addSecond()->format('Y-m-d H:i:s');
            $parameters['to'] = now()->format('Y-m-d H:i:s');
            $parameters['to-date'] = now()->format('Y-m-d H:i:s');
        }

        return $parameters;
    }

    public function getModel(): DataSource
    {
        return DataSource::where('identifier', $this->identifier)->first();
    }

    public function getNewsContent(string $url): ?string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0',
        ])
            ->get($url);

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
        $articleQueries = $this->articleQueries ?? [
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
