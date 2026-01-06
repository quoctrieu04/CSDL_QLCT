<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Saving;
use Illuminate\Http\Request;

class SavingController extends Controller
{
    public function index()
    {
        return Saving::where('user_id', auth()->id())->get();
    }
//     public function index(Request $request)
// {
//     $userId = auth()->id();

//     $query = Saving::where('user_id', $userId);

//     // Lọc theo năm
//     if ($request->has('year') && is_numeric($request->year)) {
//         $query->whereYear('start_date', $request->year);
//     }

//     // Lọc theo tháng
//     if ($request->has('month') && is_numeric($request->month)) {
//         $query->whereMonth('start_date', $request->month);
//     }

//     return $query->orderBy('id', 'desc')->get();
// }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'target_amount' => 'required|numeric',
            'monthly_amount' => 'required|numeric',
            'start_date' => 'required|date',
        ]);

        $saving = Saving::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'target_amount' => $request->target_amount,
            'monthly_amount' => $request->monthly_amount,
            'current_amount' => 0,
            'start_date' => $request->start_date,
            'status' => 'active',
        ]);

        return response()->json($saving);
    }

    public function show($id)
    {
        return Saving::where('id', $id)
                      ->where('user_id', auth()->id())
                      ->firstOrFail();
    }

    public function update(Request $request, $id)
    {
        $saving = Saving::where('id', $id)
                        ->where('user_id', auth()->id())
                        ->firstOrFail();

        $saving->update($request->all());
        return response()->json($saving);
    }

    public function destroy($id)
    {
        $saving = Saving::where('id', $id)
                        ->where('user_id', auth()->id())
                        ->firstOrFail();

        $saving->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
