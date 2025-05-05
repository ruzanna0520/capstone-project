<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sampleImages = [
            'placeholder.png'
        ];

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(2),
            'price' => $this->faker->randomFloat(2, 1000, 100000),
            'image_url' => 'products/' . $this->faker->randomElement($sampleImages),
            'category_id' => Category::inRandomOrder()->first()?->id,
        ];
    }
}
