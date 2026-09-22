<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\SaveCashCollectionRequest;
use App\Models\CashCollection;
use App\Services\Finance\CashCollectionService;
use Illuminate\Http\Request;

class CashCollectionController extends Controller
{
    public function __construct(private readonly CashCollectionService $cashCollections) {}

    public function index(Request $request)
    {
        return response()->json($this->cashCollections->dataTable($request));
    }

    public function showCashCollection()
    {
        return view('cashCollection.componentItem', $this->cashCollections->summary());
    }

    public function store(SaveCashCollectionRequest $request)
    {
        $collection = $this->cashCollections->create($request->validated());

        return response()->json([
            'message' => 'Cash collection created successfully.',
            'data' => $collection,
        ]);
    }

    public function show($id)
    {
        return response()->json(CashCollection::findOrFail($id));
    }

    public function edit($id)
    {
        //
    }

    public function update(SaveCashCollectionRequest $request, $id)
    {
        $collection = $this->cashCollections->update(
            CashCollection::findOrFail($id),
            $request->validated()
        );

        return response()->json([
            'message' => 'Cash collection updated successfully.',
            'data' => $collection,
        ]);
    }

    public function destroy($id)
    {
        $collection = CashCollection::findOrFail($id);
        $collection->delete();

        return response()->json([
            'message' => 'Cash collection deleted successfully.',
        ]);
    }
}
