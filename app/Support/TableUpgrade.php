<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Schema;

/**
 * Used by the table migrations. On a new database the table is created with its singular name.
 * On a database that still has the old plural-named table (for example the live system), that table
 * is renamed instead, so all its data is kept. Once every database has been converted this helper
 * is no longer needed.
 */
class TableUpgrade
{
    public static function create(string $new, ?string $old, Closure $definition): void
    {
        if (Schema::hasTable($new)) {
            return;
        }

        if ($old !== null && Schema::hasTable($old)) {
            Schema::rename($old, $new);

            return;
        }

        Schema::create($new, $definition);
    }
}
