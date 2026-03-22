<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_documentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('tipo_documento', 30);
            $table->string('origen_tipo', 30);
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->string('nombre_original');
            $table->string('file_path');
            $table->string('mime_type', 120);
            $table->string('observacion', 255)->nullable();
            $table->date('fecha_documento')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['equipo_id', 'document_id']);
            $table->index(['equipo_id', 'origen_tipo', 'origen_id'], 'equipo_documentos_origen_idx');
        });

        $this->backfillEquipoDocuments();
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_documentos');
    }

    private function backfillEquipoDocuments(): void
    {
        DB::table('documents')
            ->orderBy('id')
            ->chunkById(100, function ($documents): void {
                foreach ($documents as $document) {
                    $this->backfillDocument((object) $document);
                }
            });
    }

    private function backfillDocument(object $document): void
    {
        $rows = match ($document->documentable_type) {
            'App\\Models\\Equipo' => $this->rowsForEquipoDocument($document),
            'App\\Models\\Movimiento' => $this->rowsForMovimientoDocument($document),
            'App\\Models\\Mantenimiento' => $this->rowsForMantenimientoDocument($document),
            'App\\Models\\Acta' => $this->rowsForActaDocument($document),
            default => [],
        };

        foreach ($rows as $row) {
            DB::table('equipo_documentos')->updateOrInsert(
                [
                    'equipo_id' => $row['equipo_id'],
                    'document_id' => $row['document_id'],
                ],
                $row
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsForEquipoDocument(object $document): array
    {
        return [$this->baseRow($document, (int) $document->documentable_id, 'directo', (int) $document->documentable_id, null)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsForMovimientoDocument(object $document): array
    {
        $movimiento = DB::table('movimientos')->select(['id', 'equipo_id', 'fecha'])->find($document->documentable_id);

        if ($movimiento === null || $movimiento->equipo_id === null) {
            return [];
        }

        return [$this->baseRow(
            $document,
            (int) $movimiento->equipo_id,
            'movimiento',
            (int) $movimiento->id,
            $this->normalizeDate($movimiento->fecha)
        )];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsForMantenimientoDocument(object $document): array
    {
        $mantenimiento = DB::table('mantenimientos')->select(['id', 'equipo_id', 'fecha'])->find($document->documentable_id);

        if ($mantenimiento === null || $mantenimiento->equipo_id === null) {
            return [];
        }

        return [$this->baseRow(
            $document,
            (int) $mantenimiento->equipo_id,
            'mantenimiento',
            (int) $mantenimiento->id,
            $this->normalizeDate($mantenimiento->fecha)
        )];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsForActaDocument(object $document): array
    {
        $acta = DB::table('actas')->select(['id', 'fecha'])->find($document->documentable_id);

        if ($acta === null) {
            return [];
        }

        return DB::table('acta_equipo')
            ->where('acta_id', $acta->id)
            ->pluck('equipo_id')
            ->filter()
            ->map(fn ($equipoId): array => $this->baseRow(
                $document,
                (int) $equipoId,
                'acta',
                (int) $acta->id,
                $this->normalizeDate($acta->fecha)
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function baseRow(object $document, int $equipoId, string $origenTipo, ?int $origenId, ?string $fechaDocumento): array
    {
        $timestamp = $document->created_at ?: now();

        return [
            'equipo_id' => $equipoId,
            'document_id' => (int) $document->id,
            'tipo_documento' => $document->type,
            'origen_tipo' => $origenTipo,
            'origen_id' => $origenId,
            'nombre_original' => $document->original_name,
            'file_path' => $document->file_path,
            'mime_type' => $document->mime,
            'observacion' => $document->note,
            'fecha_documento' => $fechaDocumento ?: $this->normalizeDate($document->created_at),
            'user_id' => (int) $document->uploaded_by,
            'created_at' => $timestamp,
            'updated_at' => $document->updated_at ?: $timestamp,
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 10);
    }
};
