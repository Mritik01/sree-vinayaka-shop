<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// audit-only log of a Super Admin manually clicking "Reset Current Month" on the Income
// dashboard — see the migration's docblock for why this never touches PlatformIncomeRecord
class IncomeMonthReset extends Model
{
    protected $fillable = [
        'year',
        'month',
        'admin_id',
        'reset_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'reset_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
