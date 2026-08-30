<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts a previously-migrated 5e characters table to AD&D 2E.
 * Fresh installs already have the 2E create migration — this is a no-op then.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('characters')) {
            return;
        }

        Schema::table('characters', function (Blueprint $table) {
            if (! Schema::hasColumn('characters', 'thac0')) {
                $table->smallInteger('thac0')->default(20);
            }
            if (! Schema::hasColumn('characters', 'exceptional_strength')) {
                $table->string('exceptional_strength')->nullable();
            }
            if (! Schema::hasColumn('characters', 'hit_die')) {
                $table->string('hit_die')->nullable();
            }
            if (! Schema::hasColumn('characters', 'saving_throws')) {
                $table->json('saving_throws')->nullable();
            }
            if (! Schema::hasColumn('characters', 'weapon_proficiencies')) {
                $table->json('weapon_proficiencies')->nullable();
            }
            if (! Schema::hasColumn('characters', 'nonweapon_proficiencies')) {
                $table->json('nonweapon_proficiencies')->nullable();
            }
            if (! Schema::hasColumn('characters', 'priest_spheres')) {
                $table->json('priest_spheres')->nullable();
            }
        });

        $legacy = [
            'proficiency_bonus',
            'death_save_successes',
            'death_save_failures',
            'saving_throw_proficiencies',
            'skill_proficiencies',
            'skill_expertises',
            'temp_hp',
            'initiative_bonus',
            'dnd_beyond_url',
        ];

        $toDrop = array_values(array_filter($legacy, fn (string $column) => Schema::hasColumn('characters', $column)));
        if ($toDrop !== []) {
            Schema::table('characters', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn($toDrop);
            });
        }

        if (Schema::hasTable('character_spells') && ! Schema::hasColumn('character_spells', 'is_cast')) {
            Schema::table('character_spells', function (Blueprint $table) {
                $table->boolean('is_cast')->default(false);
            });
        }

        if (Schema::hasColumn('characters', 'class')) {
            foreach (DB::table('characters')->select('id', 'class', 'level')->orderBy('id')->get() as $row) {
                $rewrite = \App\Support\Adnd2e::rewriteLegacyClass((string) $row->class);
                $class = (string) $row->class;
                $path = 'single';
                $entries = null;
                if (str_contains($class, '/')) {
                    $path = 'multi';
                    $entries = \App\Support\Adnd2e::normalizeClassLevels(null, $class, (int) ($row->level ?? 1), $path);
                    $class = \App\Support\Adnd2e::displayClassName($entries, $path);
                } elseif ($rewrite['mapped'] || $rewrite['rejected']) {
                    $class = $rewrite['class'];
                    $entries = [['class' => $class, 'level' => max(1, (int) ($row->level ?? 1))]];
                }

                $update = ['class' => $class];
                if (Schema::hasColumn('characters', 'class_path')) {
                    $update['class_path'] = $path;
                }
                if ($entries !== null && Schema::hasColumn('characters', 'class_levels')) {
                    $update['class_levels'] = json_encode($entries);
                }
                if ($rewrite['rejected'] || $rewrite['mapped']) {
                    $update['imported_data'] = json_encode(['legacy_class' => $row->class]);
                }
                DB::table('characters')->where('id', $row->id)->update($update);
            }
        }

        // SQLite stores integers without unsigned enforcement; leave campaign_id
        // as-is if the original create made it required. New installs are nullable.
        if (Schema::getConnection()->getDriverName() !== 'sqlite' && Schema::hasColumn('characters', 'campaign_id')) {
            try {
                DB::statement('ALTER TABLE characters MODIFY campaign_id BIGINT UNSIGNED NULL');
            } catch (\Throwable) {
                // Ignore if already nullable or the engine forbids it.
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('characters')) {
            return;
        }

        $added = ['thac0', 'exceptional_strength', 'hit_die', 'saving_throws', 'weapon_proficiencies', 'nonweapon_proficiencies', 'priest_spheres'];
        $toDrop = array_values(array_filter($added, fn (string $column) => Schema::hasColumn('characters', $column)));
        if ($toDrop !== []) {
            Schema::table('characters', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn($toDrop);
            });
        }

        if (Schema::hasTable('character_spells') && Schema::hasColumn('character_spells', 'is_cast')) {
            Schema::table('character_spells', function (Blueprint $table) {
                $table->dropColumn('is_cast');
            });
        }
    }
};
