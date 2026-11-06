<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\BackofficeAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Log aktivitas pegawai Vasetra di backoffice.
 */
class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $logs = BackofficeAuditLog::query()
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->query('event')))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $events = BackofficeAuditLog::query()->distinct()->orderBy('event')->pluck('event');

        return view('backoffice.activity.index', compact('logs', 'events'));
    }
}
