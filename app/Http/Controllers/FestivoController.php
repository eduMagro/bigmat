<?php

namespace App\Http\Controllers;

use App\Models\Festivo;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FestivoController extends Controller
{
    public function index()
    {
        $anioActual = (int) date('Y');
        $festivos = Festivo::orderBy('fecha', 'desc')->get();

        return view('festivos.index', compact('festivos', 'anioActual'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha'  => 'required|date',
            'titulo' => 'nullable|string|max:120',
        ]);

        $fecha = Carbon::parse($data['fecha'])->startOfDay();
        Festivo::create([
            'fecha'  => $fecha->toDateString(),
            'titulo' => $data['titulo'] ?: 'Festivo',
            'anio'   => (int) $fecha->year,
        ]);

        return redirect()->route('ajustes.index')->with('success', 'Festivo creado correctamente.');
    }

    public function update(Request $request, Festivo $festivo)
    {
        $data = $request->validate([
            'fecha'  => 'required|date',
            'titulo' => 'nullable|string|max:120',
        ]);

        $fecha = Carbon::parse($data['fecha'])->startOfDay();
        $festivo->update([
            'fecha'  => $fecha->toDateString(),
            'titulo' => $data['titulo'] ?: 'Festivo',
            'anio'   => (int) $fecha->year,
        ]);

        return redirect()->route('ajustes.index')->with('success', 'Festivo actualizado correctamente.');
    }

    public function actualizarFecha(Request $request, Festivo $festivo)
    {
        $data = $request->validate([
            'fecha' => 'required|date', // formato 'Y-m-d'
        ]);

        $nueva = Carbon::parse($data['fecha'])->startOfDay();
        $festivo->fecha = $nueva->toDateString();
        $festivo->anio  = (int) $nueva->year;
        $festivo->save();

        return response()->json([
            'ok' => true,
            'festivo' => [
                'id'    => $festivo->id,
                'fecha' => $festivo->fecha,
                'anio'  => $festivo->anio,
            ],
        ]);
    }

    public function destroy(Festivo $festivo)
    {
        $festivo->delete();

        return redirect()->route('ajustes.index')->with('success', 'Festivo eliminado correctamente.');
    }
}
