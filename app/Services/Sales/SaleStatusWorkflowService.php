<?php

namespace App\Services\Sales;

use App\Enums\SaleStatus;
use App\Models\PrintedTicket;
use App\Models\Shipment;
use App\Models\ShipTicketSale;
use App\Models\VerifyTracker;
use App\Services\SteadfastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleStatusWorkflowService
{
    public function __construct(private readonly SteadfastService $steadfast) {}

    /**
     * @return array{success: bool, message: string, status?: int}
     */
    public function verify(int $id, string $status): array
    {
        if ($status === SaleStatus::TicketPrinted->value) {
            $this->markGroupedTickets($id, SaleStatus::TicketPrinted->value);

            return [
                'success' => true,
                'message' => 'Sales updated to ticket-printed successfully',
            ];
        }

        $sale = ShipTicketSale::findOrFail($id);

        if ($status === SaleStatus::ShipmentIdEntered->value) {
            return $this->createShipmentAndMarkGroupedTickets($sale, $id);
        }

        DB::transaction(function () use ($sale, $status, $id): void {
            $sale->update(['status' => $status]);
            $this->track($status, $id);
        });

        return [
            'success' => true,
            'message' => 'Sale status updated successfully',
        ];
    }

    private function markGroupedTickets(int $groupId, string $status): void
    {
        $tickets = $this->groupedTickets($groupId);

        DB::transaction(function () use ($tickets, $status): void {
            $tickets->each(function (PrintedTicket $ticket) use ($status): void {
                $sale = ShipTicketSale::find($ticket->sales_id);

                if (! $sale) {
                    return;
                }

                $sale->update(['status' => $status]);
                $this->track($status, $sale->id);
            });
        });
    }

    /**
     * @return array{success: bool, message: string, status?: int}
     */
    private function createShipmentAndMarkGroupedTickets(ShipTicketSale $sale, int $groupId): array
    {
        $consignmentId = $this->createConsignment($sale);

        if (! $consignmentId) {
            return [
                'success' => false,
                'message' => 'Failed to create shipment',
                'status' => 500,
            ];
        }

        $tickets = $this->groupedTickets($groupId);

        DB::transaction(function () use ($tickets, $consignmentId): void {
            $tickets->each(function (PrintedTicket $ticket) use ($consignmentId): void {
                $sale = ShipTicketSale::find($ticket->sales_id);

                if (! $sale) {
                    return;
                }

                Shipment::create([
                    'ticket_id' => $sale->id,
                    'shipment_id' => $consignmentId,
                ]);

                $sale->update(['status' => SaleStatus::ShipmentIdEntered->value]);
                $this->track(SaleStatus::ShipmentIdEntered->value, $sale->id);
            });
        });

        return [
            'success' => true,
            'message' => 'Sales updated to shipment_id_entered successfully',
        ];
    }

    private function createConsignment(ShipTicketSale $sale): ?string
    {
        $steadfastResult = $this->steadfast->bulkCreate([
            [
                'invoice' => 'TICKET-'.$sale->id,
                'recipient_name' => $sale->customer_name,
                'recipient_phone' => $sale->customer_mobile,
                'recipient_address' => $sale->address ?? 'N/A',
                'cod_amount' => ($sale->due_amount ?? 0) + 100,
                'note' => 'Journey ticket booking ID: '.$sale->id,
                'delivery_type' => 0,
            ],
        ]);

        $consignmentId = $steadfastResult['data'][0]['consignment_id'] ?? null;

        if (! $consignmentId) {
            Log::error('Failed to get consignment_id from Steadfast response', $steadfastResult);
        }

        return $consignmentId;
    }

    private function groupedTickets(int $groupId)
    {
        return PrintedTicket::where('group_by_id', $groupId)
            ->latest()
            ->get()
            ->unique('sales_id')
            ->values();
    }

    private function track(string $status, int $saleId): void
    {
        VerifyTracker::create([
            'name' => $status,
            'ticket_id' => $saleId,
            'verified_by' => auth()->id(),
        ]);
    }
}
