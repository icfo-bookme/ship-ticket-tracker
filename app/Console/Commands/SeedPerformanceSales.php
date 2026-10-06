<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Ship;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedPerformanceSales extends Command
{
    private const SALES_SOURCE = 'performance-test';

    protected $signature = 'sales:seed-performance {count=1000} {--status=pending} {--cleanup}';

    protected $description = 'Create or remove local performance-test sales';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('This command is available only in local or testing environments.');

            return self::FAILURE;
        }

        if ($this->option('cleanup')) {
            return $this->cleanup();
        }

        $count = (int) $this->argument('count');
        $status = (string) $this->option('status');

        if ($count < 1 || $count > 10000) {
            $this->error('Count must be between 1 and 10000.');

            return self::FAILURE;
        }

        if (! array_key_exists($status, config('sales.statuses', []))) {
            $this->error('The requested status is not configured.');

            return self::FAILURE;
        }

        $ships = Ship::query()->pluck('id');

        if ($ships->isEmpty()) {
            $this->error('Add at least one ship before generating performance sales.');

            return self::FAILURE;
        }

        $companies = Company::query()->pluck('id');
        $sellers = User::query()->pluck('id');
        $packagesByShip = ShipPackage::query()
            ->whereIn('ship_id', $ships)
            ->get(['id', 'ship_id', 'price'])
            ->groupBy('ship_id');
        $fake = FakerFactory::create();
        $now = now();
        $startingId = (int) (ShipTicketSale::query()->max('id') ?? 0);
        $rows = [];
        $selectedPackageIds = [];

        for ($index = 0; $index < $count; $index++) {
            $shipId = (int) $ships->random();
            $package = $packagesByShip->get($shipId)?->random();
            $selectedPackageIds[] = $package?->id;
            $ticketCount = $fake->numberBetween(1, 4);
            $ticketFee = (int) (($package?->price ?: $fake->numberBetween(100, 1000)) * $ticketCount);
            $otherFee = $fake->randomElement([0, 5, 10, 15]);
            $discount = $fake->numberBetween(0, min($ticketFee, 50));
            $totalPayable = max($ticketFee + $otherFee - $discount, 0);
            $receivedAmount = $fake->numberBetween(0, $totalPayable + 20);

            $rows[] = [
                'customer_name' => '[PERF] '.$fake->name(),
                'customer_mobile' => $fake->numerify('01#########'),
                'sales_source' => self::SALES_SOURCE,
                'ship_id' => $shipId,
                'journey_date' => $fake->dateTimeBetween('-30 days', '+90 days')->format('Y-m-d'),
                'ticket_fee' => $ticketFee,
                'received_amount' => $receivedAmount,
                'due_amount' => max($totalPayable - $receivedAmount, 0),
                'company_id' => $companies->isEmpty() || $fake->boolean(10) ? null : $companies->random(),
                'issued_date' => $now->toDateString(),
                'sold_by' => $sellers->isEmpty() ? null : $sellers->random(),
                'status' => $status,
                'number_of_ticket' => $ticketCount,
                'whatsapp' => $fake->boolean(80) ? $fake->numerify('01#########') : null,
                'whatsapp_username' => $fake->boolean(20) ? '@'.$fake->userName() : null,
                'other_fee' => $otherFee,
                'discount_amount' => $discount,
                'total_payable' => $totalPayable,
                'collect_from_office' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($rows, $count, $startingId, $selectedPackageIds, $fake, $now): void {
            foreach (array_chunk($rows, 250) as $chunk) {
                ShipTicketSale::query()->insert($chunk);
            }

            $sales = ShipTicketSale::query()
                ->where('sales_source', self::SALES_SOURCE)
                ->where('id', '>', $startingId)
                ->orderBy('id')
                ->get(['id', 'ship_id', 'number_of_ticket', 'received_amount']);
            $payments = [];
            $categories = [];

            foreach ($sales->values() as $index => $sale) {
                $packageId = $selectedPackageIds[$index] ?? null;

                if ($packageId) {
                    $categories[] = [
                        'ticket_id' => $sale->id,
                        'package_id' => $packageId,
                        'quantity' => $sale->number_of_ticket,
                        'type' => 'departure',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ((float) $sale->received_amount > 0) {
                    $payments[] = [
                        'sales_id' => $sale->id,
                        'payment_method' => $fake->randomElement(['Cash', 'Bkash', 'Nagad', 'Bank Transfer']),
                        'received_amount' => $sale->received_amount,
                        'paid_date' => $now->toDateString(),
                        'payment_datetime' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($categories, 500) as $chunk) {
                Category::query()->insert($chunk);
            }

            foreach (array_chunk($payments, 500) as $chunk) {
                Payment::query()->insert($chunk);
            }

            if ($sales->count() !== $count) {
                throw new \RuntimeException('Could not verify the number of generated sales.');
            }
        });

        $this->info("Created {$count} {$status} performance sales.");
        $this->line('Generated rows are tagged with sales_source=performance-test.');
        $this->line('Remove them with: php artisan sales:seed-performance --cleanup');

        return self::SUCCESS;
    }

    private function cleanup(): int
    {
        $deleted = 0;

        ShipTicketSale::query()
            ->where('sales_source', self::SALES_SOURCE)
            ->chunkById(500, function ($sales) use (&$deleted): void {
                $saleIds = $sales->modelKeys();

                Payment::query()->whereIn('sales_id', $saleIds)->delete();
                Category::query()->whereIn('ticket_id', $saleIds)->delete();
                $deleted += ShipTicketSale::query()->whereIn('id', $saleIds)->delete();
            });

        $this->info("Removed {$deleted} performance-test sales and their payment/category rows.");

        return self::SUCCESS;
    }
}
