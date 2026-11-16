<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Product;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Database\Seeders\CrmLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $agent;

    private User $otherAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CrmLookupSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('Sales Manager');

        $this->agent = User::factory()->create(['name' => 'Agent One']);
        $this->agent->assignRole('Sales Agent');

        $this->otherAgent = User::factory()->create(['name' => 'Agent Two']);
        $this->otherAgent->assignRole('Call Centre Agent');
    }

    public function test_manager_can_manually_create_lead(): void
    {
        $product = Product::query()->first();
        $source = LeadSource::query()->where('code', 'manual')->first();

        $response = $this->actingAs($this->manager)->post(route('leads.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Wanjiku',
            'phone' => '0712345678',
            'email' => 'jane@example.com',
            'product_id' => $product->id,
            'lead_source_id' => $source->id,
            'campaign' => 'Q4 Push',
            'description' => 'Interested in personal loan',
            'priority' => 'high',
            'status' => 'new',
            'consent_contact' => 1,
            'notes' => 'Called from website',
        ]);

        $lead = Lead::query()->first();
        $this->assertNotNull($lead);
        $response->assertRedirect(route('leads.show', $lead));

        $this->assertDatabaseHas('customers', [
            'full_name' => 'Jane Wanjiku',
            'phone_normalized' => PhoneNormalizer::normalize('0712345678'),
        ]);

        $this->assertSame('high', $lead->priority);
        $this->assertSame('manual', $lead->channel);
        $this->assertDatabaseHas('lead_status_histories', [
            'lead_id' => $lead->id,
            'to_status' => 'new',
        ]);
    }

    public function test_duplicate_phone_is_flagged_before_create(): void
    {
        $existing = Lead::factory()->create([
            'created_by' => $this->manager->id,
            'assigned_to' => $this->manager->id,
        ]);
        $existing->customer->update([
            'phone' => '0711111111',
            'phone_normalized' => PhoneNormalizer::normalize('0711111111'),
        ]);

        $response = $this->actingAs($this->manager)->post(route('leads.store'), [
            'first_name' => 'Dup',
            'last_name' => 'Lead',
            'phone' => '0711111111',
            'priority' => 'medium',
            'status' => 'new',
            'consent_contact' => 1,
        ]);

        $response->assertOk();
        $response->assertSee('Possible duplicates');
        $this->assertSame(1, Lead::query()->count());
    }

    public function test_acknowledged_duplicate_still_creates_lead(): void
    {
        $existing = Lead::factory()->create([
            'created_by' => $this->manager->id,
        ]);
        $existing->customer->update([
            'phone' => '0722222222',
            'phone_normalized' => PhoneNormalizer::normalize('0722222222'),
        ]);

        $this->actingAs($this->manager)->post(route('leads.store'), [
            'first_name' => 'Second',
            'last_name' => 'Lead',
            'phone' => '0722222222',
            'priority' => 'medium',
            'status' => 'new',
            'consent_contact' => 1,
            'acknowledge_duplicates' => 1,
        ])->assertRedirect();

        $this->assertSame(2, Lead::query()->count());
    }

    public function test_lead_can_be_edited_with_audit_history(): void
    {
        $lead = Lead::factory()->create([
            'created_by' => $this->manager->id,
            'assigned_to' => $this->manager->id,
            'status' => 'new',
            'priority' => 'low',
        ]);

        $this->actingAs($this->manager)->put(route('leads.update', $lead), [
            'first_name' => $lead->customer->first_name,
            'last_name' => $lead->customer->last_name,
            'phone' => $lead->customer->phone,
            'email' => $lead->customer->email,
            'priority' => 'urgent',
            'status' => 'contacted',
            'consent_contact' => 1,
            'notes' => 'Updated notes',
            'status_notes' => 'Spoke with customer',
        ])->assertRedirect(route('leads.show', $lead));

        $lead->refresh();
        $this->assertSame('urgent', $lead->priority);
        $this->assertSame('contacted', $lead->status);

        $this->assertDatabaseHas('lead_audits', [
            'lead_id' => $lead->id,
            'field' => 'priority',
            'old_value' => 'low',
            'new_value' => 'urgent',
            'user_id' => $this->manager->id,
        ]);

        $this->assertDatabaseHas('lead_status_histories', [
            'lead_id' => $lead->id,
            'from_status' => 'new',
            'to_status' => 'contacted',
        ]);
    }

    public function test_manual_assignment_notifies_assignee_and_records_history(): void
    {
        $lead = Lead::factory()->create([
            'created_by' => $this->manager->id,
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $this->actingAs($this->manager)->post(route('leads.assign', $lead), [
            'mode' => 'manual',
            'assigned_to' => $this->agent->id,
            'notes' => 'Please call today',
        ])->assertRedirect(route('leads.show', $lead));

        $lead->refresh();
        $this->assertSame($this->agent->id, $lead->assigned_to);
        $this->assertSame('assigned', $lead->status);

        $this->assertDatabaseHas('lead_assignment_histories', [
            'lead_id' => $lead->id,
            'to_user_id' => $this->agent->id,
            'assigned_by' => $this->manager->id,
            'method' => 'manual',
        ]);

        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame(1, $this->agent->fresh()->unreadNotifications()->count());
    }

    public function test_round_robin_assignment_cycles_agents(): void
    {
        $leadA = Lead::factory()->create(['created_by' => $this->manager->id, 'status' => 'new']);
        $leadB = Lead::factory()->create(['created_by' => $this->manager->id, 'status' => 'new']);

        $this->actingAs($this->manager)->post(route('leads.assign', $leadA), [
            'mode' => 'round_robin',
        ])->assertRedirect();

        $this->actingAs($this->manager)->post(route('leads.assign', $leadB), [
            'mode' => 'round_robin',
        ])->assertRedirect();

        $this->assertNotNull($leadA->fresh()->assigned_to);
        $this->assertNotNull($leadB->fresh()->assigned_to);
        $this->assertNotSame($leadA->fresh()->assigned_to, $leadB->fresh()->assigned_to);
    }

    public function test_status_change_via_pipeline_endpoint(): void
    {
        $lead = Lead::factory()->create([
            'created_by' => $this->manager->id,
            'assigned_to' => $this->manager->id,
            'status' => 'new',
        ]);

        $this->actingAs($this->manager)
            ->patchJson(route('leads.status', $lead), ['status' => 'qualified'])
            ->assertOk()
            ->assertJson(['ok' => true, 'status' => 'qualified']);

        $this->assertSame('qualified', $lead->fresh()->status);
        $this->assertDatabaseHas('lead_status_histories', [
            'lead_id' => $lead->id,
            'from_status' => 'new',
            'to_status' => 'qualified',
        ]);
    }

    public function test_agent_only_sees_assigned_leads(): void
    {
        $mine = Lead::factory()->create([
            'assigned_to' => $this->agent->id,
            'created_by' => $this->manager->id,
        ]);
        $theirs = Lead::factory()->create([
            'assigned_to' => $this->otherAgent->id,
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->agent)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee($mine->reference)
            ->assertDontSee($theirs->reference);

        $this->actingAs($this->agent)
            ->get(route('leads.show', $theirs))
            ->assertForbidden();
    }

    public function test_customer_register_and_profile(): void
    {
        $this->actingAs($this->manager)->post(route('customers.store'), [
            'first_name' => 'Peter',
            'last_name' => 'Otieno',
            'phone' => '0733333333',
            'email' => 'peter@example.com',
            'consent_marketing' => 1,
        ])->assertRedirect();

        $customer = Customer::query()->first();
        $this->assertSame(PhoneNormalizer::normalize('0733333333'), $customer->phone_normalized);

        $this->actingAs($this->manager)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Peter Otieno');
    }

    public function test_manager_can_merge_leads(): void
    {
        $primary = Lead::factory()->create([
            'created_by' => $this->manager->id,
            'assigned_to' => $this->manager->id,
            'notes' => null,
            'campaign' => null,
        ]);
        $secondary = Lead::factory()->create([
            'created_by' => $this->manager->id,
            'assigned_to' => $this->agent->id,
            'notes' => 'Keep these notes',
            'campaign' => 'Merge Campaign',
        ]);

        $this->actingAs($this->manager)->post(route('leads.merge.store', $primary), [
            'secondary_lead_id' => $secondary->id,
        ])->assertRedirect(route('leads.show', $primary));

        $primary->refresh();
        $this->assertSame('Keep these notes', $primary->notes);
        $this->assertSame('Merge Campaign', $primary->campaign);
        $this->assertSoftDeleted($secondary);
    }
}
