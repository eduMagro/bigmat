<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TurnoController extends Controller
{
    /**
     * Mostrar listado de turnos
     */
    public function index()
    {
        $turnos = Turno::ordenados()->get();

        return view('configuracion.turnos.index', compact('turnos'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        return view('configuracion.turnos.create');
    }

    /**
     * Guardar nuevo turno
     */
    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|max:50',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i',
            'offset_dias_inicio' => 'required|integer|min:-1|max:1',
            'offset_dias_fin' => 'required|integer|min:-1|max:1',
            'activo' => 'boolean',
            'orden' => 'integer|min:0',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'es_partido' => 'boolean',
        ];

        // Validaciones adicionales si es turno partido
        if ($request->has('es_partido') && $request->es_partido) {
            $rules['hora_inicio2'] = 'required|date_format:H:i';
            $rules['hora_fin2'] = 'required|date_format:H:i';
            $rules['offset_dias_inicio2'] = 'required|integer|min:-1|max:1';
            $rules['offset_dias_fin2'] = 'required|integer|min:-1|max:1';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = [
            'nombre' => $request->nombre,
            'hora_inicio' => $request->hora_inicio . ':00',
            'hora_fin' => $request->hora_fin . ':00',
            'offset_dias_inicio' => $request->offset_dias_inicio,
            'offset_dias_fin' => $request->offset_dias_fin,
            'activo' => $request->has('activo') ? 1 : 0,
            'orden' => $request->orden ?? 999,
            'color' => $request->color,
            'es_partido' => $request->has('es_partido') ? 1 : 0,
        ];

        // Campos del segundo segmento si es turno partido
        if ($request->has('es_partido') && $request->es_partido) {
            $data['hora_inicio2'] = $request->hora_inicio2 . ':00';
            $data['hora_fin2'] = $request->hora_fin2 . ':00';
            $data['offset_dias_inicio2'] = $request->offset_dias_inicio2;
            $data['offset_dias_fin2'] = $request->offset_dias_fin2;
        } else {
            $data['hora_inicio2'] = null;
            $data['hora_fin2'] = null;
            $data['offset_dias_inicio2'] = 0;
            $data['offset_dias_fin2'] = 0;
        }

        Turno::create($data);

        return redirect()->route('ajustes.index')
            ->with('success', 'Turno creado exitosamente');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Turno $turno)
    {
        return view('configuracion.turnos.edit', compact('turno'));
    }

    /**
     * Actualizar turno
     */
    public function update(Request $request, Turno $turno)
    {
        $rules = [
            'nombre' => 'required|string|max:50',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i',
            'offset_dias_inicio' => 'required|integer|min:-1|max:1',
            'offset_dias_fin' => 'required|integer|min:-1|max:1',
            'activo' => 'boolean',
            'orden' => 'integer|min:0',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'es_partido' => 'boolean',
        ];

        // Validaciones adicionales si es turno partido
        if ($request->has('es_partido') && $request->es_partido) {
            $rules['hora_inicio2'] = 'required|date_format:H:i';
            $rules['hora_fin2'] = 'required|date_format:H:i';
            $rules['offset_dias_inicio2'] = 'required|integer|min:-1|max:1';
            $rules['offset_dias_fin2'] = 'required|integer|min:-1|max:1';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = [
            'nombre' => $request->nombre,
            'hora_inicio' => $request->hora_inicio . ':00',
            'hora_fin' => $request->hora_fin . ':00',
            'offset_dias_inicio' => $request->offset_dias_inicio,
            'offset_dias_fin' => $request->offset_dias_fin,
            'activo' => $request->has('activo') ? 1 : 0,
            'orden' => $request->orden ?? 999,
            'color' => $request->color,
            'es_partido' => $request->has('es_partido') ? 1 : 0,
        ];

        // Campos del segundo segmento si es turno partido
        if ($request->has('es_partido') && $request->es_partido) {
            $data['hora_inicio2'] = $request->hora_inicio2 . ':00';
            $data['hora_fin2'] = $request->hora_fin2 . ':00';
            $data['offset_dias_inicio2'] = $request->offset_dias_inicio2;
            $data['offset_dias_fin2'] = $request->offset_dias_fin2;
        } else {
            $data['hora_inicio2'] = null;
            $data['hora_fin2'] = null;
            $data['offset_dias_inicio2'] = 0;
            $data['offset_dias_fin2'] = 0;
        }

        $turno->update($data);

        return redirect()->route('ajustes.index')
            ->with('success', 'Turno actualizado exitosamente');
    }

    /**
     * Eliminar turno (soft delete)
     */
    public function destroy(Turno $turno)
    {
        $turno->delete();

        return redirect()->route('ajustes.index')
            ->with('success', 'Turno eliminado exitosamente');
    }

    /**
     * Activar/desactivar turno
     */
    public function toggleActivo(Request $request, Turno $turno)
    {
        $turno->update(['activo' => !$turno->activo]);

        $estado = $turno->activo ? 'activado' : 'desactivado';

        // Si es una petición AJAX, retornar JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Turno {$estado} exitosamente",
                'turno' => $turno
            ]);
        }

        return redirect()->route('ajustes.index')
            ->with('success', "Turno {$estado} exitosamente");
    }
}
