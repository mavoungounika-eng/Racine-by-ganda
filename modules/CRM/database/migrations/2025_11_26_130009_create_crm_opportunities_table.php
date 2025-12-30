<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Owner
            $table->string('title');
            $table->decimal('value', 12, 2)->nullable();
            $table->string('currency')->default('XOF');
            $table->string('stage'); // discovery, proposal, negotiation, closed_won, closed_lost
            $table->integer('probability')->default(0); // %
            $table->date('expected_close_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_opportunities');
    }
};
