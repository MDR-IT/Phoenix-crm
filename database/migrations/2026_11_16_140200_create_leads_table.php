<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('campaign')->nullable()->index();
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority', 16)->default('medium')->index();
            $table->string('status', 32)->default('new')->index();
            $table->timestamp('follow_up_at')->nullable()->index();
            $table->boolean('consent_contact')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->string('town')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 32)->default('manual')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'assigned_to']);
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
