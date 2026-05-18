<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerPublishFee extends Model
{
    protected $fillable = [
        'user_id',
        'property_id',
        'provider',
        'amount_ugx',
        'currency',
        'payment_method',
        'checkout_session_id',
        'provider_transaction_id',
        'provider_last_event_id',
        'callback_received_at',
        'payment_request_sent_at',
        'status',
        'payment_status',
        'charged_at',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount_ugx' => 'integer',
            'charged_at' => 'datetime',
            'callback_received_at' => 'datetime',
            'payment_request_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
