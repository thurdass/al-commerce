<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use App\ProductAvailabilityStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('########'),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 1, 10000),
            'stock_quantity' => null,
            'availability_status' => ProductAvailabilityStatus::Available,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'availability_status' => ProductAvailabilityStatus::Inactive,
            'active' => false,
        ]);
    }
}
