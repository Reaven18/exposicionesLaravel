<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Gestión de Evaluaciones
 */
class EvaluacionController extends Controller
{
    /**
     * Listar evaluaciones.
     * @response 200 { "success": true, "data": [...], "message": "Evaluaciones recuperadas." }
     */
    public function index()
    {
        $evaluaciones = Evaluacion::with(['exposicion', 'usuario', 'detalles.criterio'])->get();
        return $this->sendResponse($evaluaciones, 'Evaluaciones recuperadas.');
    }

    /**
     * Guardar evaluación.
     * @bodyParam id_expo integer required ID exposición. Example: 1
     * @bodyParam id_usuario integer required ID del evaluador. Example: 1
     * @bodyParam observaciones string Notas opcionales. Example: Muy buena dicción.
     * @bodyParam calificaciones object[] required Array de calificaciones.
     * @bodyParam calificaciones[].id_criterio integer required ID del criterio. Example: 1
     * @bodyParam calificaciones[].nota number required Nota (0-10). Example: 9.5
     * * @response 201 { "success": true, "data": {...}, "message": "Evaluación registrada correctamente." }
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_expo'      => 'required|exists:exposiciones,id_expo',
            'id_usuario'   => 'required|exists:usuarios,id_usuario',
            'observaciones'=> 'nullable|string',
            'calificaciones' => 'required|array',
            'calificaciones.*.id_criterio' => 'required|exists:criterios,id_criterios',
            'calificaciones.*.nota'        => 'required|numeric|min:0|max:10'
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
     * @urlParam id integer required
     * @response 200 { "success": true, "data": {...} }
     * @response 404 { "success": false, "message": "Evaluación no encontrada." }
     */
    public function show($id)
    {
        $evaluacion = Evaluacion::with(['exposicion.equipo', 'usuario', 'detalles.criterio'])->find($id);
        if (!$evaluacion) return $this->sendError('Evaluación no encontrada.', [], 404);
        return $this->sendResponse($evaluacion, 'Detalle de evaluación obtenido.');
    }
}