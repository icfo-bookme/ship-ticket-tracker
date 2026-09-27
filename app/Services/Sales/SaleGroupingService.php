<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\PrintedTicket;
use App\Models\ShipTicketSale;
use Illuminate\Support\Collection;

class SaleGroupingService
{
    public function isOfficeCollection(ShipTicketSale $sale): bool
    {
        return $sale->collect_from_office
            || $sale->status === SaleStatus::CollectFromOffice->value;
    }

    public function canJoinGroup(ShipTicketSale $sale, int $groupById): bool
    {
        if ($this->isCompleted($sale)) {
            return false;
        }

        $groupSales = $this->groupSales($groupById);

        if ($groupSales->isEmpty()) {
            return false;
        }

        foreach ($groupSales as $groupSale) {
            if ($this->isCompleted($groupSale) || ! $this->salesCanGroup($sale, $groupSale)) {
                return false;
            }
        }

        return true;
    }

    public function groupFailureMessage(ShipTicketSale $sale, int $groupById): string
    {
        $groupSales = $this->groupSales($groupById);

        foreach ($groupSales as $groupSale) {
            $message = $this->pairFailureMessage($sale, $groupSale);

            if ($message !== null) {
                return $message;
            }

            if ($this->isCompleted($groupSale)) {
                return 'This ticket group has already been shipped or collected and cannot accept another sale.';
            }
        }

        return 'This ticket group is not eligible for grouping.';
    }

    public function groupSales(int $groupById): Collection
    {
        $saleIds = PrintedTicket::query()
            ->where('group_by_id', $groupById)
            ->pluck('sales_id')
            ->push($groupById)
            ->unique();

        return ShipTicketSale::query()
            ->whereIn('id', $saleIds)
            ->get(['id', 'status', 'collect_from_office', 'whatsapp', 'address']);
    }

    public function courierParcelGroupFailureMessage(Collection $sales): ?string
    {
        if ($sales->isEmpty() || $sales->contains(fn (ShipTicketSale $sale): bool => $this->isOfficeCollection($sale)
        )) {
            return 'This ticket group contains a Collect from Office sale. Separate it from courier delivery before creating a parcel.';
        }

        if ($sales->pluck('whatsapp')->filter()->unique()->count() !== 1
            || $sales->contains(fn (ShipTicketSale $sale): bool => empty($sale->whatsapp))) {
            return 'All courier sales in a parcel group must use the same WhatsApp number.';
        }

        if ($sales->contains(fn (ShipTicketSale $sale): bool => empty(trim((string) $sale->address)))) {
            return 'Every courier sale in a parcel group must have a delivery address.';
        }

        return null;
    }

    public function incompatibleSalesMessage(ShipTicketSale $sale, Collection $relatedSales): ?string
    {
        foreach ($relatedSales as $relatedSale) {
            if ($relatedSale->id === $sale->id) {
                continue;
            }

            $message = $this->pairFailureMessage($sale, $relatedSale);

            if ($message !== null) {
                return $message;
            }
        }

        return null;
    }

    private function salesCanGroup(ShipTicketSale $sale, ShipTicketSale $groupSale): bool
    {
        if (empty($sale->whatsapp) || $sale->whatsapp !== $groupSale->whatsapp) {
            return false;
        }

        if ($this->isOfficeCollection($sale) !== $this->isOfficeCollection($groupSale)) {
            return false;
        }

        if ($this->isOfficeCollection($sale)) {
            return true;
        }

        return true;
    }

    private function pairFailureMessage(ShipTicketSale $sale, ShipTicketSale $otherSale): ?string
    {
        if ($this->isOfficeCollection($sale) !== $this->isOfficeCollection($otherSale)) {
            return 'Ei WhatsApp number diye already sales create kora ase, kintu ekta Collect from Office and arekta courier delivery howate group by kora possible na.';
        }

        return null;
    }

    private function isCompleted(ShipTicketSale $sale): bool
    {
        return in_array($sale->status, [
            SaleStatus::Shipped->value,
            SaleStatus::CollectFromOffice->value,
        ], true);
    }
}
