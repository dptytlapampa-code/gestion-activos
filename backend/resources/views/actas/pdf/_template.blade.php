<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
.page { padding: 24px; }
.header { border: 1px solid #1f2937; padding: 10px; margin-bottom: 12px; }
.title { font-size: 15px; font-weight: 700; text-transform: uppercase; }
.meta { margin-top: 4px; font-size: 10px; }
.table { width: 100%; border-collapse: collapse; margin-top: 10px; }
.table th, .table td { border: 1px solid #1f2937; padding: 5px; vertical-align: top; }
.table th { background: #f1f5f9; text-align: left; }
.section { margin-top: 12px; border: 1px solid #1f2937; padding: 8px; }
.signatures { margin-top: 22px; width: 100%; border-collapse: collapse; }
.signatures td { width: 50%; padding-top: 40px; text-align: center; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="title">{{ $titulo }}</div>
        <div class="meta">Codigo: <strong>{{ $acta->codigo }}</strong></div>
        <div class="meta">Tipo: {{ strtoupper($acta->tipo) }} | Fecha: {{ $acta->fecha?->format('d/m/Y') }}</div>
        <div class="meta">Institucion origen: {{ $acta->institution?->nombre ?: '-' }}</div>
        <div class="meta">Institucion destino: {{ $acta->institucionDestino?->nombre ?: '-' }}</div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Equipo</th>
                <th>Marca / Modelo</th>
                <th>Serie</th>
                <th>Patrimonial</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
        @foreach($acta->equipos as $equipo)
            <tr>
                <td>{{ $equipo->tipoEquipo?->nombre ?? $equipo->tipo }}</td>
                <td>{{ $equipo->marca }} {{ $equipo->modelo }}</td>
                <td>{{ $equipo->numero_serie ?: '-' }}</td>
                <td>{{ $equipo->bien_patrimonial ?: '-' }}</td>
                <td>{{ $equipo->pivot->cantidad }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="section">
        <strong>Datos del evento</strong><br>
        Receptor: {{ $acta->receptor_nombre ?: '-' }} | DNI: {{ $acta->receptor_dni ?: '-' }} | Cargo: {{ $acta->receptor_cargo ?: '-' }}<br>
        Servicio origen: {{ $acta->servicioOrigen?->nombre ?: '-' }} | Oficina origen: {{ $acta->oficinaOrigen?->nombre ?: '-' }}<br>
        Servicio destino: {{ $acta->servicioDestino?->nombre ?: '-' }} | Oficina destino: {{ $acta->oficinaDestino?->nombre ?: '-' }}<br>
        Motivo de baja: {{ $acta->motivo_baja ?: '-' }}
    </div>

    <div class="section">
        <strong>Observaciones:</strong> {{ $acta->observaciones ?: '-' }}
    </div>

    <div class="section">
        <strong>Clausula:</strong> {{ $clausula }}
    </div>

    <table class="signatures">
        <tr>
            <td>_____________________________<br>Responsable que entrega</td>
            <td>_____________________________<br>Responsable que recibe</td>
        </tr>
    </table>
</div>
</body>
</html>
