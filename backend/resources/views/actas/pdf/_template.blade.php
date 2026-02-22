<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#1f2937;margin:0}
.page{padding:28px}
.header{border:1px solid #1f2937;padding:12px;margin-bottom:16px}
.title{font-size:16px;font-weight:700;text-transform:uppercase}
.meta{margin-top:6px;font-size:11px}
.table{width:100%;border-collapse:collapse;margin-top:14px}
.table th,.table td{border:1px solid #1f2937;padding:6px;vertical-align:top}
.table th{background:#f1f5f9;text-align:left}
.section{margin-top:14px;border:1px solid #1f2937;padding:10px}
.signatures{margin-top:28px;width:100%;border-collapse:collapse}
.signatures td{width:50%;padding-top:50px;text-align:center}
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="title">{{ strtoupper($acta->institution?->nombre ?? 'Institución sanitaria') }}</div>
        <div class="meta">Código de acta: <strong>{{ $acta->codigo }}</strong></div>
        <div class="meta">Tipo: {{ str_replace('_', ' ', ucfirst($acta->tipo)) }} | Fecha: {{ $acta->fecha?->format('d/m/Y') }}</div>
    </div>

    <table class="table">
        <thead><tr><th>Tipo equipo</th><th>Marca</th><th>Modelo</th><th>Serie</th><th>Bien patrimonial</th><th>Cantidad</th></tr></thead>
        <tbody>
        @foreach($acta->equipos as $equipo)
            <tr>
                <td>{{ $equipo->tipoEquipo?->nombre ?? $equipo->tipo }}</td>
                <td>{{ $equipo->marca }}</td>
                <td>{{ $equipo->modelo }}</td>
                <td>{{ $equipo->numero_serie }}</td>
                <td>{{ $equipo->bien_patrimonial }}</td>
                <td>{{ $equipo->pivot->cantidad }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="section">
        <strong>Cláusula legal:</strong> {{ $clausula }}
    </div>

    <table class="signatures">
        <tr>
            <td>_____________________________<br>Entrega / Responsable institucional</td>
            <td>_____________________________<br>Recepción / Responsable receptor</td>
        </tr>
    </table>
</div>
</body>
</html>
