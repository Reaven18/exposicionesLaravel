<?php

namespace App\Http\Controllers;

use App\Models\Maestro;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @group Gestión de Maestros
 */
class MaestroController extends Controller
{
    /**
     * Listar maestros.
     * @response 200 { "success": true, "data": [...] }
     */
    public function index()
    {
        $maestros = Maestro::with('usuario')->get();
        return $this->sendResponse($maestros, 'Lista de maestros recuperada.');
    }

    /**
     * Crear maestro.
     * @bodyParam nombre string required. Example: Prof. Gabriel
     * @bodyParam email string required. Example: gabriel@docente.com
     * @bodyParam password string required. Example: secret123
     * @bodyParam id_rol integer required. Example: 1
     * @response 201 { "success": true, "data": {...} }
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:255',
            'email'    => 'required|email|unique:usuarios,email',
            'password' => 'required|min:8',
            'id_rol'   => 'required|exists:roles,id_rol'
        ]);

        try {
            $maestro = DB::transaction(function () use ($request) {
                $usuario = User::create([
                    'nombre'   => $request->nombre,
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                    'id_rol'   => $request->id_rol
                ]);
                return Maestro::create(['id_usuario' => $usuario->id_usuario])->load('usuario');
            });
            return $this->sendResponse($maestro, 'Maestro creado con éxito.', 201);
        } catch (\Exception $e) {
            return $this->sendError('Error al registrar maestro.', [$e->getMessage()], 500);
        }
    }

    /**
     * Ver maestro específico.
     * @urlParam id integer required ID de usuario del maestro. Example: 1
     */
    public function show($id)
    {
        $maestro = Maestro::with(['usuario', 'grupos.materia'])->where('id_usuario', $id)->first();
        if (!$maestro) return $this->sendError('Maestro no encontrado.', [], 404);
        return $this->sendResponse($maestro, 'Detalles del maestro recuperados.');
    }

    /**
     * Actualizar maestro.
     * @urlParam id integer required ID de usuario.
     * @bodyParam nombre string
     * @bodyParam email string
     */
    public function update(Request $request, $id)
    {
        $usuario = User::find($id);
        if (!$usuario) return $this->sendError('Usuario no encontrado.', [], 404);
        $usuario->update($request->only(['nombre', 'email']));
        return $this->sendResponse($usuario->load('maestro'), 'Datos actualizados.');
    }

    /**
     * Eliminar maestro.
     * @urlParam id integer required
     */
    public function destroy($id)
    {
        $usuario = User::find($id);
        if (!$usuario) return $this->sendError('Maestro no existe.', [], 404);
        $usuario->delete();
        return $this->sendResponse([], 'Maestro eliminado correctamente.');
    }
}