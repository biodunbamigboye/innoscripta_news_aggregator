<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataSource>
 */
class DataSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'uri' => $this->faker->url(),
            'sync_interval' => $this->faker->numberBetween(1, 30),
            'last_sync_at' => $this->faker->dateTime(),
            'last_published_at' => $this->faker->dateTime(),
            'filters' => [],
        ];
    }
}
