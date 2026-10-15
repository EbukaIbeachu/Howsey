<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoinController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'coin_balance' => $user->coin_balance ?? 0,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $delta = (float) $request->input('delta', 0);

        if ($delta <= 0) {
            return response()->json([
                'coin_balance' => $user->coin_balance ?? 0,
            ]);
        }

        DB::transaction(function () use ($user, $delta) {
            $user->increment('coin_balance', $delta);
            $user->refresh();
        });

        return response()->json([
            'coin_balance' => $user->coin_balance,
        ]);
    }
}
