<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 16mm 12mm 14mm 12mm; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #111827;
        margin: 0;
        line-height: 1.35;
    }

    .page {
        position: relative;
    }

    .watermark {
        position: fixed;
        top: 50%;
        left: 50%;
        width: 140%;
        text-align: center;
        white-space: nowrap;
        font-size: 70px;
        font-weight: 700;
        line-height: 1;
        color: #ef4444;
        opacity: 0.2;
        transform: translate(-50%, -50%) rotate(-32deg);
        transform-origin: center center;
        z-index: 0;
    }

    .watermark-prestamo {
        margin-top: -35px;
        margin-left: -70%;
        transform: rotate(-32deg);
    }

    .content {
        position: relative;
        z-index: 1;
    }

    .document-header {
        border: 1px solid #1f2937;
        margin-bottom: 10px;
        padding: 10px 12px;
    }

    .header-table td {
        vertical-align: middle;
    }

    .header-logo-cell {
        width: 118px;
        padding-right: 12px;
    }

    .header-logo-box {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        text-align: center;
        padding: 8px;
        height: 78px;
    }

    .header-logo-box img {
        max-width: 106px;
        max-height: 62px;
    }

    .header-content {
        text-align: left;
    }

    .system-name {
        font-size: 17px;
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1.15;
    }

    .issuer-name {
        margin-top: 4px;
        font-size: 9.5px;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .title {
        margin-top: 8px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .header-meta {
        margin-top: 4px;
        font-size: 9px;
        color: #475569;
    }

    .section {
        border: 1px solid #374151;
        padding: 8px;
        margin-bottom: 9px;
    }

    .keep-together {
        page-break-inside: avoid;
    }

    .section-highlight {
        border: 2px solid #1d4ed8;
        background: #eff6ff;
    }

    .section-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 6px;
        letter-spacing: 0.05em;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .summary-table td,
    .list-table td,
    .event-table td {
        border: 1px solid #6b7280;
        padding: 5px 6px;
        vertical-align: top;
        font-size: 10px;
    }

    .summary-table .empty-cell {
        background: #f8fafc;
    }

    .detail-table th,
    .detail-table td {
        border: 1px solid #6b7280;
        padding: 4px 5px;
        vertical-align: top;
        font-size: 9px;
        line-height: 1.3;
        overflow-wrap: break-word;
        word-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }

    .detail-table th {
        background-color: #f3f4f6;
        text-align: left;
    }

    thead {
        display: table-header-group;
    }

    tr,
    td,
    th {
        page-break-inside: avoid;
    }

    .detail-col-index {
        width: 6%;
    }

    .detail-col-equipo {
        width: 14%;
    }

    .detail-col-brand {
        width: 18%;
    }

    .detail-col-identifiers {
        width: 20%;
    }

    .detail-col-accessories {
        width: 20%;
    }

    .detail-col-origin {
        width: 22%;
    }

    .detail-cell-index {
        text-align: center;
    }

    .detail-primary,
    .detail-secondary,
    .detail-meta-line {
        display: block;
    }

    .detail-primary {
        font-weight: 700;
    }

    .detail-secondary {
        margin-top: 2px;
    }

    .detail-meta-line {
        margin-top: 2px;
    }

    .detail-inline-label,
    .label {
        font-weight: 700;
    }

    .label {
        display: block;
        margin-bottom: 2px;
        text-transform: uppercase;
        font-size: 9px;
        letter-spacing: 0.04em;
        color: #374151;
    }

    .block-text {
        text-align: justify;
    }

    .muted {
        color: #475569;
    }

    .signatures td {
        width: 50%;
        padding-top: 34px;
        text-align: center;
        font-size: 10px;
    }

    .line {
        border-top: 1px solid #111827;
        margin: 0 auto 5px;
        width: 82%;
    }

    .qr-table td {
        vertical-align: middle;
    }

    .qr-code {
        width: 120px;
    }

    .qr-code svg {
        width: 120px;
        height: 120px;
    }

    .qr-text {
        padding-left: 10px;
        font-size: 10px;
    }

    .qr-url {
        margin-top: 4px;
        font-size: 8.5px;
        color: #374151;
        word-break: break-all;
    }

    .footer {
        margin-top: 8px;
        text-align: center;
        font-size: 9px;
        color: #374151;
    }

    .footer-line {
        display: block;
    }
</style>
</head>
<body>
<div class="page">
    @php
        $isAnulada = ($acta->status ?? \App\Models\Acta::STATUS_ACTIVA) === \App\Models\Acta::STATUS_ANULADA;
        $documentTitle = $pdfDocumentTitle ?? strtoupper((string) ($titulo ?? 'ACTA DE EQUIPAMIENTO INFORMATICO'));
        $systemName = $pdfSystemName ?? config('app.name');
        $issuerInstitutionName = $pdfIssuerInstitutionName ?? ($pdfInstitutionName ?? ($acta->institution?->nombre ?: 'Institucion'));
        $footerInstitutionName = $pdfFooterInstitutionName ?? $issuerInstitutionName;
        $clausulaTexto = $clausula ?? 'Se deja constancia institucional del evento de trazabilidad registrado sobre el equipamiento detallado en el presente documento.';
        $documentFacts = collect($pdfDocumentFacts ?? []);
        $originSummary = is_array($pdfOriginSummary ?? null) ? $pdfOriginSummary : [
            'institution_label' => $issuerInstitutionName,
            'headline' => $issuerInstitutionName,
            'caption' => null,
            'locations' => [],
            'requires_equipment_column' => false,
        ];
        $destinationSummary = is_array($pdfDestinationSummary ?? null) ? $pdfDestinationSummary : [
            'title' => 'Destino administrativo',
            'headline' => 'Sin destino adicional registrado.',
            'caption' => null,
        ];
        $receptorData = is_array($pdfReceptorData ?? null) ? $pdfReceptorData : [
            'is_prestamo' => $acta->tipo === \App\Models\Acta::TIPO_PRESTAMO,
            'nombre' => trim((string) ($acta->receptor_nombre ?? '')),
            'dni' => trim((string) ($acta->receptor_dni ?? '')),
            'cargo' => trim((string) ($acta->receptor_cargo ?? '')),
            'dependencia' => trim((string) ($acta->receptor_dependencia ?? '')),
            'has_data' => false,
            'summary' => '',
        ];
        $equipmentTable = is_array($pdfEquipmentTable ?? null) ? $pdfEquipmentTable : [
            'show_origin_column' => false,
            'rows' => [],
        ];
        $isPrestamo = (bool) ($receptorData['is_prestamo'] ?? false);
        $signatureRightLabel = match ($acta->tipo) {
            \App\Models\Acta::TIPO_PRESTAMO => 'Destinatario del prestamo',
            \App\Models\Acta::TIPO_BAJA => 'Autoridad interviniente',
            \App\Models\Acta::TIPO_MANTENIMIENTO => 'Responsable tecnico / receptor',
            default => 'Responsable receptor',
        };
        $multipleOriginLocations = collect($originSummary['locations'] ?? [])->filter()->values();
        $hasReceptorData = (bool) ($receptorData['has_data'] ?? false);
        $hasMotivoBaja = filled($acta->motivo_baja ?? null);
        $qrDescription = $acta->equipos->count() > 1
            ? 'Escanee este codigo para consultar la ficha publica del primer equipo asociado al acta.'
            : 'Escanee este codigo para consultar la ficha publica del equipo asociado al acta.';
    @endphp

    @if ($isAnulada)
        <div class="watermark{{ $isPrestamo ? ' watermark-prestamo' : '' }}">ACTA ANULADA</div>
    @endif

    <div class="content">
        <div class="document-header">
            <table class="header-table">
                <tr>
                    @if (! empty($pdfHeaderLogoPath))
                        <td class="header-logo-cell">
                            <div class="header-logo-box">
                                <img src="{{ $pdfHeaderLogoPath }}" alt="Logo institucional">
                            </div>
                        </td>
                    @endif
                    <td class="header-content">
                        <div class="system-name">{{ $systemName }}</div>
                        <div class="issuer-name">{{ $issuerInstitutionName }}</div>
                        <div class="title">{{ $documentTitle }}</div>
                        <div class="header-meta">
                            Documento administrativo de trazabilidad patrimonial
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section keep-together">
            <div class="section-title">Datos del documento</div>
            <table class="summary-table">
                @foreach ($documentFacts->chunk(2) as $chunk)
                    <tr>
                        @foreach ($chunk as $fact)
                            <td>
                                <span class="label">{{ $fact['label'] }}</span>
                                {{ $fact['value'] }}
                            </td>
                        @endforeach
                        @if ($chunk->count() === 1)
                            <td class="empty-cell">&nbsp;</td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>

        <div class="section keep-together">
            <div class="section-title">Contexto administrativo</div>
            <table class="summary-table">
                <tr>
                    <td>
                        <span class="label">Origen administrativo</span>
                        {{ $originSummary['headline'] ?? '-' }}
                        @if (! empty($originSummary['caption']))
                            <span class="detail-secondary muted">{{ $originSummary['caption'] }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="label">{{ $destinationSummary['title'] ?? 'Destino administrativo' }}</span>
                        {{ $destinationSummary['headline'] ?? '-' }}
                        @if (! empty($destinationSummary['caption']))
                            <span class="detail-secondary muted">{{ $destinationSummary['caption'] }}</span>
                        @endif
                    </td>
                </tr>
                @if (! $isPrestamo && $hasReceptorData)
                    <tr>
                        <td>
                            <span class="label">Responsable receptor</span>
                            {{ $receptorData['summary'] !== '' ? $receptorData['summary'] : '-' }}
                        </td>
                        <td>
                            <span class="label">Dependencia / referencia</span>
                            {{ $receptorData['dependencia'] ?: '-' }}
                        </td>
                    </tr>
                @endif
                @if ($hasMotivoBaja)
                    <tr>
                        <td colspan="2">
                            <span class="label">Motivo de baja</span>
                            {{ $acta->motivo_baja }}
                        </td>
                    </tr>
                @endif
            </table>

            @if ($multipleOriginLocations->count() > 1)
                <table class="list-table" style="margin-top: 6px;">
                    <tr>
                        <td>
                            <span class="label">Origenes involucrados</span>
                            @foreach ($multipleOriginLocations as $location)
                                <span class="detail-meta-line">{{ $loop->iteration }}. {{ $location }}</span>
                            @endforeach
                        </td>
                    </tr>
                </table>
            @endif
        </div>

        @if ($isPrestamo)
            <div class="section section-highlight keep-together">
                <div class="section-title">Identificacion del destinatario del prestamo</div>
                <table class="event-table">
                    <tr>
                        <td><span class="label">Nombre y apellido</span>{{ $receptorData['nombre'] ?: '-' }}</td>
                        <td><span class="label">DNI</span>{{ $receptorData['dni'] ?: '-' }}</td>
                        <td><span class="label">Cargo</span>{{ $receptorData['cargo'] ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3"><span class="label">Dependencia</span>{{ $receptorData['dependencia'] ?: '-' }}</td>
                    </tr>
                </table>
            </div>
        @endif

        <div class="section">
            <div class="section-title">Detalle de equipamiento</div>
            <table class="detail-table">
                <colgroup>
                    <col class="detail-col-index">
                    <col class="detail-col-equipo">
                    <col class="detail-col-brand">
                    <col class="detail-col-identifiers">
                    <col class="detail-col-accessories">
                    @if (! empty($equipmentTable['show_origin_column']))
                        <col class="detail-col-origin">
                    @endif
                </colgroup>
                <thead>
                <tr>
                    <th>#</th>
                    <th class="detail-col-equipo">Equipo</th>
                    <th class="detail-col-brand">Marca / Modelo</th>
                    <th class="detail-col-identifiers">Identificadores</th>
                    <th class="detail-col-accessories">Accesorios / nota breve</th>
                    @if (! empty($equipmentTable['show_origin_column']))
                        <th class="detail-col-origin">Origen individual</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse (($equipmentTable['rows'] ?? []) as $row)
                    <tr>
                        <td class="detail-cell-index">{{ $row['position'] }}</td>
                        <td class="detail-cell-equipo">
                            <span class="detail-primary">{{ $row['equipo'] }}</span>
                        </td>
                        <td class="detail-cell-brand">
                            <span class="detail-primary">{{ $row['marca'] ?: '-' }}</span>
                            <span class="detail-secondary">{{ $row['modelo'] ?: '-' }}</span>
                        </td>
                        <td class="detail-cell-identifiers">
                            <span class="detail-meta-line"><span class="detail-inline-label">Serie:</span> {{ $row['serie'] ?: '-' }}</span>
                            <span class="detail-meta-line"><span class="detail-inline-label">Patrimonial:</span> {{ $row['patrimonial'] ?: '-' }}</span>
                        </td>
                        <td class="detail-cell-accessories">{{ $row['accesorios'] ?: '-' }}</td>
                        @if (! empty($equipmentTable['show_origin_column']))
                            <td>{{ $row['origen'] ?: '-' }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ! empty($equipmentTable['show_origin_column']) ? 6 : 5 }}">Sin equipos asociados.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="section keep-together">
            <div class="section-title">Declaracion institucional</div>
            <div class="block-text">
                {{ $clausulaTexto }}
                <br><br>
                El responsable receptor declara recibir los bienes en el estado indicado, comprometiendose a su uso adecuado, conservacion y custodia, conforme a las normas institucionales vigentes de administracion de activos tecnologicos.
            </div>
        </div>

        <div class="section keep-together">
            <div class="section-title">Observaciones</div>
            <div>{{ $acta->observaciones ?: '-' }}</div>
        </div>

        <div class="section keep-together">
            <div class="section-title">Registro administrativo</div>
            <div class="block-text">
                El presente documento forma parte del registro administrativo del sistema, quedando disponible para fines de trazabilidad, control patrimonial y auditoria institucional.
            </div>
        </div>

        <div class="section keep-together">
            <div class="section-title">Firmas</div>
            <table class="signatures">
                <tr>
                    <td>
                        <div class="line"></div>
                        Responsable administrativo
                    </td>
                    <td>
                        <div class="line"></div>
                        {{ $signatureRightLabel }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="section keep-together">
            <div class="section-title">Validacion de ficha publica</div>
            @if (! empty($equipoQrSvg))
                <table class="qr-table">
                    <tr>
                        <td class="qr-code">{!! $equipoQrSvg !!}</td>
                        <td class="qr-text">
                            {{ $qrDescription }}
                            @if (! empty($equipoPublicUrl))
                                <div class="qr-url">{{ $equipoPublicUrl }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            @else
                <div class="qr-text">No fue posible generar el QR para esta acta.</div>
            @endif
        </div>

        <div class="footer keep-together">
            <span class="footer-line">Documento generado por {{ $systemName }}</span>
            <span class="footer-line">{{ $footerInstitutionName }}</span>
        </div>
    </div>
</div>
</body>
</html>
