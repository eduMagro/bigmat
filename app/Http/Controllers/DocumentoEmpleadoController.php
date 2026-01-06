<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DocumentoEmpleado;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DocumentoEmpleadoController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, User $user)
    {
        // Solo oficina puede subir documentos
        if (auth()->user()->rol !== 'oficina') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => 'required|string|in:contrato,prorroga,otros',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'fecha_vencimiento' => 'nullable|date',
            'comentarios' => 'nullable|string',
        ]);

        try {
            $archivo = $request->file('archivo');
            $extension = $archivo->getClientOriginalExtension();
            $nombreArchivo = Str::slug($request->titulo) . '_' . time() . '.' . $extension;

            // Ruta privada segura
            $ruta = "private/documentos_empleados/{$user->id}";
            $path = $archivo->storeAs($ruta, $nombreArchivo);

            $documento = DocumentoEmpleado::create([
                'user_id' => $user->id,
                'titulo' => $request->titulo,
                'tipo' => $request->tipo,
                'ruta_archivo' => $path, // Guardamos el path relativo al disco (storage/app)
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'comentarios' => $request->comentarios,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento subido correctamente',
                'documento' => $documento
            ]);

        } catch (\Exception $e) {
            Log::error('Error al subir documento empleado: ' . $e->getMessage());
            return response()->json(['error' => 'Error al subir el archivo'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocumentoEmpleado $documento)
    {
        // Solo oficina puede borrar
        if (auth()->user()->rol !== 'oficina') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            // Eliminar archivo físico
            if (Storage::exists($documento->ruta_archivo)) {
                Storage::delete($documento->ruta_archivo);
            }

            $documento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar documento empleado: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar el documento'], 500);
        }
    }

    /**
     * Descargar documento de forma segura.
     */
    public function download(DocumentoEmpleado $documento)
    {
        $user = auth()->user();

        // Permisos: Oficina o el propio dueño del documento
        if ($user->rol !== 'oficina' && $user->id !== $documento->user_id) {
            abort(403, 'No tienes permiso para ver este documento.');
        }

        if (!Storage::exists($documento->ruta_archivo)) {
            abort(404, 'El archivo no existe.');
        }

        return Storage::download($documento->ruta_archivo, $documento->titulo . '.' . pathinfo($documento->ruta_archivo, PATHINFO_EXTENSION));
    }

    /**
     * Actualizar fecha de incorporación del usuario.
     */
    public function updateFechaIncorporacion(Request $request, User $user)
    {
        if (auth()->user()->rol !== 'oficina') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'fecha_incorporacion' => 'nullable|date',
        ]);

        $user->fecha_incorporacion = $request->fecha_incorporacion;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Fecha de incorporación actualizada correctamente',
            'fecha_incorporacion' => $user->fecha_incorporacion ? $user->fecha_incorporacion->format('Y-m-d') : null
        ]);
    }

    /**
     * Listar documentos de un usuario (para cargar via AJAX si fuera necesario, 
     * aunque inicialmente los pasaremos a la vista).
     */
    public function index(User $user)
    {
        if (auth()->user()->rol !== 'oficina' && auth()->user()->id !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return response()->json([
            'documentos' => $user->documentosEmpleado()->orderBy('created_at', 'desc')->get()
        ]);
    }
}
