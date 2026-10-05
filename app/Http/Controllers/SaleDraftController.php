<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleDraftRequest;
use App\Http\Requests\UpdateSaleDraftRequest;
use App\Models\SaleDraft;
use App\Models\Ship;
use App\Services\Sales\SaleDraftService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SaleDraftController extends Controller
{
    public function __construct(private readonly SaleDraftService $drafts) {}

    public function page(): View
    {
        $ships = Ship::query()->orderBy('name')->get(['id', 'name']);

        return view('sale_drafts.index', compact('ships'));
    }

    public function index(Request $request)
    {
        return response()->json($this->drafts->dataTable($request));
    }

    public function store(StoreSaleDraftRequest $request)
    {
        return response()->json($this->drafts->create($request->validated(), $request->user()->id), 201);
    }

    public function show(Request $request, SaleDraft $saleDraft)
    {
        return response()->json($this->drafts->findForUser($saleDraft, $request->user()->id));
    }

    public function update(UpdateSaleDraftRequest $request, SaleDraft $saleDraft)
    {
        $draft = $this->drafts->findForUser($saleDraft, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Draft updated successfully.',
            'data' => $this->drafts->update($draft, $request->validated()),
        ]);
    }

    public function destroy(Request $request, SaleDraft $saleDraft)
    {
        $draft = $this->drafts->findForUser($saleDraft, $request->user()->id);
        $this->drafts->delete($draft);

        return response()->json(['success' => true, 'message' => 'Draft deleted successfully.']);
    }
}
