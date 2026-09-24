<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\Sales\CheckDuplicateTicketRequest;
use App\Http\Requests\Sales\StorePublicShipTicketSaleRequest;
use App\Http\Requests\Sales\StoreShipTicketSaleRequest;
use App\Http\Requests\Sales\UpdateShipTicketSaleRequest;
use App\Models\Company;
use App\Models\PrintedTicket;
use App\Models\PrintStatus;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Services\Sales\GoogleDriveTicketService;
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
            'categories',
            'companies',
            'seller:id,name',
            'coPassengers',
            'payments',
            'shipment',
            'printedTickets',
            'verifyby.verifiedByUser:id,name',
        ])->findOrFail($id);

        $context = $this->printedTicketContext($sale);

        $number = $context['number'];
        $groupByStatus = $context['groupByStatus'] ? 'yes' : 'no';
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $sale = ShipTicketSale::with('seller:id,name')->findOrFail($id);
        $ships = Ship::all();
        $companies = Company::all();
        $nextSale = $this->nextSaleFor($sale);

        return view('ship_ticket_sales.edit', compact('sale', 'ships', 'companies', 'nextSale'));
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
     * @return array{number: int, groupByStatus: bool, groupById: int|null}
     */
    private function printedTicketContext(ShipTicketSale $sale): array
    {
        $latestTicket = PrintedTicket::where('filename', 'like', $sale->whatsapp.'-%')
            ->get()
            ->sortByDesc(fn (PrintedTicket $ticket): int => (int) Str::afterLast($ticket->filename, '-'))
            ->first();

        $number = $latestTicket ? (int) Str::afterLast($latestTicket->filename, '-') : 0;

        if ($number === 0) {
            return ['number' => 0, 'groupByStatus' => false, 'groupById' => null];
        }

        $latestStatus = ShipTicketSale::where('id', $latestTicket->sales_id)->value('status');
        $groupByStatus = in_array($latestStatus, [
            SaleStatus::TicketIssued->value,
            SaleStatus::TicketPrinted->value,
        ], true);

        return [
            'number' => $number,
            'groupByStatus' => $groupByStatus,
            'groupById' => $groupByStatus ? ($latestTicket->group_by_id ?? $latestTicket->sales_id) : null,
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
