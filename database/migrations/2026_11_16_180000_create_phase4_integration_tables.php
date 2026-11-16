<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->unique();
            $table->string('display_name');
            $table->boolean('is_enabled')->default(false);
            $table->text('credentials_encrypted')->nullable();
            $table->json('config')->nullable();
            $table->string('mode', 20)->default('sandbox'); // sandbox | live | pending
            $table->string('health_status', 30)->default('pending_configuration');
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('event_type', 80)->nullable();
            $table->string('external_id', 191)->nullable();
            $table->string('idempotency_key', 191)->nullable()->unique();
            $table->boolean('signature_valid')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 30)->default('received'); // received, queued, processing, processed, failed, duplicate, rejected
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('channel_interaction_id')->nullable();
            $table->boolean('used_sandbox')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
            $table->index(['provider', 'external_id']);
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
        });

        Schema::create('channel_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('webhook_event_id')->nullable();
            $table->string('channel', 40); // whatsapp, facebook, instagram, website, messenger
            $table->string('direction', 20)->default('inbound'); // inbound | outbound
            $table->string('external_id', 191)->nullable();
            $table->string('message_type', 40)->nullable(); // text, lead_form, image, unsupported, …
            $table->text('body')->nullable();
            $table->string('sender_handle', 191)->nullable();
            $table->json('payload')->nullable();
            $table->boolean('is_unsupported')->default(false);
            $table->string('unsupported_reason')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'occurred_at']);
            $table->index(['lead_id', 'channel']);
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('webhook_event_id')->references('id')->on('webhook_events')->nullOnDelete();
        });

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->foreign('channel_interaction_id')
                ->references('id')
                ->on('channel_interactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropForeign(['channel_interaction_id']);
        });
        Schema::dropIfExists('channel_interactions');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('integration_settings');
    }
};
