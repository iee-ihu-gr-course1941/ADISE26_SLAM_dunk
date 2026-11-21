<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leaderboard extends Model
{
    protected $table = 'leaderboard';
    protected $primaryKey = 'username';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'username',
        'wins',
        'losses',
        'draws',
        'elo',
    ];

    protected function casts(): array
    {
        return [
            'username'   => 'string',
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
