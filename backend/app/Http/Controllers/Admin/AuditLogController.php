<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Auditing\AuditLogQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogQueryService $auditLogQueryService)
    {
        $this->middleware('can:manage-users');
    }

    public function live(Request $request): View
    {
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer'],
        ]);

        return view('admin.audit.live', [
            'logs' => $this->auditLogQueryService->live($filters),
        ]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'user_id' => ['nullable', 'integer'],
            'institution_id' => ['nullable', 'integer'],
            'module' => ['nullable', 'string', 'max:60'],
            'action' => ['nullable', 'string', 'max:60'],
            'entity_type' => ['nullable', 'string', 'max:100'],
            'entity_id' => ['nullable', 'integer'],
            'text' => ['nullable', 'string', 'max:255'],
            'only_accesses' => ['nullable', 'boolean'],
            'only_critical' => ['nullable', 'boolean'],
            'only_errors' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer'],
        ]);

        return view('admin.audit.index', array_merge([
            'logs' => $this->auditLogQueryService->advanced($filters),
            'filters' => $filters,
        ], $this->auditLogQueryService->filterOptions()));
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog = $this->auditLogQueryService->findForDetail($auditLog);

        return view('admin.audit.show', [
            'auditLog' => $auditLog,
            'relatedEvents' => $this->auditLogQueryService->relatedEvents($auditLog),
        ]);
    }
}
