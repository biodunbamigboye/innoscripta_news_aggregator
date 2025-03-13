<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\DataSource;
use App\Services\DataSource\TheGuardianService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TheGuardianServiceTest extends TestCase
{
    public function test_it_can_process_news()
    {
        // Create a mock DataSource
        DataSource::factory()->create([
            'name' => 'The Guardian',
            'identifier' => 'the-guardian',
            'uri' => 'https://example.com/the-guardian',
            'filters' => [
                'section' => ['default' => 'world'],
                'page-size' => 1,
            ],
            'last_published_at' => now()->subDay(),
        ]);

        // Mock the HTTP response
        Http::fake([
            'https://example.com/the-guardian*' => Http::response([
                'response' => [
                    'status' => 'ok',
                    'total' => 1,
                    'pages' => 10,
                    'results' => [
                        [
                            'id' => 'world/2023/oct/01/test-article',
                            'type' => 'article',
                            'sectionId' => 'world',
                            'sectionName' => 'World news',
                            'webPublicationDate' => '2023-10-01T00:00:00Z',
                            'webTitle' => 'Test Article',
                            'webUrl' => 'https://example.com/test-article',
                            'apiUrl' => 'https://example.com/test-article.json',
                            'fields' => [
                                'byline' => 'John Doe',
                                'thumbnail' => 'https://example.com/test-article.jpg',
                                'bodyText' => 'This is the content of the test article.',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Instantiate the service
        $service = new TheGuardianService;

        // Call the method to process news
        $service->processNews();

        // Assert the response
        $this->assertCount(1, Article::all());
        $this->assertEquals('Test Article', Article::first()->title);
    }

    public function test_duplicate_cannot_be_created()
    {
        // Create a mock DataSource
        DataSource::factory()->create([
            'name' => 'The Guardian',
            'identifier' => 'the-guardian',
            'uri' => 'https://example.com/the-guardian',
            'filters' => [
                'section' => ['default' => 'world'],
                'page-size' => 1,
            ],
            'last_published_at' => now()->subDay(),
        ]);

        // Mock the HTTP response
        Http::fake([
            'https://example.com/the-guardian*' => Http::response([
                'response' => [
                    'status' => 'ok',
                    'total' => 2,
                    'pages' => 10,
                    'results' => [
                        [
                            'id' => 'world/2023/oct/01/test-article',
                            'type' => 'article',
                            'sectionId' => 'world',
                            'sectionName' => 'World news',
                            'webPublicationDate' => '2023-10-01T00:00:00Z',
                            'webTitle' => 'Test Article',
                            'webUrl' => 'https://example.com/test-article',
                            'apiUrl' => 'https://example.com/test-article.json',
                            'fields' => [
                                'byline' => 'John Doe',
                                'thumbnail' => 'https://example.com/test-article.jpg',
                                'bodyText' => 'This is the content of the test article.',
                            ],
                        ],
                        [
                            'id' => 'world/2023/oct/01/test-article',
                            'type' => 'article',
                            'sectionId' => 'world',
                            'sectionName' => 'World news',
                            'webPublicationDate' => '2023-10-01T00:00:00Z',
                            'webTitle' => 'Test Article',
                            'webUrl' => 'https://example.com/test-article',
                            'apiUrl' => 'https://example.com/test-article.json',
                            'fields' => [
                                'byline' => 'John Doe',
                                'thumbnail' => 'https://example.com/test-article.jpg',
                                'bodyText' => 'This is the content of the test article.',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Instantiate the service
        $service = new TheGuardianService;

        // Call the method to process news
        $service->processNews();

        // Assert the response
        $this->assertCount(1, Article::all());
    }
}
