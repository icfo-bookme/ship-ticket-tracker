<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterData\StoreWhatsappDetailRequest;
use App\Http\Requests\MasterData\UpdateWhatsappDetailRequest;
use App\Models\WhatsappDetail;
use App\Services\MasterData\WhatsappDetailService;
use Illuminate\Http\Request;

class WhatsappDetailsController extends Controller
{
    public function __construct(private readonly WhatsappDetailService $whatsappDetails) {}

    public function showTableList()
    {
        return view('WhatsappDetail.index');
    }

    public function index(Request $request)
    {
        return response()->json($this->whatsappDetails->dataTable($request));
    }

    public function store(StoreWhatsappDetailRequest $request)
    {
        $whatsapp = $this->whatsappDetails->create($request->validated());

        return response()->json($whatsapp, 201);
    }

    public function show(WhatsappDetail $whatsapp): \Illuminate\Http\JsonResponse
    {
        return response()->json($whatsapp);
    }

    public function update(UpdateWhatsappDetailRequest $request, WhatsappDetail $whatsapp): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'WhatsApp details updated successfully.',
            'data' => $this->whatsappDetails->update($whatsapp, $request->validated()),
        ]);
    }

    public function destroy(WhatsappDetail $whatsapp): \Illuminate\Http\JsonResponse
    {
        $whatsapp->delete();

        return response()->json(['success' => true, 'message' => 'WhatsApp details deleted successfully.']);
    }
}
