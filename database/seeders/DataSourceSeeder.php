<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::table('data_sources')->insert(collect([
            [
                'name' => 'The Guardian',
                'identifier' => 'the-guardian',
                'uri' => 'search', // can be sections
                'filters' => json_encode([
                    'section' => [
                        'parameters' => ['world', 'politics', 'business', 'technology', 'science', 'environment', 'football'],
                        'default' => 'world',
                    ],
                    'show-fields' => [
                        'parameters' => ['all', 'thumbnail', 'headline', 'body', 'byline'],
                        'default' => 'all',
                    ],
                    'show-elements' => [
                        'parameters' => ['all', 'image', 'video'],
                        'default' => 'all',
                    ],
                    'show-blocks' => [
                        'parameters' => ['all', 'main', 'body'],
                        'default' => 'all',
                    ],
                    'page' => 1,
                    'page-size' => 5,
                    'order-by' => [
                        'parameters' => ['newest', 'oldest', 'relevance'],
                        'default' => 'newest',
                    ],
                    'q' => null,
                    'from-date' => null,
                    'to-date' => null,
                ]),
            ],
            [
                'name' => 'News API',
                'identifier' => 'news-api',
                'uri' => 'everything', // can be top-headlines
                'filters' => json_encode([
                    'country' => [
                        'parameters' => ['us', 'gb', 'au', 'ca'],
                        'default' => null,
                    ],
                    'language' => [
                        'parameters' => ['en', 'fr', 'de', 'es'],
                        'default' => null,
                    ],
                    'sources' => [
                        'parameters' => ['bbc-news', 'cnn', 'fox-news', 'google-news', 'reuters', 'the-verge'],
                        'default' => 'bbc-news',
                    ],
                    'domains' => [
                        'parameters' => ['bbc.co.uk', 'cnn.com', 'foxnews.com', 'google.com', 'reuters.com', 'theverge.com'],
                        'default' => null,
                    ],
                    'sortBy' => [
                        'parameters' => ['publishedAt', 'popularity', 'relevancy'],
                        'default' => 'publishedAt',
                    ],
                    'pageSize' => 5,
                    'q' => null,
                    'from' => null,
                    'to' => null,
                ]),
            ],
            [
                'name' => 'News API ai',
                'identifier' => 'news-api-ai',
                'uri' => '',
                'filters' => json_encode([
                    'dataType' => 'news',
                    'articlesPage' => 1,
                    'articlesCount' => 5,
                    'articlesSortBy' => 'date',
                    'articlesSortByAsc' => false,
                    'keyword' => 'world',
                ]),
            ],

        ])
            ->map(function ($item) {
                $item['created_at'] = now();
                $item['updated_at'] = now();

                return $item;
            })
            ->toArray()
        );
    }
}
