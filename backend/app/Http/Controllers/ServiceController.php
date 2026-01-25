<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:superadmin,admin,tecnico');
    }

    public function byInstitution(int $institution_id): JsonResponse
    {
        $services = Institution::query()
            ->whereKey($institution_id)
            ->firstOrFail()
            ->services()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($services);
    }
}
