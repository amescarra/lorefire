<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sheet tools the player fills. No CPHB PSP tables or power text.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('characters')) {
            return;
        }

        Schema::table('characters', function (Blueprint $table) {
            if (! Schema::hasColumn('characters', 'psp_current')) {
                $table->integer('psp_current')->nullable();
            }
            if (! Schema::hasColumn('characters', 'psp_max')) {
                $table->integer('psp_max')->nullable();
            }
            if (! Schema::hasColumn('characters', 'psionic_powers')) {
                $table->json('psionic_powers')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('characters')) {
            return;
        }

        Schema::table('characters', function (Blueprint $table) {
            foreach (['psp_current', 'psp_max', 'psionic_powers'] as $column) {
                if (Schema::hasColumn('characters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
