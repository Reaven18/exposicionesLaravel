<?php

namespace App\Http\Controllers;

use App\Models\Rubrica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Gestión de Rubricas
 */
class RubricaController extends Controller
{
    /**
     * Listar rúbricas.
     * @response 200 { "success": true, "data": [...] }
     */
    public function index()
    {
        $rubricas = Rubrica::with('criterios')->get();
        return $this->sendResponse($rubricas, 'Rúbricas recuperadas.');
    }

    /**
     * Crear rúbrica con criterios.
     * @bodyParam rubrica string required Nombre. Example: Rubrica Final
     * @bodyParam criterios object[] required
     * @bodyParam criterios[].descripcion string required. Example: Dominio del tema
     * @bodyParam criterios[].porcentaje number required (1-100). Example: 30
     * @response 201 { "success": true, "data": {...} }
     */
    public function store(Request $request)
    {
        $request->validate([
            'rubrica' => 'required|string|max:255',
            'criterios' => 'required|array|min:1',
            'criterios.*.descripcion' => 'required|string',
            'criterios.*.porcentaje' => 'required|numeric|min:1|max:100',
        ]);

        try {
            $nuevaRubrica = DB::transaction(function () use ($request) {
                $rubrica = Rubrica::create(['rubrica' => $request->rubrica]);
                foreach ($request->criterios as $criterio) {
                    $rubrica->criterios()->create($criterio);
                }
                return $rubrica->load('criterios');
            });
            return $this->sendResponse($nuevaRubrica, 'Rúbrica y criterios creados con éxito.', 201);
        } catch (\Exception $e) {
            return $this->sendError('Error al crear la rúbrica.', [$e->getMessage()], 500);
        }
    }

    /**
     * Ver rúbrica.
     * @urlParam id integer required
     */
    public function show($id)
    {
        $rubrica = Rubrica::with('criterios')->find($id);
        if (!$rubrica) return $this->sendError('Rúbrica no encontrada.', [], 404);
        return $this->sendResponse($rubrica, 'Detalles de la rúbrica obtenidos.');
    }

    /**
     * Eliminar rúbrica.
     * @urlParam id integer required
     */
    public function destroy($id)
    {
        $rubrica = Rubrica::find($id);
        if (!$rubrica) return $this->sendError('Rúbrica no encontrada.', [], 404);
        $rubrica->delete();
        return $this->sendResponse([], 'Rúbrica eliminada correctamente.');
    }
}