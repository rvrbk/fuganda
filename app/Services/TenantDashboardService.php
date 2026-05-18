<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Property;
use App\Models\User;

class TenantDashboardService
{
    public function buildFor(User $user): array
    {
        $propertyCount = Property::query()
            ->where('user_id', $user->id)
            ->count();

        $unreadMessages = Message::query()
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'stats' => [
                'property_count' => $propertyCount,
                'unread_messages' => $unreadMessages,
            ],
        ];
    }
}
