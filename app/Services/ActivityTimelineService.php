<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Lead;
use Illuminate\Support\Collection;

class ActivityTimelineService
{
    /**
     * Chronological timeline for a lead: stored activities plus legacy Phase 2 histories
     * that may pre-date Phase 3 activity rows, and placeholders for future channel types.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forLead(Lead $lead): Collection
    {
        $items = collect();

        $lead->loadMissing([
            'activities.user',
            'statusHistories.user',
            'assignmentHistories.fromUser',
            'assignmentHistories.toUser',
            'assignmentHistories.assigner',
            'leadSource',
            'creator',
        ]);

        foreach ($lead->activities as $activity) {
            $items->push($this->fromActivity($activity));
        }

        $activityKeys = $lead->activities
            ->map(fn (Activity $a) => $a->type.'|'.optional($a->occurred_at)->timestamp.'|'.$a->title)
            ->flip();

        // Seed a creation entry if none was logged yet (Phase 2 leads).
        $createdKey = Activity::TYPE_CREATED.'|'.optional($lead->created_at)->timestamp.'|Lead created';
        if (! $activityKeys->has($createdKey)) {
            $items->push([
                'type' => Activity::TYPE_CREATED,
                'type_label' => config('phoenix.activity_types.'.Activity::TYPE_CREATED, 'Created'),
                'title' => 'Lead created',
                'body' => $lead->channel ? 'Channel: '.$lead->channel : null,
                'user_name' => $lead->creator?->name,
                'occurred_at' => $lead->created_at,
                'meta' => ['channel' => $lead->channel],
                'source' => 'legacy',
            ]);
        }

        if ($lead->leadSource) {
            $sourceKey = Activity::TYPE_SOURCE.'|'.optional($lead->created_at)->timestamp.'|Source: '.$lead->leadSource->name;
            if (! $activityKeys->has($sourceKey)) {
                $items->push([
                    'type' => Activity::TYPE_SOURCE,
                    'type_label' => config('phoenix.activity_types.'.Activity::TYPE_SOURCE, 'Source'),
                    'title' => 'Source: '.$lead->leadSource->name,
                    'body' => $lead->campaign ? 'Campaign: '.$lead->campaign : null,
                    'user_name' => $lead->creator?->name,
                    'occurred_at' => $lead->created_at,
                    'meta' => ['lead_source_id' => $lead->lead_source_id],
                    'source' => 'legacy',
                ]);
            }
        }

        foreach ($lead->statusHistories as $row) {
            $title = sprintf(
                'Status: %s → %s',
                config('phoenix.lead_statuses.'.$row->from_status, $row->from_status ?: '—'),
                config('phoenix.lead_statuses.'.$row->to_status, $row->to_status)
            );
            $key = Activity::TYPE_STATUS_CHANGE.'|'.optional($row->created_at)->timestamp.'|'.$title;
            if ($activityKeys->has($key)) {
                continue;
            }
            $items->push([
                'type' => Activity::TYPE_STATUS_CHANGE,
                'type_label' => config('phoenix.activity_types.'.Activity::TYPE_STATUS_CHANGE, 'Status'),
                'title' => $title,
                'body' => $row->notes,
                'user_name' => $row->user?->name,
                'occurred_at' => $row->created_at,
                'meta' => [
                    'from_status' => $row->from_status,
                    'to_status' => $row->to_status,
                ],
                'source' => 'legacy',
            ]);
        }

        foreach ($lead->assignmentHistories as $row) {
            $title = sprintf(
                'Assigned: %s → %s',
                $row->fromUser?->name ?: 'Unassigned',
                $row->toUser?->name ?: 'Unassigned'
            );
            $key = Activity::TYPE_ASSIGNMENT.'|'.optional($row->created_at)->timestamp.'|'.$title;
            if ($activityKeys->has($key)) {
                continue;
            }
            $items->push([
                'type' => Activity::TYPE_ASSIGNMENT,
                'type_label' => config('phoenix.activity_types.'.Activity::TYPE_ASSIGNMENT, 'Assignment'),
                'title' => $title,
                'body' => $row->notes ?: ('Method: '.$row->method),
                'user_name' => $row->assigner?->name,
                'occurred_at' => $row->created_at,
                'meta' => ['method' => $row->method],
                'source' => 'legacy',
            ]);
        }

        // Phase 4 placeholder so the timeline already advertises future interaction types.
        $items->push([
            'type' => Activity::TYPE_CHANNEL_INTERACTION,
            'type_label' => config('phoenix.activity_types.'.Activity::TYPE_CHANNEL_INTERACTION, 'Channel'),
            'title' => 'Digital channel interactions',
            'body' => 'WhatsApp, Meta, Instagram, and website messages will appear here in Phase 4.',
            'user_name' => null,
            'occurred_at' => null,
            'meta' => ['placeholder' => true],
            'source' => 'placeholder',
        ]);

        return $items
            ->sortByDesc(function (array $item) {
                if ($item['source'] === 'placeholder') {
                    return -1;
                }

                return optional($item['occurred_at'])->timestamp ?? 0;
            })
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function fromActivity(Activity $activity): array
    {
        return [
            'type' => $activity->type,
            'type_label' => $activity->typeLabel(),
            'title' => $activity->title,
            'body' => $activity->body,
            'user_name' => $activity->user?->name,
            'occurred_at' => $activity->occurred_at,
            'meta' => $activity->meta ?? [],
            'source' => 'activity',
            'id' => $activity->id,
        ];
    }
}
