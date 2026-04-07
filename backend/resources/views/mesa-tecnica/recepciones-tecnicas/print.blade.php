<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante {{ $recepcionTecnica->codigo }}</title>
    <style>
        :root {
            color-scheme: light;
            --line: #d7dfeb;
            --ink: #0f172a;
            --muted: #475569;
            --soft: #eef4fa;
            --brand: #0f3d68;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background: linear-gradient(180deg, #f8fafc 0%, #eef4fa 100%);
        }

        .page { min-height: 100vh; padding: 24px; }
        .toolbar { display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 18px; }
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

        .sheet {
            max-width: 980px;
            margin: 0 auto;
            display: grid;
            gap: 18px;
        }

        .copy {
            border: 1px solid var(--line);
            border-radius: 28px;
            background: white;
            overflow: hidden;
            box-shadow: 0 24px 52px -42px rgba(15, 23, 42, 0.42);
        }

        .copy-header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 16px;
            padding: 24px 24px 20px;
            border-bottom: 1px solid var(--line);
            background:
                radial-gradient(circle at top right, rgba(15, 61, 104, 0.14), transparent 28%),
                linear-gradient(180deg, rgba(238, 244, 250, 0.95) 0%, rgba(255, 255, 255, 1) 100%);
        }

        .eyebrow {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .title {
            margin: 8px 0 0;
            font-size: 26px;
            line-height: 1.15;
            font-weight: 800;
            color: var(--brand);
        }

        .subtitle {
            margin: 8px 0 0;
            font-size: 14px;
            color: var(--muted);
        }

        .code-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #0f172a;
            color: white;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.06em;
        }

        .copy-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 172px;
            gap: 20px;
            padding: 22px 24px 24px;
        }

        .section + .section { margin-top: 18px; }
        .section-title {
            margin: 0 0 10px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--soft);
            padding: 14px;
        }

        .field-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .field-value {
            margin-top: 6px;
            font-size: 14px;
            line-height: 1.45;
            white-space: pre-line;
        }

        .field-full { grid-column: 1 / -1; }

        .qr-panel {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 24px;
            background: white;
            padding: 18px 12px;
        }

        .qr-panel svg { display: block; width: 132px; height: 132px; }
        .qr-fallback {
            text-align: center;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.5;
            word-break: break-word;
        }

        .copy-footer {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            padding: 0 24px 24px;
        }

        .signature {
            border-top: 1px dashed #94a3b8;
            padding-top: 10px;
            font-size: 12px;
            color: var(--muted);
        }

        @media (max-width: 860px) {
            .page { padding: 16px; }
            .copy-header,
            .copy-body,
            .copy-footer {
                grid-template-columns: 1fr;
            }
            .grid { grid-template-columns: 1fr; }
        }

        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: white; }
            .page { padding: 0; }
            .toolbar { display: none; }
            .sheet { gap: 10mm; }
            .copy { box-shadow: none; break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="toolbar">
            <a href="{{ route('mesa-tecnica.recepciones-tecnicas.show', $recepcionTecnica) }}">Volver</a>
            <button type="button" onclick="window.print()">Imprimir</button>
        </div>

        <div class="sheet">
            @foreach (['Copia para quien entrega', 'Copia para mesa tecnica'] as $copyLabel)
                <section class="copy">
                    <header class="copy-header">
                        <div>
                            <p class="eyebrow">{{ system_config()->nombre_sistema }}</p>
                            <h1 class="title">Comprobante de ingreso tecnico</h1>
                            <p class="subtitle">{{ $copyLabel }}</p>
                            <p class="subtitle">Sector receptor: {{ $recepcionTecnica->institution?->nombre ?: 'Sin institucion' }} / {{ $recepcionTecnica->sector_receptor }}</p>
                        </div>

                        <div class="code-badge">{{ $recepcionTecnica->codigo }}</div>
                    </header>

                    <div class="copy-body">
                        <div>
                            <div class="section">
                                <h2 class="section-title">Datos del ingreso</h2>
                                <div class="grid">
                                    <div class="field">
                                        <div class="field-label">Fecha y hora</div>
                                        <div class="field-value">{{ $recepcionTecnica->ingresado_at?->format('d/m/Y H:i') ?: '-' }}</div>
                                    </div>
                                    <div class="field">
                                        <div class="field-label">Estado</div>
                                        <div class="field-value">{{ $recepcionTecnica->statusLabel() }}</div>
                                    </div>
                                    <div class="field field-full">
                                        <div class="field-label">Equipo</div>
                                        <div class="field-value">{{ $recepcionTecnica->equipmentReference() }}</div>
                                    </div>
                                    <div class="field">
                                        <div class="field-label">Serie</div>
                                        <div class="field-value">{{ $recepcionTecnica->numero_serie ?: '-' }}</div>
                                    </div>
                                    <div class="field">
                                        <div class="field-label">Bien patrimonial</div>
                                        <div class="field-value">{{ $recepcionTecnica->bien_patrimonial ?: '-' }}</div>
                                    </div>
                                    <div class="field field-full">
                                        <div class="field-label">Procedencia</div>
                                        <div class="field-value">{{ $recepcionTecnica->procedenciaResumen() }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="section">
                                <h2 class="section-title">Quien entrega</h2>
                                <div class="grid">
                                    <div class="field">
                                        <div class="field-label">Nombre y apellido</div>
                                        <div class="field-value">{{ $recepcionTecnica->persona_nombre }}</div>
                                    </div>
                                    <div class="field">
                                        <div class="field-label">Documento</div>
                                        <div class="field-value">{{ $recepcionTecnica->persona_documento ?: '-' }}</div>
                                    </div>
                                    <div class="field">
                                        <div class="field-label">Telefono</div>
                                        <div class="field-value">{{ $recepcionTecnica->persona_telefono ?: '-' }}</div>
                                    </div>
                                    <div class="field">
                                        <div class="field-label">Relacion con el equipo</div>
                                        <div class="field-value">{{ $recepcionTecnica->persona_relacion_equipo ?: '-' }}</div>
                                    </div>
                                    <div class="field field-full">
                                        <div class="field-label">Area / servicio / institucion</div>
                                        <div class="field-value">{{ collect([$recepcionTecnica->persona_area, $recepcionTecnica->persona_institucion])->filter()->implode(' / ') ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="section">
                                <h2 class="section-title">Problema reportado</h2>
                                <div class="grid">
                                    <div class="field field-full">
                                        <div class="field-label">Falla o motivo</div>
                                        <div class="field-value">{{ $recepcionTecnica->falla_motivo ?: '-' }}</div>
                                    </div>
                                    <div class="field field-full">
                                        <div class="field-label">Descripcion</div>
                                        <div class="field-value">{{ $recepcionTecnica->descripcion_falla ?: '-' }}</div>
                                    </div>
                                    <div class="field">
                                        <div class="field-label">Accesorios entregados</div>
                                        <div class="field-value">{{ $recepcionTecnica->accesorios_entregados ?: '-' }}</div>
                                    </div>
                                    <div class="field">
                                        <div class="field-label">Estado fisico visible</div>
                                        <div class="field-value">{{ $recepcionTecnica->estado_fisico_inicial ?: '-' }}</div>
                                    </div>
                                    <div class="field field-full">
                                        <div class="field-label">Observaciones</div>
                                        <div class="field-value">{{ $recepcionTecnica->observaciones_recepcion ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <aside class="qr-panel">
                            @if ($qrSvg)
                                {!! $qrSvg !!}
                            @else
                                <div class="qr-fallback">
                                    No fue posible generar el QR.<br>
                                    {{ $publicUrl }}
                                </div>
                            @endif

                            <div class="qr-fallback">
                                Seguimiento publico<br>
                                {{ $publicUrl }}
                            </div>
                        </aside>
                    </div>

                    <div class="copy-footer">
                        <div class="signature">
                            Firma y aclaracion de quien entrega
                        </div>
                        <div class="signature">
                            Firma y aclaracion de {{ $recepcionTecnica->sector_receptor }}
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</body>
</html>
