<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
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
            $table->string('identifier', 50)->unique();
            $table->string('uri')->default('');
            $table->boolean('is_active')->default(true);
            $table->time('sync_start_time')->nullable();
            $table->integer('sync_interval')->default(60);
            $table->integer('max_processed_per_sync')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_published_at')->nullable();
            $table->json('filters')->nullable();
            $table->timestamps();
        });

        /*
        Insert default data sources
        Data is inserted in migration because these are default data sources
        that will be used by the application
        */

        Artisan::call('db:seed', ['--class' => 'DataSourceSeeder']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_sources');
    }
};
