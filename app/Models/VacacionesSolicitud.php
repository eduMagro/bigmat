<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class VacacionesSolicitud extends Model
{
    protected $table = 'solicitudes_vacaciones';

    protected $fillable = [
        'user_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'observaciones',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
