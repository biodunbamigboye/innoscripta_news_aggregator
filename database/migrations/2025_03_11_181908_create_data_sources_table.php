<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    : void {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('identifier', 20)->unique();
            $table->string('base_url')->unique();
            $table->string('api_key')->nullable();
            $table->string('api_key_param')->default('api-key')->nullable();
            $table->enum('content_type', ['json', 'html', 'url', 'string'])->default('string');
            $table->string('custom_resolver_class')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('next_sync_publication_date')->nullable();
            $table->timestamps();
        });

        /*
        Insert default data sources - Note that the API keys are encrypted in the database
        Data is inserted in migration because seeder is meant for test data while these are default data sources
        that will be used by the application
        */

        \Illuminate\Support\Facades\DB::table('data_sources')->insert(collect([
            [
                'name'          => 'The Guardian',
                'identifier'    => 'the-guardian',
                'base_url'      => 'https://content.guardianapis.com/search',
                'api_key'       => config('aggregator_api_keys.the_guardian'),
                'api_key_param' => 'api-key',
                'content_type'  => 'url',
            ],
            [
                'name'          => 'New York Times',
                'identifier'    => 'new-york-times',
                'base_url'      => 'https://api.nytimes.com/svc/topstories/v2',
                'api_key'       => config('aggregator_api_keys.new-york-times'),
                'api_key_param' => 'api-key',
                'content_type'  => 'url',
            ],
            [
                'name'          => 'News API',
                'identifier'    => 'news-api',
                'base_url'      => 'https://newsapi.org/v2/everything',
                'api_key'       => config('aggregator_api_keys.news-api'),
                'api_key_param' => 'apiKey',
                'content_type'  => 'string',
            ],
            [
                'name'          => 'News API ai',
                'identifier'    => 'news-api-ai',
                'base_url'      => 'https://eventregistry.org/api/v1/article/getArticles',
                'api_key'       => config('aggregator_api_keys.news-api-ai'),
                'api_key_param' => 'apiKey',
                'content_type'  => 'json',
            ]

        ])
            ->map(function ($item) {
                $item['api_key'] = encrypt($item['api_key']);
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
    public function down()
: void {
    Schema::dropIfExists('data_sources');
}
};
