<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentRoundRobinState extends Model
{
    protected $fillable = [
        'scope_type',
        'scope_key',
        'last_user_id',
    ];

    public function lastUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_user_id');
    }
}
