<?php

namespace App\Http\Controllers;

use App\Enums\ExportScope;
use App\Exports\EquiposCsvExport;
use App\Http\Requests\ExportEquiposRequest;
use App\Models\Equipo;
use App\Services\EquipoListingService;
use App\Services\Exports\CsvDownloadService;
use App\Services\Exports\ExportFileNameService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EquipoExportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EquipoListingService $equipoListingService,
        private readonly CsvDownloadService $csvDownloadService,
        private readonly ExportFileNameService $exportFileNameService,
    ) {}

    public function __invoke(ExportEquiposRequest $request): StreamedResponse
    {
        $this->authorize('export', Equipo::class);

        $listing = $this->equipoListingService->listingState($request);
        $filters = $this->equipoListingService->filtersFromRequest($request);
        $scope = $request->scope();

        $search = $scope === ExportScope::RESULTS ? $listing->search : '';
        $appliedFilters = $scope === ExportScope::RESULTS
            ? $filters
            : $this->equipoListingService->emptyFilters();

        $query = $this->equipoListingService->buildIndexQuery(
            $request->user(),
            $search,
            $appliedFilters
        );

        $export = new EquiposCsvExport(
            $query,
            $scope,
            $this->equipoListingService->hasActiveFilters($search, $appliedFilters),
            $this->exportFileNameService
        );

        return $this->csvDownloadService->download($export);
    }
}
