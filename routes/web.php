<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShipTicketSaleController;
use App\Http\Controllers\ShipController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShipPackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CashCollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExcelSettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WhatsappDetailsController;

Route::resource('ships', ShipController::class);


// NOTE: Welcome / booking form temporarily disabled (commented out).
// Route::get('/', [ShipTicketSaleController::class, 'bookingForm'])->name('booking.form');

// Root shows login first
Route::get('/', function () {
    return view('auth.login');
});
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');;
// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/documentation', function () {
    return view('documentation');
})->name('documentation');
Route::post('/ship-ticket/sales', [ShipTicketSaleController::class, 'publicStore'])->name('publicForm.store');
Route::get('/sales-create/success', [ShipTicketSaleController::class, 'success'])->name('publicForm.success');
Route::post('/ship-ticket-sales/check-duplicate', [ShipTicketSaleController::class, 'checkDuplicate']);
Route::get('/ship/packages/{id}', [ShipPackageController::class, 'showPackages']);
Route::get('/ship-packages/{id}', [ShipPackageController::class, 'index']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('ship-ticket-sales', ShipTicketSaleController::class);

    Route::get('/sales/{status}', [ShipTicketSaleController::class, 'pendingCS']);
    Route::get('/gdrive/verify', [ShipTicketSaleController::class, 'printedCS'])->name('gdrive.verify') ;
    Route::get('/gdrive/re-verify/{id}', [ShipTicketSaleController::class, 'reprintedCS'])->name('gdrive.reverify') ;
    Route::get('/sales/status/{status}', [ShipTicketSaleController::class, 'showPendingSales']);
    Route::put('/sales/status/{id}', [ShipTicketSaleController::class, 'update']);
    Route::put('/sale/verify/{id}/{status}', [ShipTicketSaleController::class, 'verify']);
    Route::delete('/sale/delete/{id}', [ShipTicketSaleController::class, 'destroy']);
    Route::get('/printed/sales', [ShipTicketSaleController::class, 'printedCS'])->name('printed_ticket_sales.verify');
    Route::post('/upload-pdf', [ShipTicketSaleController::class, 'upload'])
        ->name('pdf.upload');

    Route::get('/ships-details', [ShipController::class, 'showTableList']);
    Route::resource('ships', ShipController::class);

    Route::get('/companies-details', [CompanyController::class, 'showTableList']);
    Route::resource('companies', CompanyController::class);

    Route::resource('refunds', RefundController::class);
    Route::post('/full/refunds', [RefundController::class, 'fullRefunds']);
    Route::post('/partial/refund/{id}', [RefundController::class, 'partialRefund']);
    Route::get('/all/refunded', [RefundController::class, 'refunded']);
    Route::get('/all/refundable', [RefundController::class, 'refundableCS']);
    Route::get('/refunded', [RefundController::class, 'showRefundedCS']);
    Route::put('/refunded/{id}', [RefundController::class, 'update']);


    Route::post('/ship-packages', [ShipPackageController::class, 'store']);
    Route::put('/ship-packages/{id}', [ShipPackageController::class, 'update']);
    Route::delete('/ship-packages/{id}', [ShipPackageController::class, 'destroy']);

    Route::get('/admin/sales-reports', [ReportController::class, 'index']);
    Route::get('/reports', [ReportController::class, 'reports']);

    Route::post('/partial/paid/{id}', [PaymentController::class, 'partial_due_payment']);

    Route::get('/show/cash-collections', [CashCollectionController::class, 'showCashCollection']);
    Route::resource('cash-collections', CashCollectionController::class);

    Route::get('/print-all-ids', [ShipTicketSaleController::class, 'pdfPrintAll']);
    Route::get('/print-pdf/{id}', [ShipTicketSaleController::class, 'pdfDownload'])->name('print.pdf');

    Route::get('/admin/whatsapp', [WhatsappDetailsController::class, 'showTableList']);
    Route::resource('whatsapp', WhatsappDetailsController::class);

    Route::get(
        '/tickets/open/{saleId}/{filename}',
        [ShipTicketSaleController::class, 'openTicket']
    )->name('tickets.open');
    Route::get('/excel', [ExcelSettingController::class, 'showTableList']);
    Route::apiResource('excel-settings', ExcelSettingController::class);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notification/verify/{id}', [NotificationController::class, 'verify'])
    ->name('notification.verify');
});


require __DIR__ . '/auth.php';
