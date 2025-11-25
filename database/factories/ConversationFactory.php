<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'direct',
            'subject' => null,
            'context_type' => null,
            'context_id' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the conversation is a group conversation.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'group',
            'subject' => fake()->sentence(),
        ]);
    }
}
