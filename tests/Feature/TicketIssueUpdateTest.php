<?php

use App\Models\Company;
use App\Models\PrintedTicket;
use App\Models\Ship;
use App\Models\Shipment;
use App\Models\ShipTicketSale;
use App\Models\User;
use App\Services\Sales\SaleGroupingService;
use App\Services\Sales\SaleStatusWorkflowService;
use App\Services\SteadfastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('only saves PDF and grouping data from the ticket issue page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.verify', 'web'),
    ]);
    $ship = Ship::create(['name' => 'Test Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Test Company', 'status' => 1]);
    $sale = ShipTicketSale::create([
        'customer_name' => 'Original Customer',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-26',
        'number_of_ticket' => 1,
        'status' => 'payment-verified',
    ]);
    $nextSale = $sale->replicate();
    $nextSale->customer_name = 'Next Customer';
    $nextSale->save();
    $this->actingAs($user)
        ->get(route('ship-ticket-issue.show', $sale))
        ->assertOk()
        ->assertDontSee('Collect from office')
        ->assertDontSee('Copy Address')
        ->assertDontSee('Add Another Payment Record')
        ->assertDontSee('Add Co-Passenger');

    $this->actingAs($user)
        ->put(route('ship-ticket-issue.update', $sale), [
            'customer_name' => 'Tampered Customer',
            'ticket_fee' => 1,
            'additional_pdf' => ['1' => '01712345678-2.pdf'],
        ])
        ->assertRedirect(route('ship-ticket-issue.show', $nextSale));

    expect($sale->fresh()->customer_name)->toBe('Original Customer')
        ->and((float) $sale->fresh()->ticket_fee)->toBe(500.0)
        ->and($sale->fresh()->status)->toBe('ticket-issued')
        ->and($sale->printedTickets()->pluck('filename')->all())->toBe(['01712345678-2.pdf']);
});

it('keeps grouped sales ticket issued until the ticket printed action', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.verify', 'web'),
    ]);
    $ship = Ship::create(['name' => 'Test Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Test Company', 'status' => 1]);
    $referenceSale = ShipTicketSale::create([
        'customer_name' => 'Printed Customer',
        'address' => '12 Road, Dhaka',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-26',
        'number_of_ticket' => 1,
        'status' => 'ticket-printed',
    ]);
    $sale = $referenceSale->replicate();
    $sale->customer_name = 'New Customer';
    $sale->status = 'payment-verified';
    $sale->save();

    PrintedTicket::create([
        'sales_id' => $referenceSale->id,
        'filename' => 'reference.pdf',
        'group_by_id' => $referenceSale->id,
    ]);

    $this->actingAs($user)
        ->put(route('ship-ticket-issue.update', $sale), [
            'group_tickets' => 'yes',
            'group_by_id' => $referenceSale->id,
            'pdf' => ['01712345678-2.pdf'],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($sale->fresh()->status)->toBe('ticket-issued')
        ->and($referenceSale->fresh()->status)->toBe('ticket-printed')
        ->and($sale->printedTickets()->first()->group_by_id)->toBe($referenceSale->id);

    $workflow = new SaleStatusWorkflowService(Mockery::mock(SteadfastService::class), app(SaleGroupingService::class));
    $workflow->verify($referenceSale->id, 'ticket-printed');

    expect($sale->fresh()->status)->toBe('ticket-printed');
});

it('allows a new sale to join a group whose parcel is already created', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.verify', 'web'),
    ]);
    $ship = Ship::create(['name' => 'Test Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Test Company', 'status' => 1]);
    $referenceSale = ShipTicketSale::create([
        'customer_name' => 'Parcel Customer',
        'address' => '12 Road, Dhaka',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-26',
        'number_of_ticket' => 1,
        'status' => 'shipment_id_entered',
    ]);
    $sale = $referenceSale->replicate();
    $sale->customer_name = 'New Customer';
    $sale->status = 'payment-verified';
    $sale->save();
    PrintedTicket::create([
        'sales_id' => $referenceSale->id,
        'filename' => '01712345678-1.pdf',
        'group_by_id' => $referenceSale->id,
    ]);
    Shipment::create(['ticket_id' => $referenceSale->id, 'shipment_id' => 'SHP-123']);

    $this->actingAs($user)
        ->get(route('ship-ticket-issue.show', $sale))
        ->assertOk()
        ->assertSee('name="group_tickets"', false)
        ->assertSee('value="'.$referenceSale->id.'"', false);
});

it('finds group candidates by the sale WhatsApp number instead of the PDF filename prefix', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.verify', 'web'),
    ]);
    $ship = Ship::create(['name' => 'Test Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Test Company', 'status' => 1]);
    $referenceSale = ShipTicketSale::create([
        'customer_name' => 'WhatsApp Match',
        'address' => '12 Road, Dhaka',
        'customer_mobile' => '01767576768',
        'whatsapp' => '01767576768',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-26',
        'number_of_ticket' => 1,
        'status' => 'ticket-issued',
    ]);
    $sale = $referenceSale->replicate();
    $sale->customer_name = 'New WhatsApp Match';
    $sale->status = 'ticket-issued';
    $sale->save();
    PrintedTicket::create([
        'sales_id' => $referenceSale->id,
        'filename' => 'legacy-ticket-3.pdf',
        'group_by_id' => $referenceSale->id,
    ]);
    PrintedTicket::create([
        'sales_id' => $sale->id,
        'filename' => '01767576768-4.pdf',
        'group_by_id' => $referenceSale->id,
    ]);

    $this->actingAs($user)
        ->get(route('ship-ticket-issue.show', $sale))
        ->assertOk()
        ->assertSee('name="group_tickets"', false)
        ->assertSee('let currentPdfNumber = 5;', false)
        ->assertSee('value="'.$referenceSale->id.'"', false);
});

it('rejects grouping with collected or shipped sales', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.verify', 'web'),
    ]);
    $ship = Ship::create(['name' => 'Test Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Test Company', 'status' => 1]);

    foreach (['collect_from_office', 'shipped'] as $status) {
        $referenceSale = ShipTicketSale::create([
            'customer_name' => 'Ineligible Reference',
            'customer_mobile' => '01712345678',
            'whatsapp' => '01712345678',
            'sales_source' => 'Direct',
            'ship_id' => $ship->id,
            'company_id' => $company->id,
            'journey_date' => '2026-10-01',
            'ticket_fee' => 500,
            'other_fee' => 0,
            'discount_amount' => 0,
            'total_payable' => 500,
            'received_amount' => 500,
            'due_amount' => 0,
            'issued_date' => '2026-09-26',
            'number_of_ticket' => 1,
            'status' => $status,
        ]);
        PrintedTicket::create([
            'sales_id' => $referenceSale->id,
            'filename' => '01712345678-1.pdf',
            'group_by_id' => $referenceSale->id,
        ]);
        $sale = $referenceSale->replicate();
        $sale->customer_name = 'Sale To Group';
        $sale->status = 'payment-verified';
        $sale->save();

        $this->actingAs($user)
            ->get(route('ship-ticket-issue.show', $sale))
            ->assertOk()
            ->assertDontSee('name="group_tickets"', false);

        $this->actingAs($user)
            ->from(route('ship-ticket-issue.show', $sale))
            ->put(route('ship-ticket-issue.update', $sale), [
                'group_tickets' => 'yes',
                'group_by_id' => $referenceSale->id,
                'pdf' => ['01712345678-2.pdf'],
            ])
            ->assertRedirect(route('ship-ticket-issue.show', $sale))
            ->assertSessionHasErrors('group_by_id');

        expect($sale->fresh()->status)->toBe('payment-verified')
            ->and($sale->printedTickets()->exists())->toBeFalse();
    }
});

it('shows a mixed delivery mode message and rejects grouping when the current sale is office collection', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.verify', 'web'),
    ]);
    $ship = Ship::create(['name' => 'Test Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Test Company', 'status' => 1]);
    $referenceSale = ShipTicketSale::create([
        'customer_name' => 'Courier Sale',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-26',
        'number_of_ticket' => 1,
        'status' => 'ticket-printed',
    ]);
    PrintedTicket::create([
        'sales_id' => $referenceSale->id,
        'filename' => '01712345678-1.pdf',
        'group_by_id' => $referenceSale->id,
    ]);
    $sale = $referenceSale->replicate();
    $sale->customer_name = 'Office Collection Sale';
    $sale->collect_from_office = true;
    $sale->status = 'payment-verified';
    $sale->save();

    $message = 'এই WhatsApp নম্বর দিয়ে ইতোমধ্যে সেল রেকর্ড তৈরি করা আছে। তবে ডেলিভারি পদ্ধতি ভিন্ন হওয়ায়, অর্থাৎ একটি “Collect from Office” এবং অন্যটি “Courier Delivery”, এগুলো একসাথে গ্রুপ করা যাবে না।';

    $this->actingAs($user)
        ->get(route('ship-ticket-issue.show', $sale))
        ->assertOk()
        ->assertSee($message)
        ->assertDontSee('name="group_tickets"', false);

    $this->actingAs($user)
        ->from(route('ship-ticket-issue.show', $sale))
        ->put(route('ship-ticket-issue.update', $sale), [
            'group_tickets' => 'yes',
            'group_by_id' => $referenceSale->id,
            'pdf' => ['01712345678-2.pdf'],
        ])
        ->assertRedirect(route('ship-ticket-issue.show', $sale))
        ->assertSessionHasErrors('group_by_id');

    expect($sale->printedTickets()->exists())->toBeFalse();
});

it('allows grouping when both same-whatsapp sales are office collection', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.verify', 'web'),
    ]);
    $ship = Ship::create(['name' => 'Test Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Test Company', 'status' => 1]);
    $courierSale = ShipTicketSale::create([
        'customer_name' => 'Courier Sale',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'address' => '12 Courier Road, Dhaka',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-26',
        'number_of_ticket' => 1,
        'status' => 'ticket-printed',
    ]);
    PrintedTicket::create([
        'sales_id' => $courierSale->id,
        'filename' => '01712345678-9.pdf',
        'group_by_id' => $courierSale->id,
    ]);
    $referenceSale = ShipTicketSale::create([
        'customer_name' => 'Office Sale One',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-26',
        'number_of_ticket' => 1,
        'collect_from_office' => true,
        'status' => 'ticket-printed',
    ]);
    PrintedTicket::create([
        'sales_id' => $referenceSale->id,
        'filename' => '01712345678-1.pdf',
        'group_by_id' => $referenceSale->id,
    ]);
    $sale = $referenceSale->replicate();
    $sale->customer_name = 'Office Sale Two';
    $sale->status = 'payment-verified';
    $sale->save();

    $this->actingAs($user)
        ->get(route('ship-ticket-issue.show', $sale))
        ->assertOk()
        ->assertSee('name="group_tickets"', false);

    $this->actingAs($user)
        ->put(route('ship-ticket-issue.update', $sale), [
            'group_tickets' => 'yes',
            'group_by_id' => $referenceSale->id,
            'pdf' => ['01712345678-2.pdf'],
        ])
        ->assertRedirect();

    expect($sale->printedTickets()->first()->group_by_id)->toBe($referenceSale->id);

    $thirdSale = $sale->replicate();
    $thirdSale->customer_name = 'Office Sale Three';
    $thirdSale->status = 'payment-verified';
    $thirdSale->save();

    $this->actingAs($user)
        ->get(route('ship-ticket-issue.show', $thirdSale))
        ->assertOk()
        ->assertSee('name="group_tickets"', false)
        ->assertSee('value="'.$referenceSale->id.'"', false);
});

it('allows courier sales with the same whatsapp to group when their addresses differ', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::findOrCreate('sales.view', 'web'),
        Permission::findOrCreate('sales.verify', 'web'),
    ]);
    $ship = Ship::create(['name' => 'Test Ship', 'status' => 1]);
    $company = Company::create(['name' => 'Test Company', 'status' => 1]);
    $referenceSale = ShipTicketSale::create([
        'customer_name' => 'Courier Sale One',
        'customer_mobile' => '01712345678',
        'whatsapp' => '01712345678',
        'address' => '12 Road, Dhaka',
        'sales_source' => 'Direct',
        'ship_id' => $ship->id,
        'company_id' => $company->id,
        'journey_date' => '2026-10-01',
        'ticket_fee' => 500,
        'other_fee' => 0,
        'discount_amount' => 0,
        'total_payable' => 500,
        'received_amount' => 500,
        'due_amount' => 0,
        'issued_date' => '2026-09-26',
        'number_of_ticket' => 1,
        'status' => 'ticket-printed',
    ]);
    PrintedTicket::create([
        'sales_id' => $referenceSale->id,
        'filename' => '01712345678-1.pdf',
        'group_by_id' => $referenceSale->id,
    ]);
    $sale = $referenceSale->replicate();
    $sale->customer_name = 'Courier Sale Two';
    $sale->address = '44 Avenue, Dhaka';
    $sale->status = 'payment-verified';
    $sale->save();

    $this->actingAs($user)
        ->get(route('ship-ticket-issue.show', $sale))
        ->assertOk()
        ->assertSee('name="group_tickets"', false)
        ->assertSee('value="'.$referenceSale->id.'"', false);

    $this->actingAs($user)
        ->put(route('ship-ticket-issue.update', $sale), [
            'group_tickets' => 'yes',
            'group_by_id' => $referenceSale->id,
            'pdf' => ['01712345678-2.pdf'],
        ])
        ->assertRedirect();

    expect($sale->printedTickets()->first()->group_by_id)->toBe($referenceSale->id);
});
