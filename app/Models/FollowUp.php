<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowUp extends Model
{
    /** @use HasFactory<\Database\Factories\FollowUpFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_MISSED = 'missed';

    protected $fillable = [
        'lead_id',
        'customer_id',
        'assigned_to',
        'created_by',
        'type',
        'status',
        'scheduled_at',
        'completed_at',
        'notes',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_MISSED], true)
            && $this->scheduled_at->isPast();
    }

    public function typeLabel(): string
    {
        return config("phoenix.follow_up_types.{$this->type}", $this->type);
    }

    public function statusLabel(): string
    {
        return config("phoenix.follow_up_statuses.{$this->status}", $this->status);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('follow_ups.view_all') || $user->hasRole('Super Administrator')) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($user) {
            $inner->where('assigned_to', $user->id)
                ->orWhere('created_by', $user->id)
                ->orWhereHas('lead', fn (Builder $lead) => $lead->where('assigned_to', $user->id));
        });
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_MISSED]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()
            ->where('scheduled_at', '<', now());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->open()
            ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()]);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->open()
            ->where('scheduled_at', '>', now()->endOfDay());
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('notes', 'like', "%{$search}%")
                        ->orWhere('outcome', 'like', "%{$search}%")
                        ->orWhereHas('lead', function (Builder $lead) use ($search) {
                            $lead->where('reference', 'like', "%{$search}%")
                                ->orWhereHas('customer', function (Builder $customer) use ($search) {
                                    $customer->where('full_name', 'like', "%{$search}%")
                                        ->orWhere('phone', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['assigned_to'] ?? null, fn (Builder $q, $id) => $q->where('assigned_to', $id))
            ->when(($filters['bucket'] ?? null) === 'today', fn (Builder $q) => $q->dueToday())
            ->when(($filters['bucket'] ?? null) === 'overdue', fn (Builder $q) => $q->overdue())
            ->when(($filters['bucket'] ?? null) === 'upcoming', fn (Builder $q) => $q->upcoming());
    }
}
