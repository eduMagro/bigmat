<?php

namespace App\Http\Controllers;

use App\Models\Convenio;
use Illuminate\Http\Request;

class ConvenioController extends Controller
{


    public function store(Request $request)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'salario_base' => 'required|numeric',
            'plus_asistencia' => 'nullable|numeric',
            'plus_actividad' => 'nullable|numeric',
            'plus_productividad' => 'nullable|numeric',
            'plus_absentismo' => 'nullable|numeric',
            'plus_transporte' => 'nullable|numeric',
            'prorrateo_pagasextras' => 'nullable|numeric',
        ]);

        Convenio::create($request->all());

        return back()->with('success', 'Convenio creado correctamente.');
    }

    public function update(Request $request, Convenio $convenio)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'salario_base' => 'required|numeric',
            'plus_asistencia' => 'nullable|numeric',
            'plus_actividad' => 'nullable|numeric',
            'plus_productividad' => 'nullable|numeric',
            'plus_absentismo' => 'nullable|numeric',
            'plus_transporte' => 'nullable|numeric',
            'prorrateo_pagasextras' => 'nullable|numeric',
        ]);

        $convenio->update($request->all());

        return back()->with('success', 'Convenio actualizado correctamente.');
    }

    public function destroy(Convenio $convenio)
    {
        $convenio->delete();

        return back()->with('success', 'Convenio eliminado correctamente.');
    }
}
