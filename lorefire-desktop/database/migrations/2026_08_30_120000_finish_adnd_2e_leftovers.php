<?php

use App\Support\Adnd2e;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remaining 5e residue: attunement, memorization rename, notes rename,
 * concentration/ritual drop, multi/dual-class columns, leftover class values.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_items')) {
            $this->dropColumns('inventory_items', ['attuned', 'requires_attunement']);
        }

        if (Schema::hasTable('character_spells')) {
            $this->dropColumns('character_spells', ['concentration', 'ritual']);
        }

        if (Schema::hasTable('character_conditions')) {
            $this->dropColumns('character_conditions', ['exhaustion_level']);
        }

        if (! Schema::hasTable('characters')) {
            return;
        }

        Schema::table('characters', function (Blueprint $table) {
            if (! Schema::hasColumn('characters', 'class_path')) {
                $table->string('class_path')->default('single');
            }
            if (! Schema::hasColumn('characters', 'class_levels')) {
                $table->json('class_levels')->nullable();
            }
            if (! Schema::hasColumn('characters', 'memorization')) {
                $table->json('memorization')->nullable();
            }
            if (! Schema::hasColumn('characters', 'memorization_used')) {
                $table->json('memorization_used')->nullable();
            }
            if (! Schema::hasColumn('characters', 'mannerisms')) {
                $table->text('mannerisms')->nullable();
            }
            if (! Schema::hasColumn('characters', 'motivations')) {
                $table->text('motivations')->nullable();
            }
            if (! Schema::hasColumn('characters', 'ties')) {
                $table->text('ties')->nullable();
            }
            if (! Schema::hasColumn('characters', 'weaknesses')) {
                $table->text('weaknesses')->nullable();
            }
        });

        if (Schema::hasColumn('characters', 'spell_slots') && Schema::hasColumn('characters', 'memorization')) {
            DB::table('characters')->orderBy('id')->each(function ($row) {
                $slots = $row->spell_slots ?? null;
                if ($slots !== null && ($row->memorization === null || $row->memorization === '')) {
                    DB::table('characters')->where('id', $row->id)->update(['memorization' => $slots]);
                }
            });
        }

        if (Schema::hasColumn('characters', 'spell_slots_used') && Schema::hasColumn('characters', 'memorization_used')) {
            DB::table('characters')->orderBy('id')->each(function ($row) {
                $used = $row->spell_slots_used ?? null;
                if ($used !== null && ($row->memorization_used === null || $row->memorization_used === '')) {
                    DB::table('characters')->where('id', $row->id)->update(['memorization_used' => $used]);
                }
            });
        }

        $this->copyText('personality_traits', 'mannerisms');
        $this->copyText('ideals', 'motivations');
        $this->copyText('bonds', 'ties');
        $this->copyText('flaws', 'weaknesses');

        $this->dropColumns('characters', [
            'spell_slots',
            'spell_slots_used',
            'personality_traits',
            'ideals',
            'bonds',
            'flaws',
        ]);

        $this->rewriteLegacyCharacterValues();
    }

    public function down(): void
    {
        // Non-reversible cleanup.
    }

    private function copyText(string $from, string $to): void
    {
        if (! Schema::hasColumn('characters', $from) || ! Schema::hasColumn('characters', $to)) {
            return;
        }

        DB::table('characters')->orderBy('id')->each(function ($row) use ($from, $to) {
            if (! empty($row->{$from}) && empty($row->{$to})) {
                DB::table('characters')->where('id', $row->id)->update([$to => $row->{$from}]);
            }
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropColumns(string $table, array $columns): void
    {
        $toDrop = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
        if ($toDrop === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($toDrop) {
            $blueprint->dropColumn($toDrop);
        });
    }

    private function rewriteLegacyCharacterValues(): void
    {
        DB::table('characters')->orderBy('id')->each(function ($row) {
            $original = (string) $row->class;
            $path = in_array($row->class_path ?? 'single', ['single', 'multi', 'dual'], true)
                ? $row->class_path
                : 'single';
            $existingLevels = null;
            if (! empty($row->class_levels)) {
                $decoded = is_string($row->class_levels) ? json_decode($row->class_levels, true) : $row->class_levels;
                $existingLevels = is_array($decoded) ? $decoded : null;
            }

            if (str_contains($original, '/') && ($existingLevels === null || $existingLevels === [])) {
                $path = $path === 'single' ? 'multi' : $path;
            }

            $entries = Adnd2e::normalizeClassLevels($existingLevels, $original, (int) ($row->level ?? 1), $path);
            $display = Adnd2e::displayClassName($entries, $path);
            $notes = $row->imported_data ?? null;
            $imported = is_string($notes) ? json_decode($notes, true) : (is_array($notes) ? $notes : []);
            if (! is_array($imported)) {
                $imported = [];
            }

            foreach ($entries as $entry) {
                $rewrite = Adnd2e::rewriteLegacyClass($entry['class']);
                if ($rewrite['rejected'] && $original !== '' && $original !== $rewrite['class']) {
                    $imported['legacy_class'] = $original;
                }
            }
            if ($original !== $display && ! str_contains($original, '/')) {
                $rewrite = Adnd2e::rewriteLegacyClass($original);
                if ($rewrite['mapped'] || $rewrite['rejected']) {
                    $imported['legacy_class'] = $original;
                }
            }

            DB::table('characters')->where('id', $row->id)->update([
                'class' => $display,
                'level' => Adnd2e::displayLevel($entries, $path),
                'class_path' => $path === 'single' && count($entries) > 1 ? 'multi' : $path,
                'class_levels' => json_encode($entries),
                'thac0' => Adnd2e::combinedThac0($entries),
                'hit_die' => Adnd2e::combinedHitDie($entries),
                'saving_throws' => json_encode(Adnd2e::combinedSavingThrows($entries)),
                'imported_data' => $imported === [] ? $row->imported_data : json_encode($imported),
            ]);
        });
    }
};
