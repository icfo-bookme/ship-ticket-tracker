<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\Refunds\FullRefundRequest;
use App\Http\Requests\Refunds\PartialRefundRequest;
use App\Http\Requests\Refunds\StoreRefundRequest;
use App\Http\Requests\Refunds\UpdateRefundRequest;
use App\Models\Company;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Services\Refunds\RefundService;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(private readonly RefundService $refunds) {}

    public function index()
    {
        $refunds = Refund::all();

        return response()->json($refunds);
    }

    public function refundableCS(Request $request)
    {
        $shipId = $request->input('ship_id');
        $companyId = $request->input('company_id');
        $journeyDate = $request->input('journey_date');

        // DataTables parameters
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $searchValue = $request->input('search.value', '');

        $query = ShipTicketSale::with([
            'ships',
            'companies',
            'payments',
            'categories.package',
        ])
            ->whereDoesntHave('refunds', function ($refunds): void {
                $refunds->whereNotIn('status', ['cancelled']);
            })
            ->whereNotIn('status', [
                SaleStatus::Pending->value,
                SaleStatus::Refunded->value,
                SaleStatus::PartialRefunded->value,
            ]);

        // Apply filters
        if ($shipId && ! empty($shipId)) {
            $query->where('ship_id', $shipId);
        }

        if ($companyId && ! empty($companyId)) {
            $query->where('company_id', $companyId);
        }

        if ($journeyDate && ! empty($journeyDate)) {
            $query->whereDate('journey_date', $journeyDate);
        }

        // Total records ignoring search (DataTables convention)
        $totalRecords = $query->count();

        // Apply search
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                // Direct table columns
                $q->where('customer_name', 'like', "%{$searchValue}%")
                    ->orWhere('customer_mobile', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('nid', 'like', "%{$searchValue}%")
                    ->orWhere('sales_source', 'like', "%{$searchValue}%")
                    ->orWhere('ticket_fee', 'like', "%{$searchValue}%")
                    ->orWhereHas('payments', fn ($payments) => $payments->where('payment_method', 'like', "%{$searchValue}%"))
                    ->orWhere('number_of_ticket', 'like', "%{$searchValue}%")
                    ->orWhere('received_amount', 'like', "%{$searchValue}%")
                    ->orWhere('due_amount', 'like', "%{$searchValue}%")
                    ->orWhere('sold_by', 'like', "%{$searchValue}%")
                    ->orWhereHas('categories.package', fn ($packages) => $packages->where('name', 'like', "%{$searchValue}%"))
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

        $recordsFiltered = $query->count();

        $sales = $query->skip($start)
            ->orderBy('id', 'asc')
            ->take($length)
            ->get()
            ->each(fn (ShipTicketSale $sale) => $this->appendPaymentAndCategoryLabels($sale));

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $recordsFiltered,
            'data' => $sales,
        ]);
    }

    public function create()
    {
        $ships = Ship::all();
        $companies = Company::all();

        return view('refund.componentItem', compact('ships', 'companies'));
    }

    public function refunded(Request $request)
    {
        try {
            $shipId = $request->input('ship_id');
            $companyId = $request->input('company_id');
            $journeyDate = $request->input('journey_date');

            // DataTables parameters
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $searchValue = $request->input('search.value', '');

            $query = ShipTicketSale::with(['ships', 'companies', 'refunds', 'payments', 'categories.package'])
                ->where(function ($sales): void {
                    $sales->whereIn('status', [
                        SaleStatus::Refunded->value,
                        SaleStatus::PartialRefunded->value,
                    ])->orWhereHas('refunds', function ($refunds): void {
                        $refunds->where('status', 'completed')
                            ->orWhereNotNull('customer_refunded_at');
                    });
                });

            if (! empty($shipId)) {
                $query->where('ship_id', $shipId);
            }

            if (! empty($companyId)) {
                $query->where('company_id', $companyId);
            }

            if (! empty($journeyDate)) {
                $query->whereDate('journey_date', $journeyDate);
            }

            if (! empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('customer_name', 'like', "%{$searchValue}%")
                        ->orWhere('customer_mobile', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%")
                        ->orWhere('nid', 'like', "%{$searchValue}%")
                        ->orWhere('sales_source', 'like', "%{$searchValue}%")
                        ->orWhere('ticket_fee', 'like', "%{$searchValue}%")
                        ->orWhereHas('payments', fn ($payments) => $payments->where('payment_method', 'like', "%{$searchValue}%"))
                        ->orWhere('number_of_ticket', 'like', "%{$searchValue}%")
                        ->orWhere('received_amount', 'like', "%{$searchValue}%")
                        ->orWhere('due_amount', 'like', "%{$searchValue}%")
                        ->orWhere('sold_by', 'like', "%{$searchValue}%")
                        ->orWhereHas('categories.package', fn ($packages) => $packages->where('name', 'like', "%{$searchValue}%"))
                        ->orWhere('status', 'like', "%{$searchValue}%")
                        ->orWhereDate('journey_date', $searchValue)
                        ->orWhere('journey_date', 'like', "%{$searchValue}%")
                        ->orWhereDate('return_date', $searchValue)
                        ->orWhere('return_date', 'like', "%{$searchValue}%")
                        ->orWhereDate('issued_date', $searchValue)
                        ->orWhere('issued_date', 'like', "%{$searchValue}%")
                        ->orWhereHas('ships', function ($shipQuery) use ($searchValue) {
                            $shipQuery->where('name', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('companies', function ($companyQuery) use ($searchValue) {
                            $companyQuery->where('name', 'like', "%{$searchValue}%");
                        })
                        ->orWhere('id', $searchValue);
                });
            }

            $totalRecords = $query->count();

            $refundTotals = (clone $query)
                ->with('refunds')
                ->get()
                ->reduce(function (array $totals, ShipTicketSale $sale): array {
                    $completedRefunds = $sale->refunds->filter(
                        fn (Refund $refund): bool => $refund->status === 'completed' || $refund->customer_refunded_at !== null
                    );
                    $totals['tickets'] += (int) $completedRefunds->sum('refunded_number_of_tickets');
                    $totals['amount'] += (float) $completedRefunds->sum('refunded_amount');
                    $totals['gross_amount'] += (float) $completedRefunds->sum('gross_refund_amount');
                    $totals['customer_refund'] += (float) $completedRefunds->sum('customer_refund_amount');
                    $totals['partner_share'] += (float) $completedRefunds->sum('partner_share_amount');
                    $totals['company_retained'] += (float) $completedRefunds->sum('company_retained_amount');

                    return $totals;
                }, [
                    'tickets' => 0,
                    'amount' => 0,
                    'gross_amount' => 0,
                    'customer_refund' => 0,
                    'partner_share' => 0,
                    'company_retained' => 0,
                ]);

            $sales = $query->skip($start)
                ->take($length)
                ->get()
                ->each(function (ShipTicketSale $sale): void {
                    $completedRefunds = $sale->refunds->filter(
                        fn (Refund $refund): bool => $refund->status === 'completed' || $refund->customer_refunded_at !== null
                    );
                    $latestRefund = $completedRefunds->last();

                    $sale->setRelation('refund', (new Refund)->forceFill([
                        'id' => $latestRefund?->id,
                        'refunded_number_of_tickets' => $completedRefunds->sum('refunded_number_of_tickets'),
                        'refunded_amount' => $completedRefunds->sum('refunded_amount'),
                        'gross_refund_amount' => $completedRefunds->sum('gross_refund_amount'),
                        'customer_charge_percent' => $latestRefund?->customer_charge_percent,
                        'partner_share_percent' => $latestRefund?->partner_share_percent,
                        'customer_refund_amount' => $completedRefunds->sum('customer_refund_amount'),
                        'customer_refund_after_due_adjustment' => $completedRefunds->sum('customer_refund_amount'),
                        'due_adjusted_amount' => $completedRefunds->sum('due_adjusted_amount'),
                        'partner_share_amount' => $completedRefunds->sum('partner_share_amount'),
                        'company_retained_amount' => $completedRefunds->sum('company_retained_amount'),
                    ]));
                    $this->appendPaymentAndCategoryLabels($sale);
                });

            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $sales,
                'total_refunded_tickets' => $refundTotals['tickets'],
                'total_refunded_amount' => $refundTotals['amount'],
                'total_gross_amount' => $refundTotals['gross_amount'],
                'total_customer_refund' => $refundTotals['customer_refund'],
                'total_partner_share' => $refundTotals['partner_share'],
                'total_company_retained' => $refundTotals['company_retained'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while retrieving refund data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreRefundRequest $request)
    {
        $refund = $this->refunds->create($request->validated());

        return response()->json($refund, 201);
    }

    public function showRefundedCS()
    {
        $ships = Ship::all();
        $companies = Company::all();

        return view('refunded.index', compact('ships', 'companies'));
    }

    public function requested(Request $request)
    {
        $status = $request->input('status', 'requested');
        abort_unless(in_array($status, ['requested', 'partner_approved', 'payment_details_added'], true), 404);

        $query = Refund::with(['sale.ships', 'sale.companies', 'sale.categories.package', 'tickets'])
            ->whereNotNull('requested_at')
            ->whereNull('customer_refunded_at')
            ->where('status', $status);

        if ($request->filled('journey_date')) {
            $query->whereHas('sale', fn ($sale) => $sale->whereDate('journey_date', $request->input('journey_date')));
        }

        if ($request->filled('ship_id')) {
            $query->whereHas('sale', fn ($sale) => $sale->where('ship_id', $request->input('ship_id')));
        }

        if ($request->filled('company_id')) {
            $query->whereHas('sale', fn ($sale) => $sale->where('company_id', $request->input('company_id')));
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->whereHas('sale', function ($sale) use ($search): void {
                $sale->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_mobile', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        $total = $query->count();
        $requests = $query->orderBy('id', 'asc')
            ->skip((int) $request->input('start', 0))
            ->take((int) $request->input('length', 10))
            ->get();

        $requests->each(function (Refund $refund): void {
            $requestedQuantities = $refund->tickets->keyBy('category_id');
            $refund->setAttribute('total_purchase_tickets', (int) ($refund->sale?->categories->sum('quantity') ?? 0));
            $refund->setAttribute('total_refund_tickets', (int) $refund->tickets->sum('refunded_quantity'));
            $customerRefundAmount = (float) $refund->customer_refund_amount;
            $dueAmount = max((float) ($refund->sale?->due_amount ?? 0), 0);
            $dueAdjustment = round(min($dueAmount, $customerRefundAmount), 2);
            $refund->setAttribute('due_adjusted_amount', $dueAdjustment);
            $refund->setAttribute(
                'customer_refund_after_due_adjustment',
                round(max($customerRefundAmount - $dueAdjustment, 0), 2)
            );
            $refund->setAttribute('edit_categories', $refund->sale?->categories->map(function ($category) use ($requestedQuantities): array {
                return [
                    'id' => $category->id,
                    'category_id' => $category->id,
                    'type' => $category->type,
                    'quantity' => $category->quantity,
                    'purchased_quantity' => $category->quantity,
                    'refunded_quantity' => $requestedQuantities->get($category->id)?->refunded_quantity ?? 0,
                    'unit_amount' => $requestedQuantities->get($category->id)?->unit_amount,
                    'package' => $category->package,
                ];
            })->values() ?? collect());
        });

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $requests,
        ]);
    }

    public function showRequested()
    {
        return view('refund.requested', [
            'ships' => Ship::all(),
            'companies' => Company::all(),
            'refundStatus' => 'requested',
            'pageTitle' => 'Requested Refunds',
        ]);
    }

    public function showApproved()
    {
        return view('refund.requested', [
            'ships' => Ship::all(),
            'companies' => Company::all(),
            'refundStatus' => 'partner_approved',
            'pageTitle' => 'Partner Approved Refunds',
        ]);
    }

    public function showPaymentDetailsAdded()
    {
        return view('refund.requested', [
            'ships' => Ship::all(),
            'companies' => Company::all(),
            'refundStatus' => 'payment_details_added',
            'pageTitle' => 'Refunds Ready for Payment',
        ]);
    }

    public function fullRefunds(FullRefundRequest $request)
    {
        $this->refunds->fullRefund($request->validated('ids'));

        return response()->json(['status' => 'success', 'message' => 'Refund processed successfully.']);
    }

    public function partialRefund(PartialRefundRequest $request, $id)
    {
        $sale = ShipTicketSale::find($id);
        abort_unless($sale, 404, 'Sale not found.');

        $this->refunds->partialRefund($sale, $request->validated());

        return response()->json(['success' => true, 'message' => 'Refund request sent to partner.']);
    }

    public function refundCustomer(Request $request, int $id)
    {
        $refund = Refund::findOrFail($id);
        abort_unless($refund->status === 'payment_details_added', 422, 'Payment details must be added before refunding.');
        $data = $request->validate(['payment_method' => 'nullable|string|max:50', 'transaction_id' => 'nullable|string|max:150', 'payment_proof' => 'nullable|string|max:255', 'remark' => 'nullable|string|max:255']);
        $this->refunds->refundCustomer($refund, $data);

        return response()->json(['success' => true, 'message' => 'Customer refund completed.']);
    }

    public function approve(int $id)
    {
        $refund = Refund::findOrFail($id);
        $this->refunds->approve($refund);

        return response()->json([
            'success' => true,
            'message' => 'Refund request approved successfully.',
        ]);
    }

    public function addPaymentDetails(Request $request, int $id)
    {
        $details = $request->validate([
            'refund_payment_details' => 'required|string|max:5000',
        ])['refund_payment_details'];

        $this->refunds->addPaymentDetails(Refund::findOrFail($id), $details);

        return response()->json([
            'success' => true,
            'message' => 'Refund payment details added successfully.',
        ]);
    }

    public function show($id)
    {
        $refund = Refund::find($id);

        if (! $refund) {
            return response()->json(['message' => 'Refund not found'], 404);
        }

        return response()->json($refund);
    }

    public function edit($id)
    {
        // Not necessary for APIs, usually handled in web apps
    }

    public function update(UpdateRefundRequest $request, $id)
    {
        $refund = Refund::find($id);

        if (! $refund) {
            return response()->json(['message' => 'Refund not found'], 404);
        }

        $sale = ShipTicketSale::find($refund->sales_id);

        if (! $sale) {
            return response()->json(['message' => 'Associated sale not found'], 404);
        }

        $this->refunds->update($refund, $sale, $request->validated());

        return response()->json(['success' => true, 'message' => 'Refund updated successfully.']);
    }

    public function cancel(int $id)
    {
        $refund = Refund::findOrFail($id);
        $this->refunds->cancel($refund);

        return response()->json(['success' => true, 'message' => 'Refund request cancelled successfully.']);
    }

    // Remove the specified refund from storage
    public function destroy($id)
    {
        $refund = Refund::find($id);

        if (! $refund) {
            return response()->json(['message' => 'Refund not found'], 404);
        }

        $refund->delete();

        return response()->json(['message' => 'Refund deleted successfully']);
    }

    private function appendPaymentAndCategoryLabels(ShipTicketSale $sale): void
    {
        $sale->setAttribute('payment_method', $sale->payments->first()->payment_method ?? null);
        $sale->setAttribute('ticket_category', $sale->categories
            ->map(fn ($category) => $category->package?->name)
            ->filter()
            ->unique()
            ->implode(', '));
    }
}
