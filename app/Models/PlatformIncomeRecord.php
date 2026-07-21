<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// one permanent row per delivered order — see PlatformIncomeService for the only place these
// are ever created, and the migration's own docblock for why this table is append-only
class PlatformIncomeRecord extends Model
{
    protected $fillable = [
        'order_id',
        'rider_id',
        'customer_name',
        'order_amount',
        'delivery_charge',
        'fixed_commission',
        'delivery_charge_income',
        'total_income',
        'delivered_at',
    ];

    protected $casts = [
        'order_amount' => 'integer',
        'delivery_charge' => 'integer',
        'fixed_commission' => 'integer',
        'delivery_charge_income' => 'integer',
        'total_income' => 'integer',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }
}
