<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model implements Authenticatable
{
    use AuthenticatableTrait, HasFactory;

    protected $fillable = [
        'name',
        'username',
        'password',
        'phone',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
