@php
    $isMaintenanceDocumentContext = str_starts_with($documentContext, 'mantenimiento:'.$mantenimiento->id);
    $hasMaintenanceTypeError = $isMaintenanceDocumentContext && $errors->has('type');
    $hasMaintenanceNoteError = $isMaintenanceDocumentContext && $errors->has('note');
    $hasMaintenanceFileError = $isMaintenanceDocumentContext && $errors->has('file');
    $selectedMaintenanceDocumentType = $isMaintenanceDocumentContext ? old('type') : null;
    $selectedMaintenanceDocumentNote = $isMaintenanceDocumentContext ? old('note') : '';
    $maintenanceDocumentCount = $mantenimiento->documents->count();
    $maintenanceDocumentSummary = $maintenanceDocumentCount === 0
        ? 'Sin adjuntos'
        : $maintenanceDocumentCount.' '.($maintenanceDocumentCount === 1 ? 'documento adjunto' : 'documentos adjuntos');
@endphp

<div
    x-cloak
    x-show="activeMaintenanceDocumentModal === {{ $mantenimiento->id }}"
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="mantenimiento-document-modal-title-{{ $mantenimiento->id }}"
>
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-sm" @click="closeMaintenanceDocumentModal()"></div>

    <div
        x-data="{ selectedFileName: '' }"
        id="mantenimiento-document-modal-{{ $mantenimiento->id }}"
        tabindex="-1"
        class="app-panel relative z-10 flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white focus:outline-none"
        @click.stop
    >
        <div class="border-b border-slate-200 bg-slate-50/90 px-5 py-4 sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                        Mantenimiento {{ $mantenimiento->fecha?->format('d/m/Y') ?: 'sin fecha' }}
                    </p>
                    <h3 id="mantenimiento-document-modal-title-{{ $mantenimiento->id }}" class="mt-1 text-lg font-semibold text-slate-900">
                        Documentos del mantenimiento
                    </h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $mantenimiento->titulo }}</p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                    @click="closeMaintenanceDocumentModal()"
                >
                    Cerrar
                </button>
            </div>
        </div>

        <div class="overflow-y-auto px-5 py-5 sm:px-6">
            <div class="flex flex-wrap items-center gap-2">
                <span
                    @class([
                        'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold',
                        'bg-slate-100 text-slate-500' => $maintenanceDocumentCount === 0,
                        'bg-indigo-50 text-indigo-700' => $maintenanceDocumentCount > 0,
                    ])
                >
                    {{ $maintenanceDocumentSummary }}
                </span>
                <p class="text-xs text-slate-500">
                    Consulte los archivos existentes o adjunte nueva documentacion para este evento tecnico.
                </p>
            </div>

            <div class="mt-5 space-y-4">
                <section class="space-y-2">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Documentos existentes</h4>

                    @if ($maintenanceDocumentCount > 0)
                        <div class="space-y-2">
                            @foreach($mantenimiento->documents as $documento_mto)
                                <article class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50/85 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <div class="rounded-lg bg-rose-50 p-2 text-rose-600">
                                                <x-icon name="file-text" class="h-4 w-4" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-900" title="{{ $documento_mto->original_name }}">
                                                    {{ $documento_mto->original_name }}
                                                </p>
                                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                    <span class="inline-flex rounded-full bg-white px-2 py-1 font-semibold text-slate-600">
                                                        {{ ucfirst($documento_mto->type) }}
                                                    </span>
                                                    @if ($documento_mto->note)
                                                        <span class="truncate" title="{{ $documento_mto->note }}">{{ $documento_mto->note }}</span>
                                                    @else
                                                        <span>Sin observacion</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('documents.download', $documento_mto) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                            Ver
                                        </a>
                                        <a href="{{ route('documents.download', $documento_mto) }}" class="inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                            <x-icon name="download" class="h-3.5 w-3.5" />
                                            Descargar
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/75 px-4 py-4 text-sm text-slate-500">
                            Sin documentos adjuntos para este mantenimiento.
                        </div>
                    @endif
                </section>

                @if ($canManageMaintenanceDocuments)
                    <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Adjuntar documento</h4>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Complete los datos minimos del archivo y confirme la carga para dejarlo asociado a este mantenimiento.
                            </p>
                        </div>

                        @if ($isMaintenanceDocumentContext && ($hasMaintenanceTypeError || $hasMaintenanceNoteError || $hasMaintenanceFileError))
                            <div class="mt-4 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-xs text-rose-700">
                                @if ($hasMaintenanceTypeError)
                                    <p>{{ $errors->first('type') }}</p>
                                @endif
                                @if ($hasMaintenanceNoteError)
                                    <p>{{ $errors->first('note') }}</p>
                                @endif
                                @if ($hasMaintenanceFileError)
                                    <p>{{ $errors->first('file') }}</p>
                                @endif
                            </div>
                        @endif

                        <form method="POST" action="{{ route('mantenimientos.documents.store', $mantenimiento) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf
                            <input type="hidden" name="document_context" value="mantenimiento:{{ $mantenimiento->id }}">

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="mantenimiento-type-{{ $mantenimiento->id }}" class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Tipo documento</label>
                                    <select
                                        id="mantenimiento-type-{{ $mantenimiento->id }}"
                                        name="type"
                                        @class([
                                            'min-h-[2.75rem] w-full rounded-xl border bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:outline-none focus:ring-2',
                                            'border-rose-300 focus:border-rose-500 focus:ring-rose-100' => $hasMaintenanceTypeError,
                                            'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100' => ! $hasMaintenanceTypeError,
                                        ])
                                        required
                                    >
                                        @foreach(\App\Models\Document::TYPES as $type)
                                            <option value="{{ $type }}" @selected($selectedMaintenanceDocumentType === $type)>{{ ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                    @if ($hasMaintenanceTypeError)
                                        <p class="text-xs text-rose-600">{{ $errors->first('type') }}</p>
                                    @endif
                                </div>

                                <div class="space-y-2">
                                    <label for="mantenimiento-note-{{ $mantenimiento->id }}" class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Observacion</label>
                                    <input
                                        id="mantenimiento-note-{{ $mantenimiento->id }}"
                                        type="text"
                                        name="note"
                                        value="{{ $selectedMaintenanceDocumentNote }}"
                                        @class([
                                            'min-h-[2.75rem] w-full rounded-xl border bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:outline-none focus:ring-2',
                                            'border-rose-300 focus:border-rose-500 focus:ring-rose-100' => $hasMaintenanceNoteError,
                                            'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100' => ! $hasMaintenanceNoteError,
                                        ])
                                        placeholder="Detalle opcional para identificar el archivo"
                                    >
                                    @if ($hasMaintenanceNoteError)
                                        <p class="text-xs text-rose-600">{{ $errors->first('note') }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label for="mantenimiento-file-{{ $mantenimiento->id }}" class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Archivo adjunto</label>
                                <input id="mantenimiento-file-{{ $mantenimiento->id }}" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" required @change="selectedFileName = $event.target.files[0] ? $event.target.files[0].name : ''">

                                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <label
                                        for="mantenimiento-file-{{ $mantenimiento->id }}"
                                        @class([
                                            'inline-flex min-h-[2.75rem] cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition sm:min-w-[11rem]',
                                            'border-rose-300 bg-rose-50 text-rose-700 hover:border-rose-400 hover:bg-rose-100' => $hasMaintenanceFileError,
                                            'border-slate-300 bg-white text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700' => ! $hasMaintenanceFileError,
                                        ])
                                    >
                                        <x-icon name="paperclip" class="h-4 w-4" />
                                        Subir archivo
                                    </label>

                                    <div class="min-w-0 flex-1 text-xs text-slate-600">
                                        <p class="font-semibold uppercase tracking-[0.12em] text-slate-500">Archivo seleccionado</p>
                                        <p class="mt-1 break-all" x-text="selectedFileName || 'Ningun archivo seleccionado'"></p>
                                    </div>
                                </div>

                                <p class="text-[11px] text-slate-500">Formatos permitidos: PDF, JPG, PNG</p>

                                @if ($hasMaintenanceFileError)
                                    <p class="text-xs text-rose-600">{{ $errors->first('file') }}</p>
                                @endif
                            </div>

                            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    class="inline-flex min-h-[2.75rem] items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                    @click="closeMaintenanceDocumentModal()"
                                >
                                    Cancelar
                                </button>
                                <button type="submit" class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                    <x-icon name="upload" class="h-4 w-4" />
                                    Adjuntar
                                </button>
                            </div>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
