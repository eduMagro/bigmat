<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermisoAcceso extends Model
{
    protected $table = 'permisos_acceso';

    protected $fillable = [
        'user_id',
        'departamento_id',
        'seccion_id',
        'puede_ver',
        'puede_editar',
        'puede_crear'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function seccion()
    {
        return $this->belongsTo(Seccion::class);
    }
}
