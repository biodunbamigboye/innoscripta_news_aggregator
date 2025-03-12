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
                    'page-size' => 50,
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
                'name' => 'New York Times',
                'identifier' => 'new-york-times',
                'uri' => 'home.json',
                /*
                 * The possible uri value are: arts.json, automobiles.json, books/review.json, business.json,
                 * fashion.json, food.json, health.json, home.json, insider.json, magazine.json,
                 *  movies.json, nyregion.json, obituaries.json, opinion.json, politics.json, realestate.json,
                 *  science.json, sports.json, sundayreview.json, technology.json, theater.json, t-magazine.json,
                 * travel.json, upshot.json, us.json, and world.son
                 */
                'filters' => json_encode([]),
            ],
            [
                'name' => 'News API (Everything)',
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
                    'pageSize' => 50,
                    'q' => null,
                    'from' => null,
                    'to' => null,
                ]),
            ],
            [
                'name' => 'News API Top Headlines',
                'identifier' => 'news-api-top-headlines',
                'uri' => 'top-headlines',
                'filters' => json_encode([
                    'country' => [
                        'parameters' => ['us', 'gb', 'au', 'ca'],
                        'default' => null,
                    ],
                    'category' => [
                        'parameters' => ['business', 'entertainment', 'general', 'health', 'science', 'sports', 'technology'],
                        'default' => 'sport',
                    ],
                    'pageSize' => 50,
                    'q' => null,
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
