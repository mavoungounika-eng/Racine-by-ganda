<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('footer_column')->nullable()->default('info')->after('show_in_footer');
        });

        // Catégoriser les pages existantes
        DB::table('pages')
            ->whereIn('slug', ['cgv', 'confidentialite', 'mentions-legales', 'cookies'])
            ->update(['footer_column' => 'legal']);

        DB::table('pages')
            ->whereIn('slug', ['contact', 'a-propos', 'aide', 'livraison', 'retours-echanges', 'faq'])
            ->update(['footer_column' => 'info']);
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('footer_column');
        });
    }
};
