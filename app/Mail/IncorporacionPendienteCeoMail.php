<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Incorporacion;
use Illuminate\Support\Collection;

class IncorporacionPendienteCeoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Incorporacion $incorporacion;
    public Collection $incorporacionesPendientes;
    public string $aprobadoPor;

    /**
     * @param Incorporacion $incorporacion La incorporación recién aprobada por RRHH
     * @param Collection $incorporacionesPendientes Todas las incorporaciones pendientes de aprobación CEO
     * @param string $aprobadoPor Nombre de quien aprobó en RRHH
     */
    public function __construct(Incorporacion $incorporacion, Collection $incorporacionesPendientes, string $aprobadoPor)
    {
        $this->incorporacion = $incorporacion;
        $this->incorporacionesPendientes = $incorporacionesPendientes;
        $this->aprobadoPor = $aprobadoPor;
    }

    public function build()
    {
        return $this->subject('Nueva incorporación pendiente de aprobación')
            ->view('emails.incorporaciones.pendiente-ceo');
    }
}
