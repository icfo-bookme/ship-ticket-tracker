<?php

namespace App\Http\Controllers;

use App\Models\Bftn;
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
use App\Models\PrintedTicket;
use App\Models\PrintStatus;
use App\Models\User;
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

        // if ($status == 'payment-verified') {
        //     $this->printedCS();
        // }

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
            'verifyby.verifiedByUser'
        ])
            ->withCount('printedTickets')
            ->where('status', $status);
        $sales = $query->get();



        // Apply filters
        if (!empty($shipId)) {
            $query->where('ship_id', $shipId);
        }
        if (!empty($companyId)) {
            $query->where('company_id', $companyId);
        }
        if (!empty($journeyDate)) {
            $query->whereDate('journey_date', $journeyDate);
        }

        // Get total records BEFORE search
        $totalRecords = ShipTicketSale::where('status', $status)->count();

        // Apply search
        if (!empty($searchValue)) {
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
            'remark1'         => 'nullable|string',
            'remark2'         => 'nullable|string',
            'other_fee'       => 'nullable|numeric',
            'total_payable'   => 'nullable|numeric',
        ]);

        $ticketSale = ShipTicketSale::create($validated);


        if ($request->filled('co_passengers')) {
            foreach ($request->co_passengers as $coPassenger) {
                if (!empty($coPassenger['name'])) {
                    CoPassenger::create([
                        'ship_ticket_sale_id' => $ticketSale->id,
                        'name' => $coPassenger['name'],
                        'nid'  => $coPassenger['nid'],
                        'co_passernger_number'  => $coPassenger['co_passernger_number'],
                        'date_of_birth'  => $coPassenger['date_of_birth'],

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
                        'transaction_id'  => $payment_method['transaction_id'] ?? null,
                        'payment_datetime'  => $payment_method['payment_datetime'] ?? null,
                        'paid_date'  => $payment_method['paid_date'],
                        'remark'  => $payment_method['remark'],
                    ]);
                }
            }
        }

        if ($request->bftn_status == "yes" && $request->bftn_issue_datetime) {

            Bftn::create([
                'sales_id' => $ticketSale->id,
                'bftn_date_time' => $request->bftn_issue_datetime,
                'status'  => 0,
                'notifications_status'  => 1,
            ]);
        }

        if ($request->filled('ticket_categories')) {

            foreach ($request->ticket_categories as $type => $categories) {


                foreach ($categories as $category) {
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

        $ship = Ship::find($request->ship_id);
        $user = User::find($request->sold_by);

        // Prepare payment methods as a single string
        $paymentString = '';
        if (!empty($request->payment_methods)) {
            $payments = [];
            foreach ($request->payment_methods as $payment_method) {
                if (!empty($payment_method['method']) && !empty($payment_method['amount'])) {
                    $payments[] = $payment_method['method'] . '=' . $payment_method['amount'];
                }
            }
            $paymentString = implode(', ', $payments); // e.g. "Cash=100, Bikas=300"
        }

        // Now append row
        GoogleSheetService::appendRow([
            $validated['customer_name'],
            $validated['customer_mobile'],
            $validated['whatsapp'] ?? $validated['customer_mobile'],
            $validated['email'] ?? '',
            $ship->name,
            $request->sales_source,
            $validated['ticket_fee'],
            $request->received_amount,
            $paymentString,
            $user->name,
            now()->format('Y-m-d'),
            $request->address,
            $request->remark1,
            $request->remark2,
        ]);


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
            'sold_by'         => 'nullable|string|max:100',
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
        $paymentString = '';
        if (!empty($request->payment_methods)) {
            $payments = [];
            foreach ($request->payment_methods as $payment_method) {
                if (!empty($payment_method['method']) && !empty($payment_method['amount'])) {
                    $payments[] = $payment_method['method'] . '=' . $payment_method['amount'];
                }
            }
            $paymentString = implode(', ', $payments); // e.g. "Cash=100, Bikas=300"
        }
        $ship = Ship::find($request->ship_id);
        GoogleSheetService::appendRow([
            $validated['customer_name'],
            $validated['customer_mobile'],
            $validated['whatsapp'] ?? $validated['customer_mobile'],
            $validated['email'] ?? '',
            $ship->name,
            $whatsapp->whatsapp_number ?? 'not found',
            $validated['ticket_fee'],
            $request->received_amount,
            $paymentString,
            'guest',
            now()->format('Y-m-d'),
            $request->address,
            $request->remark1,
            $request->remark2,
        ]);



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
            'printedTickets',
            'verifyby.verifiedByUser:id,name'
        ])->findOrFail($id);


        $maxNumber = PrintedTicket::where('filename', 'like', $sale->whatsapp . '-%')
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(filename, "-", -1) AS UNSIGNED)) as number')
            ->value('number');

        $number = $maxNumber ?? 0;

        $groupByStatus = 'no';
        $groupById = null;
        if ($number > 0) {

            $latestTicket = PrintedTicket::where('filename', 'like', $sale->whatsapp . '-%')
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
    public function update(Request $request, $id)
    {


        $action = $request->input('action');

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
            'journey_date' => 'nullable|date',
            'return_date' => 'nullable|date',
            'number_of_ticket' => 'required|integer|min:1',
            'ticket_fee' => 'required|numeric|min:0',
            'other_fee' => 'nullable|numeric|min:0',
            'total_payable' => 'nullable|numeric|min:0',
            'received_amount' => 'nullable|numeric|min:0',
            'due_amount' => 'nullable|numeric',
            'bftn_status' => 'nullable',
            'sales_source' => 'nullable|string|max:255',
            'sold_by' => 'nullable|string|max:255',
            'issued_date' => 'nullable|date',
            'status' => 'required',
            'remark1' => 'nullable|string',
            'remark2' => 'nullable|string',
            'group_by_id' => 'nullable|integer',
            'group_tickets' => 'nullable|in:yes,no',
            'departure_quantity' => 'nullable|array',
            'return_quantity' => 'nullable|array',
            'departure_quantity.*' => 'nullable|integer|min:0',
            'return_quantity.*' => 'nullable|integer|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_method' => 'required|string',
            'payments.*.received_amount' => 'required|numeric|min:0',
            'payments.*.paid_date' => 'nullable|date',
            'payments.*.remark' => 'nullable|string',
            'co_passengers' => 'nullable|array',
            'co_passengers.*.name' => 'nullable|string|max:255',
            'co_passengers.*.nid' => 'nullable|string|max:255',
            'co_passengers.*.co_passernger_number' => 'nullable|string|max:20',
            'co_passengers.*.date_of_birth' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            $sale = ShipTicketSale::findOrFail($id);

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
                'ticket_fee' => $validated['ticket_fee'],
                'received_amount' => $validated['received_amount'] ?? 0,
                'due_amount' => $validated['due_amount'] ?? $validated['ticket_fee'],
                'other_fee' => $validated['other_fee'] ?? 0,
                'total_payable' => $validated['total_payable'] ?? 0,
                'bftn_status' => $validated['bftn_status'] ?? null,
                'sales_source' => $validated['sales_source'],
                'sold_by' => $validated['sold_by'],
                'issued_date' => $validated['issued_date'],
                'status' => $validated['status'],
                'remark1' => $validated['remark1'],
                'remark2' => $validated['remark2'],
            ]);

            // Update packages
            if ($request->departure_quantity || $request->return_quantity) {
                $this->updatePackageCategories($sale, $validated);
            }

            // Payments & co-passengers
            $this->updatePayments($sale, $validated['payments'] ?? []);
            $this->updateCoPassengers($sale, $validated['co_passengers'] ?? []);

            // ✅ Commit first
            DB::commit();


            if ($request->status === 'payment-verified') {

                if ($request->has('pdf') && is_array($request->pdf)) {

                    foreach ($request->pdf as $pdfValue) {

                        $pdfName = $pdfValue . '.pdf';

                        $data = [
                            'sales_id'   => $sale->id,
                            'filename'   => $pdfName,
                            'group_by_id' => ($validated['group_tickets'] ?? null) == 'yes'
                                ? $validated['group_by_id']
                                : $sale->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // Add group_by_id only if printed_tickets = yes



                        $sale->printedTickets()->create($data);
                    }
                }

                $sale->update([
                    'status' => 'ticket-issued',
                ]);
            }



            if ($request->next_sale_id) {
                return redirect()
                    ->route('ship-ticket-sales.show', $request->next_sale_id)
                    ->with('success', 'Ship ticket sale updated successfully and ready for verification!');
            }

            return redirect()->back()
                ->with('success', 'Ship ticket sale updated successfully!');



            // Redirect logic
            // if ($sale->status != 'payment-verified' && $action === 'update') {

            //     return redirect()->back()
            //         ->with('success', 'Ship ticket sale updated successfully!');
            // }
            // if ($sale->status != 'payment-verified' && $action === 'update_and_reverify') {

            //     return redirect()->route('gdrive.reverify', ['id' => $sale->id])
            //         ->with('success', 'Ship ticket sale updated successfully and ready for verification!');
            // }

            // if ($request->next_sale_id && $sale->status == 'payment-verified') {
            //     return redirect()->route('gdrive.verify', [
            //         'next_id' => $request->next_sale_id ?? null
            //     ]);
            // }
            // return redirect()->route('gdrive.verify')
            //     ->with('success', 'Ship ticket sale updated successfully and ready for verification!');
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

        // Process departure packages
        if (!empty($validated['departure_quantity'])) {
            foreach ($validated['departure_quantity'] as $packageId => $quantity) {
                $quantity = (int) $quantity;
                if ($quantity > 0) {
                    $sale->categories()->create([
                        'package_id' => (int) $packageId,
                        'type' => 'departure',
                        'quantity' => $quantity,
                    ]);
                }
            }
        }

        // Process return packages
        if (!empty($validated['return_quantity'])) {
            foreach ($validated['return_quantity'] as $packageId => $quantity) {
                $quantity = (int) $quantity;
                if ($quantity > 0) {
                    $sale->categories()->create([
                        'package_id' => (int) $packageId,
                        'type' => 'return',
                        'quantity' => $quantity,
                    ]);
                }
            }
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
                    'co_passenger_number' => $passenger['co_passenger_number'] ?? null,
                    "date_of_birth" => $passenger['date_of_birth'] ?? null,
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


        if ($status == "ticket-printed") {

            $tickets = PrintedTicket::where('group_by_id', $id)
                ->latest()
                ->get()
                ->unique('sales_id')
                ->values();

            $tickets->each(function ($ticket) {

                $sale = ShipTicketSale::find($ticket->sales_id);
              
                if ($sale) {

                    $sale->update([
                        'status' => 'ticket-printed'
                    ]);

                    VerifyTracker::create([
                        'name'        => 'ticket-printed',
                        'ticket_id'   => $sale->id,
                        'verified_by' => auth()->id(),
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Sales updated to ticket-printed successfully'
            ]);
        }


        $sale = ShipTicketSale::findOrFail($id);
        if ($status == "shipment_id_entered") {
            $bulkParcelData = [
                [
                    'invoice'          => 'TICKET-' . $sale->id,
                    'recipient_name'   => $sale->customer_name,
                    'recipient_phone'  => $sale->customer_mobile,
                    'recipient_address' => $sale->address ?? 'N/A',
                    'cod_amount'       => $sale->due_amount + 100 ?? 100,
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



    public function printedCS(Request $request)
    {
        $nextId = $request->query('next_id', null);

        // Google Drive client setup
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->addScope(\Google_Service_Drive::DRIVE_READONLY);

        $driveService = new \Google_Service_Drive($client);
        $folderId = '1Kw6lNhhch4H0SbXrNNNRWp_4mTEGvvCv';

        // ✅ Get last stored PDF time from DB
        $lastStoredPdf = PrintedTicket::orderBy('created_at', 'desc')->first();
        $lastStoredTime = $lastStoredPdf ? $lastStoredPdf->created_at->setTimezone('UTC')->toRfc3339String() : null;

        // Get all payment-verified sales
        $sales = ShipTicketSale::where('status', 'payment-verified')
            ->get(['id', 'whatsapp']);

        if ($sales->isEmpty()) {
            return back()->with('success', 'No verified tickets found.');
        }

        $salesByWhatsapp = $sales->pluck('id', 'whatsapp')->toArray();

        $updatedIds = [];
        $insertData = [];

        // 🔁 Retry loop for 5 times in case files not appear instantly
        for ($i = 0; $i < 5; $i++) {
            sleep(2); // wait 2 seconds between retries

            $query = "'{$folderId}' in parents and mimeType='application/pdf' and trashed=false";
            if ($lastStoredTime) {
                $query .= " and createdTime > '{$lastStoredTime}'";
            }

            $files = $driveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(name, createdTime)',
                'pageSize' => 1000,
            ]);

            $pdfNames = collect($files->getFiles())->pluck('name')->toArray();

            // Match PDF names with whatsapp numbers
            foreach ($pdfNames as $pdfName) {
                foreach ($salesByWhatsapp as $whatsapp => $saleId) {
                    if (str_contains($pdfName, (string)$whatsapp)) {

                        if (!PrintedTicket::where('filename', $pdfName)->exists()) {
                            $updatedIds[$saleId] = true;

                            $insertData[] = [
                                'sales_id' => $saleId,
                                'filename' => $pdfName,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
            }


            if (!empty($updatedIds)) {
                break; // exit retry loop if found
            }
        }

        // ✅ DB transaction to update sale status and insert new PDFs
        DB::transaction(function () use ($updatedIds, $insertData) {
            if (!empty($updatedIds)) {
                ShipTicketSale::whereIn('id', array_keys($updatedIds))
                    ->update(['status' => 'ticket-issued']);
            }

            if (!empty($insertData)) {
                PrintedTicket::insert($insertData);
            }
        });

        // Redirect or back with success message
        if ($nextId) {
            return redirect()->route('ship-ticket-sales.show', ['ship_ticket_sale' => $nextId])->with(
                'success',
                count($updatedIds) . ' ticket(s) verified successfully.'
            );
        } else {
            return back()->with(
                'success',
                count($updatedIds) . ' ticket(s) verified successfully.'
            );
        }
    }


    public function reprintedCS(Request $request, $id)
    {
        $nextId = $request->query('next_id', null);

        // Google Drive client setup
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->addScope(\Google_Service_Drive::DRIVE_READONLY);

        $driveService = new \Google_Service_Drive($client);
        $folderId = '1Kw6lNhhch4H0SbXrNNNRWp_4mTEGvvCv';

        // ✅ Get last stored PDF time from DB
        $lastStoredPdf = PrintedTicket::orderBy('created_at', 'desc')->first();
        $lastStoredTime = $lastStoredPdf ? $lastStoredPdf->created_at->setTimezone('UTC')->toRfc3339String() : null;

        // Get the single sale
        $sale = ShipTicketSale::find($id);

        if (!$sale) {
            return back()->with('error', 'No verified ticket found.');
        }

        // Make whatsapp lookup array
        $salesByWhatsapp = [$sale->whatsapp => $sale->id];

        $updatedIds = [];
        $insertData = [];

        // 🔁 Retry loop for 5 times
        for ($i = 0; $i < 5; $i++) {
            sleep(2); // wait 2 seconds between retries

            $query = "'{$folderId}' in parents and mimeType='application/pdf' and trashed=false";
            if ($lastStoredTime) {
                $query .= " and createdTime > '{$lastStoredTime}'";
            }

            $files = $driveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(name, createdTime)',
                'pageSize' => 1000,
            ]);

            $pdfNames = collect($files->getFiles())->pluck('name')->toArray();

            // Match PDF names with whatsapp number
            foreach ($pdfNames as $pdfName) {
                foreach ($salesByWhatsapp as $whatsapp => $saleId) {
                    if (str_contains($pdfName, (string)$whatsapp)) {
                        // Avoid duplicate insertion
                        if (!PrintedTicket::where('filename', $pdfName)->exists()) {
                            $updatedIds[$saleId] = true;

                            $insertData[] = [
                                'sales_id' => $saleId,
                                'filename' => $pdfName,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
            }

            if (!empty($updatedIds)) {
                break; // exit retry loop if found
            }
        }

        // ✅ DB transaction to update sale status and insert new PDFs
        DB::transaction(function () use ($updatedIds, $insertData) {
            if (!empty($updatedIds)) {
                ShipTicketSale::whereIn('id', array_keys($updatedIds))
                    ->update(['status' => 'ticket-issued']);
            }

            if (!empty($insertData)) {
                PrintedTicket::insert($insertData);
            }
        });

        // Redirect or back with success message
        if ($nextId) {
            return redirect()->route('ship-ticket-sales.show', ['ship_ticket_sale' => $nextId])
                ->with('success', count($updatedIds) . ' ticket(s) verified successfully.');
        } else {
            return back()->with('success', count($updatedIds) . ' ticket(s) verified successfully.');
        }
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
        $client = new Client();
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
