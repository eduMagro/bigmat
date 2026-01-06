<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alerta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alertas'; // Nombre de la tabla

    protected $fillable = [
        'user_id_1',
        'user_id_2',
        'destino',
        'destinatario',
        'destinatario_id',
        'mensaje',
        'tipo',
        'parent_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'leida' => 'boolean', // Para que Laravel lo maneje como booleano
        'completada' => 'boolean', // Para que Laravel lo maneje como booleano
    ];

    /**
     * Relación con el usuario que genera la alerta.
     */
    public function usuario1()
    {
        return $this->belongsTo(User::class, 'user_id_1');
    }

    /**
     * Relación con el usuario que recibe la alerta.
     */
    public function usuario2()
    {
        return $this->belongsTo(User::class, 'user_id_2');
    }
    public function destinatarioUser()
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }
    public function leidas()
    {
        return $this->hasMany(AlertaLeida::class, 'alerta_id');
    }

    /**
     * Relación con el mensaje padre (para hilos de conversación)
     */
    public function padre()
    {
        return $this->belongsTo(Alerta::class, 'parent_id');
    }

    /**
     * Relación con las respuestas (para hilos de conversación)
     */
    public function respuestas()
    {
        return $this->hasMany(Alerta::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Obtener el mensaje raíz de un hilo
     */
    public function mensajeRaiz()
    {
        if ($this->parent_id) {
            return $this->padre->mensajeRaiz();
        }
        return $this;
    }

    /**
     * Verificar si es un mensaje raíz (no tiene padre)
     */
    public function esMensajeRaiz()
    {
        return is_null($this->parent_id);
    }

    /**
     * Obtener todas las respuestas del hilo (recursivo)
     */
    public function todasLasRespuestas()
    {
        return $this->respuestas()->with('respuestas')->get();
    }

    /**
     * Scope para obtener solo las alertas no leídas.
     */

    /**
     * Accessor para obtener el nombre del emisor.
     * Si el emisor tiene rol "oficina", muestra "Sistema".
     */
    public function getNombreEmisorAttribute(): string
    {
        $emisor = $this->usuario1;

        if (!$emisor) {
            return 'Sistema';
        }

        if ($emisor->rol === 'oficina') {
            return 'Sistema';
        }

        return $emisor->nombre_completo ?? $emisor->name ?? 'Usuario';
    }
}
