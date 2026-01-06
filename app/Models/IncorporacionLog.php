<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncorporacionLog extends Model
{
    protected $table = 'incorporacion_logs';

    public $timestamps = false;

    protected $fillable = [
        'incorporacion_id',
        'accion',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
        'ip',
        'user_agent',
        'user_id',
        'created_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->created_at) {
                $model->created_at = now();
            }
        });
    }

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'created_at' => 'datetime',
    ];

    // Acciones comunes
    const ACCION_CREADA = 'incorporacion_creada';
    const ACCION_ENLACE_ENVIADO = 'enlace_enviado';
    const ACCION_DATOS_COMPLETADOS = 'datos_completados';
    const ACCION_DOCUMENTO_SUBIDO = 'documento_subido';
    const ACCION_ESTADO_CAMBIADO = 'estado_cambiado';
    const ACCION_CANCELADA = 'cancelada';

    public function incorporacion()
    {
        return $this->belongsTo(Incorporacion::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAccionTextoAttribute()
    {
        return match($this->accion) {
            self::ACCION_CREADA => 'Incorporación creada',
            self::ACCION_ENLACE_ENVIADO => 'Enlace enviado',
            self::ACCION_DATOS_COMPLETADOS => 'Datos completados por el candidato',
            self::ACCION_DOCUMENTO_SUBIDO => 'Documento subido',
            self::ACCION_ESTADO_CAMBIADO => 'Estado cambiado',
            self::ACCION_CANCELADA => 'Incorporación cancelada',
            default => $this->accion,
        };
    }
}
