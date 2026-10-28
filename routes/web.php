<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'Online Course Platform REST API',
        'status' => 'ok',
    ]);
});
