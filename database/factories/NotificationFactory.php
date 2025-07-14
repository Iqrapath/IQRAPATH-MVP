<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['system', 'custom', 'payment', 'class', 'subscription', 'feature'];
        $statuses = ['draft', 'scheduled', 'sent', 'delivered', 'read', 'failed'];
        $senderTypes = ['system', 'admin', 'teacher'];
        
        // Get a random admin user for sender_id if sender_type is admin or teacher
        $senderType = $this->faker->randomElement($senderTypes);
        $senderId = null;
        
        if ($senderType !== 'system') {
            $role = $senderType === 'admin' ? 'admin' : 'teacher';
            $user = User::where('role', $role)->inRandomOrder()->first();
            $senderId = $user ? $user->id : null;
        }
        
        return [
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(3),
            'type' => $this->faker->randomElement($types),
            'status' => $this->faker->randomElement($statuses),
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'scheduled_at' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('now', '+2 weeks') : null,
            'sent_at' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'metadata' => $this->faker->boolean(50) ? [
                'priority' => $this->faker->randomElement(['high', 'medium', 'low']),
                'category' => $this->faker->randomElement(['announcement', 'alert', 'update', 'reminder']),
                'additional_info' => $this->faker->sentence(),
            ] : null,
        ];
    }
    
    /**
     * Indicate that the notification is in draft status.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
                'sent_at' => null,
            ];
        });
    }
    
    /**
     * Indicate that the notification is scheduled.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function scheduled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'scheduled',
                'scheduled_at' => $this->faker->dateTimeBetween('now', '+2 weeks'),
                'sent_at' => null,
            ];
        });
    }
    
    /**
     * Indicate that the notification has been sent.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function sent()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'sent',
                'sent_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }
    
    /**
     * Indicate that the notification is from the system.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function fromSystem()
    {
        return $this->state(function (array $attributes) {
            return [
                'sender_type' => 'system',
                'sender_id' => null,
            ];
        });
    }
    
    /**
     * Indicate that the notification is from an admin.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function fromAdmin()
    {
        return $this->state(function (array $attributes) {
            $admin = User::where('role', 'admin')->inRandomOrder()->first();
            
            return [
                'sender_type' => 'admin',
                'sender_id' => $admin ? $admin->id : null,
            ];
        });
    }
} 