<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

/** Owner only: who did what (voids, product changes, staff accounts, backups). */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q'));
        $action = is_string($request->query('action')) && array_key_exists($request->query('action'), ActivityLog::ACTION_LABELS)
            ? $request->query('action')
            : '';

        $logs = ActivityLog::with('user')
            ->when($action !== '', fn ($w) => $w->where('action', $action))
            ->when($q !== '', fn ($w) => $w->where('description', 'like', "%{$q}%"))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('activity.index', compact('logs', 'q', 'action'));
    }
}
