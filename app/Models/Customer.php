<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'full_name',
        'phone',
        'phone_normalized',
        'phone_alt',
        'phone_alt_normalized',
        'email',
        'gender',
        'county_id',
        'town',
        'consent_marketing',
        'consent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'consent_marketing' => 'boolean',
            'consent_at' => 'datetime',
        ];
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /** Profile notes (morph). Distinct from the legacy `notes` text column on customers. */
    public function noteEntries(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest('occurred_at');
    }

    public function displayName(): string
    {
        return $this->full_name ?: trim("{$this->first_name} {$this->last_name}");
    }
}
