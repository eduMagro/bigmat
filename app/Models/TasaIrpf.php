<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TasaIrpf extends Model
{
    protected $table = 'tasas_irpf';
    protected $fillable = ['anio', 'base_desde', 'base_hasta', 'porcentaje'];
}
