<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\PrintedTicket;
use App\Models\ShipTicketSale;
use Illuminate\Database\Eloquent\Builder;

class SaleVisibilityService
{
    public function applyStatusVisibility(Builder $query, string $status): void
    {
        $groupStatuses = [
            SaleStatus::TicketIssued->value,
            SaleStatus::TicketPrinted->value,
            SaleStatus::ShipmentIdEntered->value,
            SaleStatus::Shipped->value,
            SaleStatus::CollectFromOffice->value,
        ];

        if (! in_array($status, $groupStatuses, true)) {
            $query->where('status', $status);

            return;
        }

        $groupsWithStatusSales = PrintedTicket::query()
            ->select('group_by_id')
            ->whereNotNull('group_by_id')
            ->whereIn('sales_id', ShipTicketSale::query()
                ->select('id')
                ->where('status', $status))
            ->distinct();

        $saleIdsInVisibleGroups = PrintedTicket::query()
            ->select('sales_id')
            ->whereIn('group_by_id', $groupsWithStatusSales);

        $query->where(function (Builder $visibleSales) use ($status, $saleIdsInVisibleGroups): void {
            $visibleSales->where('status', $status)
                ->orWhereIn('id', $saleIdsInVisibleGroups);
        });
    }

    public function excludeSalesWithActiveRefunds(Builder $query): void
    {
        $query->whereRaw('(
            SELECT COALESCE(SUM(refunded_number_of_tickets), 0)
            FROM refunds
            WHERE refunds.sales_id = ship_ticket_sales.id
                AND refunds.status != ?
        ) < (
            SELECT COALESCE(SUM(categories.quantity), 0)
            FROM categories
            WHERE categories.ticket_id = ship_ticket_sales.id
        )', ['cancelled']);
    }
}
