<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->string('module', 60)->default('general');
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('summary')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->string('level', 20)->default('info');
            $table->boolean('is_critical')->default(false);
            $table->boolean('is_live_event')->default(true);

            $table->index(['is_live_event', 'created_at'], 'audit_logs_live_created_at_index');
            $table->index(['module', 'created_at'], 'audit_logs_module_created_at_index');
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_at_index');
            $table->index(['institution_id', 'created_at'], 'audit_logs_institution_created_at_index');
            $table->index(['entity_type', 'entity_id', 'created_at'], 'audit_logs_entity_created_at_index');
            $table->index(['correlation_id', 'created_at'], 'audit_logs_correlation_created_at_index');
            $table->index(['level', 'is_critical', 'created_at'], 'audit_logs_level_critical_created_at_index');
        });

        DB::table('audit_logs')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $entityType = $this->legacyEntityType((string) $row->auditable_type);

                    DB::table('audit_logs')
                        ->where('id', $row->id)
                        ->update([
                            'module' => $this->legacyModule((string) $row->action, (string) $row->auditable_type),
                            'entity_type' => $entityType,
                            'entity_id' => $row->auditable_id,
                            'summary' => $this->legacySummary((string) $row->action, $entityType, $row->auditable_id),
                            'level' => $this->legacyLevel((string) $row->action),
                            'is_critical' => $this->legacyIsCritical((string) $row->action),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_live_created_at_index');
            $table->dropIndex('audit_logs_module_created_at_index');
            $table->dropIndex('audit_logs_user_created_at_index');
            $table->dropIndex('audit_logs_institution_created_at_index');
            $table->dropIndex('audit_logs_entity_created_at_index');
            $table->dropIndex('audit_logs_correlation_created_at_index');
            $table->dropIndex('audit_logs_level_critical_created_at_index');

            $table->dropConstrainedForeignId('institution_id');
            $table->dropColumn([
                'module',
                'entity_type',
                'entity_id',
                'summary',
                'metadata',
                'correlation_id',
                'level',
                'is_critical',
                'is_live_event',
            ]);
        });
    }

    private function legacyModule(string $action, string $auditableType): string
    {
        if (in_array($action, ['login', 'logout', 'login_failed'], true)) {
            return 'auth';
        }

        return match ($this->legacyEntityType($auditableType)) {
            'equipo' => 'equipos',
            'acta' => 'actas',
            'mantenimiento' => 'mantenimientos',
            'movimiento' => 'movimientos',
            'usuario' => 'usuarios',
            default => 'general',
        };
    }

    private function legacyEntityType(string $auditableType): ?string
    {
        return match ($auditableType) {
            'App\\Models\\Equipo' => 'equipo',
            'App\\Models\\Acta' => 'acta',
            'App\\Models\\Mantenimiento' => 'mantenimiento',
            'App\\Models\\Movimiento' => 'movimiento',
            'App\\Models\\User' => 'usuario',
            'auth' => 'auth',
            'acta_equipo' => 'acta',
            default => $auditableType !== '' ? class_basename($auditableType) : null,
        };
    }

    private function legacySummary(string $action, ?string $entityType, mixed $entityId): string
    {
        $entityLabel = $entityType !== null ? str_replace('_', ' ', $entityType) : 'registro';
        $entityReference = $entityId !== null ? ' #'.$entityId : '';

        return match ($action) {
            'login' => 'Se registro un inicio de sesion.',
            'logout' => 'Se registro un cierre de sesion.',
            'maintenance_external_opened' => 'Se abrio un mantenimiento externo.',
            'maintenance_external_closed' => 'Se cerro un mantenimiento externo.',
            'acta anulada' => 'Se anulo un acta.',
            'create' => "Se creo {$entityLabel}{$entityReference}.",
            'update' => "Se actualizo {$entityLabel}{$entityReference}.",
            'delete' => "Se elimino {$entityLabel}{$entityReference}.",
            default => 'Se registro un evento de auditoria.',
        };
    }

    private function legacyLevel(string $action): string
    {
        return match ($action) {
            'acta anulada', 'maintenance_external_opened', 'maintenance_external_closed' => 'critical',
            'login_failed' => 'error',
            default => 'info',
        };
    }

    private function legacyIsCritical(string $action): bool
    {
        return in_array($action, ['acta anulada', 'maintenance_external_opened', 'maintenance_external_closed'], true);
    }
};
