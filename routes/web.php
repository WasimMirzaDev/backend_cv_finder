<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Api\ResumeController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/migrate', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate');
    return "Migrate Complete!";
});

Route::get('/static-file-runner-v1', [ResumeController::class, 'staticFileRunner']);

Route::get('/seed', function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed');
    return "Seed Complete!";
});
Route::get('/optimize-clear', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return "Optimize Clear Complete!";
});

Route::get('/storage-link', function () {
    if (file_exists(public_path('storage'))) {
        return 'Storage link already exists!';
    }
    
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return 'Storage link created successfully!';
});



Route::get('/resume/{id}/download', [ResumeController::class, 'download']);

Route::get('/logs', function () {
    $logFile = storage_path('logs/laravel.log');

    if (!File::exists($logFile)) {
        return response('Log file not found.', 404);
    }

    // Read last 500 lines for performance
    $lines = explode("\n", File::get($logFile));
    $lastLines = array_slice($lines, -500);

    return Response::make(
        nl2br(e(implode("\n", $lastLines))),
        200,
        ['Content-Type' => 'text/html']
    );
});