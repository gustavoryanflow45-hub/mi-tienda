<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompareController extends Controller
{
    public function add(Request $request)
    {
        $productId = $request->input('product_id') ?? $request->input('id');
        abort_unless($productId, 422, 'product_id required');

        $userId    = auth()->id();
        $sessionId = session()->getId();

        $count = DB::table('compare_lists')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn($q) => $q->where('session_id', $sessionId))
            ->count();

        if ($count >= 4) {
            return response()->json(['status' => 'error', 'message' => 'Máximo 4 productos para comparar.'], 422);
        }

        $exists = DB::table('compare_lists')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn($q) => $q->where('session_id', $sessionId))
            ->where('product_id', $productId)
            ->exists();

        if (! $exists) {
            DB::table('compare_lists')->insert([
                'user_id'    => $userId,
                'session_id' => $userId ? null : $sessionId,
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'Producto añadido para comparar.']);
    }
}
