<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'permissions', 'active'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array',
        'active' => 'boolean',
    ];
    
    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }
}
