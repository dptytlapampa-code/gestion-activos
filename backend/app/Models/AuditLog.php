<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';

    protected $fillable = [
        'user_id',
        'institution_id',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'summary',
        'metadata',
        'correlation_id',
        'level',
        'is_critical',
        'is_live_event',
        'auditable_type',
        'auditable_id',
        'before',
        'after',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'institution_id' => 'integer',
            'entity_id' => 'integer',
            'auditable_id' => 'integer',
            'metadata' => 'array',
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
            'is_critical' => 'boolean',
            'is_live_event' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function getResolvedEntityTypeAttribute(): ?string
    {
        return $this->entity_type ?: $this->auditable_type;
    }

    public function getResolvedEntityIdAttribute(): ?int
    {
        $value = $this->entity_id ?? $this->auditable_id;

        return $value !== null ? (int) $value : null;
    }

    public function getDisplayEntityTypeAttribute(): string
    {
        $entityType = $this->resolved_entity_type;

        if ($entityType === null || $entityType === '') {
            return 'Sin entidad';
        }

        if (str_contains($entityType, '\\')) {
            return Str::headline(class_basename($entityType));
        }

        return Str::headline(str_replace(['.', '_'], ' ', $entityType));
    }

    /**
     * @return array<int, array{field:string,label:string,before:mixed,after:mixed}>
     */
    public function detailChanges(): array
    {
        $metadataChanges = data_get($this->metadata, 'changes');

        if (is_array($metadataChanges) && $metadataChanges !== []) {
            return collect($metadataChanges)
                ->filter(fn (mixed $change): bool => is_array($change))
                ->map(fn (array $change): array => [
                    'field' => (string) ($change['field'] ?? ''),
                    'label' => (string) ($change['label'] ?? Str::headline((string) ($change['field'] ?? 'Cambio'))),
                    'before' => $change['before'] ?? null,
                    'after' => $change['after'] ?? null,
                ])
                ->values()
                ->all();
        }

        $before = is_array($this->before) ? $this->before : [];
        $after = is_array($this->after) ? $this->after : [];

        return collect(array_merge(array_keys($before), array_keys($after)))
            ->unique()
            ->map(function (string $field) use ($before, $after): ?array {
                $oldValue = $before[$field] ?? null;
                $newValue = $after[$field] ?? null;

                if ($oldValue === $newValue) {
                    return null;
                }

                return [
                    'field' => $field,
                    'label' => Str::headline(str_replace('.', ' ', $field)),
                    'before' => $oldValue,
                    'after' => $newValue,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label:string,value:mixed}>
     */
    public function detailContext(): array
    {
        $details = data_get($this->metadata, 'details');

        if (! is_array($details)) {
            return [];
        }

        return collect($details)
            ->map(function (mixed $value, string|int $key): array {
                if (is_array($value)) {
                    $value = implode(', ', array_filter($value, fn (mixed $item): bool => $item !== null && $item !== ''));
                }

                return [
                    'label' => Str::headline(str_replace('.', ' ', (string) $key)),
                    'value' => $value,
                ];
            })
            ->filter(fn (array $item): bool => $item['value'] !== null && $item['value'] !== '')
            ->values()
            ->all();
    }

    public static function normalizeLevel(mixed $level): string
    {
        $normalized = Str::lower(trim((string) $level));

        return in_array($normalized, [self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR, self::LEVEL_CRITICAL], true)
            ? $normalized
            : self::LEVEL_INFO;
    }
}
