<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuyerSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_code',
        'amount_ugx',
        'currency',
        'provider',
        'status',
        'payment_status',
        'started_at',
        'renews_at',
        'canceled_at',
        'reference',
        'provider_transaction_id',
        'provider_reference',
        'provider_last_event_id',
        'checkout_session_id',
        'billing_email',
        'payment_method',
        'payment_reference_masked',
        'payment_request_sent_at',
        'overdue_notification_sent_at',
        'callback_received_at',
        'activated_at',
        'charged_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_ugx' => 'integer',
            'started_at' => 'datetime',
            'renews_at' => 'datetime',
            'canceled_at' => 'datetime',
            'callback_received_at' => 'datetime',
            'payment_request_sent_at' => 'datetime',
            'overdue_notification_sent_at' => 'datetime',
            'activated_at' => 'datetime',
            'charged_at' => 'datetime',
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
