<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('full_name')->index();
            $table->string('phone', 32)->nullable();
            $table->string('phone_normalized', 32)->nullable()->index();
            $table->string('phone_alt', 32)->nullable();
            $table->string('phone_alt_normalized', 32)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('gender', 32)->nullable();
            $table->foreignId('county_id')->nullable()->constrained()->nullOnDelete();
            $table->string('town')->nullable();
            $table->boolean('consent_marketing')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
