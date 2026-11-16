<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'customer_id',
        'product_id',
        'lead_source_id',
        'campaign',
        'description',
        'assigned_to',
        'priority',
        'status',
        'follow_up_at',
        'consent_contact',
        'notes',
        'county_id',
        'town',
        'created_by',
        'channel',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_at' => 'datetime',
            'consent_contact' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(LeadAudit::class)->latest('created_at');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class)->latest('created_at');
    }

    public function assignmentHistories(): HasMany
    {
        return $this->hasMany(LeadAssignmentHistory::class)->latest('created_at');
    }

    public function statusLabel(): string
    {
        return config("phoenix.lead_statuses.{$this->status}", $this->status);
    }

    public function priorityLabel(): string
    {
        return config("phoenix.lead_priorities.{$this->priority}", $this->priority);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('leads.view_all') || $user->hasRole('Super Administrator')) {
            return $query;
        }

        return $query->where('assigned_to', $user->id);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('reference', 'like', "%{$search}%")
                        ->orWhere('campaign', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('customer', function (Builder $customer) use ($search) {
                            $customer->where('full_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('phone_normalized', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $q, string $priority) => $q->where('priority', $priority))
            ->when($filters['product_id'] ?? null, fn (Builder $q, $id) => $q->where('product_id', $id))
            ->when($filters['lead_source_id'] ?? null, fn (Builder $q, $id) => $q->where('lead_source_id', $id))
            ->when($filters['county_id'] ?? null, fn (Builder $q, $id) => $q->where('county_id', $id))
            ->when($filters['assigned_to'] ?? null, fn (Builder $q, $id) => $q->where('assigned_to', $id))
            ->when(($filters['archived'] ?? null) === '1', fn (Builder $q) => $q->onlyTrashed())
            ->when(($filters['archived'] ?? null) !== '1', fn (Builder $q) => $q->whereNull('deleted_at'));
    }
}
