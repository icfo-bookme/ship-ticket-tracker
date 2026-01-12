<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use App\Services\GoogleSheetService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\ShipTicketSale;
use App\Models\Ship;
use App\Models\VerifyTracker;
use App\Models\CoPassenger;
use App\Models\Shipment;
use App\Models\Company;
use App\Models\Category;
use App\Models\Payment;
use App\Models\WhatsappDetail;
use App\Services\SteadfastService;
use Illuminate\Support\Facades\Log;


class ShipTicketSaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sales = ShipTicketSale::with('ships')->latest()->get();
        $ships = Ship::all();
        return view('ship_ticket_sales.index', compact('sales', 'ships'));
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

        $query = ShipTicketSale::with([
            'ships.packages',
            'categories',
            'companies',
            'coPassengers',
            'shipment',
            'verifyby' => function ($q) use ($status) {
                $q->where('name', $status)
                    ->with('verifiedByUser:id,name');
            }
        ])->where('status', $status);


        // Apply filters
        if ($shipId && !empty($shipId)) {
            $query->where('ship_id', $shipId);
        }

        if ($companyId && !empty($companyId)) {
            $query->where('company_id', $companyId);
        }

        if ($journeyDate && !empty($journeyDate)) {
            $query->whereDate('journey_date', $journeyDate);
        }

        // Apply search
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                // Direct table columns
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

                    // Date fields (search by formatted date or raw value)
                    ->orWhereDate('journey_date', $searchValue)
                    ->orWhere('journey_date', 'like', "%{$searchValue}%")
                    ->orWhereDate('return_date', $searchValue)
                    ->orWhere('return_date', 'like', "%{$searchValue}%")
                    ->orWhereDate('issued_date', $searchValue)
                    ->orWhere('issued_date', 'like', "%{$searchValue}%")

                    // Related tables (ships)
                    ->orWhereHas('ships', function ($shipQuery) use ($searchValue) {
                        $shipQuery->where('name', 'like', "%{$searchValue}%");
                    })

                    // Related tables (companies)
                    ->orWhereHas('companies', function ($companyQuery) use ($searchValue) {
                        $companyQuery->where('name', 'like', "%{$searchValue}%");
                    })

                    // Search by ID
                    ->orWhere('id', $searchValue);
            });
        }

        // Get total count before pagination
        $totalRecords = $query->count();

        // Apply pagination
        $sales = $query->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $sales
        ]);
    }


    public function showPendingSales($status)
    {
        return view('ship_ticket_sales.index', compact('status'));
    }
    /**
     * Show the form for creating a new resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name'   => 'required|string|max:100',
            'customer_mobile' => 'required|string|max:20',
            'whatsapp'        => 'nullable|string|max:20',
            'nid'             => 'nullable|string|max:50',
            'email'           => 'nullable|string|max:100',
            'sales_source'    => 'nullable|string|max:255',
            'ship_id'         => 'required|string|max:100',
            'address'         => 'nullable|string',
            'journey_date'    => 'nullable|date',
            'date_of_birth'   => 'nullable|date',
            'return_date'     => 'nullable|date',
            'ticket_fee'      => 'required|numeric',
            'received_amount' => 'required|numeric',
            'number_of_ticket' => 'required|numeric',
            'ticket_category' => 'nullable|string|max:255',
            'due_amount'      => 'nullable|numeric',
            'bftn_status'      => 'nullable',
            'company_id'      => 'nullable|string|max:100',
            'issued_date'     => 'required|date',
            'sold_by'         => 'required|string|max:100',
            'remark1'         => 'nullable|string|max:255',
            'remark2'         => 'nullable|string|max:255',
        ]);

        $ticketSale = ShipTicketSale::create($validated);

        if ($request->filled('co_passengers')) {
            foreach ($request->co_passengers as $coPassenger) {
                if (!empty($coPassenger['name']) && !empty($coPassenger['nid'])) {
                    CoPassenger::create([
                        'ship_ticket_sale_id' => $ticketSale->id,
                        'name' => $coPassenger['name'],
                        'nid'  => $coPassenger['nid'],
                        'co_passernger_number'  => $coPassenger['co_passernger_number'],
                    ]);
                }
            }
        }

        if ($request->filled('payment_methods')) {
            foreach ($request->payment_methods as $payment_method) {
                if (!empty($payment_method['method']) && !empty($payment_method['amount'])) {
                    Payment::create([
                        'sales_id' => $ticketSale->id,
                        'payment_method' => $payment_method['method'],
                        'received_amount'  => $payment_method['amount'],
                        'paid_date'  => $payment_method['paid_date'],
                    ]);
                }
            }
        }

        if ($request->filled('ticket_categories')) {

            foreach ($request->ticket_categories as $type => $categories) {


                foreach ($categories as $category) {
                    // Debug each category (optional)


                    if (
                        $category['quantity'] > 0
                    ) {
                        Category::create([
                            'ticket_id'  => $ticketSale->id,
                            'package_id' => $category['package_id'],
                            'quantity'   => $category['quantity'],
                            'type'       => $type,

                        ]);
                    }
                }
            }
        }

        return redirect()->back()
            ->with('success', 'Journey ticket saved!.');
    }
    protected $steadfast;

    public function __construct(SteadfastService $steadfast)
    {
        $this->steadfast = $steadfast;
    }

    public function publicStore(Request $request)
    {
        $validated = $request->validate([
            'customer_name'   => 'required|string|max:100',
            'customer_mobile' => 'required|string|max:20',
            'whatsapp'        => 'nullable|string|max:20',
            'nid'             => 'nullable|string|max:50',
            'email'           => 'nullable|string|max:100',
            'sales_source'    => 'nullable|string|max:255',
            'ship_id'         => 'required|string|max:100',
            'address'         => 'nullable|string',
            'journey_date'    => 'nullable|date',
            'date_of_birth'   => 'nullable|date',
            'return_date'     => 'nullable|date',
            'ticket_fee'      => 'required|numeric',
            'received_amount' => 'required|numeric',
            'number_of_ticket' => 'required|numeric',
            'ticket_category' => 'nullable|string|max:255',
            'due_amount'      => 'nullable|numeric',
            'bftn_status'      => 'nullable',
            'company_id'      => 'nullable|string|max:100',
            'issued_date'     => 'required|date',
            'sold_by'         => 'required|string|max:100',
            'remark1'         => 'nullable|string|max:255',
            'remark2'         => 'nullable|string|max:255',
        ]);

        if ($request->sales_source) {
            $whatsapp = WhatsappDetail::where('form_no', $request->sales_source)->first();

            if ($whatsapp) {
                $validated['sales_source'] = $whatsapp->whatsapp_number;
            } else {
                $validated['sales_source'] = null;
            }
        }

        $ticketSale = ShipTicketSale::create($validated);

        if ($request->filled('co_passengers')) {
            foreach ($request->co_passengers as $coPassenger) {
                if (!empty($coPassenger['name']) && !empty($coPassenger['nid'])) {
                    CoPassenger::create([
                        'ship_ticket_sale_id' => $ticketSale->id,
                        'name' => $coPassenger['name'],
                        'nid'  => $coPassenger['nid'],
                        'co_passernger_number'  => $coPassenger['co_passernger_number'],
                    ]);
                }
            }
        }

        if ($request->filled('payment_methods')) {
            foreach ($request->payment_methods as $payment_method) {
                if (!empty($payment_method['method']) && !empty($payment_method['amount'])) {
                    Payment::create([
                        'sales_id' => $ticketSale->id,
                        'payment_method' => $payment_method['method'],
                        'received_amount'  => $payment_method['amount'],
                        'paid_date'  => $payment_method['paid_date'],
                    ]);
                }
            }
        }

        if ($request->filled('ticket_categories')) {

            foreach ($request->ticket_categories as $type => $categories) {


                foreach ($categories as $category) {
                    // Debug each category (optional)


                    if (
                        $category['quantity'] > 0
                    ) {
                        Category::create([
                            'ticket_id'  => $ticketSale->id,
                            'package_id' => $category['package_id'],
                            'quantity'   => $category['quantity'],
                            'type'       => $type,

                        ]);
                    }
                }
            }
        }

        // GoogleSheetService::appendRow([
        //     $validated['customer_name'],
        //     $validated['customer_mobile'],
        //     $validated['email'] ?? '',
        //     $validated['ticket_fee'],
        //     now()->format('Y-m-d'),
        // ]);

        $bulkParcelData = [
            [
                'invoice'          => 'TICKET-' . $ticketSale->id,
                'recipient_name'   => $ticketSale->customer_name,
                'recipient_phone'  => $ticketSale->customer_mobile,
                'recipient_address' => $ticketSale->address ?? 'N/A',
                'cod_amount'       => $ticketSale->due_amount ?? 0,
                'note'             => 'Journey ticket booking ID: ' . $ticketSale->id,
                'delivery_type'    => 0,
            ]
        ];

        // Use the injected service
        $steadfastResult = $this->steadfast->bulkCreate($bulkParcelData);

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
            "payments",
            "shipment",
            'verifyby.verifiedByUser:id,name'
        ])->findOrFail($id);

        // Find next sale with SAME STATUS
        $nextSale = ShipTicketSale::where('status', $sale->status)
            ->where('id', '>', $sale->id)
            ->orderBy('id', 'asc')
            ->first();
        $ships = Ship::all();
        $companies = Company::all();

        return view('ship_ticket_sales.edit', compact('sale', 'ships', 'companies', 'nextSale'));
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
    public function update(Request $request, $id)
    {
        // Validate the request
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_mobile' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'nid' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'ship_id' => 'required|exists:ships,id',
            'company_id' => 'required|exists:company,id',
            'journey_date' => 'required|date',
            'return_date' => 'nullable|date',
            'number_of_ticket' => 'required|integer|min:1',
            'ticket_category' => 'nullable|string|max:255',
            'ticket_fee' => 'required|numeric|min:0',
            'received_amount' => 'nullable|numeric|min:0',
            'due_amount' => 'nullable|numeric|min:0',
            'bftn_status'      => 'nullable',
            'sales_source' => 'nullable|string|max:255',
            'sold_by' => 'nullable|string|max:255',
            'issued_date' => 'nullable|date',
            'status' => 'required',
            'remark1' => 'nullable|string',
            'remark2' => 'nullable|string',
            'departure_package' => 'required|exists:ship_packages,id',
            'return_package' => 'nullable|exists:ship_packages,id',
            'payments' => 'nullable|array',
            'payments.*.payment_method' => 'required|string',
            'payments.*.received_amount' => 'required|numeric|min:0',
            'payments.*.paid_date' => 'nullable|date',
            'payments.*.remark' => 'nullable|string',
            'co_passengers' => 'nullable|array',
            'co_passengers.*.name' => 'nullable|string|max:255',
            'co_passengers.*.nid' => 'nullable|string|max:255',
            'co_passengers.*.co_passernger_number' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();

        try {
            // Find the sale record
            $sale = ShipTicketSale::findOrFail($id);

            // Update main sale record
            $sale->update([
                'customer_name' => $validated['customer_name'],
                'customer_mobile' => $validated['customer_mobile'],
                'whatsapp' => $validated['whatsapp'],
                'email' => $validated['email'],
                'nid' => $validated['nid'],
                'date_of_birth' => $validated['date_of_birth'],
                'address' => $validated['address'],
                'ship_id' => $validated['ship_id'],
                'company_id' => $validated['company_id'],
                'journey_date' => $validated['journey_date'],
                'return_date' => $validated['return_date'],
                'number_of_ticket' => $validated['number_of_ticket'],
                'ticket_category' => $validated['ticket_category'],
                'ticket_fee' => $validated['ticket_fee'],
                'received_amount' => $validated['received_amount'] ?? 0,
                'due_amount' => $validated['due_amount'] ?? $validated['ticket_fee'],
                'sales_source' => $validated['sales_source'],
                'sold_by' => $validated['sold_by'],
                'issued_date' => $validated['issued_date'],
                'status' => $validated['status'],
                'remark1' => $validated['remark1'],
                'remark2' => $validated['remark2'],
            ]);

            // Update package categories (departure and return)
            $this->updatePackageCategories($sale, $validated);

            // Update payments
            $this->updatePayments($sale, $validated['payments'] ?? []);

            // Update co-passengers
            $this->updateCoPassengers($sale, $validated['co_passengers'] ?? []);

            DB::commit();
            if (!$request->next_sale_id) {
                return redirect()->route('ship-ticket-sales.show', $id)
                    ->with('success', 'Ship ticket sale updated successfully!');
            }
            return redirect()->route('ship-ticket-sales.show', $request->next_sale_id)
                ->with('success', 'Ship ticket sale updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to update ship ticket sale: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update package categories for departure and return
     */
    private function updatePackageCategories(ShipTicketSale $sale, array $validated)
    {
        // Delete existing categories
        $sale->categories()->delete();

        // Create departure package category
        $sale->categories()->create([
            'package_id' => $validated['departure_package'],
            'type' => 'departure'
        ]);

        // Create return package category if provided
        if (!empty($validated['return_package'])) {
            $sale->categories()->create([
                'package_id' => $validated['return_package'],
                'type' => 'return'
            ]);
        }
    }

    /**
     * Update payments for the sale
     */
    private function updatePayments(ShipTicketSale $sale, array $payments)
    {
        // Delete existing payments
        $sale->payments()->delete();

        // Create new payments
        foreach ($payments as $payment) {
            if (!empty($payment['payment_method']) && !empty($payment['received_amount'])) {
                $sale->payments()->create([
                    'payment_method' => $payment['payment_method'],
                    'received_amount' => $payment['received_amount'],
                    'paid_date' => $payment['paid_date'] ?? null,
                    'remark' => $payment['remark'] ?? null,
                ]);
            }
        }
    }

    /**
     * Update co-passengers for the sale
     */
    private function updateCoPassengers(ShipTicketSale $sale, array $coPassengers)
    {
        // Delete existing co-passengers
        $sale->coPassengers()->delete();

        // Create new co-passengers
        foreach ($coPassengers as $passenger) {
            if (!empty($passenger['name'])) {
                $sale->coPassengers()->create([
                    'name' => $passenger['name'],
                    'nid' => $passenger['nid'] ?? null,
                    'co_passernger_number' => $payment['co_passernger_number'] ?? null,
                ]);
            }
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


    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'customer_mobile' => 'nullable|string',
            'journey_date' => 'nullable|date',
        ]);

        if (empty($request->customer_mobile) || empty($request->journey_date)) {
            return response()->json([
                'exists' => false,
                'message' => null,
            ]);
        }

        $existingTicket = ShipTicketSale::where('customer_mobile', $request->customer_mobile)
            ->where('journey_date', $request->journey_date)
            ->first();

        return response()->json([
            'exists' => $existingTicket !== null,
            'message' => $existingTicket
                ? "This customer already has a ticket for {$request->journey_date} on {$existingTicket->sales_source}"
                : null,
        ]);
    }


    public function verify(Request $request, $id, $status)
    {


        $sale = ShipTicketSale::findOrFail($id);
        if ($status == "shipment_id_entered") {
            $bulkParcelData = [
                [
                    'invoice'          => 'TICKET-' . $sale->id,
                    'recipient_name'   => $sale->customer_name,
                    'recipient_phone'  => $sale->customer_mobile,
                    'recipient_address' => $sale->address ?? 'N/A',
                    'cod_amount'       => $sale->due_amount ?? 0,
                    'note'             => 'Journey ticket booking ID: ' . $sale->id,
                    'delivery_type'    => 0,
                ]
            ];



            // Use the injected service
            $steadfastResult = $this->steadfast->bulkCreate($bulkParcelData);

            $consignmentId = null;

            // Option 1: Direct access if you know the structure
            if (isset($steadfastResult['data'][0]['consignment_id'])) {
                $consignmentId = $steadfastResult['data'][0]['consignment_id'];
            }

            // Option 2: Safer approach with validation
            if (!empty($steadfastResult['data']) && is_array($steadfastResult['data'])) {
                $firstResult = $steadfastResult['data'][0] ?? null;
                if ($firstResult && isset($firstResult['consignment_id'])) {
                    $consignmentId = $firstResult['consignment_id'];
                }
            }

            // Check if we got a consignment_id
            if (!$consignmentId) {
                // Handle error - log it or throw exception
                Log::error('Failed to get consignment_id from Steadfast response', $steadfastResult);
                return response()->json(['success' => false, 'message' => 'Failed to create shipment'], 500);
            }

            Shipment::create([
                'ticket_id' => $sale->id,
                'shipment_id' => $consignmentId, // Use the extracted value
            ]);
        }

        $sale->update(['status' => $status]);

        VerifyTracker::create([
            'name' => $status,
            'ticket_id' => $id,
            'verified_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Sale deleted successfully']);
    }



    public function printedCS()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->addScope(Drive::DRIVE_READONLY);

        $driveService = new Drive($client);

        $folderId = '1Kw6lNhhch4H0SbXrNNNRWp_4mTEGvvCv';

        $sales = ShipTicketSale::where('status', 'payment-verified')->get();

        $updated = 0;

        foreach ($sales as $sale) {
            try {
                $fileName = $sale->id . '.pdf';

                // ✅ USE THE QUERY
                $query = "name='{$fileName}' and '{$folderId}' in parents and mimeType='application/pdf' and trashed=false";

                $files = $driveService->files->listFiles([
                    'q' => $query,
                    'fields' => 'files(id,name)',
                    'pageSize' => 1,
                ]);

                if (count($files->getFiles()) > 0) {
                    $sale->update(['status' => 'ticket-issued']);
                    $updated++;
                }
            } catch (\Exception $e) {
                \Log::error('Drive error: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with(
            'success',
            "{$updated} ticket(s) marked as printed from Google Drive."
        );
    }

    public function upload(Request $request)
    {
        $request->validate([
            'pdfs'   => 'required|array',
            'pdfs.*' => 'required|mimes:pdf|max:10240',
        ]);

        $uploadedFiles = [];

        foreach ($request->file('pdfs') as $pdf) {
            $filename = time() . '_' . uniqid() . '.' . $pdf->getClientOriginalExtension();

            $path = $pdf->storeAs('uploads/pdfs', $filename, 'public');

            $uploadedFiles[] = $path;
        }
        $this->printedCS();
        return back()->with('success', 'PDF files uploaded successfully.');
    }

    public function pdfDownload($id)
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->addScope(Drive::DRIVE_READONLY);

        $driveService = new Drive($client);

        $folderId = '1Kw6lNhhch4H0SbXrNNNRWp_4mTEGvvCv';
        $fileName = $id . '.pdf';

        // 1️⃣ Find file in Drive
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

        // 2️⃣ Stream PDF directly to browser
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
}
