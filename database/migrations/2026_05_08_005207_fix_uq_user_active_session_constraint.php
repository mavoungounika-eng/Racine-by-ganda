<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE pos_sessions SET is_active = NULL WHERE status IN ("closed", "closing")');
        if (DB::getDriverName() === "sqlite") {
            DB::statement("DROP INDEX IF EXISTS uq_user_active_session");
            DB::statement("CREATE UNIQUE INDEX uq_user_active_session ON pos_sessions (opened_by, is_active)");
        } else {
            DB::statement("ALTER TABLE pos_sessions DROP FOREIGN KEY pos_sessions_opened_by_foreign");
            DB::statement("ALTER TABLE pos_sessions DROP FOREIGN KEY pos_sessions_closed_by_foreign");
            DB::statement("ALTER TABLE pos_sessions DROP INDEX uq_user_active_session");
            DB::statement("ALTER TABLE pos_sessions ADD UNIQUE KEY uq_user_active_session (opened_by, is_active)");
            DB::statement("ALTER TABLE pos_sessions ADD CONSTRAINT pos_sessions_opened_by_foreign FOREIGN KEY (opened_by) REFERENCES users (id)");
            DB::statement("ALTER TABLE pos_sessions ADD CONSTRAINT pos_sessions_closed_by_foreign FOREIGN KEY (closed_by) REFERENCES users (id)");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === "sqlite") {
            DB::statement("DROP INDEX IF EXISTS uq_user_active_session");
            DB::statement("CREATE UNIQUE INDEX uq_user_active_session ON pos_sessions (opened_by, is_active)");
        } else {
            DB::statement("ALTER TABLE pos_sessions DROP FOREIGN KEY pos_sessions_opened_by_foreign");
            DB::statement("ALTER TABLE pos_sessions DROP FOREIGN KEY pos_sessions_closed_by_foreign");
            DB::statement("ALTER TABLE pos_sessions DROP INDEX uq_user_active_session");
            DB::statement("ALTER TABLE pos_sessions ADD UNIQUE KEY uq_user_active_session (opened_by, is_active)");
            DB::statement("ALTER TABLE pos_sessions ADD CONSTRAINT pos_sessions_opened_by_foreign FOREIGN KEY (opened_by) REFERENCES users (id)");
            DB::statement("ALTER TABLE pos_sessions ADD CONSTRAINT pos_sessions_closed_by_foreign FOREIGN KEY (closed_by) REFERENCES users (id)");
        }
    }
};