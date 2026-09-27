<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\Sales\CheckDuplicateTicketRequest;
use App\Http\Requests\Sales\StorePublicShipTicketSaleRequest;
use App\Http\Requests\Sales\StoreShipTicketSaleRequest;
use App\Http\Requests\Sales\UpdateShipTicketSaleRequest;
use App\Http\Requests\UpdateTicketIssueRequest;
use App\Models\Company;
use App\Models\PrintedTicket;
use App\Models\PrintStatus;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Services\Sales\GoogleDriveTicketService;
use App\Services\Sales\SaleGroupingService;
use App\Services\Sales\SalesDataTableService;
use App\Services\Sales\SaleStatusWorkflowService;
use App\Services\Sales\ShipTicketSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShipTicketSaleController extends Controller
{
    public function __construct(
        private readonly ShipTicketSaleService $shipTicketSales,
        private readonly SalesDataTableService $salesDataTable,
        private readonly SaleStatusWorkflowService $statusWorkflow,
        private readonly SaleGroupingService $saleGrouping,
        private readonly GoogleDriveTicketService $googleDriveTickets,
    ) {}

    public function index()
    {
        $ships = Ship::all();
        $companies = Company::all();

        return view('ship_ticket_sales.index', compact('ships', 'companies'))
            ->with('status', SaleStatus::Pending->value);
    }

    public function pendingCS(Request $request, $status)
    {
        return $this->salesDataTable->response($request, $status);
    }

    public function showPendingSales($status)
    {
        $ships = Ship::all();
        $companies = Company::all();

        return view('ship_ticket_sales.index', compact('status', 'ships', 'companies'));
    }

    public function create()
    {
        $ships = Ship::all();
        $companies = Company::all();

        return view('ship_ticket_sales.create', compact('ships', 'companies'));
    }

    public function bookingForm(Request $request)
    {
        $form = $request->query('form');

        $ships = Ship::all();
        $companies = Company::all();

        return view('welcome', compact('ships', 'companies', 'form'));
    }

    public function store(StoreShipTicketSaleRequest $request)
    {
        $this->shipTicketSales->create($request->validated(), $request->all());

        return redirect()->route('ship-ticket-sales.create')
            ->with('success', 'Journey ticket saved!.');
    }

    public function publicStore(StorePublicShipTicketSaleRequest $request)
    {
        $this->shipTicketSales->createPublic($request->validated(), $request->all());

        return redirect()
            ->route('publicForm.success')
            ->with('success', 'Journey ticket saved successfully! Your booking is confirmed.');
    }

    public function success()
    {
        $hotels = DB::connection('bookme')
            ->table('hotels')
            ->leftJoin('rooms', 'rooms.hotel_id', '=', 'hotels.id')
            ->select(
                'hotels.id',
                'hotels.name',
                'hotels.star_rating',
                'hotels.street_address',
                'hotels.city',
                'hotels.main_photo',
                DB::raw('MIN(rooms.price) as price')
            )
            ->where('hotels.is_active', 1)
            ->where('hotels.destination_id', 702)
            ->groupBy(
                'hotels.id',
                'hotels.name',
                'hotels.star_rating',
                'hotels.street_address',
                'hotels.city',
                'hotels.main_photo',
            )
            ->orderBy('price', 'asc')
            ->get();

        return view('success', compact('hotels'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $sale = ShipTicketSale::with([
            'ships.packages',
            'categories.package',
            'companies',
            'seller:id,name',
            'coPassengers',
            'payments',
            'shipment',
            'printedTickets',
            'refunds.tickets',
            'verifyby.verifiedByUser:id,name',
        ])->findOrFail($id);

        $context = $this->printedTicketContext($sale);

        $number = $context['number'];
        $groupByStatus = $context['groupByStatus'];
        $groupById = $context['groupById'];

        $totalDepartureTickets = $sale->categories
            ->where('type', 'departure')
            ->sum('quantity');

        $totalReturnTickets = $sale->categories
            ->where('type', 'return')
            ->sum('quantity');

        // Add these totals to the sale object for easy access in view

        if ($sale->status == SaleStatus::PaymentVerified->value) {
            $sale->total_departure_tickets = $totalDepartureTickets;
            $sale->total_return_tickets = $totalReturnTickets;
        } else {
            $totalDepartureTickets = 0;
            $totalReturnTickets = 0;
        }

        // Find next sale with SAME STATUS
        $nextSale = $this->nextSaleFor($sale);
        $ships = Ship::all();
        $companies = Company::all();

        return view('ship_ticket_sales.edit', compact('sale', 'number', 'groupByStatus', 'groupById', 'ships', 'companies', 'nextSale', 'totalReturnTickets', 'totalDepartureTickets'));
    }

    public function ticketsIssueShow($id)
    {
        $sale = ShipTicketSale::with([
            'ships.packages',
            'categories.package',
            'companies',
            'seller:id,name',
            'coPassengers',
            'payments',
            'shipment',
            'printedTickets',
            'refunds.tickets',
            'verifyby.verifiedByUser:id,name',
        ])->findOrFail($id);

        $viewedAt = now();
        $ticketIssueView = $sale->ticketIssueViews()->firstOrNew([
            'user_id' => auth()->id(),
        ]);
        $ticketIssueView->first_viewed_at ??= $viewedAt;
        $ticketIssueView->last_viewed_at = $viewedAt;
        $ticketIssueView->save();

        $ticketIssueViews = $sale->ticketIssueViews()
            ->with('user:id,name')
            ->orderByDesc('last_viewed_at')
            ->get();

        $context = $this->printedTicketContext($sale);

        $number = $context['number'];
        $groupByStatus = $context['groupByStatus'];
        $groupById = $context['groupById'];
        $groupingMessage = $context['groupingMessage'];

        $totalDepartureTickets = $sale->categories
            ->where('type', 'departure')
            ->sum('quantity');

        $totalReturnTickets = $sale->categories
            ->where('type', 'return')
            ->sum('quantity');

        $refundSummary = $sale->categories->map(function ($category) use ($sale): array {
            $completedQuantity = $sale->refunds
                ->where('status', 'completed')
                ->flatMap->tickets
                ->where('category_id', $category->id)
                ->sum('refunded_quantity');
            $pendingQuantity = $sale->refunds
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->flatMap->tickets
                ->where('category_id', $category->id)
                ->sum('refunded_quantity');
            $remainingQuantity = max((int) $category->quantity - (int) $completedQuantity, 0);

            return [
                'name' => $category->package?->name ?? 'Ticket category',
                'type' => ucfirst((string) $category->type),
                'purchased' => (int) $category->quantity,
                'refunded' => (int) $completedQuantity,
                'pending' => (int) $pendingQuantity,
                'remaining' => $remainingQuantity,
                'status' => $completedQuantity >= $category->quantity && $category->quantity > 0
                    ? 'Fully Refunded'
                    : ($completedQuantity > 0 ? 'Partially Refunded' : ($pendingQuantity > 0 ? 'Pending Refund' : 'No Refund')),
            ];
        });
        $hasRefundActivity = $sale->refunds->contains(
            fn ($refund): bool => $refund->status !== 'cancelled'
        );

        // Add these totals to the sale object for easy access in view

        if ($sale->status == SaleStatus::PaymentVerified->value) {
            $sale->total_departure_tickets = $totalDepartureTickets;
            $sale->total_return_tickets = $totalReturnTickets;
        } else {
            $totalDepartureTickets = 0;
            $totalReturnTickets = 0;
        }

        // Find next sale with SAME STATUS
        $nextSale = $this->nextSaleFor($sale);
        $ships = Ship::all();
        $companies = Company::all();

        return view('ship_ticket_sales.ticket_issue', compact('sale', 'number', 'groupByStatus', 'groupById', 'groupingMessage', 'ships', 'companies', 'nextSale', 'totalReturnTickets', 'totalDepartureTickets', 'ticketIssueViews', 'refundSummary', 'hasRefundActivity'));
    }

    /**
     * Find the next sale sharing the same status.
     */
    private function nextSaleFor(ShipTicketSale $sale): ?ShipTicketSale
    {
        return ShipTicketSale::where('status', $sale->status)
            ->where('id', '>', $sale->id)
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Printed ticket numbering and grouping context of a sale.
     *
     * @return array{number: int, groupByStatus: bool, groupById: int|null, groupingMessage: string|null}
     */
    private function printedTicketContext(ShipTicketSale $sale): array
    {
        if (empty($sale->whatsapp)) {
            return ['number' => 0, 'groupByStatus' => false, 'groupById' => null, 'groupingMessage' => null];
        }

        $matchingTickets = PrintedTicket::whereHas('sale', function ($query) use ($sale): void {
            $query->where('whatsapp', $sale->whatsapp);
        })
            ->with('sale:id,status,collect_from_office,whatsapp')
            ->get()
            ->sortByDesc(fn (PrintedTicket $ticket): int => (int) Str::afterLast($ticket->filename, '-'));

        $noticeStatuses = [
            SaleStatus::TicketIssued->value,
            SaleStatus::TicketPrinted->value,
            SaleStatus::ShipmentIdEntered->value,
        ];
        $latestTicket = $matchingTickets->first();
        $latestNoticeTicket = $matchingTickets->first(
            fn (PrintedTicket $ticket): bool => in_array($ticket->sale?->status, $noticeStatuses, true)
        );
        $number = $latestNoticeTicket
            ? (int) Str::afterLast($latestNoticeTicket->filename, '-')
            : 0;

        if (! $latestTicket) {
            return ['number' => 0, 'groupByStatus' => false, 'groupById' => null, 'groupingMessage' => null];
        }

        $groupCandidates = $matchingTickets->reject(fn (PrintedTicket $ticket): bool => (int) $ticket->sales_id === (int) $sale->id);
        $groupIds = $groupCandidates
            ->map(fn (PrintedTicket $ticket): int => (int) ($ticket->group_by_id ?? $ticket->sales_id))
            ->unique();
        $groupMemberTickets = PrintedTicket::query()
            ->whereIn('group_by_id', $groupIds)
            ->get(['sales_id', 'group_by_id']);
        $groupSaleIds = $groupIds
            ->merge($groupCandidates->pluck('sales_id'))
            ->merge($groupMemberTickets->pluck('sales_id'))
            ->unique();
        $relatedSales = ShipTicketSale::query()
            ->whereIn('id', $groupSaleIds)
            ->get(['id', 'status', 'collect_from_office', 'whatsapp', 'address']);
        $eligibleTicket = $groupCandidates->first(function (PrintedTicket $ticket) use ($sale): bool {
            $groupById = (int) ($ticket->group_by_id ?? $ticket->sales_id);

            return $this->saleGrouping->canJoinGroup($sale, $groupById);
        });
        $groupingMessage = $eligibleTicket
            ? null
            : $this->saleGrouping->incompatibleSalesMessage($sale, $relatedSales);

        return [
            'number' => $number,
            'groupByStatus' => $eligibleTicket !== null,
            'groupById' => $eligibleTicket
                ? (int) ($eligibleTicket->group_by_id ?? $eligibleTicket->sales_id)
                : null,
            'groupingMessage' => $groupingMessage,
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShipTicketSaleRequest $request, $id)
    {
        try {
            $this->shipTicketSales->update(
                ShipTicketSale::findOrFail($id),
                $request->validated(),
                $request->all()
            );

            if ($request->next_sale_id) {
                return redirect()
                    ->route('ship-ticket-sales.show', $request->next_sale_id)
                    ->with('success', 'Ship ticket sale updated successfully and ready for verification!');
            }

            return redirect()->back()
                ->with('success', 'Ship ticket sale updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update ship ticket sale: '.$e->getMessage())
                ->withInput();
        }
    }

    public function updateIssue(UpdateTicketIssueRequest $request, $id)
    {
        try {
            $sale = ShipTicketSale::findOrFail($id);
            $nextSale = $this->nextSaleFor($sale);
            $this->shipTicketSales->updateIssue($sale, $request->validated());

            return redirect()
                ->route('ship-ticket-issue.show', $nextSale?->id ?? $sale->id)
                ->with('success', $nextSale
                    ? 'Ticket PDF saved. Ready for the next sale.'
                    : 'Ticket PDF details saved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update ship ticket sale: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Find the sale by ID or fail
            $sale = ShipTicketSale::findOrFail($id);

            // Delete the sale
            $sale->delete();

            // Return success response
            return response()->json(['success' => true, 'message' => 'Sale deleted successfully']);
        } catch (\Exception $e) {
            // Handle the error if any, e.g., sale not found
            return response()->json(['success' => false, 'message' => 'Sale not found'], 404);
        }
    }

    public function checkDuplicate(CheckDuplicateTicketRequest $request)
    {
        return response()->json($this->shipTicketSales->duplicateMessage(
            $request->customer_mobile,
            $request->journey_date
        ));
    }

    public function verify(Request $request, $id, $status)
    {
        $result = $this->statusWorkflow->verify((int) $id, $status);

        return response()->json(
            ['success' => $result['success'], 'message' => $result['message']],
            $result['status'] ?? 200
        );
    }

    public function markBftnReceived(int $id)
    {
        $sale = ShipTicketSale::findOrFail($id);
        $sale->update(['received_status' => true]);

        return response()->json([
            'success' => true,
            'message' => 'BFTN received status updated successfully.',
        ]);
    }

    public function pdfDownload($id)
    {
        return $this->googleDriveTickets->streamPdf($id.'.pdf');
    }

    // ShipTicketSaleController.php
    public function pdfPrintAll()
    {
        return ShipTicketSale::where('status', SaleStatus::TicketIssued->value)
            ->orderBy('id')
            ->pluck('id');
    }

    public function openTicket($saleId, $filename)
    {
        $sales = PrintStatus::where('sales_id', $saleId)->first();

        if ($sales) {
            $sales->increment('total_printed_number');
        } else {
            PrintStatus::create([
                'sales_id' => $saleId,
                'total_printed_number' => 1,
            ]);
        }

        return $this->googleDriveTickets->redirectToPrintView($filename);
    }
}
