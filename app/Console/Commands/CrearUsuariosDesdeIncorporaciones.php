<?php

namespace App\Console\Commands;

use App\Models\Incorporacion;
use App\Models\User;
use App\Models\Categoria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CrearUsuariosDesdeIncorporaciones extends Command
{
    protected $signature = 'incorporaciones:crear-usuarios';
    protected $description = 'Crear usuarios a partir de incorporaciones que tengan DNI pero no tengan usuario asignado';

    public function handle()
    {
        $incorporaciones = Incorporacion::whereNotNull('dni')
            ->whereNull('user_id')
            ->get();

        if ($incorporaciones->isEmpty()) {
            $this->info('No hay incorporaciones pendientes de crear usuario.');
            return 0;
        }

        $this->info("Encontradas {$incorporaciones->count()} incorporaciones para procesar.");

        $creados = 0;
        $vinculados = 0;
        $errores = 0;

        foreach ($incorporaciones as $incorporacion) {
            try {
                $dni = strtoupper($incorporacion->dni);

                // Buscar usuario existente (ignorando eliminados)
                $usuarioExistente = User::where('dni', $dni)->first();

                if ($usuarioExistente) {
                    // Vincular usuario existente
                    $incorporacion->update(['user_id' => $usuarioExistente->id]);
                    $this->line("  [VINCULADO] {$incorporacion->name} {$incorporacion->primer_apellido} -> Usuario #{$usuarioExistente->id}");
                    $vinculados++;
                } else {
                    // Crear nuevo usuario
                    $usuario = $this->crearUsuario($incorporacion);
                    $incorporacion->update(['user_id' => $usuario->id]);
                    $this->line("  [CREADO] {$usuario->nombre_completo} (DNI: {$dni}) -> Usuario #{$usuario->id}");
                    $creados++;
                }
            } catch (\Exception $e) {
                $this->error("  [ERROR] {$incorporacion->name}: {$e->getMessage()}");
                $errores++;
            }
        }

        $this->newLine();
        $this->info("Resumen:");
        $this->info("  - Usuarios creados: {$creados}");
        $this->info("  - Usuarios vinculados: {$vinculados}");
        if ($errores > 0) {
            $this->error("  - Errores: {$errores}");
        }

        return 0;
    }

    private function crearUsuario(Incorporacion $incorporacion): User
    {
        $dni = strtoupper($incorporacion->dni);
        $empresaId = $incorporacion->empresa_destino;
        $categoriaId = Categoria::first()?->id ?? 1;
        $password = preg_replace('/[^0-9]/', '', $dni);

        return User::create([
            'name' => ucwords(strtolower($incorporacion->name)),
            'primer_apellido' => ucwords(strtolower($incorporacion->primer_apellido ?? '')),
            'segundo_apellido' => $incorporacion->segundo_apellido ? ucwords(strtolower($incorporacion->segundo_apellido)) : null,
            'dni' => $dni,
            'email' => strtolower($incorporacion->email ?? $incorporacion->email_provisional ?? ''),
            'movil_personal' => $incorporacion->telefono ?? $incorporacion->telefono_provisional,
            'password' => Hash::make($password),
            'empresa_id' => $empresaId,
            'categoria_id' => $categoriaId,
            'estado' => 'activo',
        ]);
    }
}
