<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CardSeeder extends Seeder
{
    public function run(): void
    {
        //κάνω τα cards multi-dimension array, για να τα κάνω bulk insert στο τέλος.
        $cards = [
            [
                'id' => 1,
                'name' => 'Frog',
                'starting_player' => 1,
                'moves' => [[-2, 0], [-1, 1], [1, -1]],
            ],
            [
                'id' => 2,
                'name' => 'Rabbit',
                'starting_player' => 2,
                'moves' => [[2, 0], [1, 1], [-1, -1]],
            ],
            [
                'id' => 3,
                'name' => 'Goose',
                'starting_player' => 2,
                'moves' => [[-1, 0], [-1, 1], [1, 0], [1, -1]],
            ],
            [
                'id' => 4,
                'name' => 'Rooster',
                'starting_player' => 1,
                'moves' => [[1, 0], [1, 1], [-1, 0], [-1, -1]],
            ],
            [
                'id' => 5,
                'name' => 'Horse',
                'starting_player' => 1,
                'moves' => [[0, 1], [-1, 0], [0, -1]],
            ],
            [
                'id' => 6,
                'name' => 'Ox',
                'starting_player' => 2,
                'moves' => [[0, 1], [1, 0], [0, -1]],
            ],
            [
                'id' => 7,
                'name' => 'Eel',
                'starting_player' => 2,
                'moves' => [[-1, 1], [-1, -1], [1, 0]],
            ],
            [
                'id' => 8,
                'name' => 'Cobra',
                'starting_player' => 1,
                'moves' => [[1, 1], [1, -1], [-1, 0]],
            ],
            [
                'id' => 9,
                'name' => 'Dragon',
                'starting_player' => 1,
                'moves' => [[-2, 1], [2, 1], [-1, -1], [1, -1]],
            ],
            [
                'id' => 10,
                'name' => 'Tiger',
                'starting_player' => 2,
                'moves' => [[0, 2], [0, -1]],
            ],
            [
                'id' => 11,
                'name' => 'Elephant',
                'starting_player' => 1,
                'moves' => [[-1, 0], [1, 0], [-1, 1], [1, 1]],
            ],
            [
                'id' => 12,
                'name' => 'Crab',
                'starting_player' => 2,
                'moves' => [[-2, 0], [2, 0], [0, 1]],
            ],
            [
                'id' => 13,
                'name' => 'Mantis',
                'starting_player' => 1,
                'moves' => [[-1, 1], [1, 1], [0, -1]],
            ],
            [
                'id' => 14,
                'name' => 'Monkey',
                'starting_player' => 2,
                'moves' => [[-1, 1], [1, 1], [-1, -1], [1, -1]],
            ],
            [
                'id' => 15,
                'name' => 'Boar',
                'starting_player' => 1,
                'moves' => [[0, 1], [-1, 0], [1, 0]],
            ],
            [
                'id' => 16,
                'name' => 'Crane',
                'starting_player' => 2,
                'moves' => [[0, 1], [-1, -1], [1, -1]],
            ],
        ];

        $cardRows = [];
        $moveRows = [];

        foreach ($cards as $card) {
            // Create table cards
            $cardRows[] = [
                'id' => $card['id'],
                'name' => $card['name'],
                'starting_player' => $card['starting_player'],
            ];

            foreach ($card['moves'] as [$dx, $dy]) {
                // create table card_moves
                $moveRows[] = [
                    'card_id' => $card['id'],
                    'dx' => $dx,
                    'dy' => $dy,
                ];
            }
        }

        // bulk insert μέσα στα tables
        DB::table('cards')->insert($cardRows);
        DB::table('card_moves')->insert($moveRows);

    }
}
