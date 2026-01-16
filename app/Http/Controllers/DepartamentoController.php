<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Seccion;
use App\Models\User;
use App\Models\PermisoAcceso;
use App\Services\SeccionAutoDetectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DepartamentoController extends Controller
{
    protected SeccionAutoDetectService $autoDetectService;

    public function __construct(SeccionAutoDetectService $autoDetectService)
    {
        $this->autoDetectService = $autoDetectService;
    }

    public function index()
    {
        $departamentos = Departamento::with(['usuarios', 'secciones.permisosAcceso'])->get();
        $usuariosOficina = User::where('rol', 'oficina')->orderBy('name')->get();
        $todasLasSecciones = Seccion::with('departamentos')->orderBy('orden')->get();

        // Obtener el departamento "Operarios" para la configuración del dashboard
        $departamentoOperarios = Departamento::where('nombre', 'Operarios')->with('secciones')->first();

        // Secciones del dashboard ordenadas
        $seccionesDashboard = Seccion::where('mostrar_en_dashboard', true)->orderBy('orden')->get();

        // Auto-detección de secciones
        $seccionesComparacion = $this->autoDetectService->compararConSecciones();
        $seccionesEstadisticas = $this->autoDetectService->obtenerEstadisticas();

        return view('departamentos.index', compact(
            'departamentos',
            'usuariosOficina',
            'todasLasSecciones',
            'departamentoOperarios',
            'seccionesDashboard',
            'seccionesComparacion',
            'seccionesEstadisticas'
        ));
    }

    public function asignarUsuarios(Request $request, Departamento $departamento)
    {
        $request->validate([
            'usuarios' => 'array',
            'usuarios.*' => 'exists:users,id',
        ]);

        // Si no hay usuarios marcados, se debe pasar un array vacío para eliminar todos
        $usuariosSeleccionados = collect($request->usuarios ?? []);

        // Prepara array con rol 'miembro' para todos los seleccionados
        $usuariosConRoles = $usuariosSeleccionados->mapWithKeys(function ($id) {
            return [$id => ['rol_departamental' => 'miembro']];
        });

        // 🔹 Obtener IDs de usuarios ANTES de sincronizar
        $idsAnteriores = $departamento->usuarios()->pluck('users.id')->toArray();

        // 🔹 Sincronizar la relación
        $departamento->usuarios()->sync($usuariosConRoles);

        // 🔹 Calcular usuarios eliminados
        $idsActuales = $usuariosSeleccionados->toArray();
        $usuariosQuitados = array_diff($idsAnteriores, $idsActuales);

        // 🔹 Eliminar sus permisos en la tabla permisos_acceso
        if (!empty($usuariosQuitados)) {
            PermisoAcceso::where('departamento_id', $departamento->id)
                ->whereIn('user_id', $usuariosQuitados)
                ->whereIn('seccion_id', $departamento->secciones()->pluck('secciones.id'))
                ->delete();
        }
        return redirect()->back()->with('success', 'Asignación actualizada correctamente.');
    }
    // DepartamentoController.php
    public function asignarSecciones(Request $request, Departamento $departamento)
    {
        // 🔸 IDs de secciones actuales
        $seccionesAnteriores = $departamento->secciones()->pluck('secciones.id')->toArray();

        // 🔸 IDs de secciones nuevas
        $nuevasSecciones = $request->input('secciones', []);

        // 🔸 Secciones que han sido eliminadas
        $seccionesEliminadas = array_diff($seccionesAnteriores, $nuevasSecciones);

        // 🔸 Eliminar los permisos relacionados a esas secciones
        if (!empty($seccionesEliminadas)) {
            \App\Models\PermisoAcceso::where('departamento_id', $departamento->id)
                ->whereIn('seccion_id', $seccionesEliminadas)
                ->delete();
        }

        // 🔸 Actualizar las nuevas relaciones
        $departamento->secciones()->sync($nuevasSecciones);

        return back()->with('success', 'Secciones asignadas correctamente.');
    }


    public function actualizarPermiso(Request $request, Departamento $departamento)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'accion'  => 'required|in:ver,crear,editar',
            'valor'   => 'required|boolean',
        ]);

        // Obtener todas las secciones de ese departamento
        $secciones = $departamento->secciones;

        foreach ($secciones as $seccion) {
            $permiso = PermisoAcceso::firstOrNew([
                'user_id'        => $validated['user_id'],
                'departamento_id' => $departamento->id,
                'seccion_id'     => $seccion->id,
            ]);

            $campo = 'puede_' . $validated['accion'];
            $permiso->$campo = $validated['valor'];
            $permiso->save();
        }

        return response()->json(['success' => true]);
    }

    public function create()
    {
        return view('departamentos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:departamentos,nombre',
            'descripcion' => 'nullable|string',
        ]);

        Departamento::create($request->only('nombre', 'descripcion'));

        return redirect()->route('departamentos.index')->with('success', 'Departamento creado correctamente.');
    }

    public function edit(Departamento $departamento)
    {
        return view('departamentos.edit', compact('departamento'));
    }

    public function update(Request $request, Departamento $departamento)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'nombre.required' => 'El nombre del departamento es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede superar los 1000 caracteres.',
        ]);

        try {
            $departamento->update($validated);

            // 👇 Esta respuesta es necesaria para que el fetch() la entienda
            return response()->json([
                'success' => true,
                'message' => 'Departamento actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar el departamento: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al actualizar el departamento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Departamento $departamento)
    {
        $departamento->delete();

        return redirect()->route('departamentos.index')->with('success', 'Departamento eliminado correctamente.');
    }
}
