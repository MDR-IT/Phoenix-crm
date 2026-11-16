<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\AssignmentRoundRobinState;
use App\Models\Lead;
use App\Models\LeadAssignmentHistory;
use App\Models\Team;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeadAssignmentService
{
    public function __construct(
        private readonly LeadAuditService $auditService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function assign(Lead $lead, ?User $assignee, User $actor, string $method = 'manual', ?string $notes = null): Lead
    {
        return DB::transaction(function () use ($lead, $assignee, $actor, $method, $notes) {
            $before = $lead->only(['assigned_to', 'status']);
            $fromUserId = $lead->assigned_to;
            $toUserId = $assignee?->id;

            $lead->assigned_to = $toUserId;

            if ($toUserId && $lead->status === 'new') {
                $lead->status = 'assigned';
            }

            $lead->save();

            LeadAssignmentHistory::create([
                'lead_id' => $lead->id,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'assigned_by' => $actor->id,
                'method' => $method,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            $this->auditService->recordChanges($lead, $before, $lead->only(['assigned_to', 'status']), $actor);

            if ($lead->wasChanged('status') && ($before['status'] ?? null) !== $lead->status) {
                $lead->statusHistories()->create([
                    'from_status' => $before['status'] ?? null,
                    'to_status' => $lead->status,
                    'user_id' => $actor->id,
                    'notes' => 'Auto-updated on assignment',
                    'created_at' => now(),
                ]);
            }

            $fromName = $fromUserId ? (User::find($fromUserId)?->name ?: 'User #'.$fromUserId) : 'Unassigned';
            $toName = $assignee?->name ?: 'Unassigned';
            $this->activityLogger->log(
                $lead,
                Activity::TYPE_ASSIGNMENT,
                "Assigned: {$fromName} → {$toName}",
                $actor,
                $notes ?: ('Method: '.$method),
                null,
                ['method' => $method, 'from_user_id' => $fromUserId, 'to_user_id' => $toUserId]
            );

            if ($assignee && $assignee->id !== $actor->id) {
                $assignee->notify(new LeadAssignedNotification($lead, $actor, $method));
            }

            return $lead->fresh(['assignee', 'customer']);
        });
    }

    public function assignByStrategy(Lead $lead, User $actor, string $strategy, ?int $teamId = null): Lead
    {
        $assignee = match ($strategy) {
            'round_robin' => $this->nextRoundRobinUser('global', 'default', $this->eligibleAgents()),
            'product' => $this->nextRoundRobinUser(
                'product',
                (string) ($lead->product_id ?? 'none'),
                $this->usersForProduct($lead->product_id)
            ),
            'region' => $this->nextRoundRobinUser(
                'region',
                (string) ($lead->county_id ?? 'none'),
                $this->usersForCounty($lead->county_id)
            ),
            'team' => $this->nextRoundRobinUser(
                'team',
                (string) ($teamId ?? 'none'),
                $this->usersForTeam($teamId)
            ),
            default => null,
        };

        if (! $assignee) {
            throw new \RuntimeException('No eligible assignee found for strategy: '.$strategy);
        }

        return $this->assign($lead, $assignee, $actor, $strategy);
    }

    /**
     * @return Collection<int, User>
     */
    public function eligibleAgents(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->role(['Call Centre Agent', 'Sales Agent', 'Call Centre Supervisor', 'Sales Manager'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function usersForProduct(?int $productId): Collection
    {
        if (! $productId) {
            return $this->eligibleAgents();
        }

        $specialists = User::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($q) => $q->where('products.id', $productId))
            ->orderBy('id')
            ->get();

        return $specialists->isNotEmpty() ? $specialists : $this->eligibleAgents();
    }

    /**
     * @return Collection<int, User>
     */
    private function usersForCounty(?int $countyId): Collection
    {
        if (! $countyId) {
            return $this->eligibleAgents();
        }

        $regional = User::query()
            ->where('is_active', true)
            ->where('county_id', $countyId)
            ->orderBy('id')
            ->get();

        return $regional->isNotEmpty() ? $regional : $this->eligibleAgents();
    }

    /**
     * @return Collection<int, User>
     */
    private function usersForTeam(?int $teamId): Collection
    {
        if (! $teamId) {
            return collect();
        }

        $team = Team::query()->with(['users' => fn ($q) => $q->where('is_active', true)->orderBy('users.id')])->find($teamId);

        return $team?->users ?? collect();
    }

    /**
     * @param  Collection<int, User>  $candidates
     */
    private function nextRoundRobinUser(string $scopeType, string $scopeKey, Collection $candidates): ?User
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        $state = AssignmentRoundRobinState::query()->firstOrCreate(
            ['scope_type' => $scopeType, 'scope_key' => $scopeKey],
            ['last_user_id' => null]
        );

        $ids = $candidates->pluck('id')->values();
        $lastIndex = $ids->search($state->last_user_id);
        $nextIndex = $lastIndex === false ? 0 : ($lastIndex + 1) % $ids->count();
        $nextId = $ids[$nextIndex];

        $state->update(['last_user_id' => $nextId]);

        return $candidates->firstWhere('id', $nextId);
    }
}
