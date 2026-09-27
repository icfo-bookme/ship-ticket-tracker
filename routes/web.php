<?php

use App\Http\Controllers\CashCollectionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExcelSettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShipController;
use App\Http\Controllers\ShipPackageController;
use App\Http\Controllers\ShipTicketSaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsappDetailsController;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)

// Root shows login first
Route::get('/', fn () => view('auth.login'));

Route::get('/documentation', fn () => view('documentation'))->name('documentation');

// Public booking form
Route::post('/ship-ticket/sales', [ShipTicketSaleController::class, 'publicStore'])->name('publicForm.store');
Route::get('/sales-create/success', [ShipTicketSaleController::class, 'success'])->name('publicForm.success');

// Public ship packages (booking form)
Route::get('/ship/packages/{id}', [ShipPackageController::class, 'showPackages'])->name('ship.packages');
Route::get('/ship-packages/{id}', [ShipPackageController::class, 'index']);

// Authenticated routes use permission middleware; Super Admin bypasses these checks.

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // SALES — CREATE / STORE
    Route::middleware('can:sales.create')->group(function () {
        Route::get('ship-ticket-sales/create', [ShipTicketSaleController::class, 'create'])->name('ship-ticket-sales.create');
        Route::post('ship-ticket-sales', [ShipTicketSaleController::class, 'store'])->name('ship-ticket-sales.store');
        Route::post('ship-ticket-sales/check-duplicate', [ShipTicketSaleController::class, 'checkDuplicate']);
    });

    // SALES — VIEW (listings, details, printing)
    Route::middleware('can:sales.view')->group(function () {
        // The index route shows the pending list, so it needs the pending status permission.
        Route::get('ship-ticket-sales', [ShipTicketSaleController::class, 'index'])
            ->middleware('sales.status')
            ->name('ship-ticket-sales.index');
        Route::get('ship-ticket-sales/{ship_ticket_sale}', [ShipTicketSaleController::class, 'show'])->name('ship-ticket-sales.show');

        Route::get('ship-ticket-issue/{ship_ticket_sale}', [ShipTicketSaleController::class, 'ticketsIssueShow'])->name('ship-ticket-issue.show');

        // Sales listing / status (each status needs its own sales.status.{status} permission)
        Route::get('/sales/{status}', [ShipTicketSaleController::class, 'pendingCS'])
            ->where('status', implode('|', array_keys(config('sales.statuses'))))
            ->middleware('sales.status')
            ->name('sales.data');
        Route::get('/sales/status/{status}', [ShipTicketSaleController::class, 'showPendingSales'])
            ->where('status', implode('|', array_keys(config('sales.statuses'))))
            ->middleware('sales.status')
            ->name('sales.index');

        // Payment proof (payment screenshot) — served from the private disk
        Route::get('/payments/{payment}/proof', [PaymentController::class, 'proof'])->name('payments.proof');

        // Printing / PDF
        Route::get('/print-all-ids', [ShipTicketSaleController::class, 'pdfPrintAll']);
        Route::get('/print-pdf/{id}', [ShipTicketSaleController::class, 'pdfDownload'])->name('print.pdf');
        Route::get('/tickets/open/{saleId}/{filename}', [ShipTicketSaleController::class, 'openTicket'])->name('tickets.open');
    });

    // SALES — EDIT / UPDATE
    Route::middleware('can:sales.edit')->group(function () {
        Route::put('ship-ticket-sales/{ship_ticket_sale}', [ShipTicketSaleController::class, 'update'])->name('ship-ticket-sales.update');
    });

    // SALES — VERIFY
    Route::middleware('can:sales.verify')->group(function () {
        Route::put('/sale/verify/{id}/{status}', [ShipTicketSaleController::class, 'verify']);
        Route::put('/sale/bftn-received/{id}', [ShipTicketSaleController::class, 'markBftnReceived']);
        Route::put('ship-ticket-issue/{ship_ticket_sale}', [ShipTicketSaleController::class, 'updateIssue'])->name('ship-ticket-issue.update');
    });

    // SALES — DELETE
    Route::middleware('can:sales.delete')->group(function () {
        Route::delete('ship-ticket-sales/{ship_ticket_sale}', [ShipTicketSaleController::class, 'destroy'])->name('ship-ticket-sales.destroy');
        Route::delete('/sale/delete/{id}', [ShipTicketSaleController::class, 'destroy']);
    });

    // Refunds module. Register `create` before the `{refund}` wildcard route.

    // REFUNDS — MANAGE (create, edit, process)
    Route::middleware('can:refunds.manage')->group(function () {
        Route::get('refunds/create', [RefundController::class, 'create'])->name('refunds.create');
        Route::post('refunds', [RefundController::class, 'store'])->name('refunds.store');
        Route::post('/full/refunds', [RefundController::class, 'fullRefunds']);
        Route::post('/partial/refund/{id}', [RefundController::class, 'partialRefund']);
        Route::post('/refunds/{id}/partner-payment', [RefundController::class, 'receivePartnerPayment']);
        Route::post('/refunds/{id}/customer-payment', [RefundController::class, 'refundCustomer']);
        Route::get('refunds/{refund}/edit', [RefundController::class, 'edit'])->name('refunds.edit');
        Route::put('refunds/{refund}', [RefundController::class, 'update'])->name('refunds.update');
        Route::post('refunds/{id}/cancel', [RefundController::class, 'cancel'])->name('refunds.cancel');
        Route::put('/refunded/{id}', [RefundController::class, 'update']);
        Route::delete('refunds/{refund}', [RefundController::class, 'destroy'])->name('refunds.destroy');
    });

    // REFUNDS — VIEW
    Route::middleware('can:refunds.view')->group(function () {
        Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::get('refunds/{refund}', [RefundController::class, 'show'])->name('refunds.show');
        Route::get('/all/refunded', [RefundController::class, 'refunded']);
        Route::get('/all/refund-requests', [RefundController::class, 'requested']);
        Route::get('/refund-requests', [RefundController::class, 'showRequested'])->name('refunds.requested');
        Route::get('/all/refundable', [RefundController::class, 'refundableCS']);
        Route::get('/refunded', [RefundController::class, 'showRefundedCS']);
    });

    // Master data modules

    // SHIPS
    Route::middleware('can:ships.manage')->group(function () {
        Route::get('/ships-details', [ShipController::class, 'showTableList'])->name('ships.details');
        Route::resource('ships', ShipController::class);
    });

    // COMPANIES
    Route::middleware('can:companies.manage')->group(function () {
        Route::get('/companies-details', [CompanyController::class, 'showTableList'])->name('companies.details');
        Route::resource('companies', CompanyController::class);
    });

    // SHIP PACKAGES (public show routes are defined at the top)
    Route::middleware('can:packages.manage')->group(function () {
        Route::post('/ship-packages', [ShipPackageController::class, 'store']);
        Route::put('/ship-packages/{id}', [ShipPackageController::class, 'update']);
        Route::delete('/ship-packages/{id}', [ShipPackageController::class, 'destroy']);
    });

    // Accounting modules

    // REPORTS
    Route::middleware('can:reports.view')->group(function () {
        Route::get('/admin/sales-reports', [ReportController::class, 'index'])->name('sales.reports');
        Route::get('/reports', [ReportController::class, 'reports'])->name('reports.data');
    });

    // PAYMENTS
    Route::middleware('can:payments.manage')->group(function () {
        Route::post('/partial/paid/{id}', [PaymentController::class, 'partial_due_payment']);
    });

    // CASH COLLECTIONS
    Route::middleware('can:cash.manage')->group(function () {
        Route::get('/show/cash-collections', [CashCollectionController::class, 'showCashCollection']);
        Route::resource('cash-collections', CashCollectionController::class);
    });

    // Admin modules

    // USERS MANAGEMENT
    Route::middleware('can:users.manage')->group(function () {
        Route::get('/users-details', [UserController::class, 'showTableList'])->name('users.details');
        Route::resource('users', UserController::class);
    });

    // ROLES MANAGEMENT
    Route::middleware('can:roles.manage')->group(function () {
        Route::get('/roles-details', [RoleController::class, 'showTableList'])->name('roles.details');
        Route::resource('roles', RoleController::class);
    });

    // PERMISSIONS MANAGEMENT
    Route::middleware('can:permissions.manage')->group(function () {
        Route::get('/permissions-details', [PermissionController::class, 'showTableList'])->name('permissions.details');
        Route::resource('permissions', PermissionController::class);
    });

    // WHATSAPP DETAILS
    Route::middleware('can:whatsapp.manage')->group(function () {
        Route::get('/admin/whatsapp', [WhatsappDetailsController::class, 'showTableList']);
        Route::resource('whatsapp', WhatsappDetailsController::class);
    });

    // EXCEL SETTINGS
    Route::middleware('can:excel.manage')->group(function () {
        Route::get('/excel', [ExcelSettingController::class, 'showTableList']);
        Route::apiResource('excel-settings', ExcelSettingController::class);
    });

    // NOTIFICATIONS (sales BFTN deposit alerts)
    Route::middleware('can:sales.view')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index']);
    });
    Route::middleware('can:sales.verify')->group(function () {
        Route::get('/notification/verify/{notification}', [NotificationController::class, 'verify'])->name('notification.verify');
    });
});

require __DIR__.'/auth.php';
