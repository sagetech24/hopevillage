<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL was rewriting activity_time on every row update because the column
     * had ON UPDATE CURRENT_TIMESTAMP. That made voided joins "move" to today
     * in the dashboard Activity Summary (30-day totals stayed the same).
     */
    public function up(): void
    {
        DB::statement('
            ALTER TABLE member_activities
            MODIFY activity_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ');

        // Restore original times for voided rows whose activity_time was bumped
        // by ON UPDATE CURRENT_TIMESTAMP when metadata was updated.
        DB::affectingStatement("
            UPDATE member_activities
            SET activity_time = created_at
            WHERE created_at IS NOT NULL
              AND activity_time <> created_at
              AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.status')), '') = 'void'
        ");
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE member_activities
            MODIFY activity_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ');
    }
};
