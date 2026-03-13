<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Equipo;
use App\Models\Movimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function storeForEquipo(Request $request, Equipo $equipo): RedirectResponse
    {
        $this->authorize('view', $equipo);
        $institutionId = (int) $equipo->oficina?->service?->institution_id;
        $this->authorize('create', [Document::class, $institutionId]);

        $data = $this->validateRequest($request);
        $file = $request->file('file');
        $path = $file->store('documents/'.now()->format('Y/m'));

        $equipo->documents()->create([
            'uploaded_by' => $request->user()->id,
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('status', 'Documento cargado correctamente.');
    }

    public function storeForMovimiento(Request $request, Movimiento $movimiento): RedirectResponse
    {
        $this->authorize('view', $movimiento);
        $institutionId = (int) $movimiento->equipo?->oficina?->service?->institution_id;
        $this->authorize('create', [Document::class, $institutionId]);

        $data = $this->validateRequest($request);
        $file = $request->file('file');
        $path = $file->store('documents/'.now()->format('Y/m'));

        $movimiento->documents()->create([
            'uploaded_by' => $request->user()->id,
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('status', 'Documento cargado correctamente.');
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::download($document->file_path, $document->original_name);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);
        Storage::delete($document->file_path);
        $document->delete();

        return back()->with('status', 'Documento eliminado.');
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(Document::TYPES)],
            'note' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png'],
        ]);
    }
}
