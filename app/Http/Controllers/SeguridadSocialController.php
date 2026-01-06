<?php

namespace App\Http\Controllers;

use App\Models\SeguridadSocial;
use Illuminate\Http\Request;

class SeguridadSocialController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'concepto' => 'required|string|max:255',
            'porcentaje' => 'required|numeric|min:0',
        ]);

        SeguridadSocial::create($request->all());

        return back()->with('success', 'Concepto de SS creado correctamente.');
    }

    public function update(Request $request, SeguridadSocial $seguridadSocial)
    {
        $request->validate([
            'concepto' => 'required|string|max:255',
            'porcentaje' => 'required|numeric|min:0',
        ]);

        $seguridadSocial->update($request->all());

        return back()->with('success', 'Concepto de SS actualizado correctamente.');
    }

    public function destroy(SeguridadSocial $seguridadSocial)
    {
        $seguridadSocial->delete();

        return back()->with('success', 'Concepto de SS eliminado correctamente.');
    }
}
