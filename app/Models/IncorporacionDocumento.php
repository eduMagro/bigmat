<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncorporacionDocumento extends Model
{
    protected $table = 'incorporacion_documentos';

    protected $fillable = [
        'incorporacion_id',
        'tipo',
        'archivo',
        'notas',
        'completado',
        'completado_at',
        'subido_por',
    ];

    protected $casts = [
        'completado' => 'boolean',
        'completado_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Tipos de documentos post-incorporación
    const TIPOS = [
        'info_preventiva' => 'Información materia preventiva',
        'art_19' => 'Art. 19 específica del puesto',
        'aptitud_medica' => 'Certificado de aptitud médica',
        'entrega_epis' => 'Documento entrega EPIs',
        'contrato_trabajo' => 'Contrato de trabajo',
        'idc' => 'IDC',
        'huella_sepe' => 'Huella contrato (SEPE)',
        'ta2' => 'TA2 (Alta trabajador)',
    ];

    public function incorporacion()
    {
        return $this->belongsTo(Incorporacion::class);
    }

    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function getTipoNombreAttribute()
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getArchivoUrlAttribute()
    {
        return $this->archivo ? asset('storage/incorporaciones/documentos/' . $this->archivo) : null;
    }

    public function marcarCompletado()
    {
        $this->update([
            'completado' => true,
            'completado_at' => now(),
        ]);
    }
}
