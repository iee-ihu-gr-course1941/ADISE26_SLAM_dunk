<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LeaderboardDumpController extends Controller
{
    /**
     * GET /api/v1/leaderboard
     * Επιστρέφει το leaderboard με βάση το ELO, με σελιδοποίηση 20 ανά σελίδα.
     */
    public function index(): JsonResponse
    {
        $leaderboard = DB::table('leaderboard')
            ->select('username', 'wins', 'losses', 'draws', 'elo')
            ->orderByDesc('elo')
            ->paginate(20);

        return response()->json($leaderboard);
    }
}
