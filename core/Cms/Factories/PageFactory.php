<?php

namespace PnShop\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PnShop\Cms\Models\Page;
use PnShop\Cms\PageStatus;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return ['title' => $this->faker->unique()->sentence(3), 'status' => PageStatus::Draft];
    }

    public function published(): static
    {
        return $this->state(['status' => PageStatus::Published, 'published_at' => now()->subMinute()]);
    }
}
