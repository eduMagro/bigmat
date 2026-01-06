<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Incorporacion;

class IncorporacionCompletada extends Mailable
{
    use Queueable, SerializesModels;

    public $incorporacion;

    public function __construct(Incorporacion $incorporacion)
    {
        $this->incorporacion = $incorporacion;
    }

    public function build()
    {
        $nombreCompleto = trim(
            $this->incorporacion->name . ' ' .
            $this->incorporacion->primer_apellido . ' ' .
            ($this->incorporacion->segundo_apellido ?? '')
        );

        $empresa = $this->incorporacion->empresa_destino === Incorporacion::EMPRESA_HPR
            ? 'HPR Servicios'
            : 'Hierros Paco Reyes';

        $fechaCompletado = $this->incorporacion->datos_completados_at
            ->setTimezone(new \DateTimeZone('Europe/Madrid'))
            ->format('d/m/Y H:i');

        $urlDetalle = route('incorporaciones.show', $this->incorporacion);

        $bodyHtml = "<!DOCTYPE html>
<html lang=\"es\">
<head><meta charset=\"UTF-8\"><title>Nueva incorporación completada</title></head>
<body style=\"margin:0;padding:0;background:#f3f4f6;font-family:Segoe UI, Arial, sans-serif;color:#111827;\">
  <div style=\"max-width:600px;margin:20px auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.1);\">
    <div style=\"background:#1e40af;padding:20px;text-align:center;\">
      <h1 style=\"margin:0;color:#ffffff;font-size:20px;\">Nueva Incorporacion Completada</h1>
    </div>
    <div style=\"padding:24px;\">
      <p style=\"margin:0 0 16px;font-size:15px;\">Se ha recibido la documentacion de un nuevo candidato:</p>

      <table style=\"width:100%;border-collapse:collapse;margin-bottom:20px;\">
        <tr>
          <td style=\"padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:600;width:40%;\">Nombre:</td>
          <td style=\"padding:8px 0;border-bottom:1px solid #e5e7eb;\">{$nombreCompleto}</td>
        </tr>
        <tr>
          <td style=\"padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:600;\">DNI:</td>
          <td style=\"padding:8px 0;border-bottom:1px solid #e5e7eb;\">{$this->incorporacion->dni}</td>
        </tr>
        <tr>
          <td style=\"padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:600;\">Email:</td>
          <td style=\"padding:8px 0;border-bottom:1px solid #e5e7eb;\">{$this->incorporacion->email}</td>
        </tr>
        <tr>
          <td style=\"padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:600;\">Telefono:</td>
          <td style=\"padding:8px 0;border-bottom:1px solid #e5e7eb;\">{$this->incorporacion->telefono}</td>
        </tr>
        <tr>
          <td style=\"padding:8px 0;border-bottom:1px solid #e5e7eb;font-weight:600;\">Empresa destino:</td>
          <td style=\"padding:8px 0;border-bottom:1px solid #e5e7eb;\">{$empresa}</td>
        </tr>
        <tr>
          <td style=\"padding:8px 0;font-weight:600;\">Fecha completado:</td>
          <td style=\"padding:8px 0;\">{$fechaCompletado}</td>
        </tr>
      </table>

      <div style=\"text-align:center;margin-top:24px;\">
        <a href=\"{$urlDetalle}\" style=\"display:inline-block;background:#1e40af;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;font-weight:600;\">Ver detalles de la incorporacion</a>
      </div>
    </div>
    <div style=\"background:#f9fafb;padding:16px;text-align:center;border-top:1px solid #e5e7eb;\">
      <p style=\"margin:0;font-size:12px;color:#6b7280;\">Recursos Humanos - Hierros Paco Reyes</p>
    </div>
  </div>
</body>
</html>";

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Nueva incorporacion completada: ' . $nombreCompleto)
            ->html($bodyHtml);
    }
}
