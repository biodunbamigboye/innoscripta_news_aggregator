<?php

namespace Tests\Feature;

use App\Models\Article;
use Carbon\Carbon;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    public function test_it_can_get_articles(): void
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create();

        $this->getJson(route('Get Articles'))
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'story_url',
                        'image_url',
                        'author',
                        'category',
                        'source',
                        'published_at',
                    ],
                ],
            ]);
    }

    public function test_it_can_search_article()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create();

        $article = Article::factory()->for($dataSource)->create([
            'title' => 'This is a title',
            'description' => 'This is a custom article',
            'author' => 'John Doe',
            'category' => 'Technology',
            'source' => 'The Guardian',
            'published_at' => now(),
        ]);

        $this->getJson(route('Get Articles', ['search' => 'title']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'title' => 'This is a title',
                'description' => 'This is a custom article',
                'author' => 'John Doe',
                'category' => 'Technology',
                'source' => 'The Guardian',
                'published_at' => $article->published_at->toISOString(),
            ]);
    }

    public function test_it_can_get_article_by_id()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        $article = Article::factory()->for($dataSource)->create();

        $this->getJson(route('Get Article', ['article' => $article->id]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $article->id,
                'title' => $article->title,
                'description' => $article->description,
                'story_url' => $article->story_url,
                'image_url' => $article->image_url,
                'author' => $article->author,
                'category' => $article->category,
                'source' => $article->source,
                'published_at' => $article->published_at->toISOString(),
            ]);
    }

    public function test_it_can_get_authors()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create();

        $this->getJson(route('Get Authors'))
            ->assertOk()
            ->assertJsonCount(20);
    }

    public function test_it_can_search_authors()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create();

        Article::factory()->count(4)->for($dataSource)->create(['author' => 'John Doe']);

        $this->getJson(route('Get Authors', ['search' => 'John Doe']))
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_it_can_get_sources()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create();
        Article::factory()->count(5)->for($dataSource)->create(['source' => '']);

        $this->getJson(route('Get Sources'))
            ->assertOk()
            ->assertJsonCount(20);
    }

    public function test_it_can_search_sources()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(3)->for($dataSource)->create();

        Article::factory()->count(2)->for($dataSource)->create(['source' => 'BBC']);

        $this->getJson(route('Get Sources', ['search' => 'BBC']))
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_it_can_get_categories()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create();

        $this->getJson(route('Get Categories'))
            ->assertOk()
            ->assertJsonCount(20);
    }

    public function test_it_can_search_categories()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(3)->for($dataSource)->create();

        Article::factory()->count(2)->for($dataSource)->create(['category' => 'Technology']);

        $this->getJson(route('Get Categories', ['search' => 'Technology']))
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_it_can_filter_by_author()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create();

        Article::factory()->count(5)->for($dataSource)->create(['author' => 'John Doe']);

        $this->getJson(route('Get Articles', ['author' => 'John Doe']))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonFragment([
                'author' => 'John Doe',
            ]);
    }

    public function test_it_can_filter_by_category()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create();

        Article::factory()->count(5)->for($dataSource)->create(['category' => 'Technology']);

        $this->getJson(route('Get Articles', ['category' => 'Technology']))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonFragment([
                'category' => 'Technology',
            ]);
    }

    public function test_it_can_filter_by_source()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create();

        Article::factory()->count(5)->for($dataSource)->create(['source' => 'The Guardian']);

        $this->getJson(route('Get Articles', ['source' => 'The Guardian']))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonFragment([
                'source' => 'The Guardian',
            ]);
    }

    public function test_it_can_filter_by_from_date()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create([
            'published_at' => now()->subDays(15),
        ]);

        Article::factory()->count(5)->for($dataSource)->create(['published_at' => now()->subDays(10)]);

        $response = $this->getJson(route('Get Articles', ['from_date' => now()->subDays(10)->toDateString()]))
            ->assertOk()
            ->assertJsonCount(5, 'data');

        collect($response->json('data'))->each(function ($article) {
            $this->assertTrue(Carbon::parse($article['published_at'])->isBefore(now()->subDays(10)));
        });
    }

    public function test_it_can_filter_by_date_range()
    {
        $dataSource = \App\Models\DataSource::factory()->create();

        Article::factory()->count(20)->for($dataSource)->create([
            'published_at' => now()->subDays(20),
        ]);

        Article::factory()->count(5)->for($dataSource)->create(['published_at' => now()->subDays(10)]);
        Article::factory()->count(10)->for($dataSource)->create(['published_at' => now()->subDays(13)]);

        Article::factory()->count(12)->for($dataSource)->create([
            'published_at' => now()->subDays(3),
        ]);

        $response = $this->getJson(route('Get Articles', [
            'from_date' => now()->subDays(15)->toDateString(),
            'to_date' => now()->subDays(10)->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonCount(15, 'data');

        collect($response->json('data'))->each(function ($article) {
            $this->assertTrue(Carbon::parse($article['published_at'])->isBetween(now()->subDays(15), now()->subDays(10)));
        });
    }
}
