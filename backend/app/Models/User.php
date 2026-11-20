<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $table = 'users';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'last_active';


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'iee_id',
        'username',
        'full_name',
        'api_token',
        'last_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'api_token', // δεν doxxαρουμε τον χρήστη
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_active' => 'datetime',
        ];
    }
    public function leaderboard(): User|\Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Leaderboard::class, 'username', 'username');
    }
}
