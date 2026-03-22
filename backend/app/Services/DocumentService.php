<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\Document;
use App\Models\Equipo;
use App\Models\EquipoDocumento;
use App\Models\Mantenimiento;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentService
{
    public function createForEquipo(Equipo $equipo, User $user, array $data, UploadedFile $file): Document
    {
        return $this->createAndRegister(
            $equipo,
            collect([$equipo]),
            EquipoDocumento::ORIGEN_DIRECTO,
            $equipo->id,
            $user,
            $data,
            $file,
            null
        );
    }

    public function createForMovimiento(Movimiento $movimiento, User $user, array $data, UploadedFile $file): Document
    {
        $movimiento->loadMissing('equipo');

        return $this->createAndRegister(
            $movimiento,
            collect([$movimiento->equipo])->filter(),
            EquipoDocumento::ORIGEN_MOVIMIENTO,
            $movimiento->id,
            $user,
            $data,
            $file,
            $movimiento->fecha?->toDateString()
        );
    }

    public function createForMantenimiento(Mantenimiento $mantenimiento, User $user, array $data, UploadedFile $file): Document
    {
        $mantenimiento->loadMissing('equipo');

        return $this->createAndRegister(
            $mantenimiento,
            collect([$mantenimiento->equipo])->filter(),
            EquipoDocumento::ORIGEN_MANTENIMIENTO,
            $mantenimiento->id,
            $user,
            $data,
            $file,
            $mantenimiento->fecha?->toDateString()
        );
    }

    public function registerActaDocument(Document $document, Acta $acta): void
    {
        $acta->loadMissing('equipos:id');

        $this->syncEquipoLedger(
            $document,
            $acta->equipos,
            EquipoDocumento::ORIGEN_ACTA,
            $acta->id,
            $acta->fecha?->toDateString()
        );
    }

    public function delete(Document $document): void
    {
        DB::transaction(function () use ($document): void {
            Storage::delete($document->file_path);
            $document->delete();
        });
    }

    /**
     * @param  Collection<int, Equipo>  $equipos
     */
    private function createAndRegister(
        Equipo|Movimiento|Mantenimiento $origin,
        Collection $equipos,
        string $origenTipo,
        ?int $origenId,
        User $user,
        array $data,
        UploadedFile $file,
        ?string $fechaDocumento
    ): Document {
        $path = $file->store('documents/'.now()->format('Y/m'));

        try {
            /** @var Document $document */
            $document = DB::transaction(function () use ($origin, $equipos, $origenTipo, $origenId, $user, $data, $file, $path, $fechaDocumento): Document {
                $document = $origin->documents()->create([
                    'uploaded_by' => $user->id,
                    'type' => $data['type'],
                    'note' => $data['note'] ?? null,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?: $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);

                $this->syncEquipoLedger($document, $equipos, $origenTipo, $origenId, $fechaDocumento);

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::delete($path);

            throw $exception;
        }

        return $document;
    }

    /**
     * @param  Collection<int, Equipo>  $equipos
     */
    private function syncEquipoLedger(
        Document $document,
        Collection $equipos,
        string $origenTipo,
        ?int $origenId,
        ?string $fechaDocumento
    ): void {
        $fecha = $fechaDocumento ?: $document->created_at?->toDateString() ?: now()->toDateString();

        $equipos
            ->filter(fn (?Equipo $equipo): bool => $equipo instanceof Equipo)
            ->each(function (Equipo $equipo) use ($document, $origenTipo, $origenId, $fecha): void {
                EquipoDocumento::query()->updateOrCreate(
                    [
                        'document_id' => $document->id,
                        'equipo_id' => $equipo->id,
                    ],
                    [
                        'tipo_documento' => $document->type,
                        'origen_tipo' => $origenTipo,
                        'origen_id' => $origenId,
                        'nombre_original' => $document->original_name,
                        'file_path' => $document->file_path,
                        'mime_type' => $document->mime,
                        'observacion' => $document->note,
                        'fecha_documento' => $fecha,
                        'user_id' => $document->uploaded_by,
                    ]
                );
            });
    }
}
