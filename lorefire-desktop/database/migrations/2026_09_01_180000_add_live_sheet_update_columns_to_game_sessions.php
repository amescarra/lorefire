<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-session cursor so live transcript extract does not re-apply the same line.
     */
    public function up(): void
    {
        if (! Schema::hasTable('game_sessions')) {
            return;
        }

        Schema::table('game_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('game_sessions', 'sheet_update_cursor')) {
                $table->unsignedInteger('sheet_update_cursor')->default(0);
            }
            if (! Schema::hasColumn('game_sessions', 'sheet_update_hashes')) {
                $table->json('sheet_update_hashes')->nullable();
            }
            if (! Schema::hasColumn('game_sessions', 'live_chunk_index')) {
                $table->integer('live_chunk_index')->default(-1);
            }
            if (! Schema::hasColumn('game_sessions', 'live_audio_seconds')) {
                $table->float('live_audio_seconds')->default(0);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('game_sessions')) {
            return;
        }

        Schema::table('game_sessions', function (Blueprint $table) {
            foreach (['sheet_update_cursor', 'sheet_update_hashes', 'live_chunk_index', 'live_audio_seconds'] as $column) {
                if (Schema::hasColumn('game_sessions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
