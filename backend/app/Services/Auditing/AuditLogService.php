<?php

namespace App\Services\Auditing;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditLogService
{
    public function __construct(private readonly AuditContext $auditContext) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(array $payload): AuditLog
    {
        $user = $payload['user'] ?? auth()->user();

        if ($user !== null && ! $user instanceof User) {
            $user = User::query()->find($user);
        }

        $entityType = $this->nullableString($payload['entity_type'] ?? $payload['auditable_type'] ?? null);
        $entityId = $this->nullableInt($payload['entity_id'] ?? $payload['auditable_id'] ?? null);

        $record = [
            'user_id' => $payload['user_id'] ?? $user?->id,
            'institution_id' => $this->nullableInt($payload['institution_id'] ?? $user?->institution_id),
            'module' => $this->fallbackString($payload['module'] ?? null, 'general'),
            'action' => $this->fallbackString($payload['action'] ?? null, 'evento'),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'auditable_type' => $entityType,
            'auditable_id' => $entityId,
            'summary' => $this->fallbackString($payload['summary'] ?? null, 'Se registro un evento de auditoria.'),
            'metadata' => $this->normalizePayload($payload['metadata'] ?? null),
            'before' => $this->normalizePayload($payload['before'] ?? null),
            'after' => $this->normalizePayload($payload['after'] ?? null),
            'correlation_id' => $this->nullableString($payload['correlation_id'] ?? $this->auditContext->current()),
            'ip' => $this->nullableString($payload['ip'] ?? request()?->ip()),
            'user_agent' => $this->summarizeUserAgent($payload['user_agent'] ?? request()?->userAgent()),
            'level' => AuditLog::normalizeLevel($payload['level'] ?? AuditLog::LEVEL_INFO),
            'is_critical' => (bool) ($payload['is_critical'] ?? false),
            'is_live_event' => (bool) ($payload['is_live_event'] ?? true),
        ];

        if ($record['metadata'] === null) {
            unset($record['metadata']);
        }

        if ($record['before'] === null) {
            unset($record['before']);
        }

        if ($record['after'] === null) {
            unset($record['after']);
        }

        return AuditLog::query()->create($record);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, string>  $labels
     * @return array<int, array{field:string,label:string,before:mixed,after:mixed}>
     */
    public function diff(array $before, array $after, array $labels = []): array
    {
        $fields = collect(array_merge(array_keys($before), array_keys($after)))
            ->unique()
            ->values();

        return $fields
            ->map(function (string $field) use ($before, $after, $labels): ?array {
                $oldValue = $before[$field] ?? null;
                $newValue = $after[$field] ?? null;

                if ($oldValue === $newValue) {
                    return null;
                }

                return [
                    'field' => $field,
                    'label' => $labels[$field] ?? Str::headline(str_replace('.', ' ', $field)),
                    'before' => $oldValue,
                    'after' => $newValue,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    private function normalizePayload(mixed $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $normalized = Arr::where($payload, function (mixed $value): bool {
            return ! (is_array($value) && $value === []);
        });

        return $normalized === [] ? null : $normalized;
    }

    private function fallbackString(mixed $value, string $fallback): string
    {
        $string = $this->nullableString($value);

        return $string ?? $fallback;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cast = (int) $value;

        return $cast > 0 ? $cast : null;
    }

    private function summarizeUserAgent(mixed $userAgent): ?string
    {
        $agent = $this->nullableString($userAgent);

        return $agent === null ? null : Str::limit($agent, 180);
    }
}
