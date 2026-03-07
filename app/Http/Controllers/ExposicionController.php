<?php

namespace App\Http\Controllers;

use App\Models\Exposicion;
use Illuminate\Http\Request;

/**
 * @group Gestión de Exposiciones
 */
class ExposicionController extends Controller
{
    /**
     * Listar exposiciones.
     * @response 200 { "success": true, "data": [...] }
     */
    public function index()
    {
        $exposiciones = Exposicion::with(['equipo.grupo.materia', 'rubrica'])->get();
        return $this->sendResponse($exposiciones, 'Exposiciones recuperadas.');
    }

    /**
     * Programar exposición.
     * @bodyParam id_equipo integer required ID Equipo. Example: 1
     * @bodyParam id_rubrica integer required ID Rúbrica. Example: 1
     * @bodyParam tema string required Tema. Example: Inteligencia Artificial
     * @bodyParam fecha string required Formato Y-m-d H:i:s. Example: 2024-06-15 10:00:00
     * @response 201 { "success": true, "data": {...} }
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_equipo'  => 'required|exists:equipos,id_equipo',
            'id_rubrica' => 'required|exists:rubricas,id_rubrica',
            'tema'       => 'required|string|max:255',
            'fecha'      => 'required|date_format:Y-m-d H:i:s'
        ]);

        $exposicion = Exposicion::create($request->all());
        return $this->sendResponse($exposicion->load(['equipo', 'rubrica']), 'Exposición programada correctamente.', 201);
    }

    /**
     * Ver exposición.
     * @urlParam id integer required
     * @response 200 { "success": true, "data": {...} }
     * @response 404 { "success": false, "message": "Exposición no encontrada." }
     */
    public function show($id)
    {
        $exposicion = Exposicion::with(['equipo.integrantes.usuario', 'rubrica.criterios', 'evaluaciones.usuario'])->find($id);
        if (!$exposicion) return $this->sendError('Exposición no encontrada.', [], 404);
        return $this->sendResponse($exposicion, 'Detalles de la exposición cargados.');
    }

    /**
     * Actualizar exposición.
     * @urlParam id integer required
     * @bodyParam tema string
     * @bodyParam fecha string
     * @response 200 { "success": true, "data": {...} }
     * @response 404 { "success": false, "message": "Exposición no encontrada." }
     */
    public function update(Request $request, $id)
    {
        $exposicion = Exposicion::find($id);
        if (!$exposicion) return $this->sendError('Exposición no encontrada.', [], 404);

        $exposicion->update($request->only(['tema', 'fecha', 'id_rubrica']));
        return $this->sendResponse($exposicion, 'Exposición actualizada.');
    }

    /**
     * Eliminar exposición.
     * @urlParam id integer required
     */
    public function destroy($id)
    {
        $exposicion = Exposicion::find($id);
        if (!$exposicion) return $this->sendError('Exposición no encontrada.', [], 404);
        $exposicion->delete();
        return $this->sendResponse([], 'Exposición eliminada.');
    }
}