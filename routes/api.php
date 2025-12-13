<?php

use App\Http\Controllers\TrazabilidadController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\LogisticaPaquetesProxyController;

use Illuminate\Support\Facades\Route;

Route::prefix('gateway')->group(function () {
    Route::get('/trazabilidad/{ci}', [TrazabilidadController::class, 'getTraceabilityByCi']);
    Route::get('/trazabilidad/paquete/{codigo}', [TrazabilidadController::class, 'getTraceabilityByCodigo']);
    Route::get('/trazabilidad/vehiculo/{placa}', [TrazabilidadController::class, 'getTraceabilityByPlaca']);
    Route::get('/trazabilidad/solicitante/{ci_solicitante}', [TrazabilidadController::class, 'getTraceabilityBySolicitante']);
    Route::get('/trazabilidad/provincia/{provincia}', [TrazabilidadController::class, 'getTraceabilityByProvincia']);

    Route::get('trazabilidad/animales/especie/{especie}', [TrazabilidadController::class, 'getAnimalesPorEspecie']);
    Route::get('trazabilidad/animales/liberados', [TrazabilidadController::class, 'getAnimalesLiberados']);
    Route::get(
    '/registro/ci/{ci}',
    [RegistroController::class, 'getSimplePersonaByCi']
    );
    Route::prefix('logistica')->group(function () {
        Route::get('paquetes/pendientes', [LogisticaPaquetesProxyController::class, 'pendientes']);
        Route::patch('paquetes/{id}/armar', [LogisticaPaquetesProxyController::class, 'armar'])
            ->whereNumber('id');
        Route::get('paquetes/destino-voluntario/{codigo}', [LogisticaPaquetesProxyController::class, 'destinoVoluntario']);
    });
});

