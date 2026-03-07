<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;

/**
 * @group Gestión de Materias
 *
 * Endpoints para la gestión del catálogo de materias de la institución.
 */
class MateriaController extends Controller
{
    /**
     * Listar todas las materias.
     *
     * @response 200 {
     * "success": true,
     * "data": [
     * { "id_materia": 1, "materia": "Cálculo Diferencial" },
     * { "id_materia": 2, "materia": "Física" }
     * ],
     * "message": "Materias recuperadas con éxito."
     * }
     */
    public function index()
    {
        $materias = Materia::all();
        return $this->sendResponse($materias, 'Materias recuperadas con éxito.');
    }

    /**
     * Crear una nueva materia.
     *
     * @bodyParam materia string required Nombre de la materia. Example: Inteligencia Artificial
     *
     * @response 201 {
     * "success": true,
     * "data": { "id_materia": 15, "materia": "Inteligencia Artificial" },
     * "message": "Materia creado correctamente."
     * }
     * @response 422 {
     * "message": "The given data was invalid.",
     * "errors": { "materia": ["The materia field is required."] }
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'materia' => 'required|string'
        ]);

        $materia = Materia::create($request->all());

        return $this->sendResponse(
            $materia,
            'Materia creado correctamente.',
            201
        );
    }

    /**
     * Mostrar una materia específica.
     *
     * @urlParam id integer required El ID de la materia. Example: 1
     *
     * @response 200 {
     * "success": true,
     * "data": { "id_materia": 1, "materia": "Cálculo Diferencial" },
     * "message": "Detalles de las materias obtenidos."
     * }
     * @response 404 {
     * "success": false,
     * "message": "Materia no encontrada.",
     * "data": []
     * }
     */
    public function show($id)
    {
        $materia = Materia::find($id);

        if (!$materia) {
            return $this->sendError('Materia no encontrada.', [], 404);
        }

        return $this->sendResponse($materia, 'Detalles de las materias obtenidos.');
    }

    /**
     * Eliminar una materia.
     *
     * @urlParam id integer required El ID de la materia a eliminar. Example: 1
     *
     * @response 200 {
     * "success": true,
     * "data": [],
     * "message": "Materia eliminada exitosamente."
     * }
     * @response 404 {
     * "success": false,
     * "message": "Materia no encontrada.",
     * "data": []
     * }
     */
    public function destroy($id)
    {
        $materia = Materia::find($id);
        if (!$materia) return $this->sendError('Materia no encontrada.', [], 404);

        $materia->delete();
        return $this->sendResponse([], 'Materia eliminada exitosamente.');
    }
}