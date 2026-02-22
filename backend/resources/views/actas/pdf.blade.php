<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <title>{{ $acta->codigo }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111111;
            margin: 30px;
        }

        .titulo {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-datos td {
            border: 1px solid #999999;
            padding: 6px;
            vertical-align: top;
        }

        .tabla-equipos {
            margin-top: 14px;
        }

        .tabla-equipos th,
        .tabla-equipos td {
            border: 1px solid #999999;
            padding: 6px;
            font-size: 11px;
        }

        .tabla-equipos th {
            background: #eeeeee;
            text-align: left;
        }

        .observaciones {
            border: 1px solid #999999;
            padding: 8px;
            margin-top: 14px;
            min-height: 60px;
        }

        .firmas {
            margin-top: 40px;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            padding-top: 40px;
        }
    </style>
</head>
<body>
    <div class="titulo">ACTA {{ strtoupper($acta->tipo) }} - {{ $acta->codigo }}</div>

    <table class="tabla-datos">
        <tr>
            <td><strong>Institución</strong><br>{{ $acta->institution?->nombre ?? '-' }}</td>
            <td><strong>Fecha</strong><br>{{ $acta->fecha?->format('d/m/Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Receptor</strong><br>{{ $acta->receptor_nombre }}</td>
            <td><strong>DNI</strong><br>{{ $acta->receptor_dni ?: '-' }}</td>
        </tr>
        <tr>
            <td><strong>Cargo</strong><br>{{ $acta->receptor_cargo ?: '-' }}</td>
            <td><strong>Dependencia</strong><br>{{ $acta->receptor_dependencia ?: '-' }}</td>
        </tr>
    </table>

    <table class="tabla-equipos">
        <thead>
            <tr>
                <th>Tipo de equipo</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>N° Serie</th>
                <th>Bien patrimonial</th>
                <th>Cantidad</th>
                <th>Accesorios</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($acta->equipos as $equipo)
                <tr>
                    <td>{{ $equipo->tipoEquipo?->nombre ?? '-' }}</td>
                    <td>{{ $equipo->marca ?: '-' }}</td>
                    <td>{{ $equipo->modelo ?: '-' }}</td>
                    <td>{{ $equipo->numero_serie ?: '-' }}</td>
                    <td>{{ $equipo->bien_patrimonial ?: '-' }}</td>
                    <td>{{ $equipo->pivot->cantidad }}</td>
                    <td>{{ $equipo->pivot->accesorios ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No hay equipos asociados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="observaciones">
        <strong>Observaciones:</strong><br>
        {{ $acta->observaciones ?: '-' }}
    </div>

    <table class="firmas">
        <tr>
            <td>_____________________________<br>Entrega</td>
            <td>_____________________________<br>Recibe</td>
        </tr>
    </table>
</body>
</html>
