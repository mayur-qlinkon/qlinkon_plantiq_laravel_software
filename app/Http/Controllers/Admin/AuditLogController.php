<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Platform\TenantActivity;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Scoping lives on TenantActivity's global scope, so it cannot be
        // forgotten here or in any future query against the log.
        $logs = TenantActivity::with('causer')
            ->latest()
            ->paginate(50);

        return view('admin.audit-logs.index', compact('logs'));
    }
}
