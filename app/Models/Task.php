<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'title',
        'description',
        'taskable_type',
        'taskable_id',
        'assigned_to',
        'created_by',
        'priority',
        'status',
        'due_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
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
        return $this->status === self::STATUS_OPEN
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    public function priorityLabel(): string
    {
        return config("phoenix.lead_priorities.{$this->priority}", $this->priority);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('tasks.view_all') || $user->hasRole('Super Administrator')) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($user) {
            $inner->where('assigned_to', $user->id)
                ->orWhere('created_by', $user->id);
        });
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $q, string $priority) => $q->where('priority', $priority))
            ->when($filters['assigned_to'] ?? null, fn (Builder $q, $id) => $q->where('assigned_to', $id))
            ->when(($filters['overdue'] ?? null) === '1', fn (Builder $q) => $q->overdue())
            ->when(($filters['due'] ?? null) === 'today', fn (Builder $q) => $q->dueToday())
            ->when(($filters['due'] ?? null) === 'upcoming', function (Builder $q) {
                $q->open()->whereNotNull('due_at')->where('due_at', '>', now()->endOfDay());
            });
    }
}
