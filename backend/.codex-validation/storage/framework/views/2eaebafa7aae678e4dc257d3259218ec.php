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

    .page {
        position: relative;
    }

    .watermark {
        position: fixed;
        top: 42%;
        left: 5%;
        width: 90%;
        text-align: center;
        font-size: 70px;
        font-weight: 700;
        color: #ef4444;
        opacity: 0.2;
        transform: rotate(-32deg);
        z-index: 0;
    }

    .content {
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
        width: 14%;
    }

    .detail-col-patrimonial {
        width: 14%;
    }

    .detail-col-origen {
        width: 20%;
    }

    .detail-col-destino {
        width: 18%;
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
        word-break: break-all;
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
<div class="page">
    <?php
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
    ?>

    <?php if($isAnulada): ?>
        <div class="watermark">ACTA ANULADA</div>
    <?php endif; ?>

    <div class="content">
        <div class="document-header">
            <?php if(! empty($pdfHeaderLogoPath)): ?>
                <div class="document-top-logo">
                    <img src="<?php echo e($pdfHeaderLogoPath); ?>" alt="Logo institucional">
                </div>
            <?php endif; ?>
            <div class="document-title-wrap">
                <div class="institution-name"><?php echo e($institutionName); ?></div>
                <div class="title"><?php echo e($documentTitle); ?></div>
            </div>
        </div>

        <div class="section">
            <table class="meta-table">
                <tr>
                    <td><span class="label">Codigo</span><?php echo e($acta->codigo); ?></td>
                    <td><span class="label">Tipo</span><?php echo e(strtoupper((string) $acta->tipo)); ?></td>
                    <td><span class="label">Fecha</span><?php echo e($acta->fecha?->format('d/m/Y') ?: '-'); ?></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="label">Institucion origen</span>
                        <?php if($origenMultiple): ?>
                            Multiples instituciones (<?php echo e($institucionesOrigenCount); ?>)
                        <?php else: ?>
                            <?php echo e($acta->institution?->nombre ?: '-'); ?>

                        <?php endif; ?>
                    </td>
                    <?php if($isPrestamo): ?>
                        <td><span class="label">Destinatario del prestamo</span><?php echo e($prestamoDestinatario['nombre'] ?: '-'); ?></td>
                    <?php else: ?>
                        <td><span class="label">Institucion destino</span><?php echo e($acta->institucionDestino?->nombre ?: '-'); ?></td>
                    <?php endif; ?>
                </tr>
            </table>
        </div>

        <?php if($isPrestamo): ?>
            <div class="section destinatario-prestamo">
                <div class="section-title">Destinatario del prestamo</div>
                <table class="event-table">
                    <tr>
                        <td><span class="label">Nombre y apellido</span><?php echo e($prestamoDestinatario['nombre'] ?: '-'); ?></td>
                        <td><span class="label">DNI</span><?php echo e($prestamoDestinatario['dni'] ?: '-'); ?></td>
                        <td><span class="label">Cargo</span><?php echo e($prestamoDestinatario['cargo'] ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <td colspan="2"><span class="label">Dependencia</span><?php echo e($prestamoDestinatario['dependencia'] ?: '-'); ?></td>
                        <td><span class="label">Destino institucional complementario</span><?php echo e($destinoInstitucionalHasData ? $destinoInstitucionalTexto : '-'); ?></td>
                    </tr>
                </table>
                <div class="destinatario-note">Este bloque identifica al DESTINATARIO DEL PRESTAMO.</div>
            </div>
        <?php endif; ?>

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
                <?php $__empty_1 = true; $__currentLoopData = $acta->equipos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $equipo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
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
                    ?>
                    <tr>
                        <td class="detail-cell-equipo"><?php echo e($equipo->tipoEquipo?->nombre ?? $equipo->tipo); ?></td>
                        <td class="detail-cell-marca-modelo">
                            <span class="detail-primary"><?php echo e($equipo->marca ?: '-'); ?></span>
                            <span class="detail-secondary"><?php echo e($equipo->modelo ?: '-'); ?></span>
                        </td>
                        <td class="detail-cell-serie"><?php echo e($equipo->numero_serie ?: '-'); ?></td>
                        <td class="detail-cell-patrimonial"><?php echo e($equipo->bien_patrimonial ?: '-'); ?></td>
                        <td><?php echo e($origenTexto); ?></td>
                        <td><?php echo e($destinoPrincipalTexto); ?></td>
                        <td class="detail-cell-cantidad"><?php echo e($equipo->pivot->cantidad); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7">Sin equipos asociados.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Datos del evento</div>
            <table class="event-table">
                <tr>
                    <td><span class="label">Receptor</span><?php echo e($acta->receptor_nombre ?: '-'); ?></td>
                    <td><span class="label">DNI</span><?php echo e($acta->receptor_dni ?: '-'); ?></td>
                    <td><span class="label">Cargo</span><?php echo e($acta->receptor_cargo ?: '-'); ?></td>
                </tr>
                <tr>
                    <td colspan="3"><span class="label">Dependencia</span><?php echo e($acta->receptor_dependencia ?: '-'); ?></td>
                </tr>
                <?php if($isPrestamo): ?>
                    <tr>
                        <td colspan="3"><span class="label">Destinatario del prestamo</span><?php echo e($destinoPrincipalTexto); ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2"><span class="label">Servicio origen</span><?php echo e($origenMultiple ? 'Multiples (ver detalle)' : ($acta->servicioOrigen?->nombre ?: '-')); ?></td>
                    <td><span class="label">Oficina origen</span><?php echo e($origenMultiple ? 'Multiples (ver detalle)' : ($acta->oficinaOrigen?->nombre ?: '-')); ?></td>
                </tr>
                <tr>
                    <td colspan="2"><span class="label">Servicio destino</span><?php echo e($acta->servicioDestino?->nombre ?: '-'); ?></td>
                    <td><span class="label">Oficina destino</span><?php echo e($acta->oficinaDestino?->nombre ?: '-'); ?></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Declaracion institucional</div>
            <div class="block-text">
                <?php echo e($clausulaTexto); ?>

                <br><br>
                El responsable receptor declara recibir los bienes en el estado indicado, comprometiendose a su uso adecuado, conservacion y custodia, conforme a las normas institucionales vigentes de administracion de activos tecnologicos.
            </div>
        </div>

        <div class="section">
            <div class="section-title">Observaciones</div>
            <div><?php echo e($acta->observaciones ?: '-'); ?></div>
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
            <?php if(! empty($equipoQrSvg)): ?>
                <table class="qr-table">
                    <tr>
                        <td class="qr-code"><?php echo $equipoQrSvg; ?></td>
                        <td class="qr-text">
                            Escanee este codigo para ver la ficha del equipo.
                            <?php if(! empty($equipoPublicUrl)): ?>
                                <div class="qr-url"><?php echo e($equipoPublicUrl); ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            <?php else: ?>
                <div class="qr-text">No fue posible generar el QR para esta acta.</div>
            <?php endif; ?>
        </div>

        <div class="footer">
            Documento generado por el Sistema de Gestion de Activos Informaticos<br>
            Hospital Dr. Lucio Molas - Ministerio de Salud - Provincia de La Pampa
        </div>
    </div>
</div>
</body>
</html>

<?php /**PATH G:\gestion-activos\gestion-activos\backend\.codex-validation\resources\views/actas/pdf/_template.blade.php ENDPATH**/ ?>