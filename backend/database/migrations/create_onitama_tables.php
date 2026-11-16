<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('iee_id', 50)->unique();
            $table->string('username', 50)->unique();
            $table->string('full_name', 120);
            $table->string('api_token', 80)->nullable()->unique();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('last_active')->useCurrent()->useCurrentOnUpdate();
        });

        // Leaderboard
        Schema::create('leaderboard', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('draws')->default(0);
            $table->integer('elo')->default(1000);
        });

        // Cards
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->unique();
            $table->tinyInteger('starting_player');
        });

        // Card Moves
        Schema::create('card_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('cards')->cascadeOnDelete();
            $table->tinyInteger('dx');
            $table->tinyInteger('dy');

            $table->unique(['card_id', 'dx', 'dy']);
        });

        // Friends
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['sender_id', 'receiver_id']);
        });

        // Games
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->enum('mode', ['pvp', 'pvc'])->default('pvp');

            $table->foreignId('player1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('current_turn')->default(1);

            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('win_type', ['stone', 'stream', 'timeout', 'forfeit', 'deadlock'])->nullable();

            $table->foreignId('p1_card1_id')->constrained('cards');
            $table->foreignId('p1_card2_id')->constrained('cards');
            $table->foreignId('p2_card1_id')->constrained('cards');
            $table->foreignId('p2_card2_id')->constrained('cards');
            $table->foreignId('neutral_card_id')->constrained('cards');

            $table->enum('status', ['waiting', 'in_progress', 'finished'])->default('waiting');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->smallInteger('turn_duration_seconds')->default(60);
            $table->timestamp('turn_started_at')->nullable();
            $table->timestamp('turn_expires_at')->nullable();
        });

        // Game Pieces
        Schema::create('game_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('piece_code',30);
            $table->tinyInteger('owner');
            $table->enum('type', ['master', 'student']);
            $table->unsignedTinyInteger('x');
            $table->unsignedTinyInteger('y');
            $table->boolean('is_alive')->default(true);

            $table->unique(['game_id', 'piece_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_pieces');
        Schema::dropIfExists('games');
        Schema::dropIfExists('card_moves');
        Schema::dropIfExists('cards');
        Schema::dropIfExists('friends');
        Schema::dropIfExists('leaderboard');
        Schema::dropIfExists('users');
    }
};
