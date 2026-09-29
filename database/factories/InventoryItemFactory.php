<?php

namespace Database\Factories;

use App\Enums\InventoryCategory;
use App\Models\Classroom;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement(InventoryCategory::cases());

        return [
            'classroom_id' => Classroom::factory(),
            'category' => $category,
            'name' => fake()->unique()->randomElement($category->defaultItems()).' '.fake()->unique()->numberBetween(1, 9999),
            'sort_order' => 0,
            'good_quantity' => 0,
            'damaged_quantity' => 0,
        ];
    }
}
