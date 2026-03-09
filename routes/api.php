<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\MaestroController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\ExposicionController;
use App\Http\Controllers\RubricaController;
use App\Http\Controllers\EvaluacionController;
use App\Http\Controllers\MateriaController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/
// Cualquiera puede intentar loguearse
Route::post('/login', [AuthController::class, 'login'])->name('login');

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Requieren Token de Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // --- Autenticación y Usuario Actual ---
    Route::get('/me', [AuthController::class, 'me']); 
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('role:Admin')
        ->name('register');

    // --- CRUDs Automáticos (La seguridad vive en los controladores) ---
    Route::apiResource('alumnos', AlumnoController::class);
    Route::apiResource('maestros', MaestroController::class);
    Route::apiResource('rubricas', RubricaController::class);
    Route::apiResource('exposiciones', ExposicionController::class);
    Route::apiResource('evaluaciones', EvaluacionController::class);
    Route::apiResource('grupos', GrupoController::class);
    Route::apiResource('equipos', EquipoController::class);
    Route::apiResource('materias', MateriaController::class);

    // --- Rutas Especiales (Lógica Extra) --- 
    Route::post('grupos/{id}/inscribir', [GrupoController::class, 'inscribirAlumnos']);
    Route::put('equipos/{id}/integrantes', [EquipoController::class, 'updateIntegrantes']);
    Route::get('/mis-calificaciones', [AlumnoController::class, 'misCalificaciones']);
});