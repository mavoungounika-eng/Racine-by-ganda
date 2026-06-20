<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE categories MODIFY COLUMN gender ENUM('homme', 'femme', 'unisex', 'enfant') NOT NULL DEFAULT 'unisex'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE categories MODIFY COLUMN gender ENUM('homme', 'femme', 'unisex') NOT NULL DEFAULT 'unisex'");
    }
};
