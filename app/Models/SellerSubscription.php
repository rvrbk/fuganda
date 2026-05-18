<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'plan_code',
        'amount_ugx',
        'currency',
        'payment_method',
        'payment_reference_masked',
        'billing_email',
        'provider_transaction_id',
        'provider_reference',
        'provider_last_event_id',
        'callback_received_at',
        'payment_request_sent_at',
        'overdue_notification_sent_at',
        'checkout_session_id',
        'payment_status',
        'activated_at',
        'status',
        'started_at',
        'renews_at',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_ugx' => 'integer',
            'callback_received_at' => 'datetime',
            'payment_request_sent_at' => 'datetime',
            'overdue_notification_sent_at' => 'datetime',
            'activated_at' => 'datetime',
            'started_at' => 'datetime',
            'renews_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
