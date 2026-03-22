<?php

namespace App\Support\Auditing;

use App\Services\Auditing\AuditLogService;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        if (! static::shouldRegisterAuditHooks()) {
            return;
        }

        /** @var array<int, array<string,mixed>> $beforeStates */
        $beforeStates = [];

        static::created(function (Model $model): void {
            $model->writeAudit('create', null, $model->getAttributes());
        });

        static::updating(function (Model $model) use (&$beforeStates): void {
            $beforeStates[spl_object_id($model)] = $model->getOriginal();
        });

        static::updated(function (Model $model) use (&$beforeStates): void {
            $modelId = spl_object_id($model);

            /** @var array<string,mixed>|null $before */
            $before = $beforeStates[$modelId] ?? $model->getOriginal();

            $model->writeAudit('update', $before, $model->getAttributes());

            unset($beforeStates[$modelId]);
        });

        static::deleted(function (Model $model): void {
            $model->writeAudit('delete', $model->getOriginal(), null);
        });
    }

    protected static function shouldRegisterAuditHooks(): bool
    {
        return false;
    }

    private function writeAudit(string $action, ?array $before, ?array $after): void
    {
        app(AuditLogService::class)->record([
            'user_id' => auth()->id(),
            'module' => 'general',
            'action' => $action,
            'entity_type' => static::class,
            'entity_id' => $this->getKey(),
            'summary' => sprintf('Se registro un cambio automatico sobre %s.', class_basename(static::class)),
            'before' => $before,
            'after' => $after,
            'is_live_event' => false,
        ]);
    }
}
