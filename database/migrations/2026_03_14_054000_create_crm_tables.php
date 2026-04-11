<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nettoyer les anciennes tables si elles existent (migration greenfield)
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_points');
        Schema::dropIfExists('loyalty_levels');
        Schema::dropIfExists('customer_tag_members');
        Schema::dropIfExists('customer_tags');
        Schema::dropIfExists('customer_segment_members');
        Schema::dropIfExists('customer_segments');

        // 1. customer_segments
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['automatic', 'manual', 'mixed'])->default('automatic');
            $table->string('color')->default('#3B82F6'); // Indigo/Blue default
            $table->json('rules')->nullable(); // JSON criteria for automatic segments
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('customers_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. customer_segment_members (Pivot)
        Schema::create('customer_segment_members', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('segment_id')->constrained('customer_segments')->onDelete('cascade');
            $table->string('assigned_by')->default('system');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->primary(['customer_id', 'segment_id']);
        });

        // 3. customer_tags
        Schema::create('customer_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('#10B981'); // Emerald default
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. customer_tag_members (Pivot)
        Schema::create('customer_tag_members', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('customer_tags')->onDelete('cascade');
            $table->timestamps();
            $table->primary(['customer_id', 'tag_id']);
        });

        // 5. loyalty_levels (VIP)
        Schema::create('loyalty_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('min_points')->default(0);
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->json('benefits')->nullable();
            $table->timestamps();
        });

        // 6. loyalty_points (The Ledger)
        // We handle migration from old loyalty_points/transactions if needed
        // but since we are refactoring, we create the new structure.
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->integer('points'); // Can be negative (spent/adjusted)
            $table->enum('type', ['earned', 'spent', 'adjusted', 'expired'])->default('earned');
            $table->enum('source', ['pos_sale', 'web_order', 'manual', 'referral', 'signup'])->default('web_order');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type')->nullable(); // Polymorphic source (e.g. App\Models\Order)
            $table->string('description')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_points');
        Schema::dropIfExists('loyalty_levels');
        Schema::dropIfExists('customer_tag_members');
        Schema::dropIfExists('customer_tags');
        Schema::dropIfExists('customer_segment_members');
        Schema::dropIfExists('customer_segments');
    }
};
