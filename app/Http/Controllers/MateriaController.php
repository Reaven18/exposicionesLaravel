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
     * Listar todos los grupos con su materia y maestro
     */
    public function index()
    {
        $materias = Materia::all();
        return $this->sendResponse($materias, 'Materias recuperadas con éxito.');
    }

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
     * Mostrar un grupo específico con sus alumnos inscritos
     */
    public function show($id)
    {
        $materia = Materia::find($id);

        if (!$materia) {
            return $this->sendError('Materia no encontrado.');
        }

        return $this->sendResponse($materia, 'Detalles de las materias obtenidos.');
    }

    public function destroy($id)
    {
        $materia = Materia::find($id);
        if (!$materia) return $this->sendError('Materia no encontrado.');

        $materia->delete();
        return $this->sendResponse([], 'Materia eliminada exitosamente.');
    }
}