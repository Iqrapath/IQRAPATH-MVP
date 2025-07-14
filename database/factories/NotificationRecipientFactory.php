<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationRecipient>
 */
class NotificationRecipientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NotificationRecipient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'sent', 'delivered', 'read', 'failed'];
        $channels = ['in-app', 'email', 'sms'];
        
        $status = $this->faker->randomElement($statuses);
        
        // Set delivered_at and read_at based on status
        $deliveredAt = null;
        $readAt = null;
        
        if (in_array($status, ['delivered', 'read'])) {
            $deliveredAt = $this->faker->dateTimeBetween('-1 month', 'now');
            
            if ($status === 'read') {
                $readAt = $this->faker->dateTimeBetween($deliveredAt, 'now');
            }
        }
        
        return [
            'notification_id' => Notification::factory(),
            'user_id' => User::inRandomOrder()->first() ?? User::factory(),
            'status' => $status,
            'channel' => $this->faker->randomElement($channels),
            'delivered_at' => $deliveredAt,
            'read_at' => $readAt,
        ];
    }
    
    /**
     * Indicate that the notification is pending.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'delivered_at' => null,
                'read_at' => null,
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
                'delivered_at' => null,
                'read_at' => null,
            ];
        });
    }
    
    /**
     * Indicate that the notification has been delivered.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function delivered()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'delivered',
                'delivered_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'read_at' => null,
            ];
        });
    }
    
    /**
     * Indicate that the notification has been read.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function read()
    {
        $deliveredAt = $this->faker->dateTimeBetween('-1 month', '-1 day');
        
        return $this->state(function (array $attributes) use ($deliveredAt) {
            return [
                'status' => 'read',
                'delivered_at' => $deliveredAt,
                'read_at' => $this->faker->dateTimeBetween($deliveredAt, 'now'),
            ];
        });
    }
    
    /**
     * Indicate that the notification failed to deliver.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'delivered_at' => null,
                'read_at' => null,
            ];
        });
    }
    
    /**
     * Indicate that the notification was sent via in-app channel.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inApp()
    {
        return $this->state(function (array $attributes) {
            return [
                'channel' => 'in-app',
            ];
        });
    }
    
    /**
     * Indicate that the notification was sent via email channel.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function email()
    {
        return $this->state(function (array $attributes) {
            return [
                'channel' => 'email',
            ];
        });
    }
    
    /**
     * Indicate that the notification was sent via SMS channel.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function sms()
    {
        return $this->state(function (array $attributes) {
            return [
                'channel' => 'sms',
            ];
        });
    }
} 