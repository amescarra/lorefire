<?php

namespace App\Support;

/**
 * AD&D 2nd Edition mechanical helpers.
 *
 * Tables encode published game mechanics (class groups, THAC0, saves,
 * ability adjustments, memorization counts). Wording is original — do not
 * paste rulebook prose here.
 */
class Adnd2e
{
    public const EDITION = 'adnd-2e';

    public const RACES = [
        'Human',
        'Dwarf',
        'Elf',
        'Gnome',
        'Half-Elf',
        'Halfling',
        'Half-Orc',
        'Other',
    ];

    public const CLASSES = [
        'Fighter',
        'Paladin',
        'Ranger',
        'Mage',
        'Cleric',
        'Druid',
        'Thief',
        'Bard',
    ];

    public const SPECIALIST_SCHOOLS = [
        'Abjurer',
        'Conjurer',
        'Diviner',
        'Enchanter',
        'Illusionist',
        'Invoker',
        'Necromancer',
        'Transmuter',
    ];

    public const ALIGNMENTS = [
        'Lawful Good',
        'Neutral Good',
        'Chaotic Good',
        'Lawful Neutral',
        'True Neutral',
        'Chaotic Neutral',
        'Lawful Evil',
        'Neutral Evil',
        'Chaotic Evil',
    ];

    public const SAVE_CATEGORIES = [
        'paralyzation' => 'Paralyzation, Poison, or Death Magic',
        'rod' => 'Rod, Staff, or Wand',
        'petrification' => 'Petrification or Polymorph',
        'breath' => 'Breath Weapon',
        'spell' => 'Spell',
    ];

    public const PRIEST_SPHERES = [
        'All',
        'Animal',
        'Astral',
        'Charm',
        'Combat',
        'Creation',
        'Divination',
        'Elemental',
        'Guardian',
        'Healing',
        'Necromantic',
        'Plant',
        'Protection',
        'Summoning',
        'Sun',
        'Weather',
    ];

    public const WEAPON_PROFICIENCY_SUGGESTIONS = [
        'Long sword',
        'Short sword',
        'Bastard sword',
        'Two-handed sword',
        'Battle axe',
        'Hand axe',
        'Dagger',
        'Spear',
        'Halberd',
        'Morning star',
        'Mace',
        'Warhammer',
        'Club',
        'Quarterstaff',
        'Long bow',
        'Short bow',
        'Crossbow, light',
        'Crossbow, heavy',
        'Sling',
        'Dart',
        'Javelin',
        'Lance',
        'Flail',
    ];

    public const NONWEAPON_PROFICIENCY_SUGGESTIONS = [
        'Agriculture',
        'Animal Handling',
        'Animal Lore',
        'Animal Training',
        'Artistic Ability',
        'Astrology',
        'Blacksmithing',
        'Blind-fighting',
        'Brewing',
        'Carpentry',
        'Cooking',
        'Dancing',
        'Direction Sense',
        'Endurance',
        'Engineering',
        'Etiquette',
        'Fire-building',
        'Fishing',
        'Healing',
        'Heraldry',
        'Herbalism',
        'Hunting',
        'Jumping',
        'Languages, Ancient',
        'Languages, Modern',
        'Leatherworking',
        'Local History',
        'Mining',
        'Mountaineering',
        'Musical Instrument',
        'Navigation',
        'Pottery',
        'Reading/Writing',
        'Religion',
        'Riding, Land-based',
        'Rope Use',
        'Running',
        'Seamanship',
        'Set Snares',
        'Singing',
        'Spellcraft',
        'Stonemasonry',
        'Survival',
        'Swimming',
        'Tracking',
        'Weather Sense',
        'Weaving',
    ];

    public const CONDITIONS = [
        'Blinded',
        'Charmed',
        'Confused',
        'Cursed',
        'Diseased',
        'Feebleminded',
        'Held',
        'Invisible',
        'Paralyzed',
        'Petrified',
        'Poisoned',
        'Silenced',
        'Sleeping',
        'Slowed',
        'Hasted',
        'Unconscious',
        'Dying',
        'Dead',
        'Fear',
        'Berserk',
    ];

    public const DEATH_THRESHOLD = -10;

    public static function classGroup(string $class): string
    {
        $class = self::normalizeClass($class);

        return match ($class) {
            'Fighter', 'Paladin', 'Ranger' => 'warrior',
            'Cleric', 'Druid' => 'priest',
            'Thief', 'Bard' => 'rogue',
            default => 'wizard',
        };
    }

    public static function normalizeClass(string $class): string
    {
        $class = trim($class);

        if (in_array($class, self::SPECIALIST_SCHOOLS, true) || $class === 'Wizard' || $class === 'Illusionist') {
            return 'Mage';
        }

        return match ($class) {
            'Rogue' => 'Thief',
            'Priest' => 'Cleric',
            default => $class,
        };
    }

    public static function isSpecialist(string $class, ?string $subclass = null): bool
    {
        if (in_array($class, self::SPECIALIST_SCHOOLS, true)) {
            return true;
        }

        return $subclass !== null && in_array($subclass, self::SPECIALIST_SCHOOLS, true);
    }

    public static function specialistSchool(string $class, ?string $subclass = null): ?string
    {
        if (in_array($class, self::SPECIALIST_SCHOOLS, true)) {
            return $class;
        }

        if ($subclass !== null && in_array($subclass, self::SPECIALIST_SCHOOLS, true)) {
            return $subclass;
        }

        return null;
    }

    public static function isWizard(string $class): bool
    {
        return self::classGroup($class) === 'wizard';
    }

    public static function isPriest(string $class): bool
    {
        return self::classGroup($class) === 'priest';
    }

    public static function hitDie(string $class): string
    {
        return match (self::classGroup($class)) {
            'warrior' => 'd10',
            'priest' => 'd8',
            'rogue' => 'd6',
            default => 'd4',
        };
    }

    public static function movementRate(string $race): int
    {
        return match ($race) {
            'Dwarf', 'Gnome', 'Halfling' => 6,
            default => 12,
        };
    }

    /**
     * Base THAC0 for class and level (lower is better).
     */
    public static function thac0(string $class, int $level): int
    {
        $level = max(1, min(20, $level));
        $group = self::classGroup($class);

        return match ($group) {
            'warrior' => 21 - $level,
            'priest' => match (true) {
                $level <= 3 => 20,
                $level <= 6 => 18,
                $level <= 9 => 16,
                $level <= 12 => 14,
                $level <= 15 => 12,
                $level <= 18 => 10,
                default => 8,
            },
            'rogue' => match (true) {
                $level <= 4 => 20,
                $level <= 8 => 19,
                $level <= 12 => 16,
                $level <= 16 => 14,
                default => 12,
            },
            default => match (true) {
                $level <= 5 => 20,
                $level <= 10 => 19,
                $level <= 15 => 16,
                default => 14,
            },
        };
    }

    /**
     * Five 2E saving-throw targets (roll this number or higher on d20).
     *
     * @return array{paralyzation: int, rod: int, petrification: int, breath: int, spell: int}
     */
    public static function savingThrows(string $class, int $level): array
    {
        $level = max(1, min(20, $level));
        $group = self::classGroup($class);

        $row = match ($group) {
            'warrior' => match (true) {
                $level <= 2 => [14, 16, 15, 17, 17],
                $level <= 4 => [13, 15, 14, 16, 16],
                $level <= 6 => [11, 13, 12, 13, 14],
                $level <= 8 => [10, 12, 11, 12, 13],
                $level <= 10 => [8, 10, 9, 9, 11],
                $level <= 12 => [7, 9, 8, 8, 10],
                $level <= 14 => [5, 7, 6, 5, 8],
                $level <= 16 => [4, 6, 5, 4, 7],
                default => [3, 5, 4, 4, 6],
            },
            'priest' => match (true) {
                $level <= 3 => [10, 14, 13, 16, 15],
                $level <= 6 => [9, 13, 12, 15, 14],
                $level <= 9 => [7, 11, 10, 13, 12],
                $level <= 12 => [6, 10, 9, 12, 11],
                $level <= 15 => [5, 9, 8, 11, 10],
                $level <= 18 => [4, 8, 7, 10, 9],
                default => [2, 6, 5, 8, 7],
            },
            'rogue' => match (true) {
                $level <= 4 => [13, 14, 12, 16, 15],
                $level <= 8 => [12, 12, 11, 15, 13],
                $level <= 12 => [11, 10, 10, 14, 11],
                $level <= 16 => [10, 8, 9, 13, 9],
                default => [9, 6, 8, 12, 7],
            },
            default => match (true) {
                $level <= 5 => [14, 11, 13, 15, 12],
                $level <= 10 => [13, 9, 11, 13, 10],
                $level <= 15 => [11, 7, 9, 11, 8],
                default => [10, 5, 7, 9, 6],
            },
        };

        return [
            'paralyzation' => $row[0],
            'rod' => $row[1],
            'petrification' => $row[2],
            'breath' => $row[3],
            'spell' => $row[4],
        ];
    }

    /**
     * Strength hit / damage adjustments, including exceptional strength.
     *
     * @return array{hit: int, damage: int}
     */
    public static function strengthAdjustments(int $score, ?string $exceptional = null): array
    {
        if ($score <= 1) {
            return ['hit' => -5, 'damage' => -4];
        }
        if ($score === 2) {
            return ['hit' => -3, 'damage' => -2];
        }
        if ($score === 3) {
            return ['hit' => -3, 'damage' => -1];
        }
        if ($score <= 5) {
            return ['hit' => -2, 'damage' => -1];
        }
        if ($score <= 7) {
            return ['hit' => -1, 'damage' => 0];
        }
        if ($score <= 15) {
            return ['hit' => 0, 'damage' => 0];
        }
        if ($score === 16) {
            return ['hit' => 0, 'damage' => 1];
        }
        if ($score === 17) {
            return ['hit' => 1, 'damage' => 1];
        }
        if ($score === 18) {
            $exc = self::normalizeExceptional($exceptional);
            if ($exc === null) {
                return ['hit' => 1, 'damage' => 2];
            }
            if ($exc <= 50) {
                return ['hit' => 1, 'damage' => 3];
            }
            if ($exc <= 75) {
                return ['hit' => 2, 'damage' => 3];
            }
            if ($exc <= 90) {
                return ['hit' => 2, 'damage' => 4];
            }
            if ($exc <= 99) {
                return ['hit' => 3, 'damage' => 5];
            }

            return ['hit' => 3, 'damage' => 6];
        }
        if ($score === 19) {
            return ['hit' => 3, 'damage' => 7];
        }
        if ($score === 20) {
            return ['hit' => 3, 'damage' => 8];
        }

        return ['hit' => 4, 'damage' => 9];
    }

    /**
     * Dexterity reaction / missile / defensive (AC) adjustments.
     * Defensive is applied to descending AC (negative is better).
     *
     * @return array{reaction: int, missile: int, defensive: int}
     */
    public static function dexterityAdjustments(int $score): array
    {
        return match (true) {
            $score <= 1 => ['reaction' => -6, 'missile' => -6, 'defensive' => 5],
            $score === 2 => ['reaction' => -4, 'missile' => -4, 'defensive' => 5],
            $score === 3 => ['reaction' => -3, 'missile' => -3, 'defensive' => 4],
            $score <= 5 => ['reaction' => -2, 'missile' => -2, 'defensive' => 3],
            $score === 6 => ['reaction' => -1, 'missile' => -1, 'defensive' => 2],
            $score <= 14 => ['reaction' => 0, 'missile' => 0, 'defensive' => 0],
            $score === 15 => ['reaction' => 0, 'missile' => 0, 'defensive' => -1],
            $score === 16 => ['reaction' => 1, 'missile' => 1, 'defensive' => -2],
            $score === 17 => ['reaction' => 2, 'missile' => 2, 'defensive' => -3],
            $score === 18 => ['reaction' => 2, 'missile' => 2, 'defensive' => -4],
            $score === 19 => ['reaction' => 3, 'missile' => 3, 'defensive' => -4],
            default => ['reaction' => 3, 'missile' => 3, 'defensive' => -5],
        };
    }

    /**
     * Constitution hit-point adjustment. Warriors use the higher column at 17+.
     */
    public static function constitutionHpAdjustment(int $score, string $class = 'Fighter'): int
    {
        $warrior = self::classGroup($class) === 'warrior';

        return match (true) {
            $score <= 1 => -3,
            $score === 2, $score === 3 => -2,
            $score <= 6 => -1,
            $score <= 14 => 0,
            $score === 15 => 1,
            $score === 16 => 2,
            $score === 17 => $warrior ? 3 : 2,
            $score === 18 => $warrior ? 4 : 2,
            $score === 19 => $warrior ? 5 : 2,
            default => $warrior ? 5 : 2,
        };
    }

    /**
     * Wisdom magical-defense adjustment (applied to mental/spell saves).
     */
    public static function wisdomMagicalDefense(int $score): int
    {
        return match (true) {
            $score <= 1 => -6,
            $score === 2 => -4,
            $score === 3 => -3,
            $score <= 5 => -2,
            $score <= 7 => -1,
            $score <= 14 => 0,
            $score === 15 => 1,
            $score === 16 => 2,
            $score === 17 => 3,
            default => 4,
        };
    }

    /**
     * Bonus priest spells by wisdom, keyed by spell level.
     *
     * @return array<int, int>
     */
    public static function wisdomBonusSpells(int $score): array
    {
        return match (true) {
            $score <= 12 => [],
            $score === 13 => [1 => 1],
            $score === 14 => [1 => 2],
            $score === 15 => [1 => 2, 2 => 1],
            $score === 16 => [1 => 2, 2 => 2],
            $score === 17 => [1 => 2, 2 => 2, 3 => 1],
            default => [1 => 2, 2 => 2, 3 => 1, 4 => 1],
        };
    }

    /**
     * Charisma reaction / henchmen / loyalty adjustments.
     *
     * @return array{max_henchmen: int, loyalty: int, reaction: int}
     */
    public static function charismaAdjustments(int $score): array
    {
        return match (true) {
            $score <= 2 => ['max_henchmen' => 1, 'loyalty' => -8, 'reaction' => -7],
            $score === 3 => ['max_henchmen' => 1, 'loyalty' => -6, 'reaction' => -5],
            $score <= 5 => ['max_henchmen' => 2, 'loyalty' => -4, 'reaction' => -3],
            $score <= 7 => ['max_henchmen' => 3, 'loyalty' => -2, 'reaction' => -1],
            $score <= 11 => ['max_henchmen' => 4, 'loyalty' => 0, 'reaction' => 0],
            $score === 12 => ['max_henchmen' => 5, 'loyalty' => 0, 'reaction' => 0],
            $score === 13 => ['max_henchmen' => 5, 'loyalty' => 0, 'reaction' => 1],
            $score === 14 => ['max_henchmen' => 6, 'loyalty' => 1, 'reaction' => 2],
            $score === 15 => ['max_henchmen' => 7, 'loyalty' => 3, 'reaction' => 3],
            $score === 16 => ['max_henchmen' => 8, 'loyalty' => 4, 'reaction' => 5],
            $score === 17 => ['max_henchmen' => 10, 'loyalty' => 6, 'reaction' => 6],
            default => ['max_henchmen' => 15, 'loyalty' => 8, 'reaction' => 7],
        };
    }

    /**
     * Intelligence: languages and max wizard spell level.
     *
     * @return array{languages: int, max_spell_level: int|null}
     */
    public static function intelligenceLimits(int $score): array
    {
        return match (true) {
            $score <= 8 => ['languages' => 1, 'max_spell_level' => null],
            $score === 9 => ['languages' => 2, 'max_spell_level' => 4],
            $score === 10, $score === 11 => ['languages' => 2, 'max_spell_level' => 5],
            $score === 12, $score === 13 => ['languages' => 3, 'max_spell_level' => 6],
            $score === 14, $score === 15 => ['languages' => 4, 'max_spell_level' => 7],
            $score === 16, $score === 17 => ['languages' => 5, 'max_spell_level' => 8],
            default => ['languages' => 7, 'max_spell_level' => 9],
        };
    }

    /**
     * Compact signed label for a primary combat-facing adjustment.
     */
    public static function primaryAdjustment(string $ability, int $score, ?string $exceptional = null, string $class = 'Fighter'): int
    {
        return match ($ability) {
            'strength' => self::strengthAdjustments($score, $exceptional)['hit'],
            'dexterity' => self::dexterityAdjustments($score)['missile'],
            'constitution' => self::constitutionHpAdjustment($score, $class),
            'intelligence' => self::intelligenceLimits($score)['languages'] - 2,
            'wisdom' => self::wisdomMagicalDefense($score),
            'charisma' => self::charismaAdjustments($score)['reaction'],
            default => 0,
        };
    }

    /**
     * Memorization capacity by class/level. Keys are spell levels 1–9.
     *
     * @return array<int, int>
     */
    public static function memorizationCapacity(string $class, int $level, int $wisdom = 10, ?string $subclass = null): array
    {
        $class = self::normalizeClass($class);
        $level = max(1, min(20, $level));
        $base = [];

        if (self::isWizard($class) || in_array($class, self::SPECIALIST_SCHOOLS, true)) {
            $base = self::wizardCapacity($level);
            if (self::isSpecialist($class, $subclass)) {
                foreach ($base as $spellLevel => $count) {
                    if ($count > 0) {
                        $base[$spellLevel] = $count + 1;
                    }
                }
            }
        } elseif ($class === 'Cleric' || $class === 'Druid') {
            $base = self::priestCapacity($level);
            $base = self::mergeCapacity($base, self::wisdomBonusSpells($wisdom));
        } elseif ($class === 'Paladin' && $level >= 9) {
            $base = self::paladinCapacity($level);
            $base = self::mergeCapacity($base, self::wisdomBonusSpells($wisdom));
        } elseif ($class === 'Ranger' && $level >= 8) {
            $base = self::rangerPriestCapacity($level);
            $base = self::mergeCapacity($base, self::wisdomBonusSpells($wisdom));
        } elseif ($class === 'Bard') {
            $base = self::bardCapacity($level);
        }

        return array_filter($base, fn (int $n) => $n > 0);
    }

    /**
     * Number needed on d20 to hit descending AC with the given THAC0.
     */
    public static function numberNeededToHit(int $thac0, int $armorClass): int
    {
        return $thac0 - $armorClass;
    }

    /**
     * Resolve a d20 attack vs descending AC.
     *
     * Convention used by this app: 1 always misses, 20 always hits.
     *
     * @return array{hit: bool, needed: int, roll: int, automatic: bool}
     */
    public static function resolveAttack(int $thac0, int $armorClass, int $roll): array
    {
        $needed = self::numberNeededToHit($thac0, $armorClass);
        $automatic = $roll === 1 || $roll === 20;
        $hit = $roll === 20 || ($roll !== 1 && $roll >= $needed);

        return [
            'hit' => $hit,
            'needed' => $needed,
            'roll' => $roll,
            'automatic' => $automatic,
        ];
    }

    /**
     * 2E surprise / combat initiative: d10, lower is better.
     * Dexterity reaction adjustment is subtracted (a bonus makes you act sooner).
     *
     * @return array{roll: int, modifier: int, total: int}
     */
    public static function resolveInitiative(int $d10, int $dexterity, int $otherModifiers = 0): array
    {
        $reaction = self::dexterityAdjustments($dexterity)['reaction'];
        $total = $d10 - $reaction + $otherModifiers;

        return [
            'roll' => $d10,
            'modifier' => -$reaction + $otherModifiers,
            'total' => $total,
        ];
    }

    public static function vitalityState(int $currentHp): string
    {
        if ($currentHp <= self::DEATH_THRESHOLD) {
            return 'dead';
        }
        if ($currentHp < 0) {
            return 'dying';
        }
        if ($currentHp === 0) {
            return 'unconscious';
        }

        return 'ok';
    }

    /**
     * Overnight rest: recover 1 hit point (natural healing) and rememorize.
     *
     * @param  array<string, mixed>  $classFeatures
     * @return array{current_hp: int, spell_slots_used: null, class_features: array<string, mixed>}
     */
    public static function overnightRest(int $currentHp, int $maxHp, string $class, int $level, array $classFeatures = []): array
    {
        $healed = min($maxHp, max(self::DEATH_THRESHOLD, $currentHp) + 1);
        if ($currentHp > 0) {
            $healed = min($maxHp, $currentHp + 1);
        }

        $cf = $classFeatures;
        $normalized = self::normalizeClass($class);

        if ($normalized === 'Paladin') {
            $max = $cf['lay_on_hands_max'] ?? ($level * 2);
            $cf['lay_on_hands_max'] = $max;
            $cf['lay_on_hands_current'] = $max;
            $cf['detect_evil_ready'] = true;
        }

        if ($normalized === 'Cleric' || $normalized === 'Paladin') {
            $cf['turn_undead_ready'] = true;
        }

        if ($normalized === 'Ranger') {
            $cf['tracking_ready'] = true;
        }

        if ($normalized === 'Bard') {
            $cf['influence_ready'] = true;
            $cf['legend_lore_ready'] = true;
        }

        return [
            'current_hp' => $healed,
            'spell_slots_used' => null,
            'class_features' => $cf,
        ];
    }

    /**
     * Default field bag when creating a character.
     *
     * @return array<string, mixed>
     */
    public static function defaultsFor(
        string $class,
        int $level,
        string $race,
        int $wisdom = 10,
        ?string $subclass = null,
    ): array {
        $capacity = self::memorizationCapacity($class, $level, $wisdom, $subclass);
        $slots = [];
        foreach ($capacity as $spellLevel => $count) {
            $slots[(string) $spellLevel] = $count;
        }

        $spheres = null;
        if (self::normalizeClass($class) === 'Cleric') {
            $spheres = ['major' => ['All', 'Healing'], 'minor' => ['Divination']];
        } elseif (self::normalizeClass($class) === 'Druid') {
            $spheres = ['major' => ['All', 'Animal', 'Elemental', 'Healing', 'Plant', 'Weather'], 'minor' => []];
        }

        return [
            'thac0' => self::thac0($class, $level),
            'speed' => self::movementRate($race),
            'hit_die' => self::hitDie($class),
            'armor_class' => 10,
            'saving_throws' => self::savingThrows($class, $level),
            'spell_slots' => $slots === [] ? null : $slots,
            'spell_slots_used' => null,
            'priest_spheres' => $spheres,
            'spellcasting_ability' => self::isWizard($class) || self::normalizeClass($class) === 'Bard'
                ? 'intelligence'
                : (self::isPriest($class) || in_array(self::normalizeClass($class), ['Paladin', 'Ranger'], true)
                    ? 'wisdom'
                    : null),
        ];
    }

    /**
     * @return array{initial: int, gain_every: int}
     */
    public static function weaponProficiencySlots(string $class): array
    {
        return match (self::classGroup($class)) {
            'warrior' => ['initial' => 4, 'gain_every' => 3],
            'wizard' => ['initial' => 1, 'gain_every' => 6],
            default => ['initial' => 2, 'gain_every' => 4],
        };
    }

    /**
     * @return array{initial: int, gain_every: int}
     */
    public static function nonweaponProficiencySlots(string $class): array
    {
        return match (self::classGroup($class)) {
            'warrior' => ['initial' => 3, 'gain_every' => 3],
            'wizard' => ['initial' => 4, 'gain_every' => 3],
            'priest' => ['initial' => 4, 'gain_every' => 3],
            default => ['initial' => 3, 'gain_every' => 4],
        };
    }

    public static function formatSigned(int $n): string
    {
        return $n >= 0 ? '+'.$n : (string) $n;
    }

    private static function normalizeExceptional(?string $exceptional): ?int
    {
        if ($exceptional === null || $exceptional === '') {
            return null;
        }
        $exceptional = strtoupper(trim($exceptional));
        if ($exceptional === '00' || $exceptional === '100') {
            return 100;
        }
        if (! preg_match('/^\d{1,3}$/', $exceptional)) {
            return null;
        }

        return (int) $exceptional;
    }

    /**
     * @return array<int, int>
     */
    private static function wizardCapacity(int $level): array
    {
        return match ($level) {
            1 => [1 => 1],
            2 => [1 => 2],
            3 => [1 => 2, 2 => 1],
            4 => [1 => 3, 2 => 2],
            5 => [1 => 4, 2 => 2, 3 => 1],
            6 => [1 => 4, 2 => 2, 3 => 2],
            7 => [1 => 4, 3 => 2, 2 => 3, 4 => 1],
            8 => [1 => 4, 2 => 3, 3 => 3, 4 => 2],
            9 => [1 => 4, 2 => 3, 3 => 3, 4 => 2, 5 => 1],
            10 => [1 => 4, 2 => 4, 3 => 3, 4 => 2, 5 => 2],
            11 => [1 => 4, 2 => 4, 3 => 4, 4 => 3, 5 => 3],
            12 => [1 => 4, 2 => 4, 3 => 4, 4 => 4, 5 => 4, 6 => 1],
            13 => [1 => 5, 2 => 5, 3 => 5, 4 => 4, 5 => 4, 6 => 2],
            14 => [1 => 5, 2 => 5, 3 => 5, 4 => 4, 5 => 4, 6 => 2, 7 => 1],
            15 => [1 => 5, 2 => 5, 3 => 5, 4 => 5, 5 => 5, 6 => 2, 7 => 1],
            16 => [1 => 5, 2 => 5, 3 => 5, 4 => 5, 5 => 5, 6 => 3, 7 => 2, 8 => 1],
            17 => [1 => 5, 2 => 5, 3 => 5, 4 => 5, 5 => 5, 6 => 3, 7 => 3, 8 => 2],
            18 => [1 => 5, 2 => 5, 3 => 5, 4 => 5, 5 => 5, 6 => 3, 7 => 3, 8 => 2, 9 => 1],
            19 => [1 => 5, 2 => 5, 3 => 5, 4 => 5, 5 => 5, 6 => 3, 7 => 3, 8 => 3, 9 => 1],
            default => [1 => 5, 2 => 5, 3 => 5, 4 => 5, 5 => 5, 6 => 4, 7 => 3, 8 => 3, 9 => 2],
        };
    }

    /**
     * @return array<int, int>
     */
    private static function priestCapacity(int $level): array
    {
        return match ($level) {
            1 => [1 => 1],
            2 => [1 => 2],
            3 => [1 => 2, 2 => 1],
            4 => [1 => 3, 2 => 2],
            5 => [1 => 3, 2 => 3, 3 => 1],
            6 => [1 => 3, 2 => 3, 3 => 2],
            7 => [1 => 3, 2 => 3, 3 => 2, 4 => 1],
            8 => [1 => 3, 2 => 3, 3 => 3, 4 => 2],
            9 => [1 => 4, 2 => 4, 3 => 3, 4 => 2, 5 => 1],
            10 => [1 => 4, 2 => 4, 3 => 3, 4 => 3, 5 => 2],
            11 => [1 => 5, 2 => 4, 3 => 4, 4 => 3, 5 => 2, 6 => 1],
            12 => [1 => 6, 2 => 5, 3 => 5, 4 => 3, 5 => 2, 6 => 2],
            13 => [1 => 6, 2 => 6, 3 => 6, 4 => 4, 5 => 2, 6 => 2],
            14 => [1 => 6, 2 => 6, 3 => 6, 4 => 5, 5 => 3, 6 => 2, 7 => 1],
            15 => [1 => 6, 2 => 6, 3 => 6, 4 => 6, 5 => 4, 6 => 2, 7 => 1],
            16 => [1 => 7, 2 => 7, 3 => 7, 4 => 6, 5 => 4, 6 => 3, 7 => 1],
            17 => [1 => 7, 2 => 7, 3 => 7, 4 => 7, 5 => 5, 6 => 3, 7 => 2],
            18 => [1 => 8, 2 => 8, 3 => 8, 4 => 8, 5 => 6, 6 => 4, 7 => 2],
            19 => [1 => 9, 2 => 9, 3 => 8, 4 => 8, 5 => 6, 6 => 4, 7 => 2],
            default => [1 => 9, 2 => 9, 3 => 9, 4 => 8, 5 => 7, 6 => 5, 7 => 2],
        };
    }

    /**
     * Paladin priest spells begin at 9th level.
     *
     * @return array<int, int>
     */
    private static function paladinCapacity(int $level): array
    {
        $caster = max(1, $level - 8);

        return match (true) {
            $caster === 1 => [1 => 1],
            $caster === 2 => [1 => 2],
            $caster === 3 => [1 => 2, 2 => 1],
            $caster === 4 => [1 => 2, 2 => 2],
            $caster === 5 => [1 => 2, 2 => 2, 3 => 1],
            $caster === 6 => [1 => 3, 2 => 2, 3 => 1],
            $caster === 7 => [1 => 3, 2 => 2, 3 => 1, 4 => 1],
            $caster === 8 => [1 => 3, 2 => 3, 3 => 2, 4 => 1],
            $caster === 9 => [1 => 3, 2 => 3, 3 => 3, 4 => 2],
            $caster === 10 => [1 => 3, 2 => 3, 3 => 3, 4 => 2],
            default => [1 => 3, 2 => 3, 3 => 3, 4 => 3],
        };
    }

    /**
     * Ranger priest spells begin at 8th level.
     *
     * @return array<int, int>
     */
    private static function rangerPriestCapacity(int $level): array
    {
        $caster = max(1, $level - 7);

        return match (true) {
            $caster === 1 => [1 => 1],
            $caster === 2 => [1 => 2],
            $caster === 3 => [1 => 2, 2 => 1],
            $caster === 4 => [1 => 2, 2 => 2],
            $caster === 5 => [1 => 2, 2 => 2, 3 => 1],
            default => [1 => 3, 2 => 3, 3 => 1],
        };
    }

    /**
     * @return array<int, int>
     */
    private static function bardCapacity(int $level): array
    {
        return match ($level) {
            1 => [],
            2 => [1 => 1],
            3 => [1 => 2],
            4 => [1 => 2, 2 => 1],
            5 => [1 => 3, 2 => 1],
            6 => [1 => 3, 2 => 2],
            7 => [1 => 3, 2 => 2, 3 => 1],
            8 => [1 => 3, 2 => 3, 3 => 1],
            9 => [1 => 3, 2 => 3, 3 => 2],
            10 => [1 => 3, 2 => 3, 3 => 2, 4 => 1],
            11 => [1 => 3, 2 => 3, 3 => 3, 4 => 1],
            12 => [1 => 3, 2 => 3, 3 => 3, 4 => 2],
            13 => [1 => 3, 2 => 3, 3 => 3, 4 => 2, 5 => 1],
            14 => [1 => 3, 2 => 3, 3 => 3, 4 => 3, 5 => 1],
            15 => [1 => 3, 2 => 3, 3 => 3, 4 => 3, 5 => 2],
            16 => [1 => 4, 2 => 3, 3 => 3, 4 => 3, 5 => 2, 6 => 1],
            default => [1 => 4, 2 => 4, 3 => 3, 4 => 3, 5 => 3, 6 => 2],
        };
    }

    /**
     * @param  array<int, int>  $base
     * @param  array<int, int>  $bonus
     * @return array<int, int>
     */
    private static function mergeCapacity(array $base, array $bonus): array
    {
        foreach ($bonus as $level => $count) {
            if (! isset($base[$level]) || $base[$level] === 0) {
                continue;
            }
            $base[$level] += $count;
        }

        return $base;
    }
}
