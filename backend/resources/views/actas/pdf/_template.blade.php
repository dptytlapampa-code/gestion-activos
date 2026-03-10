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

    .title {
        text-align: center;
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 8px;
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
        padding: 4px 5px;
        vertical-align: top;
        font-size: 11px;
    }

    .detail-table th {
        background-color: #f3f4f6;
        text-align: left;
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
    @php($isAnulada = ($acta->status ?? \App\Models\Acta::STATUS_ACTIVA) === \App\Models\Acta::STATUS_ANULADA)

    @if ($isAnulada)
        <div class="watermark">ACTA ANULADA</div>
    @endif

    <div class="content">
        <div class="title">ACTA DE ENTREGA DE ACTIVOS INFORM&Aacute;TICOS</div>

        <div class="section">
            <table class="meta-table">
                <tr>
                    <td><span class="label">C&oacute;digo</span>{{ $acta->codigo }}</td>
                    <td><span class="label">Tipo</span>{{ strtoupper((string) $acta->tipo) }}</td>
                    <td><span class="label">Fecha</span>{{ $acta->fecha?->format('d/m/Y') ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2"><span class="label">Instituci&oacute;n origen</span>{{ $acta->institution?->nombre ?: '-' }}</td>
                    <td><span class="label">Instituci&oacute;n destino</span>{{ $acta->institucionDestino?->nombre ?: '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Detalle de equipamiento</div>
            <table class="detail-table">
                <thead>
                <tr>
                    <th style="width: 24%;">Equipo</th>
                    <th style="width: 25%;">Marca / Modelo</th>
                    <th style="width: 17%;">Serie</th>
                    <th style="width: 20%;">Patrimonial</th>
                    <th style="width: 14%;">Cantidad</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($acta->equipos as $equipo)
                    <tr>
                        <td>{{ $equipo->tipoEquipo?->nombre ?? $equipo->tipo }}</td>
                        <td>{{ trim(($equipo->marca ?: '-') . ' / ' . ($equipo->modelo ?: '-')) }}</td>
                        <td>{{ $equipo->numero_serie ?: '-' }}</td>
                        <td>{{ $equipo->bien_patrimonial ?: '-' }}</td>
                        <td>{{ $equipo->pivot->cantidad }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Sin equipos asociados.</td>
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
                    <td colspan="2"><span class="label">Servicio origen</span>{{ $acta->servicioOrigen?->nombre ?: '-' }}</td>
                    <td><span class="label">Oficina origen</span>{{ $acta->oficinaOrigen?->nombre ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2"><span class="label">Servicio destino</span>{{ $acta->servicioDestino?->nombre ?: '-' }}</td>
                    <td><span class="label">Oficina destino</span>{{ $acta->oficinaDestino?->nombre ?: '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Declaraci&oacute;n institucional</div>
            <div class="block-text">
                Mediante la presente se deja constancia de la entrega y recepci&oacute;n del equipamiento inform&aacute;tico detallado, perteneciente al patrimonio institucional, con el fin de ser destinado al funcionamiento operativo del &aacute;rea receptora.
                <br><br>
                El responsable receptor declara recibir los bienes en el estado indicado, comprometi&eacute;ndose a su uso adecuado, conservaci&oacute;n y custodia, conforme a las normas institucionales vigentes de administraci&oacute;n de activos tecnol&oacute;gicos.
            </div>
        </div>

        <div class="section">
            <div class="section-title">Observaciones</div>
            <div>{{ $acta->observaciones ?: '-' }}</div>
        </div>

        <div class="section">
            <div class="section-title">Registro administrativo</div>
            <div class="block-text">
                El presente documento forma parte del Sistema Institucional de Gesti&oacute;n de Activos Inform&aacute;ticos, quedando registrado para fines de trazabilidad, control patrimonial y auditor&iacute;a administrativa.
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

        <div class="footer">
            Documento generado por el Sistema de Gesti&oacute;n de Activos Inform&aacute;ticos<br>
            Hospital Dr. Lucio Molas - Ministerio de Salud - Provincia de La Pampa
        </div>
    </div>
</div>
</body>
</html>
