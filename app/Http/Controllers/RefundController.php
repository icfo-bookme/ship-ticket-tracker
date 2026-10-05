<?php

namespace App\Http\Controllers;

use App\Enums\RefundStatus;
use App\Http\Requests\Refunds\FullRefundRequest;
use App\Http\Requests\Refunds\PartialRefundRequest;
use App\Http\Requests\Refunds\StoreRefundRequest;
use App\Http\Requests\Refunds\UpdateRefundRequest;
use App\Models\Company;
use App\Models\Refund;
use App\Models\Ship;
use App\Models\ShipTicketSale;
use App\Services\Refunds\RefundListingService;
use App\Services\Refunds\RefundService;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(
        private readonly RefundService $refunds,
        private readonly RefundListingService $refundListings,
    ) {}

    public function index()
    {
        $refunds = Refund::all();

        return response()->json($refunds);
    }

    public function refundableCS(Request $request)
    {
        return response()->json($this->refundListings->refundableSales($request));
    }

    public function create()
    {
        $ships = Ship::all();
        $companies = Company::all();

        return view('refund.index', compact('ships', 'companies'));
    }

    public function refunded(Request $request)
    {
        try {
            return response()->json($this->refundListings->refundedSales($request));
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

        return view('refund.requested', [
            'ships' => Ship::all(),
            'companies' => Company::all(),
            'refundStatus' => RefundStatus::Completed->value,
            'pageTitle' => 'Completed Refunds',
        ]);
    }

    public function refundedDetails(int $saleId)
    {
        $sale = $this->refundListings->refundedSale($saleId);

        return view('refunded.details', compact('sale'));
    }

    public function requested(Request $request)
    {
        return response()->json($this->refundListings->refundRequests($request));
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

        $this->refunds->delete($refund);

        return response()->json(['message' => 'Refund deleted successfully']);
    }
}
