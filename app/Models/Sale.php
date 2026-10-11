<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Sale extends Model
{
    protected $fillable = [
        'invoice_number', 'user_id', 'customer_name', 'customer_address', 'customer_contact',
        'payment_method', 'payment_reference', 'payment_proof_path', 'payment_proof_hash',
        'amount_tendered', 'change_amount', 'total_amount', 'sold_at',
        'voided_at', 'voided_by', 'void_reason',
    ];

    protected $casts = [
        'sold_at'         => 'datetime',
        'voided_at'       => 'datetime',
        'total_amount'    => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_amount'   => 'decimal:2',
    ];

    /** Payment methods the cashier can choose today. */
    public const PAYMENT_METHODS = [
        'cash' => 'Cash', 'gcash' => 'GCash', 'bank_transfer' => 'Bank Transfer',
    ];

    /** Every label, including methods that were removed but still exist on old sales. */
    public const PAYMENT_LABELS = self::PAYMENT_METHODS + [
        'paymaya' => 'PayMaya (old)', 'card' => 'Card (old)',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /** Sales that count in totals and reports (a voided sale does not). */
    public function scopeActive($query)
    {
        return $query->whereNull('voided_at');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function getPaymentLabelAttribute(): string
    {
        return self::PAYMENT_LABELS[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    public function getPaymentProofUrlAttribute(): ?string
    {
        return $this->payment_proof_path ? Storage::disk('public')->url($this->payment_proof_path) : null;
    }
}
