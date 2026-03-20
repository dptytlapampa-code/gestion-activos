<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 18mm 12mm 14mm 12mm; }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        color: #111827;
        margin: 0;
        line-height: 1.25;
    }

    .acta-page {
        position: relative;
        overflow: visible;
    }

    .acta-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 140%;
        text-align: center;
        white-space: nowrap;
        font-size: 78px;
        font-weight: 700;
        line-height: 1;
        color: #dc2626;
        opacity: 0.12;
        transform: translate(-50%, -50%) rotate(-32deg);
        transform-origin: center center;
        z-index: 0;
    }

    .acta-content {
        position: relative;
        z-index: 1;
    }
    .document-header {
        border: 1px solid #374151;
        margin-bottom: 8px;
        padding: 8px 6px;
        text-align: center;
    }

    .document-top-logo {
        margin-bottom: 6px;
    }

    .document-top-logo img {
        max-width: 180px;
        max-height: 64px;
    }

    .document-title-wrap {
        text-align: center;
    }

    .institution-name {
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 4px;
        text-transform: uppercase;
    }

    .title {
        font-size: 15px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .section {
        border: 1px solid #374151;
        padding: 7px;
        margin-bottom: 8px;
    }

    .section-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .destinatario-prestamo {
        border: 2px solid #1d4ed8;
        background: #eff6ff;
    }

    .destinatario-note {
        margin-top: 6px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        color: #1e3a8a;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .meta-table td {
        border: 1px solid #6b7280;
        padding: 4px 6px;
        vertical-align: top;
        font-size: 12px;
    }

    .detail-table th,
    .detail-table td {
        border: 1px solid #6b7280;
        padding: 3px 4px;
        vertical-align: top;
        font-size: 9px;
        line-height: 1.2;
        overflow-wrap: break-word;
        word-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }

    .detail-table th {
        background-color: #f3f4f6;
        text-align: left;
    }

    .detail-col-equipo {
        width: 12%;
    }

    .detail-col-marca-modelo {
        width: 16%;
    }

    .detail-col-serie {
        width: 15%;
    }

    .detail-col-patrimonial {
        width: 15%;
    }

    .detail-col-origen {
        width: 18%;
    }

    .detail-col-destino {
        width: 16%;
    }

    .detail-col-cantidad {
        width: 6%;
    }

    .detail-cell-equipo,
    .detail-cell-serie,
    .detail-cell-patrimonial,
    .detail-cell-cantidad {
        text-align: center;
    }

    .detail-cell-marca-modelo .detail-primary,
    .detail-cell-marca-modelo .detail-secondary {
        display: block;
    }

    .detail-cell-marca-modelo .detail-secondary {
        margin-top: 1px;
    }

    .detail-cell-serie,
    .detail-cell-patrimonial {
        font-family: DejaVu Sans Mono, DejaVu Sans, sans-serif;
        font-size: 8.5px;
        text-align: left;
        padding-left: 6px;
        padding-right: 6px;
        word-break: normal;
    }

    .event-table td {
        border: 1px solid #6b7280;
        padding: 4px 6px;
        vertical-align: top;
        font-size: 12px;
    }

    .label {
        font-weight: 700;
        display: block;
        margin-bottom: 1px;
    }

    .block-text {
        text-align: justify;
    }

    .signatures td {
        width: 50%;
        padding-top: 24px;
        text-align: center;
        font-size: 12px;
    }

    .line {
        border-top: 1px solid #111827;
        margin: 0 auto 4px;
        width: 80%;
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
        font-size: 11px;
    }

    .qr-url {
        margin-top: 4px;
        font-size: 9px;
        color: #374151;
        word-break: break-all;
    }

    .footer {
        margin-top: 6px;
        text-align: center;
        font-size: 10px;
        color: #374151;
    }
</style>
</head>
<body>
<div class="acta-page">
    @php
        $isAnulada = ($acta->status ?? \App\Models\Acta::STATUS_ACTIVA) === \App\Models\Acta::STATUS_ANULADA;
        $documentTitle = $pdfDocumentTitle ?? strtoupper((string) ($titulo ?? 'ACTA DE EQUIPAMIENTO INFORMATICO'));
        $institutionName = $pdfInstitutionName ?? ($acta->institution?->nombre ?: 'Institucion');
        $clausulaTexto = $clausula ?? 'Se deja constancia institucional del evento de trazabilidad registrado sobre el equipamiento detallado en el presente documento.';
        $origenMultiple = (bool) data_get($acta->evento_payload, 'origen_multiple', false);
        $institucionesOrigenCount = count(data_get($acta->evento_payload, 'instituciones_origen_ids', []));

        $destinoInstitucional = is_array($pdfDestinoInstitucional ?? null)
            ? $pdfDestinoInstitucional
            : [
                'institucion' => $acta->institucionDestino?->nombre,
                'servicio' => $acta->servicioDestino?->nombre,
                'oficina' => $acta->oficinaDestino?->nombre,
                'texto' => trim(implode(' / ', [
                    $acta->institucionDestino?->nombre ?: '-',
                    $acta->servicioDestino?->nombre ?: '-',
                    $acta->oficinaDestino?->nombre ?: '-',
                ])),
                'has_data' => (bool) ($acta->institucionDestino?->nombre || $acta->servicioDestino?->nombre || $acta->oficinaDestino?->nombre),
            ];

        $destinoInstitucionalTexto = (string) ($destinoInstitucional['texto'] ?? '-');
        $destinoInstitucionalHasData = (bool) ($destinoInstitucional['has_data'] ?? false);

        $prestamoDestinatario = is_array($pdfPrestamoDestinatario ?? null)
            ? $pdfPrestamoDestinatario
            : [
                'is_prestamo' => $acta->tipo === \App\Models\Acta::TIPO_PRESTAMO,
                'nombre' => trim((string) ($acta->receptor_nombre ?? '')),
                'dni' => trim((string) ($acta->receptor_dni ?? '')),
                'cargo' => trim((string) ($acta->receptor_cargo ?? '')),
                'dependencia' => trim((string) ($acta->receptor_dependencia ?? '')),
                'has_data' => false,
                'summary' => '',
            ];

        $isPrestamo = (bool) ($prestamoDestinatario['is_prestamo'] ?? false);
        $destinatarioPrestamoHasData = (bool) ($prestamoDestinatario['has_data'] ?? false);
        $destinatarioPrestamoSummary = (string) ($prestamoDestinatario['summary'] ?? '');

        if ($destinatarioPrestamoSummary === '') {
            $summaryParts = array_values(array_filter([
                $prestamoDestinatario['nombre'] ?? null,
                ! empty($prestamoDestinatario['dni']) ? 'DNI '.(string) $prestamoDestinatario['dni'] : null,
                $prestamoDestinatario['cargo'] ?? null,
                $prestamoDestinatario['dependencia'] ?? null,
            ], fn (?string $value): bool => $value !== null && trim($value) !== ''));

            $destinatarioPrestamoSummary = $summaryParts !== [] ? implode(' | ', $summaryParts) : '';
        }

        $destinoPrincipalTexto = $destinoInstitucionalTexto;

        if ($isPrestamo && $destinatarioPrestamoHasData) {
            $destinoPrincipalTexto = $destinatarioPrestamoSummary !== ''
                ? $destinatarioPrestamoSummary
                : 'Destinatario del prestamo no informado';

            if ($destinoInstitucionalHasData) {
                $destinoPrincipalTexto .= ' (Ref. institucional: '.$destinoInstitucionalTexto.')';
            }
        }
    @endphp

    @if ($isAnulada)
        <div class="acta-watermark">ACTA ANULADA</div>
    @endif

    <div class="acta-content">
        <div class="document-header">
            @if (! empty($pdfHeaderLogoPath))
                <div class="document-top-logo">
                    <img src="{{ $pdfHeaderLogoPath }}" alt="Logo institucional">
                </div>
            @endif
            <div class="document-title-wrap">
                <div class="institution-name">{{ $institutionName }}</div>
                <div class="title">{{ $documentTitle }}</div>
            </div>
        </div>

        <div class="section">
            <table class="meta-table">
                <tr>
                    <td><span class="label">Codigo</span>{{ $acta->codigo }}</td>
                    <td><span class="label">Tipo</span>{{ strtoupper((string) $acta->tipo) }}</td>
                    <td><span class="label">Fecha</span>{{ $acta->fecha?->format('d/m/Y') ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="label">Institucion origen</span>
                        @if ($origenMultiple)
                            Multiples instituciones ({{ $institucionesOrigenCount }})
                        @else
                            {{ $acta->institution?->nombre ?: '-' }}
                        @endif
                    </td>
                    @if ($isPrestamo)
                        <td><span class="label">Destinatario del prestamo</span>{{ $prestamoDestinatario['nombre'] ?: '-' }}</td>
                    @else
                        <td><span class="label">Institucion destino</span>{{ $acta->institucionDestino?->nombre ?: '-' }}</td>
                    @endif
                </tr>
            </table>
        </div>

        @if ($isPrestamo)
            <div class="section destinatario-prestamo">
                <div class="section-title">Destinatario del prestamo</div>
                <table class="event-table">
                    <tr>
                        <td><span class="label">Nombre y apellido</span>{{ $prestamoDestinatario['nombre'] ?: '-' }}</td>
                        <td><span class="label">DNI</span>{{ $prestamoDestinatario['dni'] ?: '-' }}</td>
                        <td><span class="label">Cargo</span>{{ $prestamoDestinatario['cargo'] ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="2"><span class="label">Dependencia</span>{{ $prestamoDestinatario['dependencia'] ?: '-' }}</td>
                        <td><span class="label">Destino institucional complementario</span>{{ $destinoInstitucionalHasData ? $destinoInstitucionalTexto : '-' }}</td>
                    </tr>
                </table>
                <div class="destinatario-note">Este bloque identifica al DESTINATARIO DEL PRESTAMO.</div>
            </div>
        @endif

        <div class="section">
            <div class="section-title">Detalle de equipamiento</div>
            <table class="detail-table">
                <colgroup>
                    <col class="detail-col-equipo">
                    <col class="detail-col-marca-modelo">
                    <col class="detail-col-serie">
                    <col class="detail-col-patrimonial">
                    <col class="detail-col-origen">
                    <col class="detail-col-destino">
                    <col class="detail-col-cantidad">
                </colgroup>
                <thead>
                <tr>
                    <th class="detail-col-equipo">Equipo</th>
                    <th class="detail-col-marca-modelo">Marca / Modelo</th>
                    <th class="detail-col-serie">Serie</th>
                    <th class="detail-col-patrimonial">Patrimonial</th>
                    <th class="detail-col-origen">Origen snapshot</th>
                    <th class="detail-col-destino">Destino</th>
                    <th class="detail-col-cantidad">Cant.</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($acta->equipos as $equipo)
                    @php
                        $payloadOrigen = data_get($acta->evento_payload, 'origenes_por_equipo.'.(string) $equipo->id, []);
                        $origenTexto = trim(implode(' / ', [
                            $equipo->pivot->institucion_origen_nombre
                                ?: data_get($payloadOrigen, 'institucion_nombre')
                                ?: '-',
                            $equipo->pivot->servicio_origen_nombre
                                ?: data_get($payloadOrigen, 'servicio_nombre')
                                ?: '-',
                            $equipo->pivot->oficina_origen_nombre
                                ?: data_get($payloadOrigen, 'oficina_nombre')
                                ?: '-',
                        ]));
                    @endphp
                    <tr>
                        <td class="detail-cell-equipo">{{ $equipo->tipoEquipo?->nombre ?? $equipo->tipo }}</td>
                        <td class="detail-cell-marca-modelo">
                            <span class="detail-primary">{{ $equipo->marca ?: '-' }}</span>
                            <span class="detail-secondary">{{ $equipo->modelo ?: '-' }}</span>
                        </td>
                        <td class="detail-cell-serie">{{ $equipo->numero_serie ?: '-' }}</td>
                        <td class="detail-cell-patrimonial">{{ $equipo->bien_patrimonial ?: '-' }}</td>
                        <td>{{ $origenTexto }}</td>
                        <td>{{ $destinoPrincipalTexto }}</td>
                        <td class="detail-cell-cantidad">{{ $equipo->pivot->cantidad }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Sin equipos asociados.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Datos del evento</div>
            <table class="event-table">
                <tr>
                    <td><span class="label">Receptor</span>{{ $acta->receptor_nombre ?: '-' }}</td>
                    <td><span class="label">DNI</span>{{ $acta->receptor_dni ?: '-' }}</td>
                    <td><span class="label">Cargo</span>{{ $acta->receptor_cargo ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="3"><span class="label">Dependencia</span>{{ $acta->receptor_dependencia ?: '-' }}</td>
                </tr>
                @if ($isPrestamo)
                    <tr>
                        <td colspan="3"><span class="label">Destinatario del prestamo</span>{{ $destinoPrincipalTexto }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="2"><span class="label">Servicio origen</span>{{ $origenMultiple ? 'Multiples (ver detalle)' : ($acta->servicioOrigen?->nombre ?: '-') }}</td>
                    <td><span class="label">Oficina origen</span>{{ $origenMultiple ? 'Multiples (ver detalle)' : ($acta->oficinaOrigen?->nombre ?: '-') }}</td>
                </tr>
                <tr>
                    <td colspan="2"><span class="label">Servicio destino</span>{{ $acta->servicioDestino?->nombre ?: '-' }}</td>
                    <td><span class="label">Oficina destino</span>{{ $acta->oficinaDestino?->nombre ?: '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Declaracion institucional</div>
            <div class="block-text">
                {{ $clausulaTexto }}
                <br><br>
                El responsable receptor declara recibir los bienes en el estado indicado, comprometiendose a su uso adecuado, conservacion y custodia, conforme a las normas institucionales vigentes de administracion de activos tecnologicos.
            </div>
        </div>

        <div class="section">
            <div class="section-title">Observaciones</div>
            <div>{{ $acta->observaciones ?: '-' }}</div>
        </div>

        <div class="section">
            <div class="section-title">Registro administrativo</div>
            <div class="block-text">
                El presente documento forma parte del Sistema Institucional de Gestion de Activos Informaticos, quedando registrado para fines de trazabilidad, control patrimonial y auditoria administrativa.
            </div>
        </div>

        <div class="section">
            <div class="section-title">Firmas</div>
            <table class="signatures">
                <tr>
                    <td>
                        <div class="line"></div>
                        Responsable que entrega
                    </td>
                    <td>
                        <div class="line"></div>
                        Responsable que recibe
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Validacion de ficha publica</div>
            @if (! empty($equipoQrSvg))
                <table class="qr-table">
                    <tr>
                        <td class="qr-code">{!! $equipoQrSvg !!}</td>
                        <td class="qr-text">
                            Escanee este codigo para ver la ficha del equipo.
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

        <div class="footer">
            Documento generado por el Sistema de Gestion de Activos Informaticos<br>
            Hospital Dr. Lucio Molas - Ministerio de Salud - Provincia de La Pampa
        </div>
    </div>
</div>
</body>
</html>

