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
use App\Http\Controllers\SaleDraftController;
use App\Http\Controllers\ShipController;
use App\Http\Controllers\ShipPackageController;
use App\Http\Controllers\ShipTicketSaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsappDetailsController;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)

// Root shows login first
Route::get('/', fn () => view('auth.login'));

// Public booking form
Route::post('/ship-ticket/sales', [ShipTicketSaleController::class, 'publicStore'])->name('publicForm.store');
Route::get('/sales-create/success', [ShipTicketSaleController::class, 'success'])->name('publicForm.success');

// Public package data used by the booking form.
Route::get('/ship-packages/{id}', [ShipPackageController::class, 'index'])->name('ship-packages.data');

// Authenticated routes use permission middleware; Super Admin bypasses these checks.

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'can:dashboard.view'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::view('/documentation', 'documentation')
        ->middleware('can:documentation.view')
        ->name('documentation');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // SALES — CREATE / STORE
    Route::get('sale-drafts/manage', [SaleDraftController::class, 'page'])
        ->middleware('permission.any:sale_drafts.view,sale_drafts.create')
        ->name('sale-drafts.manage');

    Route::middleware('can:sale_drafts.view')->group(function () {
        Route::get('sale-drafts', [SaleDraftController::class, 'index'])->name('sale-drafts.index');
        Route::get('sale-drafts/{sale_draft}', [SaleDraftController::class, 'show'])->name('sale-drafts.show');
    });

    Route::middleware('can:sale_drafts.create')->group(function () {
        Route::post('sale-drafts', [SaleDraftController::class, 'store'])->name('sale-drafts.store');
    });

    Route::middleware('can:sale_drafts.edit')->group(function () {
        Route::put('sale-drafts/{sale_draft}', [SaleDraftController::class, 'update'])->name('sale-drafts.update');
    });

    Route::middleware('can:sale_drafts.delete')->group(function () {
        Route::delete('sale-drafts/{sale_draft}', [SaleDraftController::class, 'destroy'])->name('sale-drafts.destroy');
    });

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
        Route::get('ship-ticket-sales/{ship_ticket_sale}', [ShipTicketSaleController::class, 'show'])
            ->middleware('can:sales.edit')
            ->name('ship-ticket-sales.show');

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
        Route::get('/payments/{payment}/proof', [PaymentController::class, 'proof'])
            ->middleware('can:payments.proof.view')
            ->name('payments.proof');
    });

    Route::get('ship-ticket-issue/{ship_ticket_sale}', [ShipTicketSaleController::class, 'ticketsIssueShow'])
        ->middleware('can:sales.issue')
        ->name('ship-ticket-issue.show');

    Route::middleware('can:sales.print')->group(function () {
        Route::get('/print-pdf/{id}', [ShipTicketSaleController::class, 'pdfDownload'])->name('print.pdf');
        Route::get('/tickets/open/{saleId}/{filename}', [ShipTicketSaleController::class, 'openTicket'])->name('tickets.open');
    });

    // SALES — EDIT / UPDATE
    Route::middleware('can:sales.edit')->group(function () {
        Route::put('ship-ticket-sales/{ship_ticket_sale}', [ShipTicketSaleController::class, 'update'])->name('ship-ticket-sales.update');
    });

    // SALES — VERIFY
    Route::put('/sale/verify/{id}/{status}', [ShipTicketSaleController::class, 'verify'])
        ->middleware('sales.transition')
        ->name('sale.verify');

    Route::put('/sale/bftn-received/{id}', [ShipTicketSaleController::class, 'markBftnReceived'])
        ->middleware('can:sales.bftn.receive')
        ->name('sale.bftn-received');

    Route::put('ship-ticket-issue/{ship_ticket_sale}', [ShipTicketSaleController::class, 'updateIssue'])
        ->middleware('can:sales.issue')
        ->name('ship-ticket-issue.update');

    // SALES — DELETE
    Route::middleware('can:sales.delete')->group(function () {
        Route::delete('ship-ticket-sales/{ship_ticket_sale}', [ShipTicketSaleController::class, 'destroy'])->name('ship-ticket-sales.destroy');
        Route::delete('/sale/delete/{id}', [ShipTicketSaleController::class, 'destroy'])->name('sale.destroy');
    });

    // Refunds module. Register `create` before the `{refund}` wildcard route.

    // REFUNDS — MANAGE (create, edit, process)
    Route::middleware('can:refunds.create')->group(function () {
        Route::get('refunds/create', [RefundController::class, 'create'])->name('refunds.create');
        Route::post('refunds', [RefundController::class, 'store'])->name('refunds.store');
        Route::post('/full/refunds', [RefundController::class, 'fullRefunds'])->name('refunds.full');
        Route::post('/partial/refund/{id}', [RefundController::class, 'partialRefund'])->name('refunds.partial');
    });

    Route::middleware('can:refunds.customer_payment')->group(function () {
        Route::post('/refunds/{id}/customer-payment', [RefundController::class, 'refundCustomer'])->name('refunds.customer-payment');
    });

    Route::middleware('can:refunds.edit')->group(function () {
        Route::get('refunds/{refund}/edit', [RefundController::class, 'edit'])->name('refunds.edit');
        Route::put('refunds/{refund}', [RefundController::class, 'update'])->name('refunds.update');
    });

    Route::middleware('can:refunds.cancel')->group(function () {
        Route::post('refunds/{id}/cancel', [RefundController::class, 'cancel'])->name('refunds.cancel');
    });

    Route::middleware('can:refunds.approve')->group(function () {
        Route::post('refunds/{id}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
    });

    Route::middleware('can:refunds.payment_details')->group(function () {
        Route::post('refunds/{id}/payment-details', [RefundController::class, 'addPaymentDetails'])->name('refunds.payment-details');
    });

    Route::middleware('can:refunds.delete')->group(function () {
        Route::delete('refunds/{refund}', [RefundController::class, 'destroy'])->name('refunds.destroy');
    });

    // REFUNDS — VIEW
    Route::middleware('can:refunds.view')->group(function () {
        Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::get('refunds/{refund}', [RefundController::class, 'show'])->name('refunds.show');
        Route::get('/all/refunded', [RefundController::class, 'refunded']);
        Route::get('/all/refund-requests', [RefundController::class, 'requested'])->name('refunds.requests.data');
        Route::get('/refund-requests', [RefundController::class, 'showRequested'])->name('refunds.requested');
        Route::get('/partner-approved-refunds', [RefundController::class, 'showApproved'])->name('refunds.approved');
        Route::get('/payment-details-added-refunds', [RefundController::class, 'showPaymentDetailsAdded'])->name('refunds.payment-details-added');
        Route::get('/refunded', [RefundController::class, 'showRefundedCS']);
        Route::get('/refunded/{sale}/details', [RefundController::class, 'refundedDetails'])->name('refunds.details');
    });

    Route::get('/all/refundable', [RefundController::class, 'refundableCS'])
        ->middleware('permission.any:refunds.view,refunds.create')
        ->name('refunds.refundable');

    // Master data modules

    // SHIPS
    Route::get('/ships-details', [ShipController::class, 'showTableList'])
        ->middleware('permission.any:ships.view,ships.create')
        ->name('ships.details');
    Route::middleware('can:ships.view')->group(function () {
        Route::resource('ships', ShipController::class)->only(['index', 'show']);
    });

    Route::middleware('can:ships.create')->group(function () {
        Route::resource('ships', ShipController::class)->only(['create', 'store']);
    });

    Route::middleware('can:ships.edit')->group(function () {
        Route::resource('ships', ShipController::class)->only(['edit', 'update']);
    });

    Route::middleware('can:ships.delete')->group(function () {
        Route::resource('ships', ShipController::class)->only(['destroy']);
    });

    // COMPANIES
    Route::get('/companies-details', [CompanyController::class, 'showTableList'])
        ->middleware('permission.any:companies.view,companies.create')
        ->name('companies.details');
    Route::middleware('can:companies.view')->group(function () {
        Route::resource('companies', CompanyController::class)->only(['index', 'show']);
    });

    Route::middleware('can:companies.create')->group(function () {
        Route::resource('companies', CompanyController::class)->only(['create', 'store']);
    });

    Route::middleware('can:companies.edit')->group(function () {
        Route::resource('companies', CompanyController::class)->only(['edit', 'update']);
    });

    Route::middleware('can:companies.delete')->group(function () {
        Route::resource('companies', CompanyController::class)->only(['destroy']);
    });

    // SHIP PACKAGES
    Route::get('/ship/packages/{id}', [ShipPackageController::class, 'showPackages'])
        ->middleware('permission.any:packages.view,packages.create')
        ->name('ship.packages');

    Route::get('/ship-packages/{id}/record', [ShipPackageController::class, 'showRecord'])
        ->middleware('can:packages.edit')
        ->name('ship-packages.record');

    Route::middleware('can:packages.create')->group(function () {
        Route::post('/ship-packages', [ShipPackageController::class, 'store'])->name('ship-packages.store');
    });

    Route::middleware('can:packages.edit')->group(function () {
        Route::put('/ship-packages/{id}', [ShipPackageController::class, 'update'])->name('ship-packages.update');
    });

    Route::middleware('can:packages.delete')->group(function () {
        Route::delete('/ship-packages/{id}', [ShipPackageController::class, 'destroy'])->name('ship-packages.destroy');
    });

    // Accounting modules

    // REPORTS
    Route::middleware('can:reports.view')->group(function () {
        Route::get('/admin/sales-reports', [ReportController::class, 'index'])->name('sales.reports');
        Route::get('/reports', [ReportController::class, 'reports'])->name('reports.data');
    });

    Route::middleware('can:extra_received.view')->group(function () {
        Route::get('/admin/extra-received', [ReportController::class, 'extraReceived'])->name('extra-received.index');
        Route::get('/extra-received/data', [ReportController::class, 'extraReceivedData'])->name('extra-received.data');
    });

    Route::middleware('can:extra_received.adjust')->group(function () {
        Route::post('/extra-received/{id}/adjust-to-other-fee', [ReportController::class, 'adjustExtraToOtherFee'])->name('extra-received.adjust');
    });

    Route::middleware('can:extra_received.refund')->group(function () {
        Route::post('/extra-received/{id}/refund', [ReportController::class, 'refundExtra'])->name('extra-received.refund');
    });

    // PAYMENTS
    Route::middleware('can:payments.due.collect')->group(function () {
        Route::post('/partial/paid/{id}', [PaymentController::class, 'partial_due_payment'])
            ->name('payments.partial');
    });

    // CASH COLLECTIONS
    Route::get('/show/cash-collections', [CashCollectionController::class, 'showCashCollection'])
        ->middleware('permission.any:cash_collections.view,cash_collections.create')
        ->name('cash-collections.page');
    Route::middleware('can:cash_collections.view')->group(function () {
        Route::resource('cash-collections', CashCollectionController::class)->only(['index', 'show']);
    });

    Route::middleware('can:cash_collections.create')->group(function () {
        Route::resource('cash-collections', CashCollectionController::class)->only(['create', 'store']);
    });

    Route::middleware('can:cash_collections.edit')->group(function () {
        Route::resource('cash-collections', CashCollectionController::class)->only(['edit', 'update']);
    });

    Route::middleware('can:cash_collections.delete')->group(function () {
        Route::resource('cash-collections', CashCollectionController::class)->only(['destroy']);
    });

    // Admin modules

    // USERS MANAGEMENT
    Route::get('/users-details', [UserController::class, 'showTableList'])
        ->middleware('permission.any:users.view,users.create')
        ->name('users.details');
    Route::middleware('can:users.view')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'show']);
    });

    Route::middleware('can:users.create')->group(function () {
        Route::resource('users', UserController::class)->only(['create', 'store']);
    });

    Route::middleware('can:users.edit')->group(function () {
        Route::resource('users', UserController::class)->only(['edit', 'update']);
    });

    Route::middleware('can:users.delete')->group(function () {
        Route::resource('users', UserController::class)->only(['destroy']);
    });

    // ROLES MANAGEMENT
    Route::get('/roles-details', [RoleController::class, 'showTableList'])
        ->middleware('permission.any:roles.view,roles.create')
        ->name('roles.details');
    Route::middleware('can:roles.view')->group(function () {
        Route::resource('roles', RoleController::class)->only(['index', 'show']);
    });

    Route::middleware('can:roles.create')->group(function () {
        Route::resource('roles', RoleController::class)->only(['create', 'store']);
    });

    Route::middleware('can:roles.edit')->group(function () {
        Route::resource('roles', RoleController::class)->only(['edit', 'update']);
    });

    Route::middleware('can:roles.delete')->group(function () {
        Route::resource('roles', RoleController::class)->only(['destroy']);
    });

    // PERMISSIONS MANAGEMENT
    Route::get('/permissions-details', [PermissionController::class, 'showTableList'])
        ->middleware('permission.any:permissions.view,permissions.create')
        ->name('permissions.details');
    Route::middleware('can:permissions.view')->group(function () {
        Route::resource('permissions', PermissionController::class)->only(['index', 'show']);
    });

    Route::middleware('can:permissions.create')->group(function () {
        Route::resource('permissions', PermissionController::class)->only(['create', 'store']);
    });

    Route::middleware('can:permissions.edit')->group(function () {
        Route::resource('permissions', PermissionController::class)->only(['edit', 'update']);
    });

    Route::middleware('can:permissions.delete')->group(function () {
        Route::resource('permissions', PermissionController::class)->only(['destroy']);
    });

    // WHATSAPP DETAILS
    Route::get('/admin/whatsapp', [WhatsappDetailsController::class, 'showTableList'])
        ->middleware('permission.any:whatsapp.view,whatsapp.create')
        ->name('whatsapp.page');
    Route::middleware('can:whatsapp.view')->group(function () {
        Route::resource('whatsapp', WhatsappDetailsController::class)->only(['index', 'show']);
    });

    Route::middleware('can:whatsapp.create')->group(function () {
        Route::resource('whatsapp', WhatsappDetailsController::class)->only(['create', 'store']);
    });

    Route::middleware('can:whatsapp.edit')->group(function () {
        Route::resource('whatsapp', WhatsappDetailsController::class)->only(['edit', 'update']);
    });

    Route::middleware('can:whatsapp.delete')->group(function () {
        Route::resource('whatsapp', WhatsappDetailsController::class)->only(['destroy']);
    });

    // EXCEL SETTINGS
    Route::get('/excel', [ExcelSettingController::class, 'showTableList'])
        ->middleware('permission.any:excel.view,excel.create')
        ->name('excel.page');
    Route::middleware('can:excel.view')->group(function () {
        Route::apiResource('excel-settings', ExcelSettingController::class)->only(['index', 'show']);
    });

    Route::middleware('can:excel.create')->group(function () {
        Route::apiResource('excel-settings', ExcelSettingController::class)->only(['store']);
    });

    Route::middleware('can:excel.edit')->group(function () {
        Route::apiResource('excel-settings', ExcelSettingController::class)->only(['update']);
    });

    Route::middleware('can:excel.delete')->group(function () {
        Route::apiResource('excel-settings', ExcelSettingController::class)->only(['destroy']);
    });

    // NOTIFICATIONS (sales BFTN deposit alerts)
    Route::middleware('can:notifications.view')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    });
    Route::middleware('can:notifications.verify')->group(function () {
        Route::get('/notification/verify/{notification}', [NotificationController::class, 'verify'])->name('notification.verify');
    });
});

require __DIR__.'/auth.php';
