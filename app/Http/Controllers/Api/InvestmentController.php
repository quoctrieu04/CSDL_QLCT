<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Investment;
use Illuminate\Support\Facades\Auth;

class InvestmentController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        return Investment::where('user_id', $userId)->get();
    }

    public function store(Request $req)
{
    $userId = Auth::id();

    $data = $req->validate([
        'name' => 'required|string',
        'type' => 'nullable|string',
        'buy_price' => 'required|numeric',
        'quantity' => 'required|numeric',
        'auto_update' => 'boolean',
        'symbol' => 'nullable|string',
        'api_source' => 'nullable|string',
        'api_field' => 'nullable|string',
        'api_path' => 'nullable|string'
    ]);

    // Auto-update = true → current_price = 0, backend sẽ cập nhật sau
    $currentPrice = $req->current_price ?? 0;

    $total = $data['buy_price'] * $data['quantity'];
    $profit = ($currentPrice * $data['quantity']) - $total;

    $investment = Investment::create([
        'user_id' => $userId,
        'name' => $data['name'],
        'type' => $req->type ?? 'custom',
        'buy_price' => $data['buy_price'],
        'current_price' => $currentPrice,
        'quantity' => $data['quantity'],
        'total_invested' => $total,
        'profit_loss' => $profit,
        'auto_update' => $req->auto_update ?? false,
        'symbol' => $req->symbol,
        'api_source' => $req->api_source,
        'api_field' => $req->api_field,
        'api_path' => $req->api_path,       
    ]);

    return response()->json($investment);
}


    public function update(Request $req, $id)
    {
        $inv = Investment::findOrFail($id);

        $data = $req->validate([
            'current_price' => 'required|numeric'
        ]);

        $inv->current_price = $data['current_price'];
        $inv->profit_loss =
            ($inv->current_price * $inv->quantity) - $inv->total_invested;

        $inv->save();

        return response()->json($inv);
    }

    public function destroy($id)
    {
        $inv = Investment::findOrFail($id);
        $inv->delete();

        return response()->json(['message' => 'deleted']);
    }
}
