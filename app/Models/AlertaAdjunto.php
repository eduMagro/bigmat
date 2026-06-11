<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaAdjunto extends Model
{
    protected $table = 'alerta_adjuntos';

    protected $fillable = [
        'alerta_id',
        'nombre_original',
        'ruta',
        'requiere_firma',
    ];

    protected $casts = [
        'requiere_firma' => 'boolean',
    ];

    public function alerta()
    {
        return $this->belongsTo(Alerta::class);
    }

    public function firmas()
    {
        return $this->hasMany(AlertaAdjuntoFirma::class);
    }
}
