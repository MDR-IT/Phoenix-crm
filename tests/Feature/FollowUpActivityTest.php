<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use App\Services\Messaging\OutboundMessenger;
use App\Services\UncontactedLeadReminderService;
use Database\Seeders\CrmLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FollowUpActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CrmLookupSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('Sales Manager');

        $this->agent = User::factory()->create(['name' => 'Agent One']);
        $this->agent->assignRole('Sales Agent');
    }

    public function test_manager_can_schedule_follow_up_and_it_appears_on_timeline(): void
    {
        $lead = Lead::factory()->create([
            'created_by' => $this->manager->id,
            'assigned_to' => $this->agent->id,
            'status' => 'assigned',
        ]);

        $scheduledAt = now()->addDay()->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->manager)->post(route('follow-ups.store'), [
            'lead_id' => $lead->id,
            'type' => 'callback',
            'scheduled_at' => $scheduledAt,
            'assigned_to' => $this->agent->id,
            'notes' => 'Call back about loan amount',
            'from_lead' => 1,
        ]);

        $response->assertRedirect(route('leads.show', $lead));

        $this->assertDatabaseHas('follow_ups', [
            'lead_id' => $lead->id,
            'type' => 'callback',
            'status' => FollowUp::STATUS_SCHEDULED,
            'assigned_to' => $this->agent->id,
        ]);

        $this->assertDatabaseHas('activities', [
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'type' => Activity::TYPE_FOLLOW_UP,
        ]);

        $show = $this->actingAs($this->manager)->get(route('leads.show', $lead));
        $show->assertOk();
        $show->assertSee('Callback scheduled');
        $show->assertSee('Activity timeline');
    }

    public function test_completing_follow_up_marks_lead_contacted(): void
    {
        $lead = Lead::factory()->create([
            'created_by' => $this->manager->id,
            'assigned_to' => $this->agent->id,
            'status' => 'assigned',
            'last_contacted_at' => null,
        ]);

        $followUp = FollowUp::factory()->create([
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
            'scheduled_at' => now()->subHour(),
            'status' => FollowUp::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($this->agent)->post(route('follow-ups.complete', $followUp), [
            'outcome' => 'Spoke with customer, interested',
        ]);

        $response->assertRedirect();

        $followUp->refresh();
        $lead->refresh();

        $this->assertSame(FollowUp::STATUS_COMPLETED, $followUp->status);
        $this->assertSame('contacted', $lead->status);
        $this->assertNotNull($lead->last_contacted_at);
        $this->assertDatabaseHas('activities', [
            'subject_id' => $lead->id,
            'type' => Activity::TYPE_FOLLOW_UP_COMPLETED,
        ]);
    }

    public function test_agent_can_add_note_to_assigned_lead(): void
    {
        $lead = Lead::factory()->create([
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->agent)->post(route('leads.notes.store', $lead), [
            'body' => 'Customer prefers afternoon calls',
            'is_pinned' => 1,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('notes', [
            'notable_type' => Lead::class,
            'notable_id' => $lead->id,
            'user_id' => $this->agent->id,
            'body' => 'Customer prefers afternoon calls',
            'is_pinned' => 1,
        ]);

        $this->assertDatabaseHas('activities', [
            'subject_id' => $lead->id,
            'type' => Activity::TYPE_NOTE,
        ]);
    }

    public function test_task_create_assign_and_complete_flow(): void
    {
        Notification::fake();

        $lead = Lead::factory()->create([
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
        ]);

        $create = $this->actingAs($this->manager)->post(route('tasks.store'), [
            'title' => 'Send KYC checklist',
            'description' => 'Email the documents list',
            'lead_id' => $lead->id,
            'assigned_to' => $this->agent->id,
            'priority' => 'high',
            'due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]);

        $create->assertRedirect(route('leads.show', $lead));

        $task = Task::query()->first();
        $this->assertNotNull($task);
        $this->assertSame(Task::STATUS_OPEN, $task->status);

        Notification::assertSentTo($this->agent, \App\Notifications\TaskAssignedNotification::class);

        $complete = $this->actingAs($this->agent)->post(route('tasks.complete', $task));
        $complete->assertRedirect();

        $task->refresh();
        $this->assertSame(Task::STATUS_COMPLETED, $task->status);
        $this->assertNotNull($task->completed_at);

        $this->assertDatabaseHas('activities', [
            'subject_id' => $lead->id,
            'type' => Activity::TYPE_TASK_COMPLETED,
        ]);
    }

    public function test_follow_up_worklist_shows_today_bucket(): void
    {
        $lead = Lead::factory()->create([
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
        ]);

        FollowUp::factory()->create([
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
            'scheduled_at' => now()->setTime(14, 0),
            'status' => FollowUp::STATUS_SCHEDULED,
            'notes' => 'Afternoon callback',
        ]);

        $response = $this->actingAs($this->manager)->get(route('follow-ups.index', ['bucket' => 'today']));
        $response->assertOk();
        $response->assertSee('Afternoon callback');
        $response->assertSee($lead->reference);
    }

    public function test_overdue_tasks_list_filter(): void
    {
        Task::factory()->create([
            'title' => 'Overdue paperwork',
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
            'status' => Task::STATUS_OPEN,
            'due_at' => now()->subDay(),
            'taskable_type' => null,
            'taskable_id' => null,
        ]);

        $response = $this->actingAs($this->manager)->get(route('tasks.index', ['overdue' => 1]));
        $response->assertOk();
        $response->assertSee('Overdue paperwork');
    }

    public function test_agent_cannot_view_unassigned_follow_up_worklist_item_owned_elsewhere(): void
    {
        $other = User::factory()->create();
        $other->assignRole('Sales Agent');

        $lead = Lead::factory()->create([
            'assigned_to' => $other->id,
            'created_by' => $this->manager->id,
        ]);

        FollowUp::factory()->create([
            'lead_id' => $lead->id,
            'assigned_to' => $other->id,
            'created_by' => $this->manager->id,
            'scheduled_at' => now()->addHour(),
            'notes' => 'Secret other agent callback',
        ]);

        $response = $this->actingAs($this->agent)->get(route('follow-ups.index', ['bucket' => '']));
        $response->assertOk();
        $response->assertDontSee('Secret other agent callback');
    }

    public function test_uncontacted_lead_reminder_notifies_assignee(): void
    {
        Notification::fake();

        $lead = Lead::factory()->create([
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
            'status' => 'new',
            'last_contacted_at' => null,
            'uncontacted_reminded_at' => null,
            'created_at' => now()->subHours(30),
        ]);

        // Force created_at past threshold (factory may overwrite timestamps).
        $lead->forceFill(['created_at' => now()->subHours(30)])->save();

        $reminded = app(UncontactedLeadReminderService::class)->remind();

        $this->assertTrue($reminded->contains('id', $lead->id));
        Notification::assertSentTo($this->agent, \App\Notifications\UncontactedLeadReminderNotification::class);
        $this->assertDatabaseHas('activities', [
            'subject_id' => $lead->id,
            'type' => Activity::TYPE_REMINDER,
        ]);
    }

    public function test_outbound_messenger_stubs_without_hardcoded_secrets(): void
    {
        $messenger = app(OutboundMessenger::class);

        $email = $messenger->email('agent@example.com', 'Test', 'Body');
        $sms = $messenger->sms('254712345678', 'Hello');

        $this->assertTrue($email->stubbed);
        $this->assertTrue($sms->stubbed);
        $this->assertFalse($messenger->smsConfigured());
    }

    public function test_dashboard_includes_follow_up_widgets(): void
    {
        $lead = Lead::factory()->create([
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
        ]);

        FollowUp::factory()->create([
            'lead_id' => $lead->id,
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
            'scheduled_at' => now()->setTime(11, 30),
            'status' => FollowUp::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($this->manager)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Follow-ups today');
        $response->assertSee('Overdue follow-ups');
        $response->assertSee('Uncontacted leads');
        $response->assertSee($lead->reference);
    }

    public function test_sales_agent_lacks_follow_ups_view_all_but_can_create(): void
    {
        $this->assertTrue($this->agent->can('follow_ups.create'));
        $this->assertTrue($this->agent->can('tasks.create'));
        $this->assertFalse($this->agent->can('follow_ups.view_all'));
        $this->assertTrue($this->manager->can('follow_ups.view_all'));
    }

    public function test_reminder_command_marks_missed_follow_ups(): void
    {
        Notification::fake();

        $lead = Lead::factory()->create([
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
        ]);

        $followUp = FollowUp::factory()->create([
            'lead_id' => $lead->id,
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
            'scheduled_at' => now()->subHours(2),
            'status' => FollowUp::STATUS_SCHEDULED,
        ]);

        $this->artisan('phoenix:process-follow-up-reminders')->assertSuccessful();

        $followUp->refresh();
        $this->assertSame(FollowUp::STATUS_MISSED, $followUp->status);
        Notification::assertSentTo($this->agent, \App\Notifications\FollowUpDueNotification::class);
    }
}
