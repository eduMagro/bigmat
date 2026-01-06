<?php

namespace App\Http\Controllers;

use App\Models\IrpfTramo;
use Illuminate\Http\Request;

class IrpfTramoController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'desde' => 'required|numeric|min:0',
            'hasta' => 'nullable|numeric|min:0',
            'porcentaje' => 'required|numeric|min:0',
        ]);

        IrpfTramo::create($request->all());

        return back()->with('success', 'Tramo IRPF creado correctamente.');
    }

    public function update(Request $request, IrpfTramo $irpfTramo)
    {
        $request->validate([
            'desde' => 'required|numeric|min:0',
            'hasta' => 'nullable|numeric|min:0',
            'porcentaje' => 'required|numeric|min:0',
        ]);

        $irpfTramo->update($request->all());

        return back()->with('success', 'Tramo IRPF actualizado correctamente.');
    }

    public function destroy(IrpfTramo $irpfTramo)
    {
        $irpfTramo->delete();

        return back()->with('success', 'Tramo IRPF eliminado correctamente.');
    }
}
