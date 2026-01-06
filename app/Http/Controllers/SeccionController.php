<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class SeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'ruta' => 'required|string|max:150|unique:secciones,ruta',
            'icono' => 'nullable|string|max:255',
        ]);

        Seccion::create($request->only('nombre', 'ruta', 'icono'));

        return back()->with('success', 'Sección creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Seccion $seccion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Seccion $seccion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, Seccion $seccione)
    {
        try {
            \Log::info('📝 Update recibido para sección', [
                'seccion_id' => $seccione->id,
                'request_data' => $request->all(),
                'has_mostrar' => $request->has('mostrar_en_dashboard'),
                'has_nombre' => $request->has('nombre'),
            ]);

            // Si solo se envía mostrar_en_dashboard (desde el checkbox)
            if ($request->has('mostrar_en_dashboard') && !$request->has('nombre')) {
                $valorAntes = $seccione->mostrar_en_dashboard;
                $valorNuevo = $request->boolean('mostrar_en_dashboard');

                $seccione->update([
                    'mostrar_en_dashboard' => $valorNuevo,
                ]);

                \Log::info('✅ Dashboard actualizado', [
                    'seccion_id' => $seccione->id,
                    'valor_antes' => $valorAntes,
                    'valor_nuevo' => $valorNuevo,
                    'guardado_en_bd' => $seccione->fresh()->mostrar_en_dashboard
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Dashboard actualizado correctamente.',
                    'data' => [
                        'seccion_id' => $seccione->id,
                        'mostrar_en_dashboard' => $seccione->fresh()->mostrar_en_dashboard
                    ]
                ]);
            }

            // Actualización completa (desde doble click)
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'ruta'   => 'nullable|string|max:255',
                'icono'  => 'nullable|string|max:255',
            ]);

            $seccione->update(array_merge($validated, [
                'mostrar_en_dashboard' => $request->boolean('mostrar_en_dashboard'),
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Sección actualizada correctamente.'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar la sección: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al actualizar la sección.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Seccion $seccion)
    {
        //
    }

    /**
     * Actualiza el orden de las secciones (drag & drop)
     */
    public function actualizarOrden(Request $request)
    {
        try {
            $request->validate([
                'orden' => 'required|array',
                'orden.*' => 'integer|exists:secciones,id',
            ]);

            foreach ($request->orden as $posicion => $seccionId) {
                Seccion::where('id', $seccionId)->update(['orden' => $posicion]);
            }

            // Limpiar caché del dashboard
            \Cache::forget('dashboard_items');

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar orden de secciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el orden.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
