<?php

namespace App\Support\Auditing;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            $model->writeAudit('create', null, $model->getAttributes());
        });

        static::updating(function (Model $model): void {
            $model->setAttribute('_audit_before', $model->getOriginal());
        });

        static::updated(function (Model $model): void {
            /** @var array<string,mixed>|null $before */
            $before = $model->getAttribute('_audit_before');
            $model->writeAudit('update', $before, $model->getAttributes());
            $model->offsetUnset('_audit_before');
        });

        static::deleted(function (Model $model): void {
            $model->writeAudit('delete', $model->getOriginal(), null);
        });
    }

    private function writeAudit(string $action, ?array $before, ?array $after): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'before' => $before,
            'after' => $after,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
