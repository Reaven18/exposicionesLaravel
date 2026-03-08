<?php

namespace App\Http\Controllers;

use App\Models\Maestro;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @group Gestión de Maestros
 *
 * Endpoints para la administración de docentes, incluyendo la creación de su cuenta de usuario vinculada.
 */
class MaestroController extends Controller
{
    /**
     * Listar maestros.
     * * Recupera la lista de maestros con su información básica de usuario.
     * @authenticated
     * @response 200 {
     * "success": true,
     * "data": [
     * {
     * "id_usuario": 1,
     * "created_at": "2026-03-08T00:00:00.000000Z",
     * "updated_at": "2026-03-08T00:00:00.000000Z",
     * "usuario": {
     * "id_usuario": 1,
     * "nombre": "Prof. Gabriel",
     * "email": "gabriel@docente.com",
     * "id_rol": 1
     * }
     * }
     * ],
     * "message": "Lista de maestros recuperada."
     * }
     */
    public function index()
    {
        $maestros = Maestro::with('usuario')->get();
        return $this->sendResponse($maestros, 'Lista de maestros recuperada.');
    }

    /**
     * Crear maestro.
     * * Crea un usuario y lo vincula automáticamente como maestro en una sola transacción.
     * @authenticated
     * @bodyParam nombre string required Nombre completo del docente. Example: Prof. Gabriel
     * @bodyParam email string required Correo institucional único. Example: gabriel@docente.com
     * @bodyParam password string required Contraseña (mín. 8 caracteres). Example: secret123
     * @bodyParam id_rol integer required ID del rol de maestro. Example: 1
     * @response 201 {
     * "success": true,
     * "data": {
     * "id_usuario": 5,
     * "usuario": {
     * "id_usuario": 5,
     * "nombre": "Prof. Gabriel",
     * "email": "gabriel@docente.com"
     * }
     * },
     * "message": "Maestro creado con éxito."
     * }
     * @response 422 {
     * "message": "The email has already been taken.",
     * "errors": { "email": ["The email has already been taken."] }
     * }
     * @response 500 {
     * "success": false,
     * "message": "Error al registrar maestro.",
     * "data": ["Detalle técnico del error..."]
     * }
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
     * * Obtiene el perfil del maestro junto con sus grupos y materias asignadas.
     * @authenticated
     * @urlParam id integer required ID de usuario del maestro. Example: 1
     * @response 200 {
     * "success": true,
     * "data": {
     * "id_usuario": 1,
     * "usuario": { "nombre": "Prof. Gabriel", "email": "gabriel@docente.com" },
     * "grupos": [
     * { "id_grupo": 10, "materia": { "nombre_materia": "Ciberseguridad" } }
     * ]
     * },
     * "message": "Detalles del maestro recuperados."
     * }
     * @response 404 { "success": false, "message": "Maestro no encontrado.", "data": [] }
     */
    public function show($id)
    {
        $maestro = Maestro::with(['usuario', 'grupos.materia'])->where('id_usuario', $id)->first();
        if (!$maestro) return $this->sendError('Maestro no encontrado.', [], 404);
        return $this->sendResponse($maestro, 'Detalles del maestro recuperados.');
    }

    /**
     * Actualizar maestro.
     * * Actualiza la información básica del usuario (nombre/email) vinculado al maestro.
     * @authenticated
     * @urlParam id integer required ID de usuario.
     * @bodyParam nombre string Nuevo nombre. Example: Gabriel actualizado
     * @bodyParam email string Nuevo correo. Example: g.nuevo@docente.com
     * @response 200 {
     * "success": true,
     * "data": { "id_usuario": 1, "nombre": "Gabriel actualizado", "maestro": {...} },
     * "message": "Datos actualizados."
     * }
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
     * @authenticated
     * @urlParam id integer required ID del usuario a eliminar.
     * @response 200 { "success": true, "data": [], "message": "Maestro eliminado correctamente." }
     */
    public function destroy($id)
    {
        $usuario = User::find($id);
        if (!$usuario) return $this->sendError('Maestro no existe.', [], 404);
        $usuario->delete();
        return $this->sendResponse([], 'Maestro eliminado correctamente.');
    }
}