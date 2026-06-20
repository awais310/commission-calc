<?php

use App\Http\Controllers\Api\FormulaController;
use Illuminate\Support\Facades\Route;

Route::prefix('formulas')->group(function () {
    Route::get('/',                    [FormulaController::class, 'index']);
    Route::post('/validate',           [FormulaController::class, 'validateFormula']);
    Route::post('/',                   [FormulaController::class, 'store']);
    Route::get('/{formula}',           [FormulaController::class, 'show']);
    Route::post('/{formula}/activate', [FormulaController::class, 'activate']);
});
