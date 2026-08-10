<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\NotificationSent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationSent>
 */
class NotificationSentFactory extends Factory
{
    protected $model = NotificationSent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject_type' => Episode::class,
            'subject_id' => Episode::factory(),
            'channel' => 'mail',
            'sent_at' => now(),
        ];
    }
}
