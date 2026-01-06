<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class NominaEnviada extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $mesAnio;
    public $rutaArchivo;
    public $nombreArchivo;

    /**
     * Create a new message instance.
     *
     * @param string $userName Nombre del usuario
     * @param string $mesAnio Mes y año de la nómina (ej: "Enero 2025")
     * @param string $rutaArchivo Ruta completa del archivo en storage
     * @param string $nombreArchivo Nombre del archivo para adjuntar
     */
    public function __construct($userName, $mesAnio, $rutaArchivo, $nombreArchivo)
    {
        $this->userName = $userName;
        $this->mesAnio = $mesAnio;
        $this->rutaArchivo = $rutaArchivo;
        $this->nombreArchivo = $nombreArchivo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Cuerpo del email
        $bodyHtml = "<!DOCTYPE html>
<html lang=\"es\">
<head><meta charset=\"UTF-8\"><title>Nómina {$this->mesAnio}</title></head>
<body style=\"margin:0;padding:0;background:#ffffff;font-family:Segoe UI, Arial, sans-serif;color:#111827;\">
  <p style=\"margin:0 0 12px;font-size:14px;\">Hola, {$this->userName}.</p>
  <p style=\"margin:0 0 12px;font-size:14px;\">
    Te enviamos tu nómina correspondiente al mes de <strong>{$this->mesAnio}</strong>.
  </p>
  <p style=\"margin:0 0 12px;font-size:14px;\">
    La nómina se encuentra adjunta en formato PDF.
  </p>
  <p style=\"margin:12px 0 12px;font-size:13px;color:#dc2626;font-weight:500;\">
    ⚠️ Este documento es confidencial y de carácter personal. No debe ser compartido.
  </p>
  <p style=\"margin:0;font-size:12px;color:#6b7280;\">
    Si detectas algún error o tienes alguna consulta, contacta con el departamento de administración.
  </p>
  <hr style=\"border:none;border-top:1px solid #e5e7eb;margin:20px 0;\">
  <p style=\"margin:0;font-size:11px;color:#9ca3af;\">
    Este es un correo automático. Por favor, no respondas a este mensaje.<br>
    Departamento de Recursos Humanos
  </p>
</body>
</html>";

        $this->html($bodyHtml);

        // Adjuntar el archivo de nómina
        $mail = $this->subject('Tu nómina de ' . $this->mesAnio)
            ->attach($this->rutaArchivo, [
                'as' => $this->nombreArchivo,
                'mime' => 'application/pdf',
            ]);

        return $mail;
    }
}
