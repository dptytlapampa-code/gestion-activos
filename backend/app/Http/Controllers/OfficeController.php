<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\JsonResponse;

class OfficeController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:superadmin,admin,tecnico');
    }

    public function byService(int $service_id): JsonResponse
    {
        $offices = Service::query()
            ->whereKey($service_id)
            ->firstOrFail()
            ->offices()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'floor']);

        return response()->json($offices);
    }
}
