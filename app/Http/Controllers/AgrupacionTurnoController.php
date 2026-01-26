<?php

namespace App\Http\Controllers;

use App\Models\AgrupacionTurno;
use App\Models\AgrupacionTurnoDia;
use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgrupacionTurnoController extends Controller
{
    /**
     * Lista todas las agrupaciones de turnos
     */
    public function index()
    {
        $agrupaciones = AgrupacionTurno::ordenadas()
            ->withCount('usuarios')
            ->with(['dias.turno'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $agrupaciones
        ]);
    }

    /**
     * Crear una nueva agrupación de turnos
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'dias' => 'required|array',
            'dias.*.dia_semana' => 'required|integer|between:0,6',
            'dias.*.turno_id' => 'nullable|exists:turnos,id',
        ]);

        try {
            DB::beginTransaction();

            $agrupacion = AgrupacionTurno::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'activo' => $request->activo ?? true,
                'orden' => AgrupacionTurno::max('orden') + 1,
            ]);

            // Crear los días
            foreach ($request->dias as $dia) {
                AgrupacionTurnoDia::create([
                    'agrupacion_turno_id' => $agrupacion->id,
                    'dia_semana' => $dia['dia_semana'],
                    'turno_id' => $dia['turno_id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Plantilla de turno creada correctamente',
                'data' => $agrupacion->load('dias.turno')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la plantilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una agrupación específica
     */
    public function show(AgrupacionTurno $agrupacionesTurno)
    {
        $agrupacionesTurno->load(['dias.turno', 'usuarios']);
        $agrupacionesTurno->loadCount('usuarios');

        return response()->json([
            'success' => true,
            'data' => $agrupacionesTurno
        ]);
    }

    /**
     * Actualizar una agrupación de turnos
     */
    public function update(Request $request, AgrupacionTurno $agrupacionesTurno)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'dias' => 'required|array',
            'dias.*.dia_semana' => 'required|integer|between:0,6',
            'dias.*.turno_id' => 'nullable|exists:turnos,id',
        ]);

        try {
            DB::beginTransaction();

            $agrupacionesTurno->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'activo' => $request->activo ?? true,
            ]);

            // Eliminar días existentes y recrear
            $agrupacionesTurno->dias()->delete();

            foreach ($request->dias as $dia) {
                AgrupacionTurnoDia::create([
                    'agrupacion_turno_id' => $agrupacionesTurno->id,
                    'dia_semana' => $dia['dia_semana'],
                    'turno_id' => $dia['turno_id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Plantilla de turno actualizada correctamente',
                'data' => $agrupacionesTurno->load('dias.turno')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la plantilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una agrupación de turnos (solo si no tiene usuarios)
     */
    public function destroy(AgrupacionTurno $agrupacionesTurno)
    {
        // Verificar si tiene usuarios asignados
        if ($agrupacionesTurno->usuarios()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: hay empleados con esta plantilla asignada'
            ], 422);
        }

        try {
            $agrupacionesTurno->delete();

            return response()->json([
                'success' => true,
                'message' => 'Plantilla de turno eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la plantilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/desactivar una agrupación
     */
    public function toggleActivo(AgrupacionTurno $agrupacionesTurno)
    {
        $agrupacionesTurno->update([
            'activo' => !$agrupacionesTurno->activo
        ]);

        return response()->json([
            'success' => true,
            'message' => $agrupacionesTurno->activo ? 'Plantilla activada' : 'Plantilla desactivada',
            'data' => $agrupacionesTurno
        ]);
    }

    /**
     * Asignar una agrupación a un usuario
     */
    public function asignarUsuario(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'agrupacion_turno_id' => 'nullable|exists:agrupaciones_turnos,id',
        ]);

        $user = \App\Models\User::find($request->user_id);
        $user->update([
            'agrupacion_turno_id' => $request->agrupacion_turno_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla asignada correctamente al empleado'
        ]);
    }
}
