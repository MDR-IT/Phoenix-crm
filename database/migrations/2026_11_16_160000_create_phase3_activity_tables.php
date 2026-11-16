<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->nullableMorphs('taskable');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority', 16)->default('medium')->index();
            $table->string('status', 16)->default('open')->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['assigned_to', 'status', 'due_at']);
        });

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32)->default('callback')->index();
            $table->string('status', 16)->default('scheduled')->index();
            $table->timestamp('scheduled_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['assigned_to', 'status', 'scheduled_at']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('type', 48)->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('related');
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('last_contacted_at')->nullable()->after('follow_up_at');
            $table->timestamp('uncontacted_reminded_at')->nullable()->after('last_contacted_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['last_contacted_at', 'uncontacted_reminded_at']);
        });

        Schema::dropIfExists('activities');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('notes');
    }
};
