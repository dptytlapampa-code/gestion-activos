<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $acta->codigo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header, .footer { width: 100%; }
        .header td { vertical-align: top; }
        .logo { width: 110px; height: 60px; border: 1px dashed #999; text-align: center; font-size: 10px; color: #666; }
        .title { text-align: center; margin: 20px 0; font-size: 15px; font-weight: bold; }
        .meta td { padding: 4px 0; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.items th, table.items td { border: 1px solid #ddd; padding: 6px; font-size: 11px; }
        table.items th { background: #f3f4f6; }
        .footer { margin-top: 24px; font-size: 11px; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td>
            <strong>{{ $acta->institution?->nombre ?? 'Institución no especificada' }}</strong><br>
            Sistema de Gestión de Activos<br>
            Inventario Salud
        </td>
        <td style="text-align: right;">
            <div class="logo">Logo institucional</div>
        </td>
    </tr>
</table>

<div class="title">ACTA {{ strtoupper($acta->tipo) }} - {{ $acta->codigo }}</div>

<table class="meta">
    <tr><td><strong>Fecha:</strong> {{ $acta->fecha?->format('d/m/Y') }}</td></tr>
    <tr><td><strong>Receptor:</strong> {{ $acta->receptor_nombre }}</td></tr>
    <tr><td><strong>DNI:</strong> {{ $acta->receptor_dni ?: '-' }}</td></tr>
    <tr><td><strong>Cargo:</strong> {{ $acta->receptor_cargo ?: '-' }}</td></tr>
    <tr><td><strong>Dependencia:</strong> {{ $acta->receptor_dependencia ?: '-' }}</td></tr>
</table>

<table class="items">
    <thead>
    <tr>
        <th>Tipo equipo</th>
        <th>Marca</th>
        <th>Modelo</th>
        <th>Nro. serie</th>
        <th>Bien patrimonial</th>
        <th>Cantidad</th>
        <th>Accesorios</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($acta->equipos as $equipo)
        <tr>
            <td>{{ $equipo->tipo }}</td>
            <td>{{ $equipo->marca }}</td>
            <td>{{ $equipo->modelo }}</td>
            <td>{{ $equipo->numero_serie }}</td>
            <td>{{ $equipo->bien_patrimonial }}</td>
            <td>{{ $equipo->pivot->cantidad }}</td>
            <td>{{ $equipo->pivot->accesorios ?: '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    <p><strong>Observaciones:</strong> {{ $acta->observaciones ?: '-' }}</p>
    <p>Generado por: {{ $acta->creator?->name ?? 'Sistema' }}</p>
</div>
</body>
</html>
