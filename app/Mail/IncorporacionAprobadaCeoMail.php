<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Incorporacion;

class IncorporacionAprobadaCeoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Incorporacion $incorporacion;
    public string $aprobadoPor;

    /**
     * @param Incorporacion $incorporacion La incorporación aprobada por el CEO
     * @param string $aprobadoPor Nombre del CEO que aprobó
     */
    public function __construct(Incorporacion $incorporacion, string $aprobadoPor)
    {
        $this->incorporacion = $incorporacion;
        $this->aprobadoPor = $aprobadoPor;
    }

    public function build()
    {
        $nombreTrabajador = trim($this->incorporacion->name . ' ' . $this->incorporacion->primer_apellido);

        return $this->subject("Incorporación aprobada: {$nombreTrabajador}")
            ->view('emails.incorporaciones.aprobada-ceo');
    }
}
