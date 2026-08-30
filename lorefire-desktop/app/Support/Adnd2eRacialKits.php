<?php

namespace App\Support;

/**
 * AD&D 2E racial-handbook kit *names* and thin eligibility only.
 *
 * Sources (names + class/race tags from public kit indexes):
 * Complete Book of Elves, Complete Book of Dwarves,
 * Complete Book of Gnomes and Halflings, Complete Book of Humanoids.
 *
 * No class-handbook kits. No benefit tables, spell lists, or handbook prose.
 *
 * @phpstan-type RacialKit array{name: string, races: list<string>, classes: list<string>, match?: 'all'|'any'}
 */
class Adnd2eRacialKits
{
    public const HUMANOID_RACES = ['Half-Orc', 'Other'];

    /**
     * @var list<RacialKit>
     */
    public const KITS = [
        // Complete Book of Elves
        ['name' => 'Herbalist', 'races' => ['Elf'], 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Archer', 'races' => ['Elf'], 'classes' => ['Fighter', 'Ranger'], 'match' => 'any'],
        ['name' => 'Wilderness Runner', 'races' => ['Elf'], 'classes' => ['Fighter', 'Ranger'], 'match' => 'any'],
        ['name' => 'Windrider', 'races' => ['Elf'], 'classes' => ['Fighter', 'Ranger'], 'match' => 'any'],
        ['name' => 'Elven Minstrel', 'races' => ['Elf'], 'classes' => ['Mage', 'Thief'], 'match' => 'all'],
        ['name' => 'Spellfilcher', 'races' => ['Elf'], 'classes' => ['Mage', 'Thief'], 'match' => 'all'],
        ['name' => 'Bladesinger', 'races' => ['Elf'], 'classes' => ['Fighter', 'Mage'], 'match' => 'all'],
        ['name' => 'War Wizard', 'races' => ['Elf'], 'classes' => ['Fighter', 'Mage'], 'match' => 'all'],
        ['name' => 'Huntsman', 'races' => ['Elf'], 'classes' => ['Fighter', 'Thief'], 'match' => 'all'],
        ['name' => 'Collector', 'races' => ['Elf'], 'classes' => ['Fighter', 'Mage', 'Thief'], 'match' => 'all'],
        ['name' => 'Infiltrator', 'races' => ['Elf'], 'classes' => ['Fighter', 'Mage', 'Thief'], 'match' => 'all'],
        ['name' => 'Undead Slayer', 'races' => ['Elf'], 'classes' => [], 'match' => 'any'],

        // Complete Book of Dwarves
        ['name' => 'Animal Master', 'races' => ['Dwarf'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Axe for Hire', 'races' => ['Dwarf'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Battlerager', 'races' => ['Dwarf'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Clansdwarf', 'races' => ['Dwarf'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Hearth Guard', 'races' => ['Dwarf'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Highborn', 'races' => ['Dwarf'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Outcast', 'races' => ['Dwarf'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Rapid Response Rider', 'races' => ['Dwarf'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Sharpshooter', 'races' => ['Dwarf'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Crafts Priest', 'races' => ['Dwarf'], 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Pariah', 'races' => ['Dwarf'], 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Patrician', 'races' => ['Dwarf'], 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Ritual Priest', 'races' => ['Dwarf'], 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Diplomat', 'races' => ['Dwarf'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Entertainer', 'races' => ['Dwarf'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Locksmith', 'races' => ['Dwarf'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Pest Controller', 'races' => ['Dwarf'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Champion', 'races' => ['Dwarf'], 'classes' => ['Fighter', 'Cleric'], 'match' => 'all'],
        ['name' => 'Temple Guard', 'races' => ['Dwarf'], 'classes' => ['Fighter', 'Cleric'], 'match' => 'all'],
        ['name' => 'Vindicator', 'races' => ['Dwarf'], 'classes' => ['Fighter', 'Cleric'], 'match' => 'all'],
        ['name' => 'Ghetto Fighter', 'races' => ['Dwarf'], 'classes' => ['Fighter', 'Thief'], 'match' => 'all'],
        ['name' => 'Trader', 'races' => ['Dwarf'], 'classes' => ['Fighter', 'Thief'], 'match' => 'all'],
        ['name' => 'Vermin Slayer', 'races' => ['Dwarf'], 'classes' => ['Fighter', 'Thief'], 'match' => 'all'],
        ['name' => 'Wayfinder', 'races' => ['Dwarf'], 'classes' => ['Fighter', 'Thief'], 'match' => 'all'],

        // Complete Book of Gnomes and Halflings — gnomes
        ['name' => 'Breachgnome', 'races' => ['Gnome'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Goblinsticker', 'races' => ['Gnome'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Mouseburglar', 'races' => ['Gnome'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Tumbler', 'races' => ['Gnome'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Imagemaker', 'races' => ['Gnome'], 'classes' => ['Mage'], 'match' => 'any'],
        ['name' => 'Vanisher', 'races' => ['Gnome'], 'classes' => ['Mage'], 'match' => 'any'],
        ['name' => 'Buffoon', 'races' => ['Gnome'], 'classes' => ['Mage', 'Thief'], 'match' => 'all'],
        ['name' => 'Stalker', 'races' => ['Gnome'], 'classes' => ['Fighter', 'Thief'], 'match' => 'all'],
        ['name' => 'Rocktender', 'races' => ['Gnome'], 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Treetender', 'races' => ['Gnome'], 'classes' => ['Cleric'], 'match' => 'any'],

        // Complete Book of Gnomes and Halflings — halflings
        ['name' => 'Archer', 'races' => ['Halfling'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Forestwalker', 'races' => ['Halfling'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Homesteader', 'races' => ['Halfling'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Mercenary', 'races' => ['Halfling'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Sheriff', 'races' => ['Halfling'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Squire', 'races' => ['Halfling'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Tunnelrat', 'races' => ['Halfling'], 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Bandit', 'races' => ['Halfling'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Bilker', 'races' => ['Halfling'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Burglar', 'races' => ['Halfling'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Smuggler', 'races' => ['Halfling'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Urchin', 'races' => ['Halfling'], 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Healer', 'races' => ['Halfling'], 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Leaftender', 'races' => ['Halfling'], 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Oracle', 'races' => ['Halfling'], 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Cartographer', 'races' => ['Halfling'], 'classes' => ['Fighter', 'Thief'], 'match' => 'all'],
        ['name' => 'Trader', 'races' => ['Halfling'], 'classes' => ['Fighter', 'Thief'], 'match' => 'all'],
        ['name' => 'Traveler', 'races' => ['Halfling'], 'classes' => ['Fighter', 'Thief'], 'match' => 'all'],

        // Complete Book of Humanoids (common kit names; Half-Orc / Other)
        ['name' => 'Tribal Defender', 'races' => self::HUMANOID_RACES, 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Mine Rowdy', 'races' => self::HUMANOID_RACES, 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Pit Fighter', 'races' => self::HUMANOID_RACES, 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Saurial Paladin', 'races' => self::HUMANOID_RACES, 'classes' => ['Paladin'], 'match' => 'any'],
        ['name' => 'Sellsword', 'races' => self::HUMANOID_RACES, 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Wilderness Protector', 'races' => self::HUMANOID_RACES, 'classes' => ['Fighter'], 'match' => 'any'],
        ['name' => 'Hedge Wizard', 'races' => self::HUMANOID_RACES, 'classes' => ['Mage'], 'match' => 'any'],
        ['name' => 'Humanoid Scholar', 'races' => self::HUMANOID_RACES, 'classes' => ['Mage'], 'match' => 'any'],
        ['name' => 'Outlaw Mage', 'races' => self::HUMANOID_RACES, 'classes' => ['Mage'], 'match' => 'any'],
        ['name' => 'Shaman', 'races' => self::HUMANOID_RACES, 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Witch Doctor', 'races' => self::HUMANOID_RACES, 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Oracle', 'races' => self::HUMANOID_RACES, 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'War Priest', 'races' => self::HUMANOID_RACES, 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Wandering Mystic', 'races' => self::HUMANOID_RACES, 'classes' => ['Cleric'], 'match' => 'any'],
        ['name' => 'Scavenger', 'races' => self::HUMANOID_RACES, 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Tramp', 'races' => self::HUMANOID_RACES, 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Tunnel Rat', 'races' => self::HUMANOID_RACES, 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Shadow', 'races' => self::HUMANOID_RACES, 'classes' => ['Thief'], 'match' => 'any'],
        ['name' => 'Humanoid Bard', 'races' => self::HUMANOID_RACES, 'classes' => ['Bard'], 'match' => 'any'],
    ];

    /**
     * @param  array<int, mixed>  $entries
     * @return list<string>
     */
    public static function suggestedNames(string $race, array $entries): array
    {
        $have = self::classNames($entries);
        $raceKey = self::normalizeRace($race);
        $names = [];

        foreach (self::KITS as $kit) {
            if (! self::raceMatches($raceKey, $kit['races'])) {
                continue;
            }
            if (! self::classMatches($have, $kit['classes'], $kit['match'] ?? 'all')) {
                continue;
            }
            $names[] = $kit['name'];
        }

        return array_values(array_unique($names));
    }

    public static function isEligible(string $kitName, string $race, array $entries): bool
    {
        return in_array($kitName, self::suggestedNames($race, $entries), true);
    }

    /**
     * @param  array<int, mixed>  $entries
     * @return list<string>
     */
    public static function subclassSuggestions(string $race, array $entries): array
    {
        $kits = self::suggestedNames($race, $entries);
        $schools = self::hasMage($entries) ? Adnd2e::SPECIALIST_SCHOOLS : [];
        $disciplines = self::hasPsionicist($entries) ? Adnd2e::PSIONIC_DISCIPLINES : [];

        return array_values(array_unique(array_merge($schools, $disciplines, $kits)));
    }

    /**
     * @param  array<int, mixed>  $entries
     */
    public static function hasMage(array $entries): bool
    {
        return in_array('Mage', self::classNames($entries), true);
    }

    /**
     * @param  array<int, mixed>  $entries
     */
    public static function hasPsionicist(array $entries): bool
    {
        return in_array('Psionicist', self::classNames($entries), true);
    }

    /**
     * @param  array<int, mixed>  $entries
     * @return list<string>
     */
    public static function classNames(array $entries): array
    {
        $have = [];
        foreach ($entries as $entry) {
            $raw = is_array($entry) ? (string) ($entry['class'] ?? '') : (string) $entry;
            $raw = trim($raw);
            if ($raw === '') {
                continue;
            }
            foreach (preg_split('/\s*\/\s*/', $raw) ?: [] as $part) {
                $rewritten = Adnd2e::rewriteLegacyClass($part);
                if (! $rewritten['rejected']) {
                    $have[] = $rewritten['class'];
                }
            }
        }

        return array_values(array_unique($have));
    }

    /**
     * @param  list<string>  $have
     * @param  list<string>  $needed
     */
    public static function classMatches(array $have, array $needed, string $match = 'all'): bool
    {
        if ($needed === []) {
            return $have !== [];
        }

        if ($match === 'any') {
            foreach ($needed as $class) {
                if (in_array($class, $have, true)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($needed as $class) {
            if (! in_array($class, $have, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $races
     */
    public static function raceMatches(string $race, array $races): bool
    {
        foreach ($races as $allowed) {
            if (strcasecmp($race, $allowed) === 0) {
                return true;
            }
        }

        return false;
    }

    public static function normalizeRace(string $race): string
    {
        $race = trim($race);
        foreach (Adnd2e::RACES as $known) {
            if (strcasecmp($race, $known) === 0) {
                return $known;
            }
        }

        return $race;
    }
}
