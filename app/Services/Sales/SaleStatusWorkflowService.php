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
    private const FULFILLMENT_STATUS_RANKS = [
        'pending' => 0,
        'payment-verified' => 10,
        'ticket-issued' => 20,
        'ticket-printed' => 30,
        'shipment_id_entered' => 40,
        'shipped' => 50,
    ];

    public function __construct(
        private readonly SteadfastService $steadfast,
        private readonly SaleGroupingService $saleGrouping,
    ) {}

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

        if ($status === SaleStatus::CollectFromOffice->value) {
            if (! $sale->collect_from_office || $sale->status !== SaleStatus::TicketPrinted->value) {
                return [
                    'success' => false,
                    'message' => 'This sale is not ready for office collection.',
                    'status' => 422,
                ];
            }

            return $this->markOfficeCollectionGroup($sale);
        }

        if ($status === SaleStatus::ShipmentIdEntered->value) {
            return $this->createShipmentAndMarkGroupedTickets($sale, $id);
        }

        if ($status === SaleStatus::Shipped->value) {
            $this->markGroupedTickets($id, SaleStatus::Shipped->value);

            return [
                'success' => true,
                'message' => 'Sales updated to shipped successfully',
            ];
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
            $sales = ShipTicketSale::query()
                ->whereIn('id', $tickets->pluck('sales_id')->unique())
                ->lockForUpdate()
                ->get();
            $highestStatus = $status;
            $highestRank = self::FULFILLMENT_STATUS_RANKS[$status] ?? 0;

            foreach ($sales as $sale) {
                $saleRank = self::FULFILLMENT_STATUS_RANKS[$sale->status] ?? null;

                if ($saleRank === null) {
                    continue;
                }

                if ($saleRank > $highestRank) {
                    $highestRank = $saleRank;
                    $highestStatus = $sale->status;
                }
            }

            $sales->each(function (ShipTicketSale $sale) use ($highestStatus): void {
                if (! array_key_exists($sale->status, self::FULFILLMENT_STATUS_RANKS) || $sale->status === $highestStatus) {
                    return;
                }

                $sale->update(['status' => $highestStatus]);
                $this->track($highestStatus, $sale->id);
            });

            if ($highestStatus === SaleStatus::ShipmentIdEntered->value) {
                $this->attachGroupToExistingShipment($sales->pluck('id')->all());
            }
        });
    }

    /**
     * @return array{success: bool, message: string, status?: int}
     */
    private function createShipmentAndMarkGroupedTickets(ShipTicketSale $sale, int $groupId): array
    {

        $tickets = $this->groupedTickets($groupId);
        $saleIds = $tickets->pluck('sales_id')->push($groupId)->unique();
        $groupSales = ShipTicketSale::query()
            ->whereIn('id', $saleIds)
            ->get(['id', 'status', 'collect_from_office', 'whatsapp', 'address']);
        $parcelGroupError = $this->saleGrouping->courierParcelGroupFailureMessage($groupSales);

        if ($parcelGroupError !== null) {
            return [
                'success' => false,
                'message' => $parcelGroupError,
                'status' => 422,
            ];
        }

        $saleIds = $saleIds->all();
        $consignmentId = Shipment::query()
            ->whereIn('ticket_id', $saleIds)
            ->whereNotNull('shipment_id')
            ->value('shipment_id');

        $consignmentId ??= $this->createConsignment($sale);

        if (! $consignmentId) {
            return [
                'success' => false,
                'message' => 'Failed to create shipment',
                'status' => 500,
            ];
        }

        DB::transaction(function () use ($tickets, $consignmentId): void {
            $tickets->each(function (PrintedTicket $ticket) use ($consignmentId): void {
                $sale = ShipTicketSale::find($ticket->sales_id);

                if (! $sale) {
                    return;
                }

                Shipment::updateOrCreate(
                    ['ticket_id' => $sale->id],
                    ['shipment_id' => $consignmentId],
                );

                $sale->update(['status' => SaleStatus::ShipmentIdEntered->value]);
                $this->track(SaleStatus::ShipmentIdEntered->value, $sale->id);
            });
        });

        return [
            'success' => true,
            'message' => 'Sales updated to shipment_id_entered successfully',
        ];
    }

    /**
     * @return array{success: bool, message: string, status?: int}
     */
    private function markOfficeCollectionGroup(ShipTicketSale $sale): array
    {
        $tickets = $this->groupedTickets($sale->id);
        $saleIds = $tickets->pluck('sales_id')->push($sale->id)->unique();
        $updated = DB::transaction(function () use ($saleIds): bool {
            $sales = ShipTicketSale::query()
                ->whereIn('id', $saleIds)
                ->lockForUpdate()
                ->get();

            if ($sales->contains(fn (ShipTicketSale $groupSale): bool => ! $groupSale->collect_from_office
                || ! in_array($groupSale->status, [
                    SaleStatus::TicketPrinted->value,
                    SaleStatus::CollectFromOffice->value,
                ], true)
            )) {
                return false;
            }

            $sales->each(function (ShipTicketSale $groupSale): void {
                if ($groupSale->status === SaleStatus::CollectFromOffice->value) {
                    return;
                }

                $groupSale->update(['status' => SaleStatus::CollectFromOffice->value]);
                $this->track(SaleStatus::CollectFromOffice->value, $groupSale->id);
            });

            return true;
        });

        if (! $updated) {
            return [
                'success' => false,
                'message' => 'Every sale in this group must be marked for office collection before confirming collection.',
                'status' => 422,
            ];
        }

        return [
            'success' => true,
            'message' => 'Grouped sales marked as collected from office.',
        ];
    }

    private function createConsignment(ShipTicketSale $sale): ?string
    {

        $invoice = 'TICKET-1111'.$sale->id;

        try {
            $steadfastResult = $this->steadfast->createOrder([
                'invoice' => $invoice,
                'recipient_name' => $sale->customer_name,
                'recipient_phone' => $sale->customer_mobile,
                'recipient_address' => $sale->address ?? 'N/A',
                'cod_amount' => ($sale->due_amount ?? 0) + 100,
                'note' => 'Journey ticket booking ID: '.$sale->id,
                'delivery_type' => 0,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Steadfast parcel creation failed', [
                'invoice' => $invoice,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        $resultItem = $steadfastResult['consignment'] ?? $steadfastResult['data'] ?? $steadfastResult;
        $itemStatus = strtolower((string) ($steadfastResult['status'] ?? ''));
        $consignmentId = is_array($resultItem) ? ($resultItem['consignment_id'] ?? null) : null;

        if ($itemStatus !== '' && ! in_array($itemStatus, ['success', '200'], true)) {
            Log::error('Steadfast rejected the parcel item', [
                'invoice' => $invoice,
                'item_status' => $itemStatus,
            ]);

            return null;
        }

        if (! $consignmentId) {
            Log::error('Failed to get consignment_id from Steadfast response', [
                'invoice' => $invoice,
                'response' => $steadfastResult,
            ]);
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

    private function attachGroupToExistingShipment(array $saleIds): void
    {
        $shipmentId = Shipment::query()
            ->whereIn('ticket_id', $saleIds)
            ->value('shipment_id');

        if (! $shipmentId) {
            return;
        }

        foreach ($saleIds as $saleId) {
            Shipment::firstOrCreate([
                'ticket_id' => $saleId,
                'shipment_id' => $shipmentId,
            ]);
        }
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
