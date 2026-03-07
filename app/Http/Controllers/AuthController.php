<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

/**
 * @group Gestión de Autenticación
 *
 * Endpoints para administrar el acceso al sistema.
 */
class AuthController extends Controller
{
    /**
     * Inicio de sesión.
     * @bodyParam email string required El correo del usuario. Example: admin@example.com
     * @bodyParam password string required La contraseña. Example: password
     * @response 200 {
     * "success": true,
     * "data": {
     * "token": "1|ra67sdf...",
     * "user": { "id_usuario": 1, 
     * "nombre": "Admin", 
     * "email": "admin@example.com", 
     * "id_rol": 1, "created_at": "2026-03-06T18:01:01.000000Z",
     *  "updated_at": "2026-03-06T18:01:01.000000Z",
     * "rol": {
     *           "id_rol": 1,
     *           "nombre_rol": "Maestro",
     *           "created_at": "2026-03-06T18:01:00.000000Z",
     *           "updated_at": "2026-03-06T18:01:00.000000Z"
     *       } }
     * },
     * "message": "Usuario autenticado con éxito."
     * }
     * @response 401 {
     * "success": false,
     * "message": "Credenciales incorrectas.",
     * "data": { "error": "No autorizado" }
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->with('rol')->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->sendError('Credenciales incorrectas.', ['error' => 'No autorizado'], 401);
        }

        $token = $user->createToken('API_TOKEN')->plainTextToken;

        $success = [
            'token' => $token,
            'user'  => $user,
        ];

        return $this->sendResponse($success, 'Usuario autenticado con éxito.');
    }

    /**
     * Registro de usuario.
     * @bodyParam nombre string required Nombre completo. Example: Nuevo Usuario
     * @bodyParam email string required Correo único. Example: nuevo@example.com
     * @bodyParam password string required Mínimo 8 caracteres. Example: password123
     * @bodyParam password_confirmation string required Debe coincidir con password. Example: password123
     * @bodyParam id_rol integer required ID del rol. Example: 2
     * @response 201 {
     * "success": true,
     * "data": { "token": "1|ra67sdf...", "user": {...} },
     * "message": "Usuario registrado correctamente."
     * }
     */
    public function register(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:255',
            'email'    => 'required|email|unique:usuarios,email',
            'password' => 'required|min:8|confirmed',
            'id_rol'   => 'required|exists:roles,id_rol'
        ]);

        $user = User::create([
            'nombre'   => $request->nombre,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'id_rol'   => $request->id_rol,
        ]);

        $success['token'] = $user->createToken('API_TOKEN')->plainTextToken;
        $success['user']  = $user->load('rol');

        return $this->sendResponse($success, 'Usuario registrado correctamente.', 201);
    }

    /**
     * Cerrar sesión.
     * @authenticated
     * @response 200 { "success": true, "data": [], "message": "Sesión cerrada y token eliminado." }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->sendResponse([], 'Sesión cerrada y token eliminado.');
    }

    /**
     * Obtener perfil del usuario logueado.
     * @authenticated
     * @response 200 { "success": true, "data": { "id_usuario": 1, "nombre": "...", "alumno": {...} }, "message": "Datos del usuario logueado." }
     */
    public function me(Request $request)
    {
        $user = $request->user()->load(['rol', 'alumno', 'maestro']);

        if ($user->id_rol == 2 && $user->alumno) {
            $user->alumno->load('grupos.materia');
        } elseif ($user->id_rol == 1 && $user->maestro) {
            $user->maestro->load('grupos.materia');
        }

        return $this->sendResponse($user, 'Datos del usuario logueado.');
    }
}