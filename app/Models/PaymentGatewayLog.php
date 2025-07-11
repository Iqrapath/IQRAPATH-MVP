<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gateway',
        'reference',
        'transaction_id',
        'user_id',
        'subscription_transaction_id',
        'status',
        'amount',
        'currency',
        'request_data',
        'response_data',
        'webhook_data',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'request_data' => 'array',
        'response_data' => 'array',
        'webhook_data' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user associated with the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription transaction associated with the payment.
     */
    public function subscriptionTransaction(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTransaction::class);
    }

    /**
     * Mark the payment as verified.
     *
     * @return bool
     */
    public function markAsVerified(): bool
    {
        $this->status = 'success';
        $this->verified_at = now();
        return $this->save();
    }

    /**
     * Mark the payment as failed.
     *
     * @param array|null $data
     * @return bool
     */
    public function markAsFailed(?array $data = null): bool
    {
        $this->status = 'failed';
        
        if ($data) {
            $this->response_data = array_merge($this->response_data ?? [], $data);
        }
        
        return $this->save();
    }

    /**
     * Update with webhook data.
     *
     * @param array $data
     * @return bool
     */
    public function updateWithWebhookData(array $data): bool
    {
        $this->webhook_data = $data;
        
        // Update status based on webhook data if applicable
        if (isset($data['status'])) {
            $gatewayStatus = strtolower($data['status']);
            
            if (in_array($gatewayStatus, ['success', 'successful', 'completed'])) {
                $this->status = 'success';
                $this->verified_at = $this->verified_at ?? now();
            } elseif (in_array($gatewayStatus, ['failed', 'failure', 'declined'])) {
                $this->status = 'failed';
            } elseif (in_array($gatewayStatus, ['abandoned', 'cancelled'])) {
                $this->status = 'abandoned';
            }
        }
        
        return $this->save();
    }
} 