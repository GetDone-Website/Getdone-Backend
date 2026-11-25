<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn () => response()->json(['name' => 'GetDone API', 'status' => 'ok', 'api' => '/api/health']));
