<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Model implements JWTSubject
{
    use Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'profile_photo',
        'email_verified_at',
        'google_id',
        'whatsapp_number',
        'is_verified',
        'verification_method',
        'whatsapp_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'whatsapp_verified_at' => 'datetime',
        'is_verified' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'scope' => 'customer',
            'role' => $this->role,
        ];
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}