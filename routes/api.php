<?php

use App\Http\Controllers\TrazabilidadController;
use Illuminate\Support\Facades\Route;

Route::prefix('gateway')->group(function () {
    Route::get('/trazabilidad/{ci}', [TrazabilidadController::class, 'getTraceabilityByCi']);
    Route::get('/trazabilidad/paquete/{codigo}', [TrazabilidadController::class, 'getTraceabilityByCodigo']);
    Route::get('/trazabilidad/vehiculo/{placa}', [TrazabilidadController::class, 'getTraceabilityByPlaca']);
    Route::get('/trazabilidad/solicitante/{ci_solicitante}', [TrazabilidadController::class, 'getTraceabilityBySolicitante']);
    Route::get('/trazabilidad/provincia/{provincia}', [TrazabilidadController::class, 'getTraceabilityByProvincia']);
});