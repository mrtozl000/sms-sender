<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'phone_number' => $this->faker->regexify('\+90[5][0-9]{9}'),
            'content' => $this->faker->sentence(10),
            'is_sent' => false,
            'message_id' => null,
            'sent_at' => null,
            'attempts' => 0,
            'last_error' => null,
        ];
    }

    /**
     * Gönderilmiş mesaj state'i
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sent' => true,
            'message_id' => 'msg_' . $this->faker->uuid(),
            'sent_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'attempts' => 1,
        ]);
    }

    /**
     * Başarısız mesaj state'i
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sent' => false,
            'attempts' => 3,
            'last_error' => $this->faker->randomElement([
                'Connection timeout',
                'Invalid phone number',
                'Service unavailable',
                'Rate limit exceeded'
            ]),
        ]);
    }

    /**
     * Kısa mesaj içeriği
     */
    public function shortContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => $this->faker->sentence(3),
        ]);
    }

    /**
     * Uzun mesaj içeriği
     */
    public function longContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => $this->faker->text(160),
        ]);
    }
}
