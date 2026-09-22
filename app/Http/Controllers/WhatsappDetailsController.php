<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterData\StoreWhatsappDetailRequest;
use App\Services\MasterData\WhatsappDetailService;
use Illuminate\Http\Request;

class WhatsappDetailsController extends Controller
{
    public function __construct(private readonly WhatsappDetailService $whatsappDetails) {}

    public function showTableList()
    {
        return view('WhatsappDetail.componentItem');
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
}
