<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoEmpleado extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'documento_empleados';

    protected $fillable = [
        'user_id',
        'tipo', // contrato, prorroga, otros
        'titulo',
        'ruta_archivo',
        'fecha_vencimiento',
        'comentarios',
        'created_by'
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
