<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\GPT4oMiniController;
use App\Http\Controllers\Api\ResumeController;
use App\Http\Controllers\GeminiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | API Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register API routes for your application. These
 * | routes are loaded by the RouteServiceProvider within a group which
 * | is assigned the "api" middleware group. Enjoy building your API!
 * |
 */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Example API route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Resume parsing endpoint
Route::post('/generate-cv-ai', [GPT4oMiniController::class, 'generateCvAi']);
Route::post('/parse-resume', [ResumeController::class, 'parseResume']);
Route::post('/analyze-paragraph', [GPT4oMiniController::class, 'analyzeResume']);
Route::get('/migrate', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate');
    return 'Migrate Complete!';
});
