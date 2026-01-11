<?php

namespace App\Http\Controllers;

use App\Models\WhatsappDetail;
use Illuminate\Http\Request;

class WhatsappDetailsController extends Controller
{
    public function showTableList()
    {
        return view('WhatsappDetail.componentItem');
    }

    public function index()
    {
        $ships = WhatsappDetail::all();
        return response()->json($ships);
    }

   public function store(Request $request)
{
    $validated = $request->validate([
        'tag' => 'required|string|max:255',
        'whatsapp_number' => 'required|digits_between:10,15',
        'form_no' => 'required|string|max:100',
        'url' => 'required|url|max:255',
    ]);

    $whatsapp = new WhatsappDetail($validated);
    $whatsapp->save();

    return response()->json($whatsapp, 201);
}

}
