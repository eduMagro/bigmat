<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f3f4f6; padding: 20px; margin: 0;">
    <div style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden;">

        {{-- Header con logo/marca --}}
        <div style="background: linear-gradient(135deg, #1e3a5f 0%, #111827 100%); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">
                {{ config('app.name') }}
            </h1>
            <p style="color: #9ca3af; margin: 8px 0 0 0; font-size: 14px;">
                Sistema de Gestion
            </p>
        </div>

        {{-- Contenido principal --}}
        <div style="padding: 40px 30px;">

            {{-- Logo de la empresa --}}
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="{{ url('imagenes/logoBigmat.png') }}" alt="{{ config('app.name') }}" style="max-width: 180px; height: auto;">
            </div>

            {{-- Saludo --}}
            <h2 style="color: #1f2937; margin: 0 0 20px 0; text-align: center; font-size: 22px;">
                Hola{{ $user->name ? ', ' . $user->name : '' }}
            </h2>

            {{-- Mensaje principal --}}
            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 25px 0; text-align: center;">
                Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.
                Haz clic en el siguiente boton para crear una nueva contraseña.
            </p>

            {{-- Boton de accion --}}
            <div style="text-align: center; margin: 35px 0;">
                <a href="{{ $url }}"
                    style="display: inline-block; padding: 14px 40px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);">
                    Restablecer contraseña
                </a>
            </div>

            {{-- Aviso de expiracion --}}
            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 4px; margin: 25px 0;">
                <p style="margin: 0; color: #92400e; font-size: 14px;">
                    <strong>Importante:</strong> Este enlace expirara en <strong>60 minutos</strong>.
                    Si no solicitaste restablecer tu contraseña, puedes ignorar este correo.
                </p>
            </div>

            {{-- Enlace alternativo --}}
            <div style="background-color: #f9fafb; padding: 15px; border-radius: 8px; margin-top: 25px;">
                <p style="color: #6b7280; font-size: 13px; margin: 0 0 10px 0;">
                    Si el boton no funciona, copia y pega este enlace en tu navegador:
                </p>
                <p style="color: #2563eb; font-size: 12px; word-break: break-all; margin: 0; background-color: #ffffff; padding: 10px; border-radius: 4px; border: 1px solid #e5e7eb;">
                    {{ $url }}
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div style="background-color: #f9fafb; padding: 25px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="color: #6b7280; font-size: 13px; margin: 0 0 5px 0;">
                Este es un correo automatico, por favor no respondas a este mensaje.
            </p>
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
            </p>
        </div>
    </div>

    {{-- Mensaje de seguridad --}}
    <div style="max-width: 600px; margin: 20px auto 0; text-align: center;">
        <p style="color: #9ca3af; font-size: 11px; margin: 0;">
            Si no solicitaste este cambio de contraseña, tu cuenta sigue segura.
            <br>Simplemente ignora este correo.
        </p>
    </div>
</body>

</html>
