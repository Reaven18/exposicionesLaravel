<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Gestión de Equipos
 */
class EquipoController extends Controller
{
    /**
     * Listar equipos.
     * @response 200 { "success": true, "data": [...], "message": "Equipos recuperados exitosamente." }
     */
    public function index()
    {
        $equipos = Equipo::with(['grupo.materia', 'integrantes.usuario'])->get();
        return $this->sendResponse($equipos, 'Equipos recuperados exitosamente.');
    }

    /**
     * Crear un equipo.
     * @bodyParam id_grupo integer required ID del grupo. Example: 1
     * @bodyParam equipo string required Nombre del equipo. Example: Los Dinamita
     * @bodyParam alumnos int[] required IDs de los alumnos (id_usuario). Example: [1, 2, 3]
     * @response 201 { "success": true, "data": {...}, "message": "Equipo creado e integrantes asignados." }
     * @response 500 { "success": false, "message": "Error al crear el equipo.", "data": ["..."] }
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_grupo' => 'required|exists:grupos,id_grupo',
            'equipo'   => 'required|string|max:100',
            'alumnos'  => 'required|array',
            'alumnos.*'=> 'exists:alumnos,id_usuario'
        ]);

        try {
            $nuevoEquipo = DB::transaction(function () use ($request) {
                $equipo = Equipo::create([
                    'id_grupo' => $request->id_grupo,
                    'equipo'   => $request->equipo,
                    'active'   => true
                ]);
                $equipo->integrantes()->attach($request->alumnos);
                return $equipo->load('integrantes.usuario');
            });
            return $this->sendResponse($nuevoEquipo, 'Equipo creado e integrantes asignados.', 201);
        } catch (\Exception $e) {
            return $this->sendError('Error al crear el equipo.', [$e->getMessage()], 500);
        }
    }

    /**
     * Ver un equipo específico.
     * @urlParam id integer required ID del equipo. Example: 1
     * @response 200 { "success": true, "data": {...}, "message": "Detalles del equipo recuperados." }
     * @response 404 { "success": false, "message": "Equipo no encontrado.", "data": [] }
     */
    public function show($id)
    {
        $equipo = Equipo::with(['grupo.materia', 'integrantes.usuario', 'exposiciones'])->find($id);
        if (!$equipo) return $this->sendError('Equipo no encontrado.', [], 404);
        return $this->sendResponse($equipo, 'Detalles del equipo recuperados.');
    }

    /**
     * Actualizar integrantes.
     * @urlParam id integer required ID del equipo.
     * @bodyParam alumnos int[] required Nuevos IDs de alumnos. Example: [4, 5]
     * @response 200 { "success": true, "data": {...}, "message": "Integrantes actualizados." }
     * @response 404 { "success": false, "message": "Equipo no encontrado.", "data": [] }
     */
    public function updateIntegrantes(Request $request, $id)
    {
        $request->validate(['alumnos' => 'required|array', 'alumnos.*' => 'exists:alumnos,id_usuario']);
        $equipo = Equipo::find($id);
        if (!$equipo) return $this->sendError('Equipo no encontrado.', [], 404);

        $equipo->integrantes()->sync($request->alumnos);
        return $this->sendResponse($equipo->load('integrantes.usuario'), 'Integrantes actualizados.');
    }

    /**
     * Eliminar equipo.
     * @urlParam id integer required
     * @response 200 { "success": true, "message": "Equipo eliminado correctamente." }
     */
    public function destroy($id)
    {
        $equipo = Equipo::find($id);
        if (!$equipo) return $this->sendError('Equipo no encontrado.', [], 404);
        $equipo->delete();
        return $this->sendResponse([], 'Equipo eliminado correctamente.');
    }
}