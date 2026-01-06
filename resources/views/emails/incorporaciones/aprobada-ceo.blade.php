<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Incorporación aprobada por CEO</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        {{-- Header con icono de éxito --}}
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="width: 60px; height: 60px; background-color: #22c55e; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center;">
                <span style="color: white; font-size: 30px; line-height: 60px;">&#10003;</span>
            </div>
            <h2 style="color: #22c55e; margin: 0;">Incorporación Aprobada</h2>
            <p style="color: #6b7280; margin-top: 5px;">El CEO ha dado su aprobación</p>
        </div>

        {{-- Información del trabajador --}}
        <div style="background-color: #dcfce7; border-left: 4px solid #22c55e; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <h3 style="color: #166534; margin: 0 0 10px 0;">
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
            @if($incorporacion->dni)
            <p style="margin: 5px 0; color: #374151;">
                <strong>DNI:</strong> {{ $incorporacion->dni }}
            </p>
            @endif
        </div>

        {{-- Detalles de aprobación --}}
        <div style="background-color: #f9fafb; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <h4 style="color: #374151; margin: 0 0 10px 0;">Detalles de la aprobación</h4>
            <p style="margin: 5px 0; color: #6b7280;">
                <strong>Aprobado por:</strong> {{ $aprobadoPor }}
            </p>
            <p style="margin: 5px 0; color: #6b7280;">
                <strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}
            </p>
            @if($incorporacion->aprobado_rrhh_at)
            <p style="margin: 5px 0; color: #6b7280;">
                <strong>Aprobación RRHH:</strong> {{ $incorporacion->aprobado_rrhh_at->format('d/m/Y H:i') }}
                @if($incorporacion->aprobadorRrhh)
                    por {{ $incorporacion->aprobadorRrhh->nombre_completo }}
                @endif
            </p>
            @endif
        </div>

        {{-- Botón de acción --}}
        <div style="text-align: center; margin: 25px 0;">
            <a href="{{ route('incorporaciones.show', $incorporacion) }}"
                style="display: inline-block; padding: 12px 30px; background-color: #22c55e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Ver incorporación completa
            </a>
        </div>

        {{-- Nota informativa --}}
        <div style="background-color: #fef3c7; border: 1px solid #f59e0b; padding: 12px; border-radius: 4px; margin-top: 20px;">
            <p style="margin: 0; color: #92400e; font-size: 14px;">
                <strong>Siguiente paso:</strong> El trabajador ya puede ser incorporado a la empresa.
                Asegúrese de completar toda la documentación pendiente.
            </p>
        </div>

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
