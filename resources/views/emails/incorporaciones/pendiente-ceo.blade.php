<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nueva incorporación pendiente de aprobación</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        {{-- Header --}}
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="color: #2563eb; margin: 0;">Nueva Incorporación Pendiente</h2>
            <p style="color: #6b7280; margin-top: 5px;">Requiere su aprobación</p>
        </div>

        {{-- Información de la incorporación recién aprobada --}}
        <div style="background-color: #dbeafe; border-left: 4px solid #2563eb; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <h3 style="color: #1e40af; margin: 0 0 10px 0;">
                {{ $incorporacion->name }} {{ $incorporacion->primer_apellido }} {{ $incorporacion->segundo_apellido }}
            </h3>
            <p style="margin: 5px 0; color: #374151;">
                <strong>Empresa:</strong> {{ $incorporacion->empresa_nombre }}
            </p>
            @if($incorporacion->puesto)
            <p style="margin: 5px 0; color: #374151;">
                <strong>Puesto:</strong> {{ $incorporacion->puesto }}
            </p>
            @endif
            <p style="margin: 5px 0; color: #374151;">
                <strong>Aprobado por RRHH:</strong> {{ $aprobadoPor }}
            </p>
            <p style="margin: 15px 0 5px 0;">
                <a href="{{ route('incorporaciones.show', $incorporacion) }}"
                    style="display: inline-block; padding: 10px 20px; background-color: #2563eb; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    Ver incorporación
                </a>
            </p>
        </div>

        {{-- Lista de todas las incorporaciones pendientes --}}
        @if($incorporacionesPendientes->count() > 0)
        <div style="margin-top: 30px;">
            <h3 style="color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
                Incorporaciones pendientes de su aprobación ({{ $incorporacionesPendientes->count() }})
            </h3>

            <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="background-color: #f3f4f6;">
                        <th style="text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb;">Nombre</th>
                        <th style="text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb;">Empresa</th>
                        <th style="text-align: center; padding: 10px; border-bottom: 1px solid #e5e7eb;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($incorporacionesPendientes as $inc)
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;">
                            {{ $inc->name }} {{ $inc->primer_apellido }}
                            @if($inc->puesto)
                                <br><small style="color: #6b7280;">{{ $inc->puesto }}</small>
                            @endif
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;">
                            {{ $inc->empresa_nombre }}
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: center;">
                            <a href="{{ route('incorporaciones.show', $inc) }}"
                                style="color: #2563eb; text-decoration: none; font-weight: bold;">
                                Ver
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Footer --}}
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center;">
            <p style="color: #6b7280; font-size: 14px;">
                Este es un correo automático del sistema de gestión.
            </p>
            <p style="color: #9ca3af; font-size: 12px;">
                {{ config('app.name') }} - {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>
</body>

</html>
