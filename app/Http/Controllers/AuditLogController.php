<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $action = trim($request->string('action')->toString());
        $q = trim($request->string('q')->toString());

        $logs = AuditLog::query()
            ->with('actor')
            ->where('household_id', $hid)
            ->when($action !== '', fn($qq) => $qq->where('action', $action))
            ->when($q !== '', function ($qq) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $qq->where(function ($sub) use ($like) {
                    $sub->where('entity_type', 'like', $like)
                        ->orWhere('action', 'like', $like)
                        ->orWhereRaw("JSON_EXTRACT(meta, '$') like ?", [$like]);
                });
            })
            ->orderByDesc('occurred_at')
            ->paginate(30)
            ->withQueryString();

        $actions = AuditLog::query()
            ->where('household_id', $hid)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('audit.index', [
            'logs' => $logs,
            'actions' => $actions,
            'f' => ['action' => $action, 'q' => $q],
        ]);
    }
}
