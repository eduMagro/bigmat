<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpiUsuario extends Model
{
    protected $table = 'epis_usuario';

    protected $fillable = [
        'user_id',
        'epi_id',
        'cantidad',
        'entregado_en',
        'devuelto_en',
        'notas',
    ];

    protected $casts = [
        'entregado_en' => 'datetime',
        'devuelto_en' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'epi_id');
    }
}

