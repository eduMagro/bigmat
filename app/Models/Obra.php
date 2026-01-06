<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Obra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'obras';

    protected $fillable = [
        'obra',
        'cod_obra',
        'cliente_id',
        'ciudad',
        'direccion',
        'completada',
        'latitud',
        'longitud',
        'distancia',
        'ancho_m',
        'largo_m',
        'estado',
        'tipo'
    ];

    public function getEsAlmacenAttribute(): bool
    {
        return preg_match('/almac.*n/i', $this->obra ?? '') > 0;
    }

    public function getEsNaveAAttribute(): bool
    {
        return stripos($this->obra ?? '', 'nave a') !== false;
    }

    public function getEsNaveBAttribute(): bool
    {
        return stripos($this->obra ?? '', 'nave b') !== false;
    }
}
