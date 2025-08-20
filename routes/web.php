<?php

use App\Models\Sale;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sales/{sale}/receipt', function (Sale $sale) {
    $next = request()->query('next', '/');

    return response()->view('print.receipt', [
        'sale' => $sale,
        'next' => $next,
    ]);
})->name('sales.receipt');
