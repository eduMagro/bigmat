<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 11px; margin: 0; padding: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px 0; color: #1e3a8a; }
        .subtitle { font-size: 11px; color: #6b7280; margin: 0 0 16px 0; }
        .meta { margin-bottom: 16px; }
        .meta-row { margin-bottom: 3px; }
        .meta-label { font-weight: bold; color: #374151; }
        .resumen {
            background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px;
            padding: 8px 12px; margin-bottom: 16px; font-size: 11px;
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #1e40af; color: #ffffff; text-align: left;
            padding: 6px 8px; font-size: 10px; border: 1px solid #1e3a8a;
        }
        td {
            padding: 6px 8px; border: 1px solid #d1d5db; vertical-align: middle;
            font-size: 10px;
        }
        tr:nth-child(even) td { background: #f9fafb; }
        .estado-firmado { color: #16a34a; font-weight: bold; }
        .estado-pendiente { color: #b45309; font-weight: bold; }
        .firma-img { max-height: 40px; max-width: 140px; }
        .sin-firma { color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>
    <h1>Registro de firmas del documento</h1>
    <p class="subtitle">Bigmat</p>

    <div class="meta">
        <div class="meta-row"><span class="meta-label">Documento:</span> {{ $documento->nombre_original }}</div>
        <div class="meta-row"><span class="meta-label">Fecha de envío:</span> {{ optional($documento->created_at)->format('d/m/Y H:i') }}</div>
        <div class="meta-row"><span class="meta-label">Generado el:</span> {{ $generadoEl }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Trabajador</th>
                <th style="width: 90px;">Estado</th>
                <th style="width: 95px;">Fecha firma</th>
                <th style="width: 160px;">Firma</th>
            </tr>
        </thead>
        <tbody>
            @forelse($filas as $i => $fila)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $fila['nombre'] }}</td>
                    <td>
                        @if($fila['firmado'])
                            <span class="estado-firmado">Firmado</span>
                        @else
                            <span class="estado-pendiente">Pendiente</span>
                        @endif
                    </td>
                    <td>{{ $fila['fecha'] ?? '—' }}</td>
                    <td>
                        @if($fila['firma_base64'])
                            <img src="{{ $fila['firma_base64'] }}" class="firma-img" alt="Firma">
                        @else
                            <span class="sin-firma">Sin firma</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #9ca3af;">Este documento no tiene firmas registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
