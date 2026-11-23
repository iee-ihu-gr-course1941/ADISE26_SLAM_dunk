<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LobbyController extends Controller
{

    /**
     * GET /api/v1/lobbies
     * Query για ολα τα lobbies που είναι "waiting" και "pvp"
     */
    public function index(): JsonResponse
    {
        $lobbies = DB::table('games')
            ->join('users as p1', 'games.player1_id', '=', 'p1.id')
            ->where('games.status', 'waiting')
            ->where('games.mode', 'pvp')
            ->whereNull('games.player2_id')
            ->select(
                'games.id',
                'p1.username as host_username',
                'games.turn_duration_seconds',
                'games.created_at'
            )
            ->orderByDesc('games.created_at')
            ->get();

        return response()->json($lobbies);
    }

    /**
     * POST /api/v1/lobbies
     * Create lobby και κανε deal τις κάρτες
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'turn_duration_seconds' => 'sometimes|integer|min:15|max:300',
        ]);

        $userId = (int) $request->user()->getAuthIdentifier();

        // Guardrail: Μόνο ένας ενεργός αγώνας ανα χρήστη.
        if ($this->userHasActiveGame($userId)) {
            return response()->json([
                'message' => 'You already have an active or waiting game.',
            ], 409);
        }

        // 5 τυχαίες κάρτες
        $cardIds = DB::table('cards')->inRandomOrder()->limit(5)->pluck('id')->toArray();
        if (count($cardIds) < 5) {
            return response()->json([
                'message' => 'Not enough cards in the database to start a game.',
            ], 500);
        }

        $gameId = DB::table('games')->insertGetId([
            'mode'                  => 'pvp',
            'player1_id'            => $userId,
            'player2_id'            => null,
            'p1_card1_id'           => $cardIds[0],
            'p1_card2_id'           => $cardIds[1],
            'p2_card1_id'           => $cardIds[2],
            'p2_card2_id'           => $cardIds[3],
            'neutral_card_id'       => $cardIds[4],
            'status'                => 'waiting',
            'turn_duration_seconds' => $validated['turn_duration_seconds'] ?? 60,
        ]);

        $game = DB::table('games')->where('id', $gameId)->first();

        return response()->json($game, 201);
    }

    /**
     * POST /api/v1/lobbies/{id}/join
     * Join open lobby. (με {id} το id του lobby)
     */
    public function join(Request $request, int $id): JsonResponse
    {
        $userId = (int) $request->user()->getAuthIdentifier();

        if ($this->userHasActiveGame($userId)) {
            return response()->json([
                'message' => 'You already have an active or waiting game.',
            ], 409);
        }

        return DB::transaction(function () use ($id, $userId) {
            $game = DB::table('games')->where('id', $id)->lockForUpdate()->first();

            if (!$game) {
                return response()->json(['message' => 'Lobby not found.'], 404);
            }
            if ($game->status !== 'waiting' || $game->player2_id !== null) {
                return response()->json(['message' => 'This lobby is no longer available.'], 409);
            }
            if ((int) $game->player1_id === $userId) {
                return response()->json(['message' => 'You cannot join your own lobby.'], 409);
            }

            DB::table('games')->where('id', $id)->update([
                'player2_id' => $userId,
                'updated_at' => now(),
            ]);

            $updatedGame = DB::table('games')->where('id', $id)->first();

            return response()->json($updatedGame);
        });
    }

    /**
     * DELETE /api/v1/lobbies
     * Ουσιαστικά "cancel matchmaking", διαγραφή του lobby.
     */
    public function cancel(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->getAuthIdentifier();

        $deleted = DB::table('games')
            ->where('player1_id', $userId)
            ->where('status', 'waiting')
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'No waiting lobby found to cancel.'], 404);
        }

        return response()->json(['message' => 'Lobby cancelled.']);
    }

    private function userHasActiveGame(int $userId): bool
    {
        return DB::table('games')
            ->where(function ($query) use ($userId) {
                $query->where('player1_id', $userId)
                      ->orWhere('player2_id', $userId);
            })
            ->whereIn('status', ['waiting', 'in_progress'])
            ->exists();
    }
}

