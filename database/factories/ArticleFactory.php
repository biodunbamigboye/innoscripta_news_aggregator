<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'story_url' => $this->faker->unique()->url(),
            'image_url' => $this->faker->imageUrl(),
            'content' => $this->faker->paragraphs(3, true),
            'author' => $this->faker->unique()->name(),
            'category' => $this->faker->unique()->word,
            'source' => $this->faker->company,
            'published_at' => $this->faker->dateTime,
            'data_source_identifier' => 'the-guardian',
        ];
    }
}
