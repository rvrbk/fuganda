<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'uuid',
        'client_id',
        'client_secret',
        'redirect_uri',
        'personal_access_client',
        'password_client',
        'revoked',
        'user_id',
        'scopes',
    ];

    protected $casts = [
        'revoked' => 'boolean',
        'personal_access_client' => 'boolean',
        'password_client' => 'boolean',
        'scopes' => 'array',
    ];

    protected $hidden = [
        'client_secret',
    ];

    public static function generateClientId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function generateClientSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function generateUuid(): string
    {
        return (string) Illuminate\Support\Str::uuid();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tokens()
    {
        return $this->hasMany(PersonalAccessToken::class, 'tokenable_id')
            ->where('tokenable_type', self::class);
    }
}
