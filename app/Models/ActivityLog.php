<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

/** One line in the Owner's activity trail ("who did what"). */
class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'description'];

    public const ACTION_LABELS = [
        'sale_void'        => 'Sale voided',
        'product_add'      => 'Product added',
        'product_edit'     => 'Product edited',
        'product_archive'  => 'Product archived',
        'product_restore'  => 'Product restored',
        'product_delete'   => 'Product deleted',
        'staff_add'        => 'Staff added',
        'staff_edit'       => 'Staff edited',
        'staff_delete'     => 'Staff deleted',
        'backup_create'    => 'Backup created',
        'backup_restore'   => 'Backup restored',
    ];

    /** Writes one line. It must never break the action being logged, so any error is ignored. */
    public static function record(string $action, string $description): void
    {
        try {
            static::create([
                'user_id'     => auth()->id(),
                'action'      => $action,
                'description' => mb_substr($description, 0, 500),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
