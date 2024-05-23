<?php

use App\Http\Controllers\ProjectCommandController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('projects')->controller(ProjectController::class)->group(function () {

    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::post('/{id}/publish', 'publish');
    Route::post('/{id}/pull', 'pullCode');

    Route::post('/{id}/env/copy', 'copyEnv');
    Route::get('/{id}/env', 'showEnv');
    Route::put('/{id}/env', 'updateEnv');

});

Route::prefix('projects/{id}/commands')->controller(ProjectCommandController::class)->group(function(){
    
    Route::post('/', 'store');

});
