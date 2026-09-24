<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any?}', function () {
    $spa = public_path('spa.html');
    if (is_file($spa)) {
        return response()->file($spa);
    }

    return view('welcome');
})->where('any', '^(?!api|storage|up|sanctum).*$');
