<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sales\CheckDuplicateTicketRequest;
use App\Http\Requests\Sales\StorePublicShipTicketSaleRequest;
use App\Http\Requests\Sales\StoreShipTicketSaleRequest;
use App\Http\Requests\Sales\UpdateShipTicketSaleRequest;
use App\Models\Company;
use App\Models\PrintedTicket;
use App\Models\PrintStatus;
use App\Models\Ship;
use App\Models\Shipment;
use App\Models\ShipTicketSale;
use App\Models\VerifyTracker;
use App\Services\Sales\ShipTicketSaleService;
use App\Services\SteadfastService;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipTicketSaleController extends Controller
{
    public function __construct(
        private readonly SteadfastService $steadfast,
        private readonly ShipTicketSaleService $shipTicketSales,
    ) {}

    public function index()
    {
        $sales = ShipTicketSale::with('ships')->latest()->get();
        $ships = Ship::all();

        return view('ship_ticket_sales.index', compact('sales', 'ships'))
            ->with('status', 'pending');
    }

    public function pendingCS(Request $request, $status)
    {
        $shipId = $request->input('ship_id');
        $companyId = $request->input('company_id');
        $journeyDate = $request->input('journey_date');

        // DataTables parameters
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $searchValue = $request->input('search.value', '');

        // Base query
        $query = ShipTicketSale::with([
            'ships.packages',
            'categories',
            'companies',
            'coPassengers',
            'shipment',
            'payments',
            'PrintStatus',
            'printedTickets',
            'groupedTickets',
            'verifyby.verifiedByUser',
        ])
            ->withCount('printedTickets')
            ->where('status', $status);

        // Apply filters
        if (! empty($shipId)) {
            $query->where('ship_id', $shipId);
        }
        if (! empty($companyId)) {
            $query->where('company_id', $companyId);
        }
        if (! empty($journeyDate)) {
            $query->whereDate('journey_date', $journeyDate);
        }

        // Get total records BEFORE search
        $totalRecords = ShipTicketSale::where('status', $status)->count();

        // Apply search
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('customer_name', 'like', "%{$searchValue}%")
                    ->orWhere('customer_mobile', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('nid', 'like', "%{$searchValue}%")
                    ->orWhere('sales_source', 'like', "%{$searchValue}%")
                    ->orWhere('ticket_fee', 'like', "%{$searchValue}%")
                    ->orWhere('payment_method', 'like', "%{$searchValue}%")
                    ->orWhere('number_of_ticket', 'like', "%{$searchValue}%")
                    ->orWhere('received_amount', 'like', "%{$searchValue}%")
                    ->orWhere('due_amount', 'like', "%{$searchValue}%")
                    ->orWhere('sold_by', 'like', "%{$searchValue}%")
                    ->orWhere('ticket_category', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhereDate('journey_date', $searchValue)
                    ->orWhereDate('return_date', $searchValue)
                    ->orWhereDate('issued_date', $searchValue)
                    ->orWhereHas('ships', function ($shipQuery) use ($searchValue) {
                        $shipQuery->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('companies', function ($companyQuery) use ($searchValue) {
                        $companyQuery->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('shipment', function ($shipmentQuery) use ($searchValue) {
                        $shipmentQuery->where('shipment_id', 'like', "%{$searchValue}%");
                    })
                    ->orWhere('id', $searchValue);
            });
        }

        // Get filtered count
        $filteredRecords = $query->count();

        // Apply pagination
        $sales = $query->skip($start)->take($length)->get();

        // Return JSON for DataTables
        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $sales,
        ]);
    }

    public function showPendingSales($status)
    {
        return view('ship_ticket_sales.index', compact('status'));
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
            'coPassengers',
            'payments',
            'shipment',
            'printedTickets',
            'verifyby.verifiedByUser:id,name',
        ])->findOrFail($id);

        $maxNumber = PrintedTicket::where('filename', 'like', $sale->whatsapp.'-%')
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(filename, "-", -1) AS UNSIGNED)) as number')
            ->value('number');

        $number = $maxNumber ?? 0;

        $groupByStatus = 'no';
        $groupById = null;
        if ($number > 0) {

            $latestTicket = PrintedTicket::where('filename', 'like', $sale->whatsapp.'-%')
                ->orderByRaw('CAST(SUBSTRING_INDEX(filename, "-", -1) AS UNSIGNED) DESC')
                ->first();

            if ($latestTicket) {
                $saleStatus = ShipTicketSale::where('id', $latestTicket->sales_id)
                    ->value('status');

                if (in_array($saleStatus, ['ticket-issued', 'ticket-printed'])) {
                    $groupByStatus = 'yes';
                    $groupById = $latestTicket->group_by_id ?? $latestTicket->sales_id;
                }
            }
        }

        $totalDepartureTickets = $sale->categories
            ->where('type', 'departure')
            ->sum('quantity');

        $totalReturnTickets = $sale->categories
            ->where('type', 'return')
            ->sum('quantity');

        // Add these totals to the sale object for easy access in view

        if ($sale->status == 'payment-verified') {
            $sale->total_departure_tickets = $totalDepartureTickets;
            $sale->total_return_tickets = $totalReturnTickets;
        } else {
            $totalDepartureTickets = 0;
            $totalReturnTickets = 0;
        }

        // Find next sale with SAME STATUS
        $nextSale = ShipTicketSale::where('status', $sale->status)
            ->where('id', '>', $sale->id)
            ->orderBy('id', 'asc')
            ->first();
        $ships = Ship::all();
        $companies = Company::all();

        return view('ship_ticket_sales.edit', compact('sale', 'number', 'groupByStatus', 'groupById', 'ships', 'companies', 'nextSale', 'totalReturnTickets', 'totalDepartureTickets'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $sale = ShipTicketSale::findOrFail($id);

        return view('ship_ticket_sales.edit', compact('sale'));
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
        if ($status == 'ticket-printed') {

            $tickets = PrintedTicket::where('group_by_id', $id)
                ->latest()
                ->get()
                ->unique('sales_id')
                ->values();

            DB::transaction(function () use ($tickets): void {
                $tickets->each(function ($ticket) {

                    $sale = ShipTicketSale::find($ticket->sales_id);

                    if ($sale) {

                        $sale->update([
                            'status' => 'ticket-printed',
                        ]);

                        VerifyTracker::create([
                            'name' => 'ticket-printed',
                            'ticket_id' => $sale->id,
                            'verified_by' => auth()->id(),
                        ]);
                    }
                });
            });

            return response()->json([
                'success' => true,
                'message' => 'Sales updated to ticket-printed successfully',
            ]);
        }

        $sale = ShipTicketSale::findOrFail($id);
        if ($status == 'shipment_id_entered') {
            $bulkParcelData = [
                [
                    'invoice' => 'TICKET-'.$sale->id,
                    'recipient_name' => $sale->customer_name,
                    'recipient_phone' => $sale->customer_mobile,
                    'recipient_address' => $sale->address ?? 'N/A',
                    'cod_amount' => $sale->due_amount + 100 ?? 100,
                    'note' => 'Journey ticket booking ID: '.$sale->id,
                    'delivery_type' => 0,
                ],
            ];

            // Use the injected service
            $steadfastResult = $this->steadfast->bulkCreate($bulkParcelData);

            $consignmentId = null;

            // Option 1: Direct access if you know the structure
            if (isset($steadfastResult['data'][0]['consignment_id'])) {
                $consignmentId = $steadfastResult['data'][0]['consignment_id'];
            }

            // Option 2: Safer approach with validation
            if (! empty($steadfastResult['data']) && is_array($steadfastResult['data'])) {
                $firstResult = $steadfastResult['data'][0] ?? null;
                if ($firstResult && isset($firstResult['consignment_id'])) {
                    $consignmentId = $firstResult['consignment_id'];
                }
            }

            // Check if we got a consignment_id
            if (! $consignmentId) {
                // Handle error - log it or throw exception
                Log::error('Failed to get consignment_id from Steadfast response', $steadfastResult);

                return response()->json(['success' => false, 'message' => 'Failed to create shipment'], 500);
            }

            $tickets = PrintedTicket::where('group_by_id', $id)
                ->latest()
                ->get()
                ->unique('sales_id')
                ->values();

            DB::transaction(function () use ($tickets, $consignmentId): void {
                $tickets->each(function ($ticket) use ($consignmentId) {

                    $sale = ShipTicketSale::find($ticket->sales_id);

                    if ($sale) {

                        Shipment::create([
                            'ticket_id' => $sale->id,
                            'shipment_id' => $consignmentId,
                        ]);

                        $sale->update([
                            'status' => 'shipment_id_entered',
                        ]);

                        VerifyTracker::create([
                            'name' => 'shipment_id_entered',
                            'ticket_id' => $sale->id,
                            'verified_by' => auth()->id(),
                        ]);
                    }
                });
            });

            return response()->json([
                'success' => true,
                'message' => 'Sales updated to ticket-printed successfully',
            ]);
        }

        DB::transaction(function () use ($sale, $status, $id): void {
            $sale->update(['status' => $status]);

            VerifyTracker::create([
                'name' => $status,
                'ticket_id' => $id,
                'verified_by' => auth()->id(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Sale deleted successfully']);
    }

    public function pdfDownload($id)
    {
        $client = new Client;
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->addScope(Drive::DRIVE_READONLY);

        $driveService = new Drive($client);

        $folderId = '1Kw6lNhhch4H0SbXrNNNRWp_4mTEGvvCv';
        $fileName = $id.'.pdf';

        // 1ï¸âƒ£ Find file in Drive
        $query = "name='{$fileName}' and '{$folderId}' in parents and trashed=false";
        $files = $driveService->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name)',
            'pageSize' => 1,
        ]);

        if (count($files->getFiles()) === 0) {
            abort(404, 'PDF not found in Google Drive');
        }

        $fileId = $files->getFiles()[0]->getId();

        // 2ï¸âƒ£ Stream PDF directly to browser
        $response = $driveService->files->get($fileId, ['alt' => 'media']);

        return response()->stream(
            function () use ($response) {
                echo $response->getBody()->getContents();
            },
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$fileName}\"",
            ]
        );
    }

    // ShipTicketSaleController.php
    public function pdfPrintAll()
    {
        return ShipTicketSale::where('status', 'ticket-issued')
            ->orderBy('id')
            ->pluck('id');
    }

    public function openTicket($saleId, $filename)
    {
        // Increment print count
        $sales = PrintStatus::where('sales_id', $saleId)->first();

        if ($sales) {
            $sales->increment('total_printed_number');
        } else {
            PrintStatus::create([
                'sales_id' => $saleId,
                'total_printed_number' => 1,
            ]);
        }

        // Google Drive client
        $client = new Client;
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->addScope(Drive::DRIVE_READONLY);

        $driveService = new Drive($client);

        $folderId = '1Kw6lNhhch4H0SbXrNNNRWp_4mTEGvvCv';

        // Use the actual filename passed
        $query = "name='{$filename}' 
              and '{$folderId}' in parents 
              and mimeType='application/pdf' 
              and trashed=false";

        $files = $driveService->files->listFiles([
            'q' => $query,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);

        if (count($files->getFiles()) === 0) {
            return redirect()->back()->with('error', 'Ticket not found in Google Drive');
        }

        $fileId = $files->getFiles()[0]->getId();

        // Correct URL using Drive file ID
        $driveUrl = "https://drive.google.com/file/d/{$fileId}/view?print=true";

        return redirect()->away($driveUrl);
    }
}
