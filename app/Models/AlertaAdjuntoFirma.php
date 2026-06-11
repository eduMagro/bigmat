<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaAdjuntoFirma extends Model
{
    protected $table = 'alerta_adjunto_firmas';

    protected $fillable = [
        'alerta_adjunto_id',
        'user_id',
        'firmado',
        'firmado_dia',
        'firma_ruta',
    ];

    protected $casts = [
        'firmado' => 'boolean',
        'firmado_dia' => 'datetime',
    ];

    public function adjunto()
    {
        return $this->belongsTo(AlertaAdjunto::class, 'alerta_adjunto_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
