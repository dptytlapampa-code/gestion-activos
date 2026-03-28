<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiqueta {{ $equipo->codigo_interno ?: 'Equipo' }}</title>
    <style>
        :root {
            color-scheme: light;
            --line: #d8e1eb;
            --ink: #0f172a;
            --muted: #475569;
            --soft: #eef4fa;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top right, rgba(15, 23, 42, 0.08), transparent 30%),
                linear-gradient(180deg, #f8fafc 0%, #eef4fa 100%);
        }

        .page { min-height: 100vh; padding: 24px; }
        .toolbar { display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 20px; }
        .toolbar a, .toolbar button {
            border: 1px solid var(--line);
            background: white;
            color: var(--ink);
            border-radius: 14px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .label-sheet {
            margin: 0 auto;
            max-width: 960px;
            border: 1px dashed var(--line);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.9);
            padding: 28px;
            box-shadow: 0 30px 60px -44px rgba(15, 23, 42, 0.45);
        }

        .label-card {
            width: 100%;
            max-width: 430px;
            min-height: 270px;
            border: 1px solid var(--line);
            border-radius: 22px;
            background: white;
            overflow: hidden;
        }

        .label-card-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(238, 244, 250, 0.9) 0%, rgba(255, 255, 255, 1) 100%);
        }

        .eyebrow {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .title { margin: 8px 0 0; font-size: 24px; line-height: 1.2; font-weight: 700; }
        .subtitle { margin: 6px 0 0; font-size: 14px; color: var(--muted); }

        .label-card-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 144px;
            gap: 18px;
            padding: 20px;
        }

        .code-block {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--soft);
            padding: 16px;
        }

        .code-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .code-value {
            margin-top: 10px;
            font-size: 26px;
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: 0.04em;
            word-break: break-word;
        }

        .details { margin-top: 16px; display: grid; gap: 10px; }
        .details-row { border-top: 1px solid var(--line); padding-top: 10px; }
        .details-row:first-child { border-top: none; padding-top: 0; }

        .details-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .details-value { margin-top: 4px; font-size: 14px; line-height: 1.4; }

        .qr-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: white;
            padding: 12px;
            min-height: 144px;
        }

        .qr-panel svg { display: block; width: 120px; height: 120px; }
        .qr-fallback { text-align: center; font-size: 12px; color: var(--muted); line-height: 1.5; }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 0 20px 20px;
            font-size: 12px;
            color: var(--muted);
        }

        .sheet-caption { margin-top: 16px; font-size: 13px; color: var(--muted); }

        @media (max-width: 720px) {
            .page { padding: 16px; }
            .label-sheet { padding: 18px; }
            .label-card-body { grid-template-columns: 1fr; }
            .qr-panel { max-width: 160px; }
        }

        @media print {
            @page { size: A4 portrait; margin: 12mm; }
            body { background: white; }
            .page { padding: 0; }
            .toolbar, .sheet-caption { display: none; }
            .label-sheet {
                border: none;
                border-radius: 0;
                box-shadow: none;
                background: white;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="toolbar">
            <a href="{{ route('mesa-tecnica.index') }}">Volver a Mesa Tecnica</a>
            <button type="button" onclick="window.print()">Imprimir</button>
        </div>

        <div class="label-sheet">
            <div class="label-card">
                <div class="label-card-header">
                    <p class="eyebrow">{{ system_config()->nombre_sistema }}</p>
                    <h1 class="title">{{ $equipo->tipoEquipo?->nombre ?? $equipo->tipo ?? 'Equipo institucional' }}</h1>
                    <p class="subtitle">{{ $equipo->marca ?: 'Sin marca' }} {{ $equipo->modelo ?: '' }}</p>
                </div>

                <div class="label-card-body">
                    <div>
                        <div class="code-block">
                            <div class="code-label">Codigo interno</div>
                            <div class="code-value">{{ $equipo->codigo_interno ?: 'SIN CODIGO' }}</div>
                        </div>

                        <div class="details">
                            <div class="details-row">
                                <div class="details-label">Serie</div>
                                <div class="details-value">{{ $equipo->numero_serie ?: '-' }}</div>
                            </div>
                            <div class="details-row">
                                <div class="details-label">Patrimonial</div>
                                <div class="details-value">{{ $equipo->bien_patrimonial ?: '-' }}</div>
                            </div>
                            <div class="details-row">
                                <div class="details-label">Institucion / ubicacion</div>
                                <div class="details-value">{{ $location ?: 'Sin ubicacion visible' }}</div>
                            </div>
                            <div class="details-row">
                                <div class="details-label">Estado</div>
                                <div class="details-value">{{ $estadoLabel }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="qr-panel">
                        @if ($qrSvg)
                            {!! $qrSvg !!}
                        @else
                            <div class="qr-fallback">
                                No fue posible generar el QR.<br>
                                @if ($publicUrl)
                                    {{ $publicUrl }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="footer">
                    <span>Generada: {{ $generatedAt }}</span>
                    <span>Uso institucional</span>
                </div>
            </div>

            <p class="sheet-caption">
                Esta vista esta preparada para reimpresion en hoja comun. El QR dirige a la ficha publica y estable del equipo.
            </p>
        </div>
    </div>
</body>
</html>
