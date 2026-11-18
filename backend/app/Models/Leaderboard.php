<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leaderboard extends Model
{
    protected $table = 'leaderboard';

    protected $primaryKey = 'user_id';
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'wins',
        'losses',
        'draws',
        'elo',
    ];

    protected function casts(): array
    {
        return [
            'user_id'    => 'integer',
            'wins'       => 'integer',
            'losses'     => 'integer',
            'draws'      => 'integer',
            'elo'        => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'username');
    }
}
