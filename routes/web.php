<?php

use App\Http\Controllers\CashCollectionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExcelSettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShipController;
use App\Http\Controllers\ShipPackageController;
use App\Http\Controllers\ShipTicketSaleController;
use App\Http\Controllers\WhatsappDetailsController;
use Illuminate\Support\Facades\Route;

// Root shows login first
Route::get('/', fn () => view('auth.login'));

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/documentation', fn () => view('documentation'))->name('documentation');

// Route::get('/', [ShipTicketSaleController::class, 'bookingForm'])->name('booking.form');
Route::post('/ship-ticket/sales', [ShipTicketSaleController::class, 'publicStore'])->name('publicForm.store');
Route::get('/sales-create/success', [ShipTicketSaleController::class, 'success'])->name('publicForm.success');

// PUBLIC SHIP PACKAGE ROUTES

Route::get('/ship/packages/{id}', [ShipPackageController::class, 'showPackages'])->name('ship.packages');
Route::get('/ship-packages/{id}', [ShipPackageController::class, 'index']);

// AUTHENTICATED ROUTES
Route::middleware('auth')->group(function () {

    // PROFILE
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // SHIP TICKET SALES
    Route::resource('ship-ticket-sales', ShipTicketSaleController::class);
    Route::post('/ship-ticket-sales/check-duplicate', [ShipTicketSaleController::class, 'checkDuplicate']);

    // Sales listing / status
    Route::get('/sales/{status}', [ShipTicketSaleController::class, 'pendingCS'])
        ->where('status', implode('|', array_keys(config('sales.statuses'))))
        ->name('sales.data');
    Route::get('/sales/status/{status}', [ShipTicketSaleController::class, 'showPendingSales'])
        ->where('status', implode('|', array_keys(config('sales.statuses'))))
        ->name('sales.index');
    Route::put('/sales/status/{id}', [ShipTicketSaleController::class, 'update']);

    // Verification (Google Drive)
    Route::get('/gdrive/verify', [ShipTicketSaleController::class, 'printedCS'])->name('gdrive.verify');
    Route::get('/gdrive/re-verify/{id}', [ShipTicketSaleController::class, 'reprintedCS'])->name('gdrive.reverify');
    Route::put('/sale/verify/{id}/{status}', [ShipTicketSaleController::class, 'verify']);
    Route::get('/printed/sales', [ShipTicketSaleController::class, 'printedCS'])->name('printed_ticket_sales.verify');
    Route::post('/upload-pdf', [ShipTicketSaleController::class, 'upload'])->name('pdf.upload');

    // Printing / PDF
    Route::get('/print-all-ids', [ShipTicketSaleController::class, 'pdfPrintAll']);
    Route::get('/print-pdf/{id}', [ShipTicketSaleController::class, 'pdfDownload'])->name('print.pdf');
    Route::get('/tickets/open/{saleId}/{filename}', [ShipTicketSaleController::class, 'openTicket'])->name('tickets.open');

    // Delete sale
    Route::delete('/sale/delete/{id}', [ShipTicketSaleController::class, 'destroy']);

    // SHIPS
    Route::get('/ships-details', [ShipController::class, 'showTableList'])->name('ships.details');
    Route::resource('ships', ShipController::class);

    // COMPANIES
    Route::get('/companies-details', [CompanyController::class, 'showTableList'])->name('companies.details');
    Route::resource('companies', CompanyController::class);

    // REFUNDS
    Route::resource('refunds', RefundController::class);
    Route::post('/full/refunds', [RefundController::class, 'fullRefunds']);
    Route::post('/partial/refund/{id}', [RefundController::class, 'partialRefund']);
    Route::get('/all/refunded', [RefundController::class, 'refunded']);
    Route::get('/all/refundable', [RefundController::class, 'refundableCS']);
    Route::get('/refunded', [RefundController::class, 'showRefundedCS']);
    Route::put('/refunded/{id}', [RefundController::class, 'update']);

    // SHIP PACKAGES
    Route::post('/ship-packages', [ShipPackageController::class, 'store']);
    Route::put('/ship-packages/{id}', [ShipPackageController::class, 'update']);
    Route::delete('/ship-packages/{id}', [ShipPackageController::class, 'destroy']);

    // REPORTS
    Route::get('/admin/sales-reports', [ReportController::class, 'index']);
    Route::get('/reports', [ReportController::class, 'reports']);

    // PAYMENTS
    Route::post('/partial/paid/{id}', [PaymentController::class, 'partial_due_payment']);

    // CASH COLLECTIONS
    Route::get('/show/cash-collections', [CashCollectionController::class, 'showCashCollection']);
    Route::resource('cash-collections', CashCollectionController::class);

    // WHATSAPP
    Route::get('/admin/whatsapp', [WhatsappDetailsController::class, 'showTableList']);
    Route::resource('whatsapp', WhatsappDetailsController::class);

    // EXCEL SETTINGS
    Route::get('/excel', [ExcelSettingController::class, 'showTableList']);
    Route::apiResource('excel-settings', ExcelSettingController::class);

    // NOTIFICATIONS
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notification/verify/{id}', [NotificationController::class, 'verify'])->name('notification.verify');
});

require __DIR__.'/auth.php';
