<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TasaSeguridadSocial extends Model
{
    protected $table = 'tasas_seguridad_social';
    protected $fillable = ['destinatario', 'tipo_aportacion', 'porcentaje', 'fecha_inicio', 'fecha_fin'];
}
