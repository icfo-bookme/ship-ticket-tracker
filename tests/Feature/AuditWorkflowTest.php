<?php

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PrintedTicket;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Finance\ExtraPaymentService;
use App\Services\Finance\PaymentService;
use App\Services\Refunds\RefundService;
use App\Services\Sales\SaleStatusWorkflowService;
use App\Services\Sales\ShipTicketSaleService;

beforeEach(function () {
    $this->actor = User::factory()->create();
    $this->actingAs($this->actor);
    $ship = Ship::create(['name' => 'Workflow Audit Ship', 'status' => 1]);
    $this->sale = ShipTicketSale::create([
        'customer_name' => 'Audit Customer', 'customer_mobile' => '01712345678',
        'ship_id' => $ship->id, 'sales_source' => 'Direct', 'journey_date' => '2026-10-28',
        'issued_date' => '2026-10-27', 'status' => 'pending', 'total_payable' => 500,
        'other_fee' => 0, 'discount_amount' => 0,
    ]);
    $package = ShipPackage::create(['ship_id' => $ship->id, 'name' => 'Deck', 'price' => 500]);
    $this->category = $this->sale->categories()->create([
        'package_id' => $package->id, 'quantity' => 1, 'unit_price' => 500, 'type' => 'departure',
    ]);
});

it('logs sale deletion and restoration without removing history', function () {
    $service = app(ShipTicketSaleService::class);
    $service->delete($this->sale->id);
    $service->restore($this->sale->id);
    $logs = AuditLog::query()->orderBy('id')->get();

    expect($logs->pluck('event')->all())->toBe(['deleted', 'restored'])
        ->and($logs[0]->old_values['customer_name'])->toBe('Audit Customer')
        ->and($logs[1]->new_values['customer_name'])->toBe('Audit Customer')
        ->and($logs[0]->context['sale_id'])->toBe($this->sale->id)
        ->and($this->sale->fresh()->trashed())->toBeFalse();
});

it('logs a due collection and its financial adjustments as one entry', function () {
    app(PaymentService::class)->payDue($this->sale->id, [
        'paid_amount' => 200, 'payment_method' => 'Cash', 'other_fee' => 20,
    ]);
    $log = AuditLog::query()->sole();

    expect($log->user_id)->toBe($this->actor->id)
        ->and($log->old_values['payments'])->toBe([])
        ->and($log->new_values['payments'][0]['received_amount'])->toBe('200.00')
        ->and($log->new_values['other_fee'])->toBe('20.00')
        ->and($log->new_values['total_payable'])->toBe('520.00');
});

it('logs BFTN settlement once for an equivalent repeated request', function () {
    $payment = $this->sale->payments()->create([
        'payment_method' => 'Bank Transfer', 'is_bftn' => true,
        'settlement_status' => 'pending', 'received_amount' => 200,
    ]);
    $service = app(SaleStatusWorkflowService::class);
    $service->markPaymentBftnReceived($payment->id, '2026-10-27 12:00:00');
    $service->markPaymentBftnReceived($payment->id, '2026-10-27 12:00:00');
    $log = AuditLog::query()->sole();

    expect($log->auditable_type)->toBe($payment->getMorphClass())
        ->and($log->old_values['settlement_status'])->toBe('pending')
        ->and($log->new_values['settlement_status'])->toBe('received')
        ->and($log->sale_id)->toBe($this->sale->id)
        ->and($log->context['sale_id'])->toBe($this->sale->id);
});

it('does not log a rejected BFTN settlement', function () {
    $payment = $this->sale->payments()->create(['payment_method' => 'Cash', 'is_bftn' => false, 'received_amount' => 100]);

    expect(fn () => app(SaleStatusWorkflowService::class)->markPaymentBftnReceived($payment->id, '2026-10-27 12:00:00'))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    expect(AuditLog::query()->count())->toBe(0);
});

it('logs status changes and skips a repeated unchanged transition', function () {
    $service = app(SaleStatusWorkflowService::class);
    $service->verify($this->sale->id, 'payment-verified');
    $service->verify($this->sale->id, 'payment-verified');
    $log = AuditLog::query()->sole();

    expect($log->event)->toBe('status_changed')
        ->and($log->old_values)->toBe(['status' => 'pending'])
        ->and($log->new_values)->toBe(['status' => 'payment-verified']);
});

it('logs each sale affected by a grouped status change', function () {
    $second = $this->sale->replicate();
    $second->save();
    foreach ([$this->sale, $second] as $sale) {
        $sale->update(['status' => 'ticket-issued', 'total_payable' => 0]);
        PrintedTicket::create(['sales_id' => $sale->id, 'filename' => $sale->id.'.pdf', 'group_by_id' => $this->sale->id]);
    }
    app(SaleStatusWorkflowService::class)->verify($this->sale->id, 'ticket-printed');

    expect(AuditLog::query()->where('event', 'status_changed')->pluck('auditable_id')->sort()->values()->all())
        ->toBe([$this->sale->id, $second->id]);
});

it('retains the complete refund lifecycle and sale context after deletion', function () {
    $service = app(RefundService::class);
    $refund = $service->create([
        'sales_id' => $this->sale->id, 'refunded_number_of_tickets' => 1,
        'refunded_amount' => 100, 'gross_refund_amount' => 100, 'customer_refund_amount' => 100,
    ]);
    $service->approve($refund);
    $service->addPaymentDetails($refund, 'Cash desk');
    $service->cancel($refund);
    $service->delete($refund);
    $logs = AuditLog::query()->orderBy('id')->get();

    expect($logs->pluck('event')->all())->toBe(['created', 'status_changed', 'updated', 'status_changed', 'deleted'])
        ->and($logs[2]->new_values['refund_payment_details'])->toBe('Cash desk')
        ->and($logs->last()->old_values['status'])->toBe('cancelled')
        ->and($logs->last()->sale_id)->toBe($this->sale->id)
        ->and($logs->last()->context['sale_id'])->toBe($this->sale->id)
        ->and(Refund::query()->find($refund->id))->toBeNull();
});

it('captures refund tickets and customer payouts', function () {
    $this->sale->payments()->create(['payment_method' => 'Cash', 'received_amount' => 500]);
    $service = app(RefundService::class);
    $service->partialRefund($this->sale, [
        'ticket_selections' => [['category_id' => $this->category->id, 'refunded_quantity' => 1]],
        'customer_charge_percent' => 0, 'partner_share_percent' => 0,
    ]);
    $refund = Refund::query()->sole();
    expect(AuditLog::query()->sole()->new_values['tickets'][0]['category_name'])->toBe('Deck');

    $service->refundCustomer($refund, ['payment_method' => 'Cash']);
    $log = AuditLog::query()->latest('id')->firstOrFail();

    expect($log->new_values['customer_payments'][0]['amount'])->toBe('500.00')
        ->and($log->new_values['status'])->toBe('completed');
});

it('rolls back due collection if audit persistence fails', function () {
    $this->partialMock(AuditLogService::class, function ($mock) {
        $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('Audit failed'));
    });

    expect(fn () => app(PaymentService::class)->payDue($this->sale->id, [
        'paid_amount' => 200, 'payment_method' => 'Cash', 'other_fee' => 20,
    ]))->toThrow(RuntimeException::class, 'Audit failed');

    expect(Payment::query()->count())->toBe(0)
        ->and((float) $this->sale->fresh()->total_payable)->toBe(500.0)
        ->and(AuditLog::query()->count())->toBe(0);
});

it('logs a bulk refund request with its category details', function () {
    $this->sale->update(['status' => 'ticket-issued']);
    app(RefundService::class)->fullRefund([$this->sale->id]);
    $log = AuditLog::query()->sole();

    expect($log->event)->toBe('created')
        ->and($log->new_values['refund_type'])->toBe('bulk')
        ->and($log->new_values['tickets'][0]['refunded_quantity'])->toBe(1)
        ->and($log->context['sale_id'])->toBe($this->sale->id);
});

it('logs an extra payment adjustment and skips an unchanged repeated adjustment', function () {
    $this->sale->payments()->create(['payment_method' => 'Cash', 'received_amount' => 600]);
    $service = app(ExtraPaymentService::class);
    $service->adjustToOtherFee($this->sale->id);
    $service->adjustToOtherFee($this->sale->id);
    $log = AuditLog::query()->sole();

    expect($log->new_values['other_fee'])->toBe('100.00')
        ->and($log->new_values['total_payable'])->toBe('600.00');
});

it('logs an extra payment refund request only once', function () {
    $this->sale->payments()->create(['payment_method' => 'Cash', 'received_amount' => 600]);
    $service = app(ExtraPaymentService::class);
    $service->requestRefund($this->sale->id);
    $service->requestRefund($this->sale->id);
    $log = AuditLog::query()->sole();

    expect($log->new_values['refund_type'])->toBe('extra_payment')
        ->and($log->new_values['gross_refund_amount'])->toBe('100.00')
        ->and($log->context['sale_id'])->toBe($this->sale->id);
});
