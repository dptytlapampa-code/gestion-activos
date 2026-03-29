<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\Equipo;

class ActaPdfDataService
{
    public function __construct(
        private readonly QrCodeService $qrCodeService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Acta $acta): array
    {
        $acta->loadMissing([
            'institution',
            'institucionDestino',
            'servicioDestino',
            'oficinaDestino',
            'creator',
            'equipos.tipoEquipo',
            'equipos.oficina.service.institution',
        ]);

        $settings = system_config();
        $systemName = $this->nullableTrim($settings->nombre_sistema ?? $settings->site_name ?? config('app.name'))
            ?? config('app.name');
        $headerMastheadPath = $settings->logo_pdf_file_path ?? $settings->logo_institucional_file_path ?? null;
        $issuerInstitutionName = $this->resolveIssuerInstitutionName($acta, $systemName);

        $actaPublicUrl = $this->resolveActaPublicUrl($acta);
        $equipoQr = $this->resolveSingleEquipoForQr($acta);
        $equipoPublicUrl = $equipoQr !== null
            ? route('equipos.public.show', ['uuid' => $equipoQr->uuid])
            : null;
        $actaQrImageDataUri = $this->qrCodeService->pngDataUri($actaPublicUrl, 112, 1, [
            'module' => 'actas',
            'feature' => 'pdf',
            'qr_type' => 'acta',
            'acta_id' => $acta->id,
            'acta_uuid' => $acta->uuid,
            'acta_codigo' => $acta->codigo,
        ]);
        $equipoQrImageDataUri = $this->qrCodeService->pngDataUri($equipoPublicUrl, 104, 1, [
            'module' => 'actas',
            'feature' => 'pdf',
            'qr_type' => 'equipo',
            'acta_id' => $acta->id,
            'acta_uuid' => $acta->uuid,
            'acta_codigo' => $acta->codigo,
            'equipo_id' => $equipoQr?->id,
            'equipo_uuid' => $equipoQr?->uuid,
            'equipo_codigo_interno' => $equipoQr?->codigo_interno,
        ]);

        $originSummary = $this->buildOriginSummary($acta, $issuerInstitutionName);
        $destinoInstitucional = $this->buildDestinoInstitucional($acta);
        $receptorData = $this->buildReceptorData($acta);
        $destinationSummary = $this->buildDestinationSummary($destinoInstitucional, $receptorData);
        $equipmentTable = $this->buildEquipmentTable($acta, $originSummary);

        return [
            'pdfSystemName' => $systemName,
            'pdfInstitutionName' => $issuerInstitutionName,
            'pdfIssuerInstitutionName' => $issuerInstitutionName,
            'pdfFooterInstitutionName' => $systemName,
            'pdfFooterText' => $this->resolveFooterText($systemName),
            'pdfHeaderLogoPath' => $headerMastheadPath,
            'pdfHeaderMastheadPath' => $headerMastheadPath,
            'pdfDocumentTitle' => $this->resolveTitle($acta),
            'pdfDocumentFacts' => $this->buildDocumentFacts($acta, $issuerInstitutionName),
            'pdfOriginSummary' => $originSummary,
            'pdfDestinationSummary' => $destinationSummary,
            'pdfReceptorData' => $receptorData,
            'pdfEquipmentTable' => $equipmentTable,
            'actaPublicUrl' => $actaPublicUrl,
            'actaQrImageDataUri' => $actaQrImageDataUri,
            'equipoPublicUrl' => $equipoPublicUrl,
            'equipoQrImageDataUri' => $equipoQrImageDataUri,
            'pdfQrCards' => $this->buildQrCards(
                $acta,
                $actaPublicUrl,
                $actaQrImageDataUri,
                $equipoQr,
                $equipoPublicUrl,
                $equipoQrImageDataUri
            ),
            'pdfDestinoInstitucional' => $destinoInstitucional,
            'pdfPrestamoDestinatario' => $receptorData,
        ];
    }

    private function resolveIssuerInstitutionName(Acta $acta, string $fallback): string
    {
        $payloadName = $this->nullableTrim(data_get($acta->evento_payload, 'institution_name'));

        if ($payloadName !== null) {
            return $payloadName;
        }

        $institutionId = (int) (data_get($acta->evento_payload, 'institution_id') ?? $acta->institution_id ?? 0);
        $originSnapshots = collect(data_get($acta->evento_payload, 'origenes_por_equipo', []))
            ->filter(fn (mixed $item): bool => is_array($item));

        $matchingSnapshotName = $originSnapshots
            ->first(function (array $item) use ($institutionId): bool {
                if ($institutionId <= 0) {
                    return false;
                }

                return (int) ($item['institucion_id'] ?? 0) === $institutionId
                    && $this->nullableTrim($item['institucion_nombre'] ?? null) !== null;
            });

        if (is_array($matchingSnapshotName)) {
            $resolved = $this->nullableTrim($matchingSnapshotName['institucion_nombre'] ?? null);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        $singleSnapshotName = $originSnapshots
            ->map(fn (array $item): ?string => $this->nullableTrim($item['institucion_nombre'] ?? null))
            ->filter()
            ->unique()
            ->values();

        if ($singleSnapshotName->count() === 1) {
            return (string) $singleSnapshotName->first();
        }

        return $this->nullableTrim($acta->institution?->nombre) ?? $fallback;
    }

    private function resolveActaPublicUrl(Acta $acta): ?string
    {
        $uuid = $this->nullableTrim($acta->uuid ?? null);

        if ($uuid === null) {
            return null;
        }

        return route('actas.public.show', ['uuid' => $uuid]);
    }

    private function resolveSingleEquipoForQr(Acta $acta): ?Equipo
    {
        if ($acta->equipos->count() !== 1) {
            return null;
        }

        return $acta->equipos
            ->first(fn (Equipo $equipo): bool => is_string($equipo->uuid) && $equipo->uuid !== '');
    }

    /**
     * @return array<int, array{title:string,description:string,url:string,image_src:string,meta:string}>
     */
    private function buildQrCards(
        Acta $acta,
        ?string $actaPublicUrl,
        ?string $actaQrImageDataUri,
        ?Equipo $equipoQr,
        ?string $equipoPublicUrl,
        ?string $equipoQrImageDataUri,
    ): array {
        $cards = [];

        if ($actaPublicUrl !== null && $actaQrImageDataUri !== null) {
            $cards[] = [
                'title' => 'Acta patrimonial',
                'description' => 'Escanee este codigo para validar esta acta en modo de solo lectura.',
                'url' => $actaPublicUrl,
                'image_src' => $actaQrImageDataUri,
                'meta' => $acta->codigo,
            ];
        }

        if ($equipoQr !== null && $equipoPublicUrl !== null && $equipoQrImageDataUri !== null) {
            $cards[] = [
                'title' => 'Ficha publica del equipo',
                'description' => 'Disponible solo en actas con un unico equipo para reforzar la trazabilidad sin saturar el documento.',
                'url' => $equipoPublicUrl,
                'image_src' => $equipoQrImageDataUri,
                'meta' => $this->nullableTrim($equipoQr->codigo_interno)
                    ?? $equipoQr->primaryIdentifier(),
            ];
        }

        return $cards;
    }

    private function resolveTitle(Acta $acta): string
    {
        return match ($acta->tipo) {
            Acta::TIPO_ENTREGA => 'ACTA DE ENTREGA DE EQUIPAMIENTO INFORMATICO',
            Acta::TIPO_PRESTAMO => 'ACTA DE PRESTAMO DE EQUIPAMIENTO INFORMATICO',
            Acta::TIPO_TRASLADO => 'ACTA DE TRASLADO DE EQUIPAMIENTO INFORMATICO',
            Acta::TIPO_BAJA => 'ACTA DE BAJA DE EQUIPAMIENTO INFORMATICO',
            Acta::TIPO_DEVOLUCION => 'ACTA DE DEVOLUCION DE EQUIPAMIENTO INFORMATICO',
            Acta::TIPO_MANTENIMIENTO => 'ACTA DE MANTENIMIENTO DE EQUIPAMIENTO INFORMATICO',
            default => 'ACTA DE EQUIPAMIENTO INFORMATICO',
        };
    }

    /**
     * @return array<int, array{label:string,value:string}>
     */
    private function buildDocumentFacts(Acta $acta, string $issuerInstitutionName): array
    {
        return [
            ['label' => 'Codigo', 'value' => $acta->codigo],
            ['label' => 'Tipo', 'value' => $this->resolveTypeLabel($acta)],
            ['label' => 'Fecha', 'value' => $acta->fecha?->format('d/m/Y') ?: '-'],
            ['label' => 'Estado', 'value' => $this->resolveStatusLabel($acta)],
            ['label' => 'Institucion administradora', 'value' => $issuerInstitutionName],
            ['label' => 'Generado por', 'value' => $this->nullableTrim($acta->creator?->name) ?? '-'],
        ];
    }

    /**
     * @return array{
     *     institution_label:string,
     *     headline:string,
     *     caption:?string,
     *     locations:array<int, string>,
     *     requires_equipment_column:bool
     * }
     */
    private function buildOriginSummary(Acta $acta, string $issuerInstitutionName): array
    {
        $locationLabels = $acta->equipos
            ->map(fn (Equipo $equipo): string => $this->resolveOriginLabel($acta, $equipo))
            ->filter(fn (string $label): bool => $label !== '-')
            ->unique()
            ->values();

        $institucionLabel = $this->nullableTrim($acta->institution?->nombre) ?? $issuerInstitutionName;
        $requiresEquipmentColumn = $locationLabels->count() > 1;

        return [
            'institution_label' => $institucionLabel,
            'headline' => $requiresEquipmentColumn
                ? 'Multiples ubicaciones de origen registradas'
                : ((string) ($locationLabels->first() ?? $institucionLabel)),
            'caption' => $requiresEquipmentColumn
                ? 'La trazabilidad individual de origen se conserva en la tabla de detalle.'
                : null,
            'locations' => $locationLabels->all(),
            'requires_equipment_column' => $requiresEquipmentColumn,
        ];
    }

    /**
     * @return array{institucion:?string,servicio:?string,oficina:?string,texto:string,has_data:bool}
     */
    private function buildDestinoInstitucional(Acta $acta): array
    {
        $institucion = $this->nullableTrim($acta->institucionDestino?->nombre);
        $servicio = $this->nullableTrim($acta->servicioDestino?->nombre);
        $oficina = $this->nullableTrim($acta->oficinaDestino?->nombre);

        $partes = array_values(array_filter([
            $institucion,
            $servicio,
            $oficina,
        ], fn (?string $value): bool => $value !== null && $value !== ''));

        $hasData = $partes !== [];

        return [
            'institucion' => $institucion,
            'servicio' => $servicio,
            'oficina' => $oficina,
            'texto' => $hasData ? implode(' / ', $partes) : '-',
            'has_data' => $hasData,
        ];
    }

    /**
     * @return array{is_prestamo:bool,nombre:?string,dni:?string,cargo:?string,dependencia:?string,has_data:bool,summary:string}
     */
    private function buildReceptorData(Acta $acta): array
    {
        $nombre = $this->nullableTrim($acta->receptor_nombre);
        $dni = $this->nullableTrim($acta->receptor_dni);
        $cargo = $this->nullableTrim($acta->receptor_cargo);
        $dependencia = $this->nullableTrim($acta->receptor_dependencia);

        $hasData = collect([$nombre, $dni, $cargo, $dependencia])
            ->contains(fn (?string $value): bool => $value !== null && $value !== '');

        $summaryParts = array_values(array_filter([
            $nombre,
            $dni !== null && $dni !== '' ? 'DNI '.$dni : null,
            $cargo,
            $dependencia,
        ], fn (?string $value): bool => $value !== null && $value !== ''));

        return [
            'is_prestamo' => $acta->tipo === Acta::TIPO_PRESTAMO,
            'nombre' => $nombre,
            'dni' => $dni,
            'cargo' => $cargo,
            'dependencia' => $dependencia,
            'has_data' => $hasData,
            'summary' => $summaryParts !== [] ? implode(' | ', $summaryParts) : '',
        ];
    }

    /**
     * @param  array{institucion:?string,servicio:?string,oficina:?string,texto:string,has_data:bool}  $destinoInstitucional
     * @param  array{is_prestamo:bool,nombre:?string,dni:?string,cargo:?string,dependencia:?string,has_data:bool,summary:string}  $receptorData
     * @return array{title:string,headline:string,caption:?string}
     */
    private function buildDestinationSummary(array $destinoInstitucional, array $receptorData): array
    {
        if (($receptorData['is_prestamo'] ?? false) === true) {
            return [
                'title' => 'Destinatario del prestamo',
                'headline' => $receptorData['summary'] !== ''
                    ? $receptorData['summary']
                    : 'Destinatario del prestamo no informado',
                'caption' => ($destinoInstitucional['has_data'] ?? false) === true
                    ? 'Referencia institucional: '.$destinoInstitucional['texto']
                    : null,
            ];
        }

        if (($destinoInstitucional['has_data'] ?? false) === true) {
            return [
                'title' => 'Destino administrativo',
                'headline' => (string) $destinoInstitucional['texto'],
                'caption' => null,
            ];
        }

        return [
            'title' => 'Destino administrativo',
            'headline' => 'Sin destino adicional registrado.',
            'caption' => null,
        ];
    }

    /**
     * @param  array{
     *     institution_label:string,
     *     headline:string,
     *     caption:?string,
     *     locations:array<int, string>,
     *     requires_equipment_column:bool
     * }  $originSummary
     * @return array{
     *     show_origin_column:bool,
     *     rows:array<int, array{
     *         position:int,
     *         equipo:string,
     *         marca:?string,
     *         modelo:?string,
     *         codigo_interno:?string,
     *         serie:?string,
     *         patrimonial:?string,
     *         accesorios:?string,
     *         origen:?string
     *     }>
     * }
     */
    private function buildEquipmentTable(Acta $acta, array $originSummary): array
    {
        $showOriginColumn = (bool) ($originSummary['requires_equipment_column'] ?? false);

        return [
            'show_origin_column' => $showOriginColumn,
            'rows' => $acta->equipos
                ->values()
                ->map(function (Equipo $equipo, int $index) use ($acta, $showOriginColumn): array {
                    return [
                        'position' => $index + 1,
                        'equipo' => $equipo->tipoEquipo?->nombre ?? $equipo->tipo ?? 'Equipo',
                        'marca' => $this->nullableTrim($equipo->marca),
                        'modelo' => $this->nullableTrim($equipo->modelo),
                        'codigo_interno' => $this->nullableTrim($equipo->codigo_interno),
                        'serie' => $this->nullableTrim($equipo->numero_serie),
                        'patrimonial' => $this->nullableTrim($equipo->bien_patrimonial),
                        'accesorios' => $this->nullableTrim($equipo->pivot->accesorios ?? null),
                        'origen' => $showOriginColumn ? $this->resolveOriginLabel($acta, $equipo) : null,
                    ];
                })
                ->all(),
        ];
    }

    private function resolveOriginLabel(Acta $acta, Equipo $equipo): string
    {
        $payloadOrigen = data_get($acta->evento_payload, 'origenes_por_equipo.'.(string) $equipo->id, []);

        $partes = array_values(array_filter([
            $this->nullableTrim($equipo->pivot->institucion_origen_nombre ?? data_get($payloadOrigen, 'institucion_nombre'))
                ?? $this->nullableTrim($equipo->oficina?->service?->institution?->nombre),
            $this->nullableTrim($equipo->pivot->servicio_origen_nombre ?? data_get($payloadOrigen, 'servicio_nombre'))
                ?? $this->nullableTrim($equipo->oficina?->service?->nombre),
            $this->nullableTrim($equipo->pivot->oficina_origen_nombre ?? data_get($payloadOrigen, 'oficina_nombre'))
                ?? $this->nullableTrim($equipo->oficina?->nombre),
        ], fn (?string $value): bool => $value !== null && $value !== ''));

        return $partes !== [] ? implode(' / ', $partes) : '-';
    }

    private function resolveTypeLabel(Acta $acta): string
    {
        $label = Acta::LABELS[$acta->tipo] ?? strtoupper((string) $acta->tipo);

        return ucfirst(strtolower($label));
    }

    private function resolveStatusLabel(Acta $acta): string
    {
        return ($acta->status ?? Acta::STATUS_ACTIVA) === Acta::STATUS_ANULADA
            ? 'Anulada'
            : 'Activa';
    }

    private function resolveFooterText(string $systemName): string
    {
        $systemName = $this->nullableTrim($systemName);

        if ($systemName !== null) {
            return 'Documento generado por '.$systemName;
        }

        return 'Documento generado por el Sistema Institucional de Gestion de Activos Informaticos';
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
