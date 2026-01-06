<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Obra;
use App\Models\TasaSeguridadSocial;
use App\Models\Convenio;
use App\Models\Turno;
use App\Models\TasaIrpf;
use App\Models\Categoria;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index()
    {
        $empresas = Empresa::all();
        $obras = Obra::all();
        $porcentajes_ss = TasaSeguridadSocial::all();
        $tramos = TasaIrpf::all();
        $convenio = Convenio::all();
        $turnos = Turno::all();
        $categorias = Categoria::all();
        return view('empresas.index', compact('empresas', 'obras', 'porcentajes_ss', 'tramos', 'convenio', 'turnos', 'categorias'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'nif' => 'nullable|string|max:20',
            'numero_ss' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'localidad' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:10',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        Empresa::create($validated);

        return redirect()->route('empresas.index')->with('success', 'Empresa creada correctamente.');
    }

    public function update(Request $request, Empresa $empresa)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'nif' => 'nullable|string|max:20',
            'numero_ss' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'localidad' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:10',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $empresa->update($validated);

        return redirect()->route('empresas.index')->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroy(Empresa $empresa)
    {
        $empresa->delete();
        return redirect()->route('empresas.index')->with('success', 'Empresa eliminada correctamente.');
    }

    /**
     * Actualiza un campo de categoría en línea
     */
    public function updateCategoriaField(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:categorias,id',
                'field' => 'required|string|in:nombre',
                'value' => 'required|string|max:255',
            ]);

            $categoria = Categoria::findOrFail($validated['id']);
            $categoria->{$validated['field']} = $validated['value'];
            $categoria->save();

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error actualizando categoría: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea una nueva categoría
     */
    public function storeCategoria(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255|unique:categorias,nombre',
            ]);

            $categoria = new Categoria();
            $categoria->nombre = $validated['nombre'];
            $categoria->save();

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada correctamente.',
                'categoria' => $categoria
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creando categoría: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina una categoría
     */
    public function destroyCategoria(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:categorias,id',
            ]);

            $categoria = Categoria::findOrFail($validated['id']);

            // Verificar si tiene usuarios asociados
            if ($categoria->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar: hay usuarios asignados a esta categoría.'
                ], 400);
            }

            $categoria->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error eliminando categoría: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría.'
            ], 500);
        }
    }

    /**
     * Crea una nueva empresa via JSON
     */
    public function storeJson(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'direccion' => 'nullable|string|max:255',
                'localidad' => 'nullable|string|max:255',
                'provincia' => 'nullable|string|max:255',
                'codigo_postal' => 'nullable|string|max:10',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'nif' => 'nullable|string|max:20',
                'numero_ss' => 'nullable|string|max:50',
            ]);

            $empresa = Empresa::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Empresa creada correctamente.',
                'empresa' => $empresa
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creando empresa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la empresa.'
            ], 500);
        }
    }

    /**
     * Actualiza un campo de empresa en línea
     */
    public function updateField(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:empresas,id',
                'field' => 'required|string|in:nombre,direccion,localidad,provincia,codigo_postal,telefono,email,nif,numero_ss',
                'value' => 'nullable|string|max:255',
            ]);

            $empresa = Empresa::findOrFail($validated['id']);
            $empresa->{$validated['field']} = $validated['value'];
            $empresa->save();

            return response()->json([
                'success' => true,
                'message' => 'Empresa actualizada correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error actualizando empresa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la empresa.'
            ], 500);
        }
    }

    /**
     * Elimina una empresa via JSON
     */
    public function destroyJson(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:empresas,id',
            ]);

            $empresa = Empresa::findOrFail($validated['id']);
            $empresa->delete();

            return response()->json([
                'success' => true,
                'message' => 'Empresa eliminada correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error eliminando empresa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la empresa.'
            ], 500);
        }
    }

    // ==================== TURNOS ====================

    public function storeTurno(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'hora_inicio' => 'nullable|string',
                'hora_fin' => 'nullable|string',
                'hora_entrada' => 'nullable|string',
                'entrada_offset' => 'nullable|integer',
                'hora_salida' => 'nullable|string',
                'salida_offset' => 'nullable|integer',
                'color' => 'nullable|string|max:20',
            ]);

            $turno = Turno::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Turno creado correctamente.',
                'turno' => $turno
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creando turno: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el turno.'
            ], 500);
        }
    }

    public function updateTurnoField(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:turnos,id',
                'field' => 'required|string|in:nombre,hora_inicio,hora_fin,hora_entrada,entrada_offset,hora_salida,salida_offset,color',
                'value' => 'nullable|string|max:255',
            ]);

            $turno = Turno::findOrFail($validated['id']);
            $turno->{$validated['field']} = $validated['value'];
            $turno->save();

            return response()->json([
                'success' => true,
                'message' => 'Turno actualizado correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error actualizando turno: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el turno.'
            ], 500);
        }
    }

    public function destroyTurno(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:turnos,id',
            ]);

            $turno = Turno::findOrFail($validated['id']);
            $turno->delete();

            return response()->json([
                'success' => true,
                'message' => 'Turno eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error eliminando turno: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el turno.'
            ], 500);
        }
    }

    // ==================== PORCENTAJES SEGURIDAD SOCIAL ====================

    public function storePorcentajeSS(Request $request)
    {
        try {
            $validated = $request->validate([
                'tipo_aportacion' => 'required|string|max:255',
                'porcentaje' => 'required|numeric|min:0|max:100',
            ]);

            $porcentaje = TasaSeguridadSocial::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Porcentaje creado correctamente.',
                'porcentaje' => $porcentaje
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creando porcentaje SS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el porcentaje.'
            ], 500);
        }
    }

    public function updatePorcentajeSSField(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:tasas_seguridad_social,id',
                'field' => 'required|string|in:tipo_aportacion,porcentaje',
                'value' => 'nullable|string|max:255',
            ]);

            $porcentaje = TasaSeguridadSocial::findOrFail($validated['id']);
            $porcentaje->{$validated['field']} = $validated['value'];
            $porcentaje->save();

            return response()->json([
                'success' => true,
                'message' => 'Porcentaje actualizado correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error actualizando porcentaje SS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el porcentaje.'
            ], 500);
        }
    }

    public function destroyPorcentajeSS(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:tasas_seguridad_social,id',
            ]);

            $porcentaje = TasaSeguridadSocial::findOrFail($validated['id']);
            $porcentaje->delete();

            return response()->json([
                'success' => true,
                'message' => 'Porcentaje eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error eliminando porcentaje SS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el porcentaje.'
            ], 500);
        }
    }

    // ==================== TRAMOS IRPF ====================

    public function storeTramoIrpf(Request $request)
    {
        try {
            $validated = $request->validate([
                'tramo_inicial' => 'required|numeric|min:0',
                'tramo_final' => 'nullable|numeric|min:0',
                'porcentaje' => 'required|numeric|min:0|max:100',
            ]);

            $tramo = TasaIrpf::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tramo IRPF creado correctamente.',
                'tramo' => $tramo
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creando tramo IRPF: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tramo.'
            ], 500);
        }
    }

    public function updateTramoIrpfField(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:tasas_irpf,id',
                'field' => 'required|string|in:tramo_inicial,tramo_final,porcentaje',
                'value' => 'nullable|string|max:255',
            ]);

            $tramo = TasaIrpf::findOrFail($validated['id']);
            $tramo->{$validated['field']} = $validated['value'];
            $tramo->save();

            return response()->json([
                'success' => true,
                'message' => 'Tramo IRPF actualizado correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error actualizando tramo IRPF: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tramo.'
            ], 500);
        }
    }

    public function destroyTramoIrpf(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:tasas_irpf,id',
            ]);

            $tramo = TasaIrpf::findOrFail($validated['id']);
            $tramo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tramo IRPF eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error eliminando tramo IRPF: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tramo.'
            ], 500);
        }
    }

    // ==================== CONVENIOS ====================

    public function storeConvenio(Request $request)
    {
        try {
            $validated = $request->validate([
                'categoria_id' => 'required|exists:categorias,id',
                'salario_base' => 'required|numeric|min:0',
                'liquido_minimo_pactado' => 'nullable|numeric|min:0',
                'plus_asistencia' => 'nullable|numeric|min:0',
                'plus_actividad' => 'nullable|numeric|min:0',
                'plus_productividad' => 'nullable|numeric|min:0',
                'plus_absentismo' => 'nullable|numeric|min:0',
                'plus_transporte' => 'nullable|numeric|min:0',
                'prorrateo_pagasextras' => 'nullable|numeric|min:0',
            ]);

            $convenio = Convenio::create($validated);
            $convenio->load('categoria');

            return response()->json([
                'success' => true,
                'message' => 'Convenio creado correctamente.',
                'convenio' => $convenio
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creando convenio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el convenio.'
            ], 500);
        }
    }

    public function updateConvenioField(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:convenio,id',
                'field' => 'required|string|in:categoria_id,salario_base,liquido_minimo_pactado,plus_asistencia,plus_actividad,plus_productividad,plus_absentismo,plus_transporte,prorrateo_pagasextras',
                'value' => 'nullable|string|max:255',
            ]);

            $convenio = Convenio::findOrFail($validated['id']);
            $convenio->{$validated['field']} = $validated['value'];
            $convenio->save();

            return response()->json([
                'success' => true,
                'message' => 'Convenio actualizado correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error actualizando convenio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el convenio.'
            ], 500);
        }
    }

    public function destroyConvenio(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:convenio,id',
            ]);

            $convenio = Convenio::findOrFail($validated['id']);
            $convenio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Convenio eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error eliminando convenio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el convenio.'
            ], 500);
        }
    }

    // ==================== OBRAS (LUGARES DE TRABAJO) ====================

    public function storeObra(Request $request)
    {
        try {
            $validated = $request->validate([
                'obra' => 'required|string|max:255',
                'direccion' => 'nullable|string|max:255',
                'ciudad' => 'nullable|string|max:255',
                'latitud' => 'nullable|numeric|between:-90,90',
                'longitud' => 'nullable|numeric|between:-180,180',
                'distancia' => 'nullable|integer|min:1',
            ]);

            // Generar cod_obra unico
            $validated['cod_obra'] = 'OBR-' . strtoupper(uniqid());

            // Convertir valores vacios a null
            if (empty($validated['latitud'])) $validated['latitud'] = null;
            if (empty($validated['longitud'])) $validated['longitud'] = null;
            if (empty($validated['distancia'])) $validated['distancia'] = null;

            $obra = Obra::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Lugar de trabajo creado correctamente.',
                'obra' => $obra
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creando obra: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el lugar de trabajo.'
            ], 500);
        }
    }

    public function updateObraField(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:obras,id',
                'field' => 'required|string|in:obra,direccion,ciudad,latitud,longitud,distancia',
                'value' => 'nullable',
            ]);

            $obra = Obra::findOrFail($validated['id']);

            // Convertir valores vacios a null para campos numericos
            $value = $validated['value'];
            if (in_array($validated['field'], ['latitud', 'longitud', 'distancia']) && $value === '') {
                $value = null;
            }

            $obra->{$validated['field']} = $value;
            $obra->save();

            return response()->json([
                'success' => true,
                'message' => 'Lugar de trabajo actualizado correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error actualizando obra: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el lugar de trabajo.'
            ], 500);
        }
    }

    public function destroyObra(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:obras,id',
            ]);

            $obra = Obra::findOrFail($validated['id']);
            $obra->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lugar de trabajo eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error eliminando obra: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el lugar de trabajo.'
            ], 500);
        }
    }
}
