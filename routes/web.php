<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShipTicketSaleController;
use App\Http\Controllers\ShipController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ShipPackageController;

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
    Route::get('/sales/{status}', [ShipTicketSaleController::class, 'pendingCS']);
    Route::get('/sales/status/{status}', [ShipTicketSaleController::class, 'showPendingSales']);
    Route::put('/sales/status/{id}', [ShipTicketSaleController::class, 'update']);
    Route::put('/sale/verify/{id}/{status}', [ShipTicketSaleController::class, 'verify']);
    Route::delete('/sale/delete/{id}', [ShipTicketSaleController::class, 'destroy']);

    Route::get('/ships-details', [ShipController::class, 'showTableList']);
    Route::resource('ships', ShipController::class);

    Route::get('/companies-details', [CompanyController::class, 'showTableList']);
    Route::resource('companies', CompanyController::class);

    Route::resource('refunds', RefundController::class);
    Route::post('/full/refunds', [RefundController::class, 'fullRefunds']);
    Route::post('/partial/refund/{id}', [RefundController::class, 'partialRefund']);
    Route::get('/all/refunded', [RefundController::class, 'refunded']);
    Route::get('/refunded', [RefundController::class, 'showRefundedCS']);
    Route::put('/refunded/{id}', [RefundController::class, 'update']);

    Route::get('/ship/packages/{id}', [ShipPackageController::class, 'showPackages']);
    Route::get('/ship-packages/{id}', [ShipPackageController::class, 'index']);
    Route::post('/ship-packages', [ShipPackageController::class, 'store']);
    Route::put('/ship-packages/{id}', [ShipPackageController::class, 'update']);
    Route::delete('/ship-packages/{id}', [ShipPackageController::class, 'destroy']);

    
});
require __DIR__.'/auth.php';
