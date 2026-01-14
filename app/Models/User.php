<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\AsignacionTurno;
use App\Models\Epi;
use App\Models\EpiUsuario;
use App\Notifications\ResetPasswordNotification;


class User extends Authenticatable
{
    use SoftDeletes;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     * 
     * 
     */
    public $timestamps = true;

    protected $table = 'users';


    protected $fillable = [
        'name',
        'primer_apellido',
        'segundo_apellido',
        'email',
        'imagen',
        'movil_personal',
        'movil_empresa',
        'numero_corto',
        'empresa_id',
        'password',
        'dni',
        'rol',
        'categoria_id',
        'maquina_id',
        'turno',
        'turno_actual',
        'dias_vacaciones',
        'estado',
        'updated_by',
        'puede_usar_asistente',
        'puede_modificar_bd',
        'acepta_politica_privacidad',
        'acepta_politica_cookies',
        'fecha_aceptacion_politicas',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $appends = ['nombre_completo'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'puede_usar_asistente' => 'boolean',
        'puede_modificar_bd' => 'boolean',
        'acepta_politica_privacidad' => 'boolean',
        'acepta_politica_cookies' => 'boolean',
        'fecha_aceptacion_politicas' => 'date',
    ];

    /**
     * Enviar notificación de restablecimiento de contraseña en español
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function getRutaImagenAttribute()
    {
        return $this->imagen
            ? route('usuarios.imagen', $this->imagen)
            : null;
    }


    protected static function booted()
    {
        static::addGlobalScope('soloActivos', function ($query) {
            $query->where('estado', 'activo');
        });
    }
    public function alertasLeidas()
    {
        return $this->belongsToMany(Alerta::class, 'alertas_users')
            ->withPivot('leida_en')
            ->withTimestamps();
    }
    public function getNombreCompletoAttribute()
    {
        return trim("{$this->name} {$this->primer_apellido} {$this->segundo_apellido}");
    }

    public function getFechaIncorporacionEfectivaAttribute()
    {
        return $this->incorporacion?->fecha_incorporacion;
    }

    /**
     * Calcular días de vacaciones correspondientes al año actual
     * basado en la fecha de incorporación.
     */
    public function getVacacionesCorrespondientesAttribute()
    {
        // 1. Si tiene un manual override, usarlo
        if ($this->vacaciones_totales) {
            return $this->vacaciones_totales;
        }

        $diasPorAnio = 22; // Base estándar

        // 2. Determinar la fecha de incorporación efectiva
        $inicio = $this->fecha_incorporacion_efectiva;

        // Si no hay fecha de incorporación, asumimos año completo
        if (!$inicio) {
            return $diasPorAnio;
        }

        $now = now();
        $finAnio = $now->copy()->endOfYear();

        // 3. Si se incorporó en un año anterior, le corresponden todos
        if ($inicio->year < $now->year) {
            return $diasPorAnio;
        }

        // 4. Si se incorpora este año (o en el futuro de este año??), cálculo proporcional
        // Si la fecha es futura al fin de año actual, 0 días
        if ($inicio->gt($finAnio)) {
            return 0;
        }

        // Días restantes desde la incorporación hasta fin de año
        $diasTrabajados = $inicio->diffInDays($finAnio) + 1;
        $diasEnAnio = $now->isLeapYear() ? 366 : 365;

        $correspondientes = ($diasTrabajados / $diasEnAnio) * $diasPorAnio;

        // Redondear a enteros (o 0.5?) - Generalmente se redondea al entero más cercano o superior.
        // Haremos round estándar por ahora.
        return round($correspondientes);
    }

    // En app/Models/User.php

    public function compañeroDeTurno()
    {
        // Obtener la asignación de hoy del usuario autenticado
        $asignacion = $this->asignacionesTurnos()
            ->whereDate('fecha', now()->toDateString())
            ->first();

        if (!$asignacion) {
            return null;
        }

        // Buscar un compañero distinto con misma asignación
        return User::whereHas('asignacionesTurnos', function ($query) use ($asignacion) {
            $query->whereDate('fecha', now()->toDateString())
                ->where('maquina_id', $asignacion->maquina_id)
                ->where('turno_id', $asignacion->turno_id);
        })
            ->where('id', '!=', $this->id) // Excluirse a sí mismo
            ->first(); // Solo uno
    }

    public function isOnline()
    {
        $resultado = DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('last_activity', '>=', now()->subMinutes(1)->timestamp)
            ->exists();
        return $resultado;
    }

    /**
     * Relación con `AsignacionTurno` (Un usuario puede tener muchas asignaciones de turnos)
     */
    public function asignacionesTurnos()
    {
        return $this->hasMany(AsignacionTurno::class, 'user_id');
    }

    /**
     * Obtener el turno actual del usuario basado en la asignación más reciente.
     */
    public function turnoActual()
    {
        return $this->hasOne(AsignacionTurno::class, 'user_id')->latest('fecha')->with('turno');
    }
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
    public function convenio()
    {
        return $this->belongsTo(Convenio::class, 'categoria_id', 'categoria_id');
    }
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
    public function modelo145()
    {
        return $this->hasOne(Modelo145::class);
    }

    public function incorporacion()
    {
        return $this->hasOne(Incorporacion::class);
    }

    public function esOperario()
    {
        return $this->rol === 'operario';
    }

    public function esOficina()
    {
        return $this->rol === 'oficina' || $this->rol === 'admin';
    }

    /**
     * Verificar si el usuario ha aceptado todas las políticas
     */
    public function haAceptadoPoliticas(): bool
    {
        return $this->acepta_politica_privacidad && $this->acepta_politica_cookies;
    }

    public function documentosEmpleado()
    {
        return $this->hasMany(DocumentoEmpleado::class);
    }

    /* ============================================
       SCOPES PARA CONSULTAS DE OPERARIOS
       ============================================ */

    /**
     * Scope: Filtra solo operarios activos.
     * Uso: User::operarios()->get()
     */
    public function scopeOperarios($query)
    {
        return $query->where('rol', 'operario');
    }

    /**
     * Scope: Filtra por empresa.
     * Uso: User::operarios()->deEmpresa(1)->get()
     */
    public function scopeDeEmpresa($query, $empresaId)
    {
        if ($empresaId === null) {
            return $query->whereNull('empresa_id');
        }
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope: Filtra operarios de una máquina específica.
     * Uso: User::operarios()->deMaquina(5)->get()
     */
    public function scopeDeMaquina($query, $maquinaId)
    {
        return $query->where('maquina_id', $maquinaId);
    }

    public function departamentos()
    {
        return $this->belongsToMany(Departamento::class)
            ->withPivot('rol_departamental')
            ->withTimestamps();
    }

    public function esAdminDepartamento(): bool
    {
        return $this->departamentos()
            ->where(function ($q) {
                // Ejemplo 1 - por slug del departamento
                $q->where('nombre', 'administrador');

                // Ejemplo 2 - por nombre
                // $q->orWhere('nombre', 'Administrador');
    
                // Ejemplo 3 - por rol en el pivot
                // $q->orWherePivot('rol_departamental', 'administrador');
            })
            ->exists();
    }
    public function esProduccionDepartamento(): bool
    {
        return $this->departamentos()
            ->where(function ($q) {
                // Ejemplo 1 - por slug del departamento
                $q->where('nombre', 'produccion');

                // Ejemplo 2 - por nombre
                // $q->orWhere('nombre', 'Administrador');
    
                // Ejemplo 3 - por rol en el pivot
                // $q->orWherePivot('rol_departamental', 'administrador');
            })
            ->exists();
    }
    public function permisosAcceso()
    {
        return $this->hasMany(PermisoAcceso::class);
    }


    public function fcmTokens()
    {
        return $this->hasMany(UserFcmToken::class);
    }

    public function activeFcmTokens()
    {
        return $this->fcmTokens()->active();
    }

    public function epis()
    {
        return $this->belongsToMany(Epi::class, 'epis_usuario')
            ->withPivot(['id', 'cantidad', 'entregado_en', 'devuelto_en', 'notas'])
            ->withTimestamps();
    }

    public function episAsignaciones()
    {
        return $this->hasMany(EpiUsuario::class, 'user_id');
    }
}
