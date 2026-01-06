<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seccion extends Model
{
    use SoftDeletes;
    protected $table = 'secciones';
    // 🔐 Campos que pueden asignarse en masa
    protected $fillable = [
        'nombre',
        'ruta',
        'icono',
        'mostrar_en_dashboard',
        'orden',
    ];
    protected $casts = [
        'mostrar_en_dashboard' => 'boolean',
    ];

    // ✅ Asegura que los timestamps estén activos
    public $timestamps = true;

    // 🔁 Relación con Departamentos (muchos a muchos)
    public function departamentos()
    {
        return $this->belongsToMany(Departamento::class, 'departamento_seccion');
    }


    public function permisosAcceso()
    {
        return $this->hasMany(PermisoAcceso::class);
    }
    // En Seccion.php
    public static function dashboardItems()
    {
        return Cache::rememberForever('dashboard_items', function () {
            return self::with('departamentos:id')
                ->select('id', 'nombre', 'ruta', 'icono', 'orden')
                ->where('mostrar_en_dashboard', true)
                ->orderBy('orden')
                ->get()
                ->map(fn($s) => [
                    'route' => $s->ruta,
                    'label' => $s->nombre,
                    'icon' => asset($s->icono ?? 'imagenes/iconos/default.png'),
                    'departamentos' => $s->departamentos->pluck('id')->toArray(),
                ]);
        });
    }
}
