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
            ->get(['id', 'status', 'collect_from_office', 'whatsapp', 'whatsapp_username', 'address']);
    }

    public function courierParcelGroupFailureMessage(Collection $sales): ?string
    {
        if ($sales->isEmpty() || $sales->contains(fn (ShipTicketSale $sale): bool => $this->isOfficeCollection($sale)
        )) {
            return 'This ticket group contains a Collect from Office sale. Separate it from courier delivery before creating a parcel.';
        }

        $contactIdentities = $sales->map(fn (ShipTicketSale $sale): ?string => $this->contactIdentity($sale));

        if ($contactIdentities->contains(null) || $contactIdentities->unique()->count() !== 1) {
            return 'All courier sales in a parcel group must use the same WhatsApp number or username.';
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
        if ($this->contactIdentity($sale) === null
            || $this->contactIdentity($sale) !== $this->contactIdentity($groupSale)) {
            return false;
        }

        if ($this->isOfficeCollection($sale) !== $this->isOfficeCollection($groupSale)) {
            return false;
        }

        if ($this->isOfficeCollection($sale)) {
            return true;
        }

        return ! empty(trim((string) $sale->address))
            && ! empty(trim((string) $groupSale->address));
    }

    private function pairFailureMessage(ShipTicketSale $sale, ShipTicketSale $otherSale): ?string
    {
        if ($this->contactIdentity($sale) !== $this->contactIdentity($otherSale)) {
            return 'Both sales must share the same WhatsApp number or username before grouping.';
        }

        if ($this->isOfficeCollection($sale) !== $this->isOfficeCollection($otherSale)) {
            return 'এই WhatsApp নম্বর দিয়ে ইতোমধ্যে সেল রেকর্ড তৈরি করা আছে। তবে ডেলিভারি পদ্ধতি ভিন্ন হওয়ায়, অর্থাৎ একটি “Collect from Office” এবং অন্যটি “Courier Delivery”, এগুলো একসাথে গ্রুপ করা যাবে না।';
        }

        if (! $this->isOfficeCollection($sale)
            && (empty(trim((string) $sale->address)) || empty(trim((string) $otherSale->address)))) {
            return 'Both courier sales must have a delivery address before grouping.';
        }

        return null;
    }

    private function contactIdentity(ShipTicketSale $sale): ?string
    {
        if (filled($sale->whatsapp)) {
            return 'number:'.trim((string) $sale->whatsapp);
        }

        if (filled($sale->whatsapp_username)) {
            return 'username:'.mb_strtolower(trim((string) $sale->whatsapp_username));
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
