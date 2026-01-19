<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevisionFichajeSolicitud extends Model
{
    use HasFactory;

    protected $table = 'revision_fichaje_solicitudes';

    protected $fillable = [
        'user_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'observaciones',
        'resuelta_por',
        'resuelta_en',
        'motivo_denegacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'resuelta_en' => 'datetime',
    ];

    /**
     * Usuario que solicita la revision
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Programador que resolvio la solicitud
     */
    public function resueltaPor()
    {
        return $this->belongsTo(User::class, 'resuelta_por');
    }

    /**
     * Scope para solicitudes pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }
}
