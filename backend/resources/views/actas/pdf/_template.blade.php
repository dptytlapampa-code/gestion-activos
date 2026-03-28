<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 16mm 14mm 16mm 14mm; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #182433;
        margin: 0;
        line-height: 1.4;
    }

    .page {
        position: relative;
    }

    .watermark {
        position: fixed;
        top: 50%;
        left: 50%;
        width: 100%;
        text-align: center;
        white-space: nowrap;
        font-size: 70px;
        font-weight: 700;
        line-height: 1;
        color: #dc2626;
        opacity: 0.16;
        transform: translate(-50%, -50%) rotate(-32deg);
        transform-origin: center center;
        z-index: 0;
    }

    .content {
        position: relative;
        z-index: 1;
    }

    .document-header {
        margin-bottom: 16px;
    }

    .masthead {
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #d3dce4;
        text-align: center;
    }

    .masthead-image {
        display: block;
        width: 100%;
        max-width: 100%;
        max-height: 96px;
        margin: 0 auto;
        object-fit: contain;
        object-position: center top;
    }

    .title-block {
        text-align: center;
        border-bottom: 2px solid #23364d;
        padding-bottom: 12px;
    }

    .title-block-compact {
        padding-top: 2px;
    }

    .header-kicker {
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: #667788;
        margin-bottom: 5px;
    }

    .system-name {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1.12;
        color: #13253b;
    }

    .issuer-name {
        margin-top: 3px;
        font-size: 9.5px;
        color: #506173;
    }

    .title {
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1.18;
        letter-spacing: 0.03em;
        color: #17283e;
    }

    .header-meta {
        margin-top: 5px;
        font-size: 9px;
        color: #647588;
    }

    .section {
        margin-bottom: 14px;
    }

    .keep-together {
        page-break-inside: avoid;
    }

    .section-title {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: #5f7082;
        padding-bottom: 5px;
        margin-bottom: 8px;
        border-bottom: 1px solid #d7e0e8;
    }

    .summary-table,
    .detail-table,
    .signature-table,
    .qr-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .summary-table tr + tr td {
        border-top: 1px solid #e1e8ee;
    }

    .summary-table td {
        width: 50%;
        padding: 8px 0;
        vertical-align: top;
    }

    .summary-table td + td {
        padding-left: 16px;
        border-left: 1px solid #e1e8ee;
    }

    .field-label {
        display: block;
        margin-bottom: 2px;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #6f8091;
    }

    .field-value {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #1b2a3b;
    }

    .field-note {
        display: block;
        margin-top: 3px;
        font-size: 9px;
        color: #687a8c;
    }

    .context-note {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #e1e8ee;
        font-size: 9.5px;
        color: #44556a;
    }

    .context-note-line {
        display: block;
        margin-top: 3px;
    }

    .highlight-panel {
        background: #f4f7fa;
        border-left: 3px solid #23364d;
        padding: 10px 12px;
    }

    .text-block {
        font-size: 10.5px;
        color: #223246;
        text-align: justify;
    }

    .detail-table thead th {
        padding: 7px 6px;
        background: #f3f6f9;
        border-bottom: 1.5px solid #23364d;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        text-align: left;
        color: #4f6275;
    }

    .detail-table tbody td {
        padding: 8px 6px;
        border-bottom: 1px solid #dbe4eb;
        vertical-align: top;
        font-size: 9.5px;
        color: #1b2a3b;
        overflow-wrap: break-word;
        word-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }

    .detail-table tbody tr:last-child td {
        border-bottom: 1.5px solid #23364d;
    }

    .detail-col-equipo {
        width: 16%;
    }

    .detail-col-brand {
        width: 22%;
    }

    .detail-col-identifiers {
        width: 23%;
    }

    .detail-col-accessories {
        width: 19%;
    }

    .detail-col-origin {
        width: 20%;
    }

    .detail-primary,
    .detail-secondary,
    .detail-meta-line {
        display: block;
    }

    .detail-primary {
        font-weight: 700;
        color: #13253b;
    }

    .detail-secondary {
        margin-top: 2px;
        color: #506173;
    }

    .detail-meta-line {
        margin-top: 2px;
        color: #32455a;
    }

    .detail-inline-label {
        font-weight: 700;
        color: #5d7082;
    }

    .signature-table td {
        width: 50%;
        padding-top: 48px;
        text-align: center;
        vertical-align: bottom;
        font-size: 10px;
    }

    .signature-table td + td {
        padding-left: 18px;
    }

    .signature-line {
        border-top: 1px solid #25374d;
        margin: 0 auto 6px;
        width: 82%;
    }

    .signature-label {
        font-weight: 600;
        color: #2d3d51;
    }

    .qr-panel {
        background: #f7fafc;
        border-top: 1px solid #dbe4eb;
        border-bottom: 1px solid #dbe4eb;
    }

    .qr-table td {
        vertical-align: middle;
        padding: 10px 0;
    }

    .qr-code {
        width: 124px;
    }

    .qr-code svg {
        width: 120px;
        height: 120px;
    }

    .qr-text {
        padding-left: 14px;
        font-size: 10px;
        color: #23364d;
    }

    .qr-url {
        margin-top: 5px;
        font-size: 8.5px;
        color: #5d7082;
        word-break: break-all;
    }

    .footer {
        margin-top: 12px;
        padding-top: 8px;
        border-top: 1px solid #d7e0e8;
        text-align: center;
        font-size: 8.5px;
        color: #5f7082;
    }

    .footer-line {
        display: block;
    }

    thead {
        display: table-header-group;
    }

    tr,
    td,
    th {
        page-break-inside: avoid;
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
        $headerMastheadPath = $pdfHeaderMastheadPath ?? $pdfHeaderLogoPath ?? null;
        $footerText = $pdfFooterText ?? ('Documento generado por '.$systemName);
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
        <div class="watermark">ACTA ANULADA</div>
    @endif

    <div class="content">
        <div class="document-header">
            @if (! empty($headerMastheadPath))
                <div class="masthead">
                    <img class="masthead-image" src="{{ $headerMastheadPath }}" alt="Membrete institucional">
                </div>
            @endif

            <div class="title-block{{ empty($headerMastheadPath) ? ' title-block-compact' : '' }}">
                @if (empty($headerMastheadPath))
                    <div class="header-kicker">Gestion institucional de activos</div>
                    <div class="system-name">{{ $systemName }}</div>
                    <div class="issuer-name">{{ $issuerInstitutionName }}</div>
                @endif

                <div class="title">{{ $documentTitle }}</div>
                <div class="header-meta">
                    Documento administrativo de trazabilidad patrimonial y operativa
                </div>
            </div>
        </div>

        <div class="section keep-together">
            <div class="section-title">Datos del documento</div>
            <table class="summary-table">
                @foreach ($documentFacts->chunk(2) as $chunk)
                    <tr>
                        @foreach ($chunk as $fact)
                            <td>
                                <span class="field-label">{{ $fact['label'] }}</span>
                                <span class="field-value">{{ $fact['value'] }}</span>
                            </td>
                        @endforeach
                        @if ($chunk->count() === 1)
                            <td>&nbsp;</td>
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
                        <span class="field-label">Origen administrativo</span>
                        <span class="field-value">{{ $originSummary['headline'] ?? '-' }}</span>
                        @if (! empty($originSummary['caption']))
                            <span class="field-note">{{ $originSummary['caption'] }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="field-label">{{ $destinationSummary['title'] ?? 'Destino administrativo' }}</span>
                        <span class="field-value">{{ $destinationSummary['headline'] ?? '-' }}</span>
                        @if (! empty($destinationSummary['caption']))
                            <span class="field-note">{{ $destinationSummary['caption'] }}</span>
                        @endif
                    </td>
                </tr>
                @if (! $isPrestamo && $hasReceptorData)
                    <tr>
                        <td>
                            <span class="field-label">Responsable receptor</span>
                            <span class="field-value">{{ $receptorData['summary'] !== '' ? $receptorData['summary'] : '-' }}</span>
                        </td>
                        <td>
                            <span class="field-label">Dependencia / referencia</span>
                            <span class="field-value">{{ $receptorData['dependencia'] ?: '-' }}</span>
                        </td>
                    </tr>
                @endif
                @if ($hasMotivoBaja)
                    <tr>
                        <td colspan="2">
                            <span class="field-label">Motivo de baja</span>
                            <span class="field-value">{{ $acta->motivo_baja }}</span>
                        </td>
                    </tr>
                @endif
            </table>

            @if ($multipleOriginLocations->count() > 1)
                <div class="context-note">
                    <span class="field-label">Origenes involucrados</span>
                    @foreach ($multipleOriginLocations as $location)
                        <span class="context-note-line">{{ $loop->iteration }}. {{ $location }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($isPrestamo)
            <div class="section keep-together">
                <div class="section-title">Destinatario del prestamo</div>
                <div class="highlight-panel">
                    <table class="summary-table">
                        <tr>
                            <td>
                                <span class="field-label">Nombre y apellido</span>
                                <span class="field-value">{{ $receptorData['nombre'] ?: '-' }}</span>
                            </td>
                            <td>
                                <span class="field-label">DNI</span>
                                <span class="field-value">{{ $receptorData['dni'] ?: '-' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="field-label">Cargo</span>
                                <span class="field-value">{{ $receptorData['cargo'] ?: '-' }}</span>
                            </td>
                            <td>
                                <span class="field-label">Dependencia</span>
                                <span class="field-value">{{ $receptorData['dependencia'] ?: '-' }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        <div class="section">
            <div class="section-title">Detalle de equipamiento</div>
            <table class="detail-table">
                <colgroup>
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
                    <th class="detail-col-equipo">Equipo</th>
                    <th class="detail-col-brand">Marca / Modelo</th>
                    <th class="detail-col-identifiers">Identificacion</th>
                    <th class="detail-col-accessories">Accesorios / nota</th>
                    @if (! empty($equipmentTable['show_origin_column']))
                        <th class="detail-col-origin">Origen individual</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse (($equipmentTable['rows'] ?? []) as $row)
                    <tr>
                        <td>
                            <span class="detail-primary">{{ $row['equipo'] }}</span>
                        </td>
                        <td>
                            <span class="detail-primary">{{ $row['marca'] ?: '-' }}</span>
                            <span class="detail-secondary">{{ $row['modelo'] ?: '-' }}</span>
                        </td>
                        <td>
                            <span class="detail-meta-line"><span class="detail-inline-label">Serie:</span> {{ $row['serie'] ?: '-' }}</span>
                            <span class="detail-meta-line"><span class="detail-inline-label">Patrimonial:</span> {{ $row['patrimonial'] ?: '-' }}</span>
                        </td>
                        <td>{{ $row['accesorios'] ?: '-' }}</td>
                        @if (! empty($equipmentTable['show_origin_column']))
                            <td>{{ $row['origen'] ?: '-' }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ! empty($equipmentTable['show_origin_column']) ? 5 : 4 }}">Sin equipos asociados.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="section keep-together">
            <div class="section-title">Declaracion institucional</div>
            <div class="highlight-panel">
                <div class="text-block">
                    {{ $clausulaTexto }}
                    <br><br>
                    El responsable receptor declara recibir los bienes en el estado indicado, comprometiendose a su uso adecuado, conservacion y custodia, conforme a las normas institucionales vigentes de administracion de activos tecnologicos.
                </div>
            </div>
        </div>

        <div class="section keep-together">
            <div class="section-title">Observaciones</div>
            <div class="text-block">{{ $acta->observaciones ?: '-' }}</div>
        </div>

        <div class="section keep-together">
            <div class="section-title">Registro administrativo</div>
            <div class="text-block">
                El presente documento forma parte del registro administrativo del sistema, quedando disponible para fines de trazabilidad, control patrimonial y auditoria institucional.
            </div>
        </div>

        <div class="section keep-together">
            <div class="section-title">Firmas</div>
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-line"></div>
                        <span class="signature-label">Responsable administrativo</span>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <span class="signature-label">{{ $signatureRightLabel }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section keep-together">
            <div class="section-title">Validacion publica</div>
            <div class="qr-panel">
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
        </div>

        <div class="footer keep-together">
            <span class="footer-line">{{ $footerText }}</span>
        </div>
    </div>
</div>
</body>
</html>
