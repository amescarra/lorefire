<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One known-spell row can be memorized more than once (2E Vancian).
     */
    public function up(): void
    {
        if (! Schema::hasTable('character_spells')) {
            return;
        }

        Schema::table('character_spells', function (Blueprint $table) {
            if (! Schema::hasColumn('character_spells', 'times_memorized')) {
                $table->unsignedTinyInteger('times_memorized')->default(0);
            }
            if (! Schema::hasColumn('character_spells', 'times_cast')) {
                $table->unsignedTinyInteger('times_cast')->default(0);
            }
        });

        foreach (DB::table('character_spells')->orderBy('id')->cursor() as $row) {
            $prepared = (bool) $row->is_prepared;
            $times = $prepared ? max(1, (int) ($row->times_memorized ?? 0)) : (int) ($row->times_memorized ?? 0);
            $cast = $prepared && (bool) $row->is_cast ? $times : (int) ($row->times_cast ?? 0);
            if ($cast > $times) {
                $cast = $times;
            }

            DB::table('character_spells')->where('id', $row->id)->update([
                'times_memorized' => $times,
                'times_cast' => $cast,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('character_spells')) {
            return;
        }

        Schema::table('character_spells', function (Blueprint $table) {
            if (Schema::hasColumn('character_spells', 'times_cast')) {
                $table->dropColumn('times_cast');
            }
            if (Schema::hasColumn('character_spells', 'times_memorized')) {
                $table->dropColumn('times_memorized');
            }
        });
    }
};
