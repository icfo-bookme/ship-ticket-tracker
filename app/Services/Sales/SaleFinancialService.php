<?php

namespace App\Services\Sales;

use App\Enums\RefundStatus;
use App\Models\ShipPackage;
use App\Models\ShipTicketSale;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SaleFinancialService
{
    /**
     * @param  array<string, array<int, array{package_id?: int|string, quantity?: int|string}>>  $ticketCategories
     */
    public function ticketFeeFromCategories(
        int|string $shipId,
        ?string $returnDate,
        array $ticketCategories,
        ?float $fallback,
        bool $publicPricing = false,
    ): float {
        if ($ticketCategories === []) {
            return round($fallback ?? 0, 2);
        }

        $quantities = [];
        foreach ($ticketCategories as $type => $categories) {
            if (! in_array($type, ['departure', 'return'], true) || ! is_array($categories)) {
                continue;
            }

            foreach ($categories as $category) {
                $packageId = (int) ($category['package_id'] ?? 0);
                $quantity = (int) ($category['quantity'] ?? 0);
                $this->validateQuantity($quantity);

                if ($packageId > 0) {
                    $quantities[$packageId][$type] = ($quantities[$packageId][$type] ?? 0) + $quantity;
                }
            }
        }

        return $this->calculateTicketFee($shipId, $returnDate, $quantities, $fallback, $publicPricing);
    }

    /**
     * @param  array<int|string, int|string>  $departureQuantities
     * @param  array<int|string, int|string>  $returnQuantities
     */
    public function ticketFeeFromPackageQuantities(
        int|string $shipId,
        ?string $returnDate,
        array $departureQuantities,
        array $returnQuantities,
        ?float $fallback,
    ): float {
        if ($departureQuantities === [] && $returnQuantities === []) {
            return round($fallback ?? 0, 2);
        }

        $quantities = [];
        foreach (['departure' => $departureQuantities, 'return' => $returnQuantities] as $type => $items) {
            foreach ($items as $packageId => $quantity) {
                $quantity = (int) $quantity;
                $this->validateQuantity($quantity);
                $quantities[(int) $packageId][$type] = $quantity;
            }
        }

        return $this->calculateTicketFee($shipId, $returnDate, $quantities, $fallback, false);
    }

    /** @return array{ticket_fee: float, total_payable: float, received_amount: float, due_amount: float} */
    public function summary(float $ticketFee, float $otherFee, float $discount, float $received): array
    {
        $ticketFee = round(max($ticketFee, 0), 2);
        $otherFee = round(max($otherFee, 0), 2);
        $discount = round(max($discount, 0), 2);
        $received = round(max($received, 0), 2);

        if ($discount > $ticketFee) {
            throw ValidationException::withMessages([
                'discount_amount' => 'Discount cannot exceed the calculated ticket fee.',
            ]);
        }

        $totalPayable = round(max($ticketFee + $otherFee - $discount, 0), 2);

        return [
            'ticket_fee' => $ticketFee,
            'total_payable' => $totalPayable,
            'received_amount' => $received,
            'due_amount' => round(max($totalPayable - $received, 0), 2),
        ];
    }

    /** @return array{ticket_fee: float, total_payable: float, received_amount: float, due_amount: float, extra_received_amount: float, extra_remaining_amount: float} */
    public function currentSummary(ShipTicketSale $sale): array
    {
        $sale->loadMissing(['payments', 'refunds']);
        $receivedAmount = $sale->payments->isNotEmpty()
            ? (float) $sale->payments->sum('received_amount')
            : (float) $sale->received_amount;
        $summary = $this->summary(
            (float) $sale->ticket_fee,
            (float) $sale->other_fee,
            (float) $sale->discount_amount,
            $receivedAmount,
        );
        $dueAdjustments = (float) $sale->refunds
            ->filter(fn ($refund): bool => $refund->status === RefundStatus::Completed->value || $refund->customer_refunded_at !== null)
            ->sum('due_adjusted_amount');
        $summary['due_amount'] = round(max($summary['due_amount'] - $dueAdjustments, 0), 2);
        $extraReceivedAmount = round(max($summary['received_amount'] - $summary['total_payable'], 0), 2);
        $extraRefunds = (float) $sale->refunds
            ->where('refund_type', 'extra_payment')
            ->where('status', '!=', RefundStatus::Cancelled->value)
            ->sum('refunded_amount');
        $summary['extra_received_amount'] = $extraReceivedAmount;
        $summary['extra_remaining_amount'] = round(max($extraReceivedAmount - $extraRefunds, 0), 2);

        return $summary;
    }

    /** @param array<int, array{departure?: int, return?: int}> $quantities */
    private function calculateTicketFee(
        int|string $shipId,
        ?string $returnDate,
        array $quantities,
        ?float $fallback,
        bool $publicPricing,
    ): float {
        $activePackageIds = collect($quantities)
            ->filter(fn (array $item): bool => (int) ($item['departure'] ?? 0) > 0 || (int) ($item['return'] ?? 0) > 0)
            ->keys();

        if ($activePackageIds->isEmpty()) {
            return $quantities === [] ? round($fallback ?? 0, 2) : 0.0;
        }

        $packages = ShipPackage::query()
            ->where('ship_id', $shipId)
            ->whereIn('id', $activePackageIds)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        if ($packages->count() !== $activePackageIds->count()) {
            throw ValidationException::withMessages([
                'ticket_categories' => 'One or more selected ticket packages are invalid for this ship.',
            ]);
        }

        if (blank($returnDate)) {
            $total = 0.0;
            foreach ($quantities as $packageId => $item) {
                $price = (float) $packages->get($packageId)->price;
                $total += ((int) ($item['departure'] ?? 0) + (int) ($item['return'] ?? 0)) * $price;
            }

            return round($total, 2);
        }

        return round($publicPricing
            ? $this->calculatePublicRoundTrip($packages, $quantities)
            : $this->calculateStaffRoundTrip($packages, $quantities), 2);
    }

    /**
     * @param  Collection<int, ShipPackage>  $packages
     * @param  array<int, array{departure?: int, return?: int}>  $quantities
     */
    private function calculateStaffRoundTrip(Collection $packages, array $quantities): float
    {
        $total = 0.0;
        foreach ($packages as $packageId => $package) {
            $departure = (int) ($quantities[$packageId]['departure'] ?? 0);
            $return = (int) ($quantities[$packageId]['return'] ?? 0);
            $singlePrice = (float) $package->price;
            $roundTripPrice = (float) $package->round_trip_price;
            $returnOnlyPrice = $roundTripPrice > 0 ? $roundTripPrice - $singlePrice : $singlePrice;
            $paired = min($departure, $return);

            $total += $paired > 0 && $roundTripPrice > 0
                ? $paired * $roundTripPrice
                : $paired * ($singlePrice + $returnOnlyPrice);
            $total += ($departure - $paired) * $singlePrice;
            $total += ($return - $paired) * $returnOnlyPrice;
        }

        return $total;
    }

    /**
     * @param  Collection<int, ShipPackage>  $packages
     * @param  array<int, array{departure?: int, return?: int}>  $quantities
     */
    private function calculatePublicRoundTrip(Collection $packages, array $quantities): float
    {
        $total = 0.0;
        $remaining = [];

        foreach ($packages as $packageId => $package) {
            $departure = (int) ($quantities[$packageId]['departure'] ?? 0);
            $return = (int) ($quantities[$packageId]['return'] ?? 0);
            $singlePrice = (float) $package->price;
            $roundTripPrice = (float) $package->round_trip_price;
            $paired = $roundTripPrice > 0 ? min($departure, $return) : 0;

            $total += $paired * $roundTripPrice;
            $remaining[$packageId] = [
                'departure' => $departure - $paired,
                'return' => $return - $paired,
                'single_price' => $singlePrice,
                'return_price' => $roundTripPrice > 0 ? $roundTripPrice - $singlePrice : $singlePrice,
            ];
        }

        foreach (array_keys($remaining) as $departureId) {
            foreach (array_keys($remaining) as $returnId) {
                if ($departureId === $returnId || $remaining[$departureId]['departure'] <= 0) {
                    continue;
                }

                $paired = min($remaining[$departureId]['departure'], $remaining[$returnId]['return']);
                if ($paired <= 0) {
                    continue;
                }

                $total += $paired * ($remaining[$departureId]['single_price'] + $remaining[$returnId]['return_price']);
                $remaining[$departureId]['departure'] -= $paired;
                $remaining[$returnId]['return'] -= $paired;
            }
        }

        foreach ($remaining as $item) {
            $total += $item['departure'] * $item['single_price'];
            $total += $item['return'] * $item['single_price'];
        }

        return $total;
    }

    private function validateQuantity(int $quantity): void
    {
        if ($quantity < 0) {
            throw ValidationException::withMessages([
                'ticket_categories' => 'Ticket package quantities cannot be negative.',
            ]);
        }
    }
}
