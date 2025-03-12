<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('identifier', 20)->unique();
            $table->string('uri')->default('');
            $table->boolean('is_active')->default(true);
            $table->time('sync_start_time')->nullable();
            $table->integer('sync_interval')->default(60);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_published_at')->nullable();
            $table->json('filters')->nullable();
            $table->timestamps();
        });

        /*
        Insert default data sources
        Data is inserted in migration because seeder is meant for test data while these are default data sources
        that will be used by the application
        */

        \Illuminate\Support\Facades\DB::table('data_sources')->insert(collect([
            [
                'name' => 'The Guardian',
                'identifier' => 'the-guardian',
                'uri' => '',
                'filters' => json_encode([]),
            ],
            [
                'name' => 'New York Times',
                'identifier' => 'new-york-times',
                'uri' => '',
                'filters' => json_encode([]),
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
                    'category' => [
                        'parameters' => ['business', 'entertainment', 'general', 'health', 'science', 'sports', 'technology'],
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
                'name' => 'News API ai',
                'identifier' => 'news-api-ai',
                'uri' => '',
                'filters' => json_encode([]),
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_sources');
    }
};
