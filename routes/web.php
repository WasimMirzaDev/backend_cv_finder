<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/migrate', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate');
    return "Migrate Complete!";
});

Route::get('/seed', function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed');
    return "Seed Complete!";
});
Route::get('/optimize-clear', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return "Optimize Clear Complete!";
});