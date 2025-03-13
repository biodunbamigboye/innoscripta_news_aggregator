<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\DataSource;
use App\Services\DataSource\NewsApiAIService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsAPIAIServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_it_can_process_news(): void
    {
        // Create a mock DataSource
        $dataSource = DataSource::factory()->create([
            'name' => 'News API AI Service',
            'identifier' => 'news-api-ai',
            'uri' => 'https://example.com/news',
            'filters' => [
                'category' => ['default' => 'general'],
                'pageSize' => 1,
            ],
            'last_published_at' => now()->subDay(),
        ]);

        // Mock the HTTP response
        Http::fake([
            'https://example.com/news*' => Http::response([
                'articles' => [
                    'results' => [
                        [
                            'uri' => '7546825937',
                            'lang' => 'eng',
                            'isDuplicate' => false,
                            'date' => '2023-05-16',
                            'time' => '10:35:00',
                            'dateTime' => '2023-05-16T10:35:00Z',
                            'dateTimePub' => '2023-05-16T10:34:00Z',
                            'dataType' => 'news',
                            'sim' => 0,
                            'url' => 'https://www.rmol.co/20230516/22538-tesla-changed-a-deadline-for-investor-proposals-angering-activists/',
                            'title' => 'Test Article',
                            'body' => "Tesla investors will have fewer opportunities to express discontent with management at the company's annual meeting, which is taking place Tuesday in Austin, Texas, (trimmed) ...",
                            'source' => [
                                'uri' => 'rmol.co',
                                'dataType' => 'news',
                                'title' => 'rmol.co',
                            ],
                            'authors' => [],
                            'concepts' => [
                                [
                                    'uri' => 'http://en.wikipedia.org/wiki/Tesla,_Inc.',
                                    'type' => 'org',
                                    'score' => 5,
                                    'label' => [
                                        'eng' => 'Tesla, Inc.',
                                    ],
                                ],
                                [
                                    'uri' => 'http://en.wikipedia.org/wiki/Shareholder',
                                    'type' => 'wiki',
                                    'score' => 4,
                                    'label' => [
                                        'eng' => 'Shareholder',
                                    ],
                                ],
                                [
                                    'uri' => 'http://en.wikipedia.org/wiki/Automotive_industry',
                                    'type' => 'wiki',
                                    'score' => 3,
                                    'label' => [
                                        'eng' => 'Automotive industry',
                                    ],
                                ],
                                [
                                    'uri' => 'http://en.wikipedia.org/wiki/Austin,_Texas',
                                    'type' => 'loc',
                                    'score' => 3,
                                    'label' => [
                                        'eng' => 'Austin, Texas',
                                    ],
                                    'location' => [
                                        'type' => 'place',
                                        'label' => [
                                            'eng' => 'Austin, Texas',
                                        ],
                                        'country' => [
                                            'type' => 'country',
                                            'label' => [
                                                'eng' => 'United States',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    '#META ITEM' => 'other concepts...',
                                ],
                            ],
                            'image' => 'https://www.rmol.co/wp-content/uploads/2023/05/16tesla-fckq-facebookJumbo-4859007.jpg',
                            'eventUri' => null,
                            'shares' => [],
                            'sentiment' => 0.01960784313725483,
                            'wgt' => 421929300,
                            'relevance' => 100,
                        ],
                    ],
                    'totalResults' => 38246,
                    'page' => 1,
                    'count' => 100,
                    'pages' => 383,
                ],
            ], 200),
        ]);

        // Instantiate the service
        $service = new NewsApiAIService;

        // Call the method to process news
        $service->processNews(1);

        // Assert the response
        $this->assertCount(1, Article::all());
        $this->assertEquals('Test Article', Article::first()->title);
    }
}
