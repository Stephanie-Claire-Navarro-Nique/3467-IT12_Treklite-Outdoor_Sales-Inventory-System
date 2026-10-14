<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

/** One line in the Owner's activity trail ("who did what"). */
class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action_type', 'action', 'description'];

    public const ACTION_LABELS = [
        'sale_void'        => 'Sale voided',
        'product_add'      => 'Product added',
        'product_edit'     => 'Product edited',
        'product_archive'  => 'Product archived',
        'product_restore'  => 'Product restored',
        'product_delete'   => 'Product deleted',
        'option_add'       => 'List item added',
        'option_edit'      => 'List item edited',
        'option_toggle'    => 'List item enabled/disabled',
        'option_delete'    => 'List item deleted',
        'staff_add'        => 'Staff added',
        'staff_edit'       => 'Staff edited',
        'staff_delete'     => 'Staff deleted',
        'backup_create'    => 'Backup created',
        'backup_restore'   => 'Backup restored',
    ];

    /** Group of each action (saved in activity_logs.action_type); the Activity Log can color the entries by it. */
    public const ACTION_TYPES = [
        'sale'    => 'Sales',
        'product' => 'Products',
        'user'    => 'Accounts',
        'backup'  => 'Backups',
        'other'   => 'Other',
    ];

    /** Which group an action belongs to, from its code (sale_void -> sale, staff_add -> user, ...). */
    public static function typeOf(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'sale_')    => 'sale',
            str_starts_with($action, 'product_'),
            str_starts_with($action, 'option_')  => 'product',
            str_starts_with($action, 'staff_')   => 'user',
            str_starts_with($action, 'backup_')  => 'backup',
            default                              => 'other',
        };
    }

    /** Writes one line. It must never break the action being logged, so any error is ignored. */
    public static function record(string $action, string $description): void
    {
        try {
            static::create([
                'user_id'     => auth()->id(),
                'action_type' => self::typeOf($action),
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
