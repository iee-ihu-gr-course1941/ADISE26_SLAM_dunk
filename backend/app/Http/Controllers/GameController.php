<?php

namespace App\Http\Controllers;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

class GameController extends Controller
{
    //onitama game logic, move validation, turn tracking, game state manager, legalmove checker etc v1


    ///card id -> list of ['dx' => , 'dy' => ] offsets for movement
    private array $cardMoves = [];

    //get /api/v1/game
    public function show(Request $request): JsonResponse
    {
        return $this->processRequest($request, 'show');
    }

    //post /api/v1/game/start -- player 1 starts their current lobby
    public function start(Request $request): JsonResponse
    {
        return $this->processRequest($request, 'start');
    }

    //post /api/v1/game/move -- also accepts a pass when no legal move exists
    public function move(Request $request): JsonResponse
    {
        $options = $request->validate([
            'pass' => 'sometimes|boolean'
        ]);

        $isPass = false;
        if (isset($options['pass'])) {
            $isPass = (bool)$options['pass'];
        }
        if ($isPass) {
            $data = $request->validate([
                'card_id' => 'required|integer|min:1',
            ]);
            $data['card_id'] = (int)$data['card_id'];

            return $this->processRequest($request, 'pass', $data);
        }
        $data = $request->validate([
            'piece_code' => 'required|string|max:10',
            'card_id' => 'required|integer|min:1',
            'x' => 'required|integer|min:0|max:4',
            'y' => 'required|integer|min:0|max:4',
        ]);

        // int typecasting because i am paranoid
        $data['x'] = (int)$data['x'];
        $data['y'] = (int)$data['y'];
        $data['card_id'] = (int)$data['card_id'];

        return $this->processRequest($request, 'move', $data);
    }

    //shared flow for routes so far, private so it cannot be accessed, the public methods above call it with an action name
    private function processRequest(Request $request, string $action, array $data = []): JsonResponse
    {
        //the function here runs all the changes parallel/together laravel will roll back the changes if some exception occurs
        return DB::transaction(function () use ($request, $action, $data) {
            //1 find the players game and load its board and card moves
            $gameId = $this->getCurrentGameId($request, $action);
            $game = $this->loadGame($gameId);
            //pvp only for now, including games that were already started
            if ($game['mode'] !== 'pvp') {
                abort(409, 'Only PvP games are supported for now.');
            }
            $player = $this->getPlayerSeat($request, $game);
            $this->loadCardMoves();

            //2 turn / anti-afk mechanism
            $checkedGame = $this->applyTimeout($game);
            if ($checkedGame['status'] !== $game['status']) {
                $this->saveGame($checkedGame);
                return $this->gameResponse($checkedGame);
            }

            //3 a get request only needs the current state after everything has occured so
            if ($action === 'show') {
                return $this->gameResponse($game);
            }

            //4 apply the actions to game arrays
            if ($action !== 'start' && $game['status'] !== 'in_progress') {
                abort(409, 'The game is not in progress.');
            }
            try {
                if ($action === 'start') {
                    $game = $this->startGame($game, $player);
                } elseif ($action === 'move') {
                    $game = $this->applyMove($game, $player, $data);
                } elseif ($action === 'pass') {
                    $game = $this->passTurn($game, $player, $data['card_id']);
                } else {
                    throw new LogicException('Unknown game action.');
                }
            } catch (InvalidArgumentException $exception) {
                //specific exception throw
                throw ValidationException::withMessages([
                    'move' => $exception->getMessage(),
                ]);
            }

            //5.1 cpu random logic utilizing legal moves
            $hasCPU = $game['mode'] === 'pvc';
            $isPlaying = $game['status'] = 'in_progress';
            $isCPUTurn = $game['current_turn'] === 2;
            if($hasCPU && $isPlaying && $isCPUTurn){
                $game = $this->playCPUTurn($game);
            }




            //5.2 last step now guys yippee, we save the game and post it back to frontend
            $this->saveGame($game);
            return $this->gameResponse($game);
        });
    }

    private function playCPUTurn(array $game): array
    {
        if($game['mode'] !== 'pvc'){
            throw new InvalidArgumentException('Invalid game mode');
        }
        if($game['current_turn'] !== 2){
            throw new InvalidArgumentException('Invalid turn');
        }
        if($game['winner_id'] !== null){
            throw new InvalidArgumentException('Game already won');
        }
        $moves = $this->getLegalMoves($game);
        if(count($moves) === 0){
            $playerCards = $this->getPlayerCards($game,2);
            $randomIndex = array_rand($playerCards);
            $cardId = $playerCards[$randomIndex];
            return $this->passTurn($game, 2, $cardId);
        }
        // array_rand my beloived
        $randomIndex = array_rand($moves);
        $move = $moves[$randomIndex];
        return $this->applyMove($game, 2, $move);
    }





    private function getCurrentGameId(Request $request, string $action): int
    {
        $user = $request->user();
        if ($user === null) {
            abort(401, 'Login is required');
        }
        $userId = (int)$user->getAuthIdentifier();
        $games = DB::table('games')
            ->where(function ($query) use ($userId) {
                $query->where('player1_id', $userId);
                $query->orWhere('player2_id', $userId);
            })
            ->whereIn('status', ['waiting', 'in_progress'])
            ->limit(2)
            ->get();
        if (count($games) > 1) {
            abort(400, 'User has more than one game in progress');
        }
        if (count($games) === 1) {
            return (int)$games[0]->id;
        }

        if ($action === 'show') {
            $lastGame = DB::table('games')
                ->where(function ($query) use ($userId) {
                    $query->where('player1_id', $userId);
                    $query->orWhere('player2_id', $userId);
                })
                ->where('status', 'finished')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();
            if ($lastGame !== null) {
                return (int)$lastGame->id;
            }
        }
        abort(404, 'No game found');
    }


    //only called inside transactions
    private function loadGame(int $gameId): array
    {
        $row = DB::table('games')->where('id', $gameId)->lockForUpdate()->first();
        if ($row === null) {
            abort(404, 'Game not found');
        }

        //queriee bualder,,, he returns the ooobjects! convert database roaw rowaw to arraah raayy
        //the typecast (array) changes the database row object into an associative php array (i loooove php)
        $game = (array)$row;
        $integerFields = [
            'id', 'player1_id', 'current_turn', 'turn_duration_seconds',
            'p1_card1_id', 'p1_card2_id', 'p2_card1_id', 'p2_card2_id',
            'neutral_card_id',
        ];
        foreach ($integerFields as $field) {
            //typecast (int) converts database values "2" str to int
            $game[$field] = (int)$game[$field];
        }
        if ($game['player2_id'] !== null) {
            $game['player2_id'] = (int)$game['player2_id'];
        }
        if ($game['winner_id'] !== null) {
            $game['winner_id'] = (int)$game['winner_id'];
        }
        $game['pieces'] = [];
        $rows = DB::table('game_pieces')->where('game_id', $gameId)->get();
        foreach ($rows as $row) {
            $piece = (array)$row;
            $piece['id'] = (int)$piece['id'];
            $piece['game_id'] = (int)$piece['game_id'];
            $piece['owner'] = (int)$piece['owner'];
            $piece['x'] = (int)$piece['x'];
            $piece['y'] = (int)$piece['y'];
            $piece['is_alive'] = (bool)$piece['is_alive'];
            $game['pieces'][$piece['piece_code']] = $piece;
        }

        return $game;
    }

    private function loadCardMoves(): void
    {
        $this->cardMoves = [];
        $rows = DB::table('card_moves')->get();
        foreach ($rows as $row) {
            $cardMove = (array)$row;
            $cardId = (int)$cardMove['card_id'];

            $this->cardMoves[$cardId][] = [
                'dx' => (int)$cardMove['dx'],
                'dy' => (int)$cardMove['dy'],
            ];
        }
    }

    //identity from laravel, never from a json / user id whatevs
    private function getPlayerSeat(Request $request, array $game): int
    {
        $user = $request->user();
        if ($user === null) {
            abort(401, 'Login is required.');
        }

        $userId = (int)$user->getAuthIdentifier();
        if ($userId === $game['player1_id']) {
            return 1;
        }
        if ($game['mode'] === 'pvp') {
            if ($userId === $game['player2_id']) {
                return 2;
            }
        }

        abort(403, 'You are not a player in this game.');
    }

    private function startGame(array $game, int $player){
        if ($player !== 1){
            abort (403, 'Only player 1 can start the game.');
        }
        if ($game['status'] !== 'waiting'){
            abort(403, 'Only a waiting game can be started.');
        }
        if ($game['mode'] !== 'pvp'){
            abort(409, 'Only PvP games are supported for now.');
        }
        if ($game['player2_id'] === null){
            abort(409, 'Wait for player 2 to join.');
        }
        if ($game['player1_id'] === $game['player2_id']){
            abort(409, 'The players must be different users.');
        }
        if ($game['turn_duration_seconds'] <= 0){
            abort(409, 'The turn duration must be greater than 0.');
        }
        if (count($game['pieces']) !== 0){
            abort(409, 'The controller initializes pieces; the waiting board must be empty.');
        }

        $cardIds = [
            $game['p1_card1_id'], $game['p1_card2_id'],
            $game['p2_card1_id'], $game['p2_card2_id'], $game['neutral_card_id'],
        ];
        if(count(array_unique($cardIds)) !==5){
            abort(409, 'The game must have 5 different cards.');
        }
        $cards = DB::table('cards')->whereIn('id', $cardIds)->get();
        if (count($cards) !== 5){
            abort(409, 'A selected cards ID was not found.');
        }
        foreach ($cardIds as $cardId){
            $this->getCardMoves($cardId);
        }

        //neutral card's seal selects the first player at game-start
        foreach ($cards as $card){
            $card = (array) $card;
            if ((int) $card['id'] === $game['neutral_card_id']){
                $game['current_turn'] = (int) $card['starting_player'];
            }
        }
        if ($game['current_turn'] !== 1 && $game['current_turn'] !== 2){
            abort(409, 'The neutral card has an invalid starting player.');
        }
        $game['pieces'] = $this->initialPieces($game['id']);
        $game['status'] = 'in_progress';
        $game['winner_id'] = null;
        $game['win_type'] = null;

        return $this->startTurnTimer($game);
    }

    private function initialPieces(int $gameId):array{
        $pieces = [];
        for ($player = 1; $player<=2; $player++){
            $y = 0;
            if ($player===2){
                $y = 4;
            }
            $studentNumber = 1;
            for($x = 0; $x<=4; $x++){
                if($x === 2){
                    $pieceCode = 'p' . $player. '_m';
                    $type = 'master';
                }else{
                    $pieceCode = 'p' . $player. '_s' . $studentNumber;
                    $type = 'student';
                    $studentNumber++;
                }
                $pieces[$pieceCode] = [
                    'game_id' => $gameId,
                    'piece_code' => $pieceCode,
                    'owner' => $player,
                    'type' => $type,
                    'x' => $x,
                    'y' => $y,
                    'is_alive' => true,
                ];
            }
        }
        return $pieces;
    }

    private function saveGame(array $game):void{
        DB::table('games')->where('id', $game['id'])->update([
            'status' => $game['status'],
            'current_turn' => $game['current_turn'],
            'winner_id' => $game['winner_id'],
            'win_type' => $game['win_type'],
            'p1_card1_id' => $game['p1_card1_id'],
            'p1_card2_id' => $game['p1_card2_id'],
            'p2_card1_id' => $game['p2_card1_id'],
            'p2_card2_id' => $game['p2_card2_id'],
            'neutral_card_id' => $game['neutral_card_id'],
            'turn_started_at' => $game['turn_started_at'],
            'turn_expires_at' => $game['turn_expires_at'],
            'updated_at' => now()->toDateTimeString(),
        ]);
        foreach ($game['pieces'] as $piece) {
            // The first array identifies the piece The second contains its state
            // updateOrInsert creates it on start, or updates it on later turns
            DB::table('game_pieces')->updateOrInsert(
                ['game_id' => $game['id'], 'piece_code' => $piece['piece_code']],
                [
                    'owner' => $piece['owner'],
                    'type' => $piece['type'],
                    'x' => $piece['x'],
                    'y' => $piece['y'],
                    'is_alive' => $piece['is_alive'],
                ]
            );
        }
    }

    private function gameResponse(array $game): JsonResponse{
        //reloading so responses include generated piece ids and saved timestamps
        $game = $this->loadGame($game['id']);
        $legalMoves = $this->getLegalMoves($game);
        $canPass = false;
        if ($game['status'] === 'in_progress' && count($legalMoves) === 0){
            $canPass = true;
        }

        return response()->json([
            'game'=>$game,
            'legal_moves'=>$legalMoves,
            'can_pass'=>$canPass,
            'server_time'=>now()->toDateTimeString(),
        ]);
    }

    private function startTurnTimer(array $game): array{
        $startedAt = now();
        $expiresAt = $startedAt->copy()->addSeconds($game['turn_duration_seconds']);
        $game['turn_started_at'] = $startedAt->toDateTimeString();
        $game['turn_expires_at'] = $expiresAt->toDateTimeString();

        return $game;
    }

    private function applyTimeout(array $game): array{
        if ($game['status'] !== 'in_progress') {
            return $game;
        }
        if ($game['turn_expires_at'] === null) {
            throw new LogicException('An active game needs a turn deadline.');
        }

        $deadline = Carbon::parse($game['turn_expires_at']);
        if (now()->greaterThanOrEqualTo($deadline)) {
            $winner = $this->otherPlayer($game['current_turn']);
            return $this->finishGame($game, $winner, 'timeout');
        }

        return $game;
    }

    private function otherPlayer(int $player): int
    {
        if ($player === 1) {
            return 2;
        }

        return 1;
    }

    private function finishGame(array $game, int $winner, string $winType): array
    {
        $game['status'] = 'finished';
        $game['winner_id'] = $winner;
        $game['win_type'] = $winType;
        $game['turn_started_at'] = null;
        $game['turn_expires_at'] = null;

        return $game;
    }

    // following methods implement the rules for both players
    // ?string means this function can return a string OR null
    // message means invalid, nnull means all checks good!
    private function validateTurn(array $game, int $player): ?string
    {
        if ($player !== 1 && $player !== 2) {
            return 'Invalid player seat.';
        }
        if ($game['status'] !== 'in_progress') {
            return 'The game is not in progress.';
        }
        if ($game['winner_id'] !== null) {
            return 'The game is not in progress.';
        }
        if ($game['current_turn'] !== $player) {
            return 'It is not your turn.';
        }
        return null;
    }
    // returns null for a legal move, otherwise an error message
    private function validateMove(array $game, int $player, array $move): ?string
    {
        // 1 check the game status and whose turn it is
        $error = $this->validateTurn($game, $player);
        if ($error !== null) {
            return $error;
        }
        // 2 check that the move contains the expected data
        if (!isset($move['piece_code'], $move['card_id'], $move['x'], $move['y'])) {
            return 'The move must contain piece_code, card_id, x, and y.';
        }
        if (!is_string($move['piece_code'])) {
            return 'The piece code must be a string.';
        }
        if (!is_int($move['card_id'])) {
            return 'The card ID must be an integer.';
        }
        if (!is_int($move['x']) || !is_int($move['y'])) {
            return 'The destination coordinates must be integers.';
        }

        // 3 check that this is a living piece owned by the current player
        $pieceCode = $move['piece_code'];
        if (!isset($game['pieces'][$pieceCode])) {
            return 'This piece does not exist.';
        }
        $piece = $game['pieces'][$pieceCode];
        if ($piece['is_alive'] === false) {
            return 'This piece has been captured.';
        }
        if ($piece['owner'] !== $player) {
            return 'This piece belongs to the other player.';
        }

        // 4 the selected card must be one of this player's two cards
        $playerCards = $this->getPlayerCards($game, $player);
        if (!in_array($move['card_id'], $playerCards, true)) {
            return 'The selected card is not in your hand.';
        }
        // 5 stay on the board and do not land on a friendly piece
        if ($move['x'] < 0 || $move['x'] > 4) {
            return 'destination is outside the board.';
        }
        if ($move['y'] < 0 || $move['y'] > 4) {
            return 'destination is outside the board.';
        }
        $targetPieceCode = $this->findPieceAt($game, $move['x'], $move['y']);
        if ($targetPieceCode !== null) {
            $targetPlayer = $game['pieces'][$targetPieceCode]['owner'];
            if ($targetPlayer === $player) {
                return 'friendly piece occupies the destination.';
            }
        }

        // 6 accept the destination only if the card allows it
        foreach ($this->getCardMoves($move['card_id']) as $cardMove) {
            $destination = $this->getDestination($piece, $player, $cardMove);

            if ($destination['x'] === $move['x'] && $destination['y'] === $move['y']) {
                return null;
            }
        }
        return 'The selected card does not allow this movement.';
    }
    // legal moves for the current turn, suitable for highlighting
    private function getLegalMoves(array $game): array
    {
        if ($game['status'] !== 'in_progress') {
            return [];
        }
        if ($game['winner_id'] !== null) {
            return [];
        }
        $player = $game['current_turn'];
        $moves = [];

        // try every living piece, each of the player's cards, and each card offset
        // keep only candidates accepted by validateMove()
        foreach ($game['pieces'] as $pieceCode => $piece) {
            if ($piece['is_alive'] === false) {
                continue;
            }
            if ($piece['owner'] !== $player) {
                continue;
            }
            foreach ($this->getPlayerCards($game, $player) as $cardId) {
                foreach ($this->getCardMoves($cardId) as $cardMove) {
                    $destination = $this->getDestination($piece, $player, $cardMove);

                    $move = [
                        'piece_code' => $pieceCode,
                        'card_id' => $cardId,
                        'x' => $destination['x'],
                        'y' => $destination['y'],
                    ];
                    $error = $this->validateMove($game, $player, $move);
                    if ($error === null) {
                        $moves[] = $move;
                    }
                }
            }
        }
        return $moves;
    }
    private function applyMove(array $game, int $player, array $move): array
    {
        // 1 refuse the move if any rule fails
        $error = $this->validateMove($game, $player, $move);
        if ($error !== null) {
            throw new InvalidArgumentException($error);
        }
        // 2 capture any opponent piece at the destination
        $targetPieceCode = $this->findPieceAt($game, $move['x'], $move['y']);
        $capturedMaster = false;
        if ($targetPieceCode !== null) {
            if ($game['pieces'][$targetPieceCode]['type'] === 'master') {
                $capturedMaster = true;
            }
            $game['pieces'][$targetPieceCode]['is_alive'] = false;
        }
        // 3 update the moving piece's coordinates
        $pieceCode = $move['piece_code'];
        $game['pieces'][$pieceCode]['x'] = $move['x'];
        $game['pieces'][$pieceCode]['y'] = $move['y'];

        // 4 swap the used card with the neutral card
        $game = $this->exchangeCard($game, $player, $move['card_id']);

        // 5 a master capture or entry into the enemy temple wins
        $enemyTempleY = 4;
        if ($player === 2) {
            $enemyTempleY = 0;
        }
        if ($capturedMaster) {
            return $this->finishGame($game, $player, 'stone');
        }

        $isMaster = $game['pieces'][$pieceCode]['type'] === 'master';
        $isEnemyTemple = $move['x'] === 2 && $move['y'] === $enemyTempleY;

        if ($isMaster && $isEnemyTemple) {
            return $this->finishGame($game, $player, 'stream');
        }

        // 6 otherwise, give the next player a turn and restart the timer
        return $this->advanceTurn($game, $player);
    }

    // passing is allowed only when neither card permits any legal move
    private function passTurn(array $game, int $player, int $cardId): array
    {
        $error = $this->validateTurn($game, $player);
        if ($error !== null) {
            throw new InvalidArgumentException($error);
        }
        $playerCards = $this->getPlayerCards($game, $player);
        if (!in_array($cardId, $playerCards, true)) {
            throw new InvalidArgumentException('The selected card is not in your hand.');
        }
        $legalMoves = $this->getLegalMoves($game);
        if (count($legalMoves) > 0) {
            throw new InvalidArgumentException('You must move when a legal move exists.');
        }
        $game = $this->exchangeCard($game, $player, $cardId);
        return $this->advanceTurn($game, $player);
    }
    private function getDestination(array $piece, int $player, array $cardMove): array
    {
        $dx = $cardMove['dx'];
        $dy = $cardMove['dy'];

        // player 2 sees the card rotated by 180 degrees: reverse both offsets
        if ($player === 2) {
            $dx = -$dx;
            $dy = -$dy;
        }

        return [
            'x' => $piece['x'] + $dx,
            'y' => $piece['y'] + $dy,
        ];
    }

    private function getCardMoves(int $cardId): array
    {
        if (empty($this->cardMoves[$cardId])) {
            throw new LogicException('Missing movement data for card ' . $cardId);
        }
        return $this->cardMoves[$cardId];
    }

    private function getPlayerCards(array $game, int $player): array
    {
        if ($player === 1) {
            return [$game['p1_card1_id'], $game['p1_card2_id']];
        }
        return [$game['p2_card1_id'], $game['p2_card2_id']];
    }

    private function findPieceAt(array $game, int $x, int $y): ?string
    {
        foreach ($game['pieces'] as $pieceCode => $piece) {
            if ($piece['is_alive'] === false) {
                continue;
            }
            if ($piece['x'] === $x && $piece['y'] === $y) {
                return $pieceCode;
            }
        }
        return null;
    }

    private function exchangeCard(array $game, int $player, int $cardId): array
    {
        if ($player === 1) {
            $cardSlots = ['p1_card1_id', 'p1_card2_id'];
        } else {
            $cardSlots = ['p2_card1_id', 'p2_card2_id'];
        }
        foreach ($cardSlots as $slot) {
            if ($game[$slot] === $cardId) {
                $game[$slot] = $game['neutral_card_id'];
                $game['neutral_card_id'] = $cardId;
                return $game;
            }
        }
        throw new LogicException('Cannot exchange a card outside the player hand.');
    }

    private function advanceTurn(array $game, int $player): array
    {
        if ($player === 1) {
            $game['current_turn'] = 2;
        } else {
            $game['current_turn'] = 1;
        }
        return $this->startTurnTimer($game);
    }






}

