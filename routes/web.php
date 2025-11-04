<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShipTicketSaleController;
use App\Http\Controllers\ShipController;

Route::resource('ships', ShipController::class);


Route::get('/', function () {
    return view('auth.login');
});
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('ship-ticket-sales', ShipTicketSaleController::class);
    Route::post('/ship-ticket-sales/check-duplicate', [ShipTicketSaleController::class, 'checkDuplicate']);
    Route::get('/sales/pending', [ShipTicketSaleController::class, 'pendingCS']);
    Route::get('/sales/status/pending', [ShipTicketSaleController::class, 'showPendingSales']);
    Route::put('/sales/status/{id}', [ShipTicketSaleController::class, 'update']);
    Route::delete('/sale/delete/{id}', [ShipTicketSaleController::class, 'destroy']);

     Route::get('/ships-details', [ShipController::class, 'showTableList']);
    Route::resource('ships', ShipController::class);

});
require __DIR__.'/auth.php';
