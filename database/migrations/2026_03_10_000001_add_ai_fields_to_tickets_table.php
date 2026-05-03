<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('incident_type', 50)->nullable()->after('issue_priority');
            $table->unsignedTinyInteger('incident_weight')->default(3)->after('incident_type');
            $table->enum('ai_status', ['pending', 'classified', 'failed', 'manual'])->default('pending')->after('incident_weight');
            $table->text('ai_suggestion')->nullable()->after('ai_status');
            $table->text('ai_justification')->nullable()->after('ai_suggestion');
        });

        // For existing tickets: set neutral classification (manual, not pending)
        // so they don't show "Clasificando..." forever in the Kanban.
        // New tickets will start as 'pending' and get classified by the AI job.
        DB::statement("
            UPDATE tickets
            SET
                incident_type      = 'funcionalidad_menor',
                ai_status          = 'manual',
                effective_priority = (
                    CASE client_priority
                        WHEN 'vip'    THEN 1
                        WHEN 'high'   THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low'    THEN 4
                        ELSE 3
                    END
                ) * 3
        ");
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'incident_type',
                'incident_weight',
                'ai_status',
                'ai_suggestion',
                'ai_justification',
            ]);
        });
    }
};
