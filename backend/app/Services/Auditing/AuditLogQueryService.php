<?php

namespace App\Services\Auditing;

use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditLogQueryService
{
    /**
     * @return array<int, string>
     */
    private const DETAIL_COLUMNS = [
        'id',
        'created_at',
        'user_id',
        'institution_id',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'auditable_type',
        'auditable_id',
        'summary',
        'metadata',
        'before',
        'after',
        'correlation_id',
        'ip',
        'user_agent',
        'level',
        'is_critical',
        'is_live_event',
    ];

    /**
     * @return array<int, string>
     */
    private const LIST_COLUMNS = [
        'id',
        'created_at',
        'user_id',
        'institution_id',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'auditable_type',
        'auditable_id',
        'summary',
        'correlation_id',
        'level',
        'is_critical',
        'is_live_event',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function live(array $filters = []): LengthAwarePaginator
    {
        $perPage = $this->perPage($filters['per_page'] ?? 20, 20);

        return AuditLog::query()
            ->select(self::LIST_COLUMNS)
            ->with($this->listRelations())
            ->where('is_live_event', true)
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function advanced(array $filters = []): LengthAwarePaginator
    {
        $perPage = $this->perPage($filters['per_page'] ?? 25, 25);

        return AuditLog::query()
            ->select(self::LIST_COLUMNS)
            ->with($this->listRelations())
            ->when($this->filled($filters, 'date_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', (string) $filters['date_from']))
            ->when($this->filled($filters, 'date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', (string) $filters['date_to']))
            ->when($this->filled($filters, 'user_id'), fn (Builder $query) => $query->where('user_id', (int) $filters['user_id']))
            ->when($this->filled($filters, 'institution_id'), fn (Builder $query) => $query->where('institution_id', (int) $filters['institution_id']))
            ->when($this->filled($filters, 'module'), fn (Builder $query) => $query->where('module', (string) $filters['module']))
            ->when($this->filled($filters, 'action'), fn (Builder $query) => $query->where('action', (string) $filters['action']))
            ->when($this->filled($filters, 'entity_type'), fn (Builder $query) => $query->where('entity_type', (string) $filters['entity_type']))
            ->when($this->filled($filters, 'entity_id'), fn (Builder $query) => $query->where('entity_id', (int) $filters['entity_id']))
            ->when($this->filled($filters, 'only_accesses'), fn (Builder $query) => $query->where('module', 'auth'))
            ->when($this->filled($filters, 'only_critical'), fn (Builder $query) => $query->where('is_critical', true))
            ->when($this->filled($filters, 'only_errors'), fn (Builder $query) => $query->whereIn('level', [AuditLog::LEVEL_WARNING, AuditLog::LEVEL_ERROR, AuditLog::LEVEL_CRITICAL]))
            ->when($this->filled($filters, 'text'), fn (Builder $query) => $this->applyFreeTextFilter($query, (string) $filters['text']))
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail(AuditLog $auditLog): AuditLog
    {
        return AuditLog::query()
            ->select(self::DETAIL_COLUMNS)
            ->with(['user:id,name,email', 'institution:id,nombre'])
            ->findOrFail($auditLog->id);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function relatedEvents(AuditLog $auditLog, int $limit = 20): Collection
    {
        if ($auditLog->correlation_id === null || $auditLog->correlation_id === '') {
            return collect();
        }

        return AuditLog::query()
            ->select(self::LIST_COLUMNS)
            ->with($this->listRelations())
            ->where('correlation_id', $auditLog->correlation_id)
            ->whereKeyNot($auditLog->id)
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, Collection<int, mixed>>
     */
    public function filterOptions(): array
    {
        return [
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'institutions' => Institution::query()->orderBy('nombre')->get(['id', 'nombre']),
            'modules' => AuditLog::query()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditLog::query()->whereNotNull('action')->distinct()->orderBy('action')->pluck('action'),
            'entityTypes' => AuditLog::query()->whereNotNull('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function listRelations(): array
    {
        return [
            'user:id,name',
            'institution:id,nombre',
        ];
    }

    private function applyFreeTextFilter(Builder $query, string $text): void
    {
        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $needle = '%'.trim($text).'%';

        $query->where(function (Builder $builder) use ($likeOperator, $needle, $text): void {
            $builder
                ->where('summary', $likeOperator, $needle)
                ->orWhere('module', $likeOperator, $needle)
                ->orWhere('action', $likeOperator, $needle)
                ->orWhere('entity_type', $likeOperator, $needle)
                ->orWhere('correlation_id', $likeOperator, $needle)
                ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', $likeOperator, $needle))
                ->orWhereHas('institution', fn (Builder $institutionQuery) => $institutionQuery->where('nombre', $likeOperator, $needle));

            if (is_numeric($text)) {
                $builder->orWhere('entity_id', (int) $text);
            }
        });
    }

    private function filled(array $filters, string $key): bool
    {
        return array_key_exists($key, $filters)
            && $filters[$key] !== null
            && $filters[$key] !== ''
            && $filters[$key] !== false;
    }

    private function perPage(mixed $value, int $default): int
    {
        $perPage = (int) $value;

        return in_array($perPage, [15, 20, 25, 50], true) ? $perPage : $default;
    }
}
