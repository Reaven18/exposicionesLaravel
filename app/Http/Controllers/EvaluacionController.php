<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Gestión de Evaluaciones
 *
 * Endpoints para registrar y consultar las evaluaciones realizadas por los usuarios a las exposiciones.
 */
class EvaluacionController extends Controller
{
    /**
     * Listar evaluaciones.
     * * Recupera el historial completo de evaluaciones con sus detalles técnicos.
     * @authenticated
     * @response 200 {
     * "success": true,
     * "data": [
     * {
     * "id_evaluacion": 1,
     * "id_expo": 5,
     * "id_usuario": 2,
     * "observaciones": "Excelente dominio del tema.",
     * "fecha": "2026-03-08 10:00:00",
     * "exposicion": { "id_expo": 5, "tema": "Arquitectura de Microservicios" },
     * "usuario": { "id_usuario": 2, "nombre": "Dr. Arjona" },
     * "detalles": [
     * {
     * "id_detalle": 1,
     * "calificacion": 9.5,
     * "criterio": { "id_criterios": 1, "nombre_criterio": "Puntualidad" }
     * }
     * ]
     * }
     * ],
     * "message": "Evaluaciones recuperadas."
     * }
     */
    public function index()
    {
        $evaluaciones = Evaluacion::with(['exposicion', 'usuario', 'detalles.criterio'])->get();
        return $this->sendResponse($evaluaciones, 'Evaluaciones recuperadas.');
    }

    /**
     * Guardar evaluación.
     * * Registra una nueva evaluación y sus calificaciones individuales por criterio dentro de una transacción.
     * @authenticated
     * @bodyParam id_expo integer required ID de la exposición a evaluar. Example: 1
     * @bodyParam id_usuario integer required ID del evaluador. Example: 1
     * @bodyParam observaciones string Notas u observaciones adicionales. Example: Muy buena dicción.
     * @bodyParam calificaciones object[] required Array de calificaciones por criterio.
     * @bodyParam calificaciones[].id_criterio integer required ID del criterio. Example: 1
     * @bodyParam calificaciones[].nota number required Nota numérica (0-10). Example: 9.5
     * @response 201 {
     * "success": true,
     * "data": {
     * "id_evaluacion": 10,
     * "id_expo": 1,
     * "observaciones": "Muy buena dicción.",
     * "detalles": [
     * { "id_criterios": 1, "calificacion": 9.5, "criterio": { "nombre_criterio": "Dominio" } }
     * ]
     * },
     * "message": "Evaluación registrada correctamente."
     * }
     * @response 422 {
     * "message": "The calificaciones.0.nota must be a number.",
     * "errors": { "calificaciones.0.nota": ["The calificaciones.0.nota must be a number."] }
     * }
     * @response 500 {
     * "success": false,
     * "message": "Error al registrar evaluación.",
     * "data": ["Error de integridad en la base de datos..."]
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_expo'      => 'required|exists:exposiciones,id_expo',
            'id_usuario'   => 'required|exists:usuarios,id_usuario',
            'observaciones'=> 'nullable|string',
            'calificaciones' => 'required|array',
            'calificaciones.*.id_criterio' => 'required|exists:criterios,id_criterios',
            'calificaciones.*.nota'         => 'required|numeric|min:0|max:10'
        ]);

        try {
            $evaluacion = DB::transaction(function () use ($request) {
                $nuevaEval = Evaluacion::create([
                    'id_expo'      => $request->id_expo,
                    'id_usuario'   => $request->id_usuario,
                    'observaciones'=> $request->observaciones,
                    'fecha'        => now()
                ]);

                foreach ($request->calificaciones as $item) {
                    $nuevaEval->detalles()->create([
                        'id_criterios' => $item['id_criterio'],
                        'calificacion' => $item['nota']
                    ]);
                }
                return $nuevaEval->load('detalles.criterio');
            });
            return $this->sendResponse($evaluacion, 'Evaluación registrada correctamente.', 201);
        } catch (\Exception $e) {
            return $this->sendError('Error al registrar evaluación.', [$e->getMessage()], 500);
        }
    }

    /**
     * Ver evaluación específica.
     *@authenticated
     * @urlParam id integer required ID de la evaluación. Example: 1
     * @response 200 {
     * "success": true,
     * "data": {
     * "id_evaluacion": 1,
     * "exposicion": { "tema": "IA", "equipo": { "equipo": "Los Linces" } },
     * "detalles": [{
     *           "id_criterios": 1,
     *           "calificacion": 9.5,
     *           "criterio": {
     *               "nombre_criterio": "Dominio"
     *           }
     *       }]
     * },
     * "message": "Detalle de evaluación obtenido."
     * }
     * @response 404 { "success": false, "message": "Evaluación no encontrada.", "data": [] }
     */
    public function show($id)
    {
        $evaluacion = Evaluacion::with(['exposicion.equipo', 'usuario', 'detalles.criterio'])->find($id);
        if (!$evaluacion) return $this->sendError('Evaluación no encontrada.', [], 404);
        return $this->sendResponse($evaluacion, 'Detalle de evaluación obtenido.');
    }

    /**
     * Eliminar evaluación.
     * * Elimina una evaluación específica del sistema. Si la base de datos tiene configurado ON DELETE CASCADE, también se eliminarán sus detalles de calificación.
     * @authenticated
     * @urlParam id integer required ID de la evaluación a eliminar. Example: 1
     * @response 200 {
     * "success": true,
     * "data": [],
     * "message": "Evaluación eliminada correctamente."
     * }
     * @response 404 {
     * "success": false,
     * "message": "Evaluación no encontrada.",
     * "data": []
     * }
     * @response 500 {
     * "success": false,
     * "message": "Error al eliminar la evaluación.",
     * "data": ["Detalle del error SQL..."]
     * }
     */
    public function destroy($id)
    {
        $evaluacion = Evaluacion::find($id);
        
        if (!$evaluacion) {
            return $this->sendError('Evaluación no encontrada.', [], 404);
        }

        try {
            $evaluacion->delete();
            return $this->sendResponse([], 'Evaluación eliminada correctamente.');
        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar la evaluación.', [$e->getMessage()], 500);
        }
    }

}