<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\Sales\CheckDuplicateTicketRequest;
use App\Http\Requests\Sales\MarkBftnReceivedRequest;
use App\Http\Requests\Sales\StorePublicShipTicketSaleRequest;
use App\Http\Requests\Sales\StoreShipTicketSaleRequest;
use App\Http\Requests\Sales\UpdateShipTicketSaleRequest;
use App\Http\Requests\UpdateTicketIssueRequest;
use App\Models\Company;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Services\Sales\GoogleDriveTicketService;
use App\Services\Sales\SaleDetailService;
use App\Services\Sales\SalesDataTableService;
use App\Services\Sales\SaleStatusWorkflowService;
use App\Services\Sales\ShipTicketSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipTicketSaleController extends Controller
{
    public function __construct(
        private readonly ShipTicketSaleService $shipTicketSales,
        private readonly SalesDataTableService $salesDataTable,
        private readonly SaleStatusWorkflowService $statusWorkflow,
        private readonly SaleDetailService $saleDetails,
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
        $context = $this->saleDetails->editContext($sale);

        return view('ship_ticket_sales.edit', [
            'sale' => $sale,
            'ships' => Ship::all(),
            'companies' => Company::all(),
            ...$context,
        ]);
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

        $context = $this->saleDetails->ticketIssueContext($sale, (int) auth()->id());

        return view('ship_ticket_sales.ticket_issue', [
            'sale' => $sale,
            'ships' => Ship::all(),
            'companies' => Company::all(),
            ...$context,
        ]);
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
            $nextSale = $this->saleDetails->nextSaleFor($sale);
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
            $this->shipTicketSales->delete((int) $id);

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

    public function markBftnReceived(MarkBftnReceivedRequest $request, int $id)
    {
        $this->statusWorkflow->markBftnReceived($id, $request->validated('received_at'));

        return response()->json([
            'success' => true,
            'message' => 'BFTN received status updated successfully.',
        ]);
    }

    public function pdfDownload($id)
    {
        return $this->googleDriveTickets->streamPdf($id.'.pdf');
    }

    public function openTicket($saleId, $filename)
    {
        $this->statusWorkflow->recordTicketPrint((int) $saleId);

        return $this->googleDriveTickets->redirectToPrintView($filename);
    }
}
