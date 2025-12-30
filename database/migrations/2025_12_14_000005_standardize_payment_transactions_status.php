<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Convertit l'enum status en VARCHAR(32) pour plus de flexibilité.
     * Migration portable : compatible MySQL et SQLite.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->upSqlite();
        } else {
            $this->upMysql();
        }
    }

    /**
     * Migration pour MySQL/PostgreSQL
     */
    private function upMysql(): void
    {
        // Supprimer l'index sur status
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        // Modifier la colonne status en VARCHAR(32)
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->change();
        });

        // Migrer les valeurs existantes
        DB::table('payment_transactions')
            ->where('status', 'success')
            ->update(['status' => 'succeeded']);

        DB::table('payment_transactions')
            ->where('status', 'cancelled')
            ->update(['status' => 'canceled']);

        // Recréer l'index sur status
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Migration pour SQLite (rebuild de table)
     */
    private function upSqlite(): void
    {
        // Récupérer tous les index de la table payment_transactions
        $indexes = DB::select("
            SELECT name FROM sqlite_master 
            WHERE type = 'index' 
            AND tbl_name = 'payment_transactions'
            AND sql IS NOT NULL
        ");
        
        // Supprimer tous les index avant de renommer (SQLite ne les supprime pas automatiquement)
        foreach ($indexes as $index) {
            try {
                DB::statement("DROP INDEX IF EXISTS {$index->name}");
            } catch (\Exception $e) {
                // Ignorer si l'index n'existe pas
            }
        }

        // Renommer la table existante
        DB::statement('ALTER TABLE payment_transactions RENAME TO payment_transactions_old');

        // Recréer la table avec status en string(32)
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('monetbil');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_ref')->unique();
            $table->string('item_ref')->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->string('transaction_uuid')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('XAF');
            $table->string('status', 32)->default('pending'); // VARCHAR(32) au lieu d'ENUM
            $table->string('operator')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // Indexes identiques à la table originale (unique() crée automatiquement un index)
            // payment_ref et transaction_id ont déjà ->unique() qui crée l'index
            $table->index('order_id');
            $table->index('status');
        });

        // Copier les données avec mapping des statuts
        DB::statement("
            INSERT INTO payment_transactions (
                id, provider, order_id, payment_ref, item_ref, transaction_id, transaction_uuid,
                amount, currency, status, operator, phone, fee, raw_payload, notified_at,
                created_at, updated_at
            )
            SELECT 
                id, provider, order_id, payment_ref, item_ref, transaction_id, transaction_uuid,
                amount, currency,
                CASE status
                    WHEN 'success' THEN 'succeeded'
                    WHEN 'cancelled' THEN 'canceled'
                    ELSE status
                END as status,
                operator, phone, fee, raw_payload, notified_at,
                created_at, updated_at
            FROM payment_transactions_old
        ");

        // Supprimer l'ancienne table
        Schema::drop('payment_transactions_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->downSqlite();
        } else {
            $this->downMysql();
        }
    }

    /**
     * Rollback pour MySQL/PostgreSQL
     */
    private function downMysql(): void
    {
        // Supprimer l'index sur status
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        // Reconvertir les valeurs
        DB::table('payment_transactions')
            ->where('status', 'succeeded')
            ->update(['status' => 'success']);

        DB::table('payment_transactions')
            ->where('status', 'canceled')
            ->update(['status' => 'cancelled']);

        // Reconvertir en enum (limité aux valeurs originales)
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending')->change();
        });

        // Recréer l'index
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Rollback pour SQLite (rebuild de table)
     */
    private function downSqlite(): void
    {
        // Récupérer tous les index de la table payment_transactions
        $indexes = DB::select("
            SELECT name FROM sqlite_master 
            WHERE type = 'index' 
            AND tbl_name = 'payment_transactions'
            AND sql IS NOT NULL
        ");
        
        // Supprimer tous les index avant de renommer
        foreach ($indexes as $index) {
            try {
                DB::statement("DROP INDEX IF EXISTS {$index->name}");
            } catch (\Exception $e) {
                // Ignorer si l'index n'existe pas
            }
        }

        // Renommer la table
        DB::statement('ALTER TABLE payment_transactions RENAME TO payment_transactions_old');

        // Recréer avec ENUM (SQLite ne supporte pas ENUM, on utilise string avec contrainte)
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('monetbil');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_ref')->unique();
            $table->string('item_ref')->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->string('transaction_uuid')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('XAF');
            $table->string('status', 32)->default('pending'); // SQLite n'a pas ENUM, on garde string
            $table->string('operator')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index('payment_ref');
            $table->index('transaction_id');
            $table->index('order_id');
            $table->index('status');
        });

        // Copier les données avec reconversion des statuts
        DB::statement("
            INSERT INTO payment_transactions (
                id, provider, order_id, payment_ref, item_ref, transaction_id, transaction_uuid,
                amount, currency, status, operator, phone, fee, raw_payload, notified_at,
                created_at, updated_at
            )
            SELECT 
                id, provider, order_id, payment_ref, item_ref, transaction_id, transaction_uuid,
                amount, currency,
                CASE status
                    WHEN 'succeeded' THEN 'success'
                    WHEN 'canceled' THEN 'cancelled'
                    ELSE status
                END as status,
                operator, phone, fee, raw_payload, notified_at,
                created_at, updated_at
            FROM payment_transactions_old
        ");

        // Supprimer l'ancienne table
        Schema::drop('payment_transactions_old');
    }
};




