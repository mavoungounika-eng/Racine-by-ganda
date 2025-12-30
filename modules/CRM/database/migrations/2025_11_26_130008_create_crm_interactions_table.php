<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Agent
            $table->string('type'); // call, email, meeting, note, amira
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->dateTime('occurred_at');
            $table->string('outcome')->nullable(); // no_answer, interested, scheduled, closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_interactions');
    }
};
