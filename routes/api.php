<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Endpoint REST API (mis. untuk scanner eksternal / integrasi pihak ketiga)
| akan ditambahkan di sini pada tahap modul Stock Opname & Redeem,
| diamankan dengan Laravel Sanctum token.
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/user', function (\Illuminate\Http\Request $request) {
    return $request->user();
});
