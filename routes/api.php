<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('sensors')->group(function () {
    Route::post('/login', [SensorController::class, 'login']);
    Route::patch('/update/{id}', [SensorController::class, 'edit']);
    Route::post('/register', [SensorController::class, 'register']);
    Route::post('/heartbeat', [SensorController::class, 'heartbeat']);
    Route::get('/uuid', [SensorController::class, 'login2']);
});

Route::prefix('users')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::patch('{id}/edit', [UserController::class, 'edit']);
});


Route::prefix('assets')->group(function () {
    Route::post('/register', [AssetController::class, 'register']);
    Route::patch('/update/{id}', [AssetController::class, 'edit']);
    Route::get('/{id}', [AssetController::class, 'detail']);
});


Route::prefix('organizations')->group(function () {
    Route::post('/create', [OrganizationController::class, 'create']);
    Route::patch('/update/{id}', [OrganizationController::class, 'edit']);
    Route::get('{id}/all', [OrganizationController::class, 'get_all']);
    Route::get('/{id}/sensors/all', [OrganizationController::class, 'get_sensor']);
    Route::get('/{id}/assets/all', [OrganizationController::class, 'get_asset']);
    Route::get('/{id}/roles/all', [OrganizationController::class, 'get_role']);
    Route::get('/{id}/users/all', [OrganizationController::class, 'get_user']);
    Route::post('/{id}/users/edit_role', [OrganizationController::class, 'edit_role']);
    Route::get('/{id}', [OrganizationController::class, 'profile']);
    Route::post('{id}/users/register', [UserController::class, 'register']);
});