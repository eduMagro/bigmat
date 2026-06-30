<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor'];

    /**
     * Cache en memoria por request de las claves ya leidas.
     *
     * @var array<string, string|null>
     */
    protected static array $cache = [];

    /**
     * Obtiene el valor de una clave de configuracion.
     */
    public static function get(string $clave, ?string $default = null): ?string
    {
        if (array_key_exists($clave, static::$cache)) {
            return static::$cache[$clave] ?? $default;
        }

        $valor = static::where('clave', $clave)->value('valor');
        static::$cache[$clave] = $valor;

        return $valor ?? $default;
    }

    /**
     * Obtiene el valor de una clave como booleano.
     */
    public static function getBool(string $clave, bool $default = false): bool
    {
        $valor = static::get($clave);

        if ($valor === null) {
            return $default;
        }

        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Guarda (crea o actualiza) el valor de una clave.
     */
    public static function set(string $clave, ?string $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        static::$cache[$clave] = $valor;
    }

    /**
     * Guarda una clave booleana.
     */
    public static function setBool(string $clave, bool $valor): void
    {
        static::set($clave, $valor ? '1' : '0');
    }
}
