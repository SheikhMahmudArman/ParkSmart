<?php

use Illuminate\Support\Facades\Route;

Route::get('/{path?}', function () {
    $index = public_path('app/index.html');
    if (is_file($index)) {
        return response()->file($index, ['Cache-Control' => 'no-cache']);
    }

    return view('welcome');
})->where('path', '^(?!api(?:/|$)|up$).*$');
