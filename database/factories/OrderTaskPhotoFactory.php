<?php

namespace Database\Factories;

use App\Models\OrderTask;
use App\Models\OrderTaskPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderTaskPhoto>
 */
class OrderTaskPhotoFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_task_id' => OrderTask::factory(),
            'type' => 'before',
            'path' => 'orders/'.fake()->numberBetween(1, 999).'/tasks/photo.webp',
            'status' => OrderTaskPhoto::STATUS_DONE,
        ];
    }

    /** @return Factory<OrderTaskPhoto> */
    public function before(): Factory
    {
        return $this->state(fn () => ['type' => 'before']);
    }

    /** @return Factory<OrderTaskPhoto> */
    public function after(): Factory
    {
        return $this->state(fn () => ['type' => 'after']);
    }

    /** @return Factory<OrderTaskPhoto> */
    public function pending(): Factory
    {
        return $this->state(fn () => ['status' => OrderTaskPhoto::STATUS_PENDING]);
    }
}
