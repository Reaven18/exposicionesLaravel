<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
     /**
     * Listar todos los grupos con su materia y maestro
     */
    public function index()
    {
        $materias = Materia::with(['materia'])->get();
        return $this->sendResponse($materias, 'Materias recuperadas con éxito.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'materia' => 'required|string|max:10'
        ]);

        $materia = Materia::create($request->all());

        return $this->sendResponse(
            $materia->load(['materia']),
            'Materia creado correctamente.',
            201
        );
    }

    /**
     * Mostrar un grupo específico con sus alumnos inscritos
     */
    public function show($id)
    {
        $materia = Materia::with(['materia'])
                      ->find($id);

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
