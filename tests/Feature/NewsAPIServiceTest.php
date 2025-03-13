<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\DataSource;
use App\Services\DataSource\NewsAPIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsAPIServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_news(): void
    {
        // Create a mock DataSource
        $dataSource = DataSource::factory()->create([
            'name' => 'News API',
            'identifier' => 'news-api',
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
                'status' => 'ok',
                'totalResults' => 1,
                'articles' => [
                    [
                        'source' => ['id' => null, 'name' => 'Example'],
                        'author' => 'John Doe',
                        'title' => 'Test Article',
                        'description' => 'This is a test article.',
                        'url' => 'https://example.com/test-article',
                        'urlToImage' => 'https://example.com/test-article.jpg',
                        'publishedAt' => '2023-10-01T00:00:00Z',
                        'content' => 'This is the content of the test article.',
                    ],
                ],
            ], 200),
        ]);

        // Instantiate the service
        $service = new NewsAPIService;

        // Call the method to process news
        $service->processNews(1);

        // Assert the response
        $this->assertCount(1, Article::all());
        $this->assertEquals('Test Article', Article::first()->title);
    }
}
