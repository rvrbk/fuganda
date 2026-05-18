<?php

namespace App\Services;

use App\Mail\NewPropertyMessageMail;
use App\Models\Message;
use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MessagingService
{
    public function inbox(User $user, array $filters): LengthAwarePaginator
    {
        // Mark all newly received messages as read when inbox is opened.
        Message::query()
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $query = Message::query()
            ->with([
                'property:id,title,user_id',
                'sender:id,name',
                'receiver:id,name',
            ])
            ->where(function ($builder) use ($user): void {
                $builder
                    ->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            });

        if (! empty($filters['property_id'])) {
            $query->where('property_id', (int) $filters['property_id']);
        }

        if (! empty($filters['counterpart_id'])) {
            $counterpartId = (int) $filters['counterpart_id'];
            $query->where(function ($builder) use ($user, $counterpartId): void {
                $builder
                    ->where(function ($inner) use ($user, $counterpartId): void {
                        $inner->where('sender_id', $user->id)->where('receiver_id', $counterpartId);
                    })
                    ->orWhere(function ($inner) use ($user, $counterpartId): void {
                        $inner->where('sender_id', $counterpartId)->where('receiver_id', $user->id);
                    });
            });
        }

        return $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function send(User $sender, array $attributes): Message
    {
        $property = Property::query()->findOrFail((int) $attributes['property_id']);
        $receiverId = array_key_exists('receiver_id', $attributes) && $attributes['receiver_id'] !== null
            ? (int) $attributes['receiver_id']
            : (int) $property->user_id;
        $receiver = User::query()->findOrFail($receiverId);

        if ((int) $sender->id === (int) $receiver->id) {
            throw new AuthorizationException('You cannot send a message to yourself.');
        }

        $message = Message::query()->create([
            'property_id' => $property->id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'body' => $attributes['body'],
            'read_at' => null,
        ])->load([
            'property:id,title,user_id',
            'sender:id,name',
            'receiver:id,name',
        ]);

        if (! empty($receiver->email)) {
            try {
                Mail::to($receiver->email)->send(new NewPropertyMessageMail($message));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $message;
    }

    public function unreadCount(User $user): int
    {
        return Message::query()
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
