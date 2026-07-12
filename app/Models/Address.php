<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'address_line',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // atomically makes this the only default address for its owner
    public function makeDefault(): void
    {
        DB::transaction(function () {
            $this->user->addresses()->where('id', '!=', $this->id)->update(['is_default' => false]);
            $this->forceFill(['is_default' => true])->save();
        });
    }
}
