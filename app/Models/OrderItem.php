<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    // shown in the admin's reason picker (Remove item modal) and everywhere a removal reason
    // is displayed (admin's Removed Items audit section, customer's apology card/bill)
    public const REMOVAL_REASONS = [
        'out_of_stock' => 'Out of Stock',
        'damaged' => 'Damaged',
        'quality_issue' => 'Quality Issue',
        'custom' => 'Custom Reason',
    ];

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_price',
        'quantity',
        'portion',
        'line_total',
        'is_gift',
        'confirmed_at',
        'removed_at',
        'removed_by_admin_id',
        'removal_reason',
        'removal_reason_note',
        'added_by_admin_id',
        'added_at',
    ];

    protected $casts = [
        'is_gift' => 'boolean',
        'confirmed_at' => 'datetime',
        'removed_at' => 'datetime',
        'added_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function removedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'removed_by_admin_id');
    }

    public function addedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'added_by_admin_id');
    }

    public function portionLabel(): ?string
    {
        return $this->portion > 0 ? Product::portionLabel($this->portion, $this->product?->unit ?? 'weight') : null;
    }

    public function isRemoved(): bool
    {
        return $this->removed_at !== null;
    }

    // true for a line an admin added to an already-placed order (vs an original checkout item)
    public function isAdminAdded(): bool
    {
        return $this->added_at !== null;
    }

    public function removalReasonLabel(): ?string
    {
        return $this->removal_reason ? (self::REMOVAL_REASONS[$this->removal_reason] ?? $this->removal_reason) : null;
    }
}
