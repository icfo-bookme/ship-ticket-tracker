<?php

namespace Database\Seeders;

use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Services\Refunds\RefundService;
use Illuminate\Database\Seeder;

class CompanyErrorRefundSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $sale = ShipTicketSale::query()->where('customer_name', 'Company loss demo')->first();

        if ($sale) {
            return;
        }

        $ship = Ship::firstOrCreate(['name' => 'Company loss demo ship'], ['status' => 1]);
        $package = ShipPackage::firstOrCreate(['ship_id' => $ship->id, 'name' => 'Demo ticket'], ['price' => 1000]);
        $sale = ShipTicketSale::create([
            'customer_name' => 'Company loss demo', 'customer_mobile' => '01700000000',
            'ship_id' => $ship->id, 'status' => 'ticket-issued',
            'issued_date' => now()->toDateString(), 'total_payable' => 1000,
            'discount_amount' => 0, 'other_fee' => 0,
        ]);
        $category = $sale->categories()->create([
            'package_id' => $package->id, 'quantity' => 1, 'type' => 'departure', 'unit_price' => 1000,
        ]);
        $sale->payments()->create([
            'payment_method' => 'Cash', 'received_amount' => 1000,
            'is_bftn' => false, 'settlement_status' => 'received',
        ]);
        app(RefundService::class)->partialRefund($sale, [
            'ticket_selections' => [['category_id' => $category->id, 'refunded_quantity' => 1]],
            'customer_charge_percent' => 0, 'partner_share_percent' => 20,
            'is_company_error' => true, 'company_loss_type' => 'partner_share',
            'company_error_reason' => 'Demonstration of a company-funded partner share.',
        ]);
    }
}
