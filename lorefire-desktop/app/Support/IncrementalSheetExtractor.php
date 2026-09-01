<?php

namespace App\Support;

use App\Models\Character;
use Illuminate\Support\Collection;

/**
 * Conservative, incremental sheet extractor for live transcript / Oracle text.
 * Only named HP, gold, XP, inventory, and Vancian spell copy changes that are
 * clearly stated. Does not invent PHB text or unknown spells.
 */
class IncrementalSheetExtractor
{
    /**
     * @param  Collection<int, Character>  $characters
     * @return array{actions: array<int, array<string, mixed>>, line_hashes: array<int, string>}
     */
    public static function extract(string $text, Collection $characters, ?string $sourceId = null): array
    {
        $actions = [];
        $lineHashes = [];

        foreach (self::splitLines($text) as $line) {
            $hash = self::lineHash($line);
            $lineHashes[] = 'line:'.$hash;
            foreach (self::extractLine($line, $hash, $characters) as $action) {
                if ($sourceId) {
                    $action['fingerprint'] = ($action['fingerprint'] ?? '').':'.$sourceId;
                }
                $actions[] = $action;
            }
        }

        return [
            'actions' => $actions,
            'line_hashes' => $lineHashes,
        ];
    }

    public static function lineHash(string $text): string
    {
        $normalized = strtolower($text);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?? '');

        return sha1($normalized);
    }

    /**
     * @return array<int, string>
     */
    public static function splitLines(string $text): array
    {
        $parts = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $lines = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed === '') {
                continue;
            }
            $lines[] = $trimmed;
        }

        return $lines;
    }

    /**
     * @param  Collection<int, Character>  $characters
     * @return array<int, array<string, mixed>>
     */
    protected static function extractLine(string $line, string $lineHash, Collection $characters): array
    {
        if (self::isHedged($line)) {
            return [];
        }

        $actions = [];

        if ($rest = self::extractRest($line, $lineHash, $characters)) {
            $actions[] = $rest;
        }

        $character = self::characterMentioned($line, $characters);

        foreach (self::extractSpellActions($line, $lineHash, $character, $characters) as $action) {
            $actions[] = $action;
        }

        if ($hp = self::extractHp($line, $lineHash, $character, $characters)) {
            $actions[] = $hp;
        }

        if ($gold = self::extractGold($line, $lineHash, $character, $characters)) {
            $actions[] = $gold;
        }

        if ($xp = self::extractXp($line, $lineHash, $character, $characters)) {
            $actions[] = $xp;
        }

        if ($inv = self::extractInventory($line, $lineHash, $character, $characters)) {
            $actions[] = $inv;
        }

        return $actions;
    }

    protected static function isHedged(string $line): bool
    {
        return (bool) preg_match(
            '/\b(might|may|could|should|would|can|will|wants to|going to|gonna|tries to|almost|maybe|perhaps)\b/i',
            $line
        );
    }

    /**
     * @param  Collection<int, Character>  $characters
     */
    protected static function extractRest(string $line, string $lineHash, Collection $characters): ?array
    {
        if (! preg_match('/\b(overnight rest|rest for the night|camp for the night|we rest overnight|rememorize(?:s|d)?(?: spells)? after rest)\b/i', $line)) {
            return null;
        }

        $character = self::characterMentioned($line, $characters);

        return [
            'type' => 'rest',
            'character_id' => $character?->id,
            'fingerprint' => 'rest:'.($character?->id ?? 'party').':'.$lineHash,
        ];
    }

    /**
     * @param  Collection<int, Character>  $characters
     * @return array<int, array<string, mixed>>
     */
    protected static function extractSpellActions(string $line, string $lineHash, ?Character $character, Collection $characters): array
    {
        $actions = [];

        $marked = false;
        if (preg_match('/\b(?:mark|note)\s+(.+?)\s+(?:as\s+)?cast\b/i', $line, $m)) {
            $named = self::matchKnownSpell(trim($m[1]), $character, $characters);
            if ($named) {
                $marked = true;
                $actions[] = self::spellAction('spell_cast', $named['character'], $named['spell'], 1, $lineHash);
            }
        }

        $verb = '\b(?:casts?|is casting|uses|used|burns?|expends?)\b';
        if (! $marked && preg_match('/'.$verb.'/i', $line)) {
            $named = self::matchKnownSpell($line, $character, $characters);
            if ($named && ! preg_match('/\b(how does|what is|explain|tell me about)\b/i', $line)) {
                $copies = self::copyCount($line);
                $actions[] = self::spellAction('spell_cast', $named['character'], $named['spell'], $copies, $lineHash);
            }
        }

        if (preg_match('/\b(?:rememorizes?|restores? one copy of|un-?casts?)\b/i', $line)) {
            $named = self::matchKnownSpell($line, $character, $characters);
            if ($named) {
                $actions[] = self::spellAction('spell_restore', $named['character'], $named['spell'], self::copyCount($line), $lineHash);
            }
        }

        if (preg_match('/\b(?:memorizes?|prepares?)\b/i', $line) && ! preg_match('/\brememorize/i', $line)) {
            $named = self::matchKnownSpell($line, $character, $characters);
            if ($named) {
                $actions[] = self::spellAction('spell_memorize', $named['character'], $named['spell'], self::copyCount($line), $lineHash);
            }
        }

        if (preg_match('/\b(?:adds?|learns?|scribes?)\b.{0,40}\b(?:to (?:the )?(?:spellbook|repertoire)|as a (\d+)(?:st|nd|rd|th)?[- ]level spell)\b/i', $line, $m)) {
            $level = isset($m[1]) ? (int) $m[1] : null;
            $name = self::newSpellName($line);
            $target = $character ?? self::uniqueCaster($characters);
            if ($name && $target && $level !== null && $level >= 1 && $level <= 9) {
                $actions[] = [
                    'type' => 'spell_learn',
                    'character_id' => $target->id,
                    'spell_name' => $name,
                    'level' => $level,
                    'fingerprint' => 'spell_learn:'.$target->id.':'.self::lineHash($name).':'.$lineHash,
                ];
            }
        }

        return $actions;
    }

    /**
     * @return array{character: Character, spell: object}|null
     */
    protected static function matchKnownSpell(string $haystack, ?Character $character, Collection $characters): ?array
    {
        $pool = $character ? collect([$character]) : $characters;
        $hits = [];

        foreach ($pool as $c) {
            foreach ($c->spells ?? [] as $spell) {
                $name = trim((string) $spell->name);
                if ($name === '') {
                    continue;
                }
                if (preg_match('/\b'.self::namePattern($name).'\b/i', $haystack)) {
                    $hits[] = ['character' => $c, 'spell' => $spell, 'len' => strlen($name)];
                }
            }
        }

        if ($hits === [] && $character) {
            return null;
        }

        if ($hits === []) {
            foreach ($characters as $c) {
                foreach ($c->spells ?? [] as $spell) {
                    $name = trim((string) $spell->name);
                    if ($name === '') {
                        continue;
                    }
                    if (preg_match('/\b'.self::namePattern($name).'\b/i', $haystack)) {
                        $hits[] = ['character' => $c, 'spell' => $spell, 'len' => strlen($name)];
                    }
                }
            }
        }

        if ($hits === []) {
            return null;
        }

        usort($hits, fn ($a, $b) => $b['len'] <=> $a['len']);
        $longest = $hits[0]['len'];
        $longestHits = array_values(array_filter($hits, fn ($h) => $h['len'] === $longest));
        $characterIds = array_unique(array_map(fn ($h) => $h['character']->id, $longestHits));

        if (! $character && count($characterIds) > 1) {
            $withRemaining = array_values(array_filter($longestHits, function ($h) {
                $spell = $h['spell'];
                $times = Adnd2e::effectiveTimesMemorized((int) $spell->times_memorized, (bool) $spell->is_prepared);

                return Adnd2e::remainingMemorized($times, (int) $spell->times_cast) > 0;
            }));
            $remainingIds = array_unique(array_map(fn ($h) => $h['character']->id, $withRemaining));
            if (count($remainingIds) !== 1) {
                return null;
            }

            return $withRemaining[0];
        }

        return $longestHits[0];
    }

    protected static function uniqueCaster(Collection $characters): ?Character
    {
        return $characters->count() === 1 ? $characters->first() : null;
    }

    protected static function newSpellName(string $line): ?string
    {
        if (preg_match('/\b(?:adds?|learns?|scribes?)\s+(?:the spell\s+)?([A-Za-z][A-Za-z0-9\' \-]{1,40}?)(?:\s+to |\s+as a\s)/i', $line, $m)) {
            $name = trim($m[1]);

            return $name !== '' ? $name : null;
        }

        return null;
    }

    protected static function copyCount(string $line): int
    {
        if (preg_match('/\b(twice|two times|two copies|two)\b/i', $line)) {
            return 2;
        }
        if (preg_match('/\b(thrice|three times|three copies|three)\b/i', $line)) {
            return 3;
        }
        if (preg_match('/\b(\d+)\s+(?:times|copies)\b/i', $line, $m)) {
            return max(1, min(Adnd2e::MAX_TIMES_MEMORIZED, (int) $m[1]));
        }

        return 1;
    }

    /**
     * @param  Collection<int, Character>  $characters
     */
    protected static function extractHp(string $line, string $lineHash, ?Character $character, Collection $characters): ?array
    {
        $character ??= self::characterMentioned($line, $characters);
        if (! $character) {
            return null;
        }

        if (preg_match('/\b(?:down to|now at|at|hp(?: is| to)?|hit points? (?:are|is|to))\s+(\d+)\b/i', $line, $m)
            && preg_match('/\b(hp|hit points?|down to|now at)\b/i', $line)) {
            return [
                'type' => 'hp_set',
                'character_id' => $character->id,
                'value' => (int) $m[1],
                'fingerprint' => 'hp_set:'.$character->id.':'.$m[1].':'.$lineHash,
            ];
        }

        if (preg_match('/\b(?:takes|suffers|loses|takes)\s+(\d+)\s+(?:points? of )?(?:damage|hp|hit points?)\b/i', $line, $m)) {
            return [
                'type' => 'hp_delta',
                'character_id' => $character->id,
                'delta' => -1 * (int) $m[1],
                'fingerprint' => 'hp_delta:'.$character->id.':-'.$m[1].':'.$lineHash,
            ];
        }

        if (preg_match('/\b(?:healed?|gains?|recovers?|restored)\s+(?:for\s+)?(\d+)\s*(?:hp|hit points?)?\b/i', $line, $m)
            && preg_match('/\b(heal|hp|hit points?)\b/i', $line)) {
            return [
                'type' => 'hp_delta',
                'character_id' => $character->id,
                'delta' => (int) $m[1],
                'fingerprint' => 'hp_delta:'.$character->id.':+'.$m[1].':'.$lineHash,
            ];
        }

        return null;
    }

    /**
     * @param  Collection<int, Character>  $characters
     */
    protected static function extractGold(string $line, string $lineHash, ?Character $character, Collection $characters): ?array
    {
        $character ??= self::characterMentioned($line, $characters);
        if (! $character) {
            return null;
        }

        if (! preg_match('/\b(gold|gp|gold pieces?)\b/i', $line)) {
            return null;
        }

        if (preg_match('/\b(?:now has|has)\s+(\d+)\s+(?:gold|gp|gold pieces?)\b/i', $line, $m)) {
            return [
                'type' => 'gold_set',
                'character_id' => $character->id,
                'value' => (int) $m[1],
                'fingerprint' => 'gold_set:'.$character->id.':'.$m[1].':'.$lineHash,
            ];
        }

        if (preg_match('/\b(?:gains?|finds?|gets?|receives?|loots?)\s+(\d+)\s+(?:gold|gp|gold pieces?)\b/i', $line, $m)) {
            return [
                'type' => 'gold_delta',
                'character_id' => $character->id,
                'delta' => (int) $m[1],
                'fingerprint' => 'gold_delta:'.$character->id.':+'.$m[1].':'.$lineHash,
            ];
        }

        if (preg_match('/\b(?:spends?|pays?|loses?|drops?)\s+(\d+)\s+(?:gold|gp|gold pieces?)\b/i', $line, $m)) {
            return [
                'type' => 'gold_delta',
                'character_id' => $character->id,
                'delta' => -1 * (int) $m[1],
                'fingerprint' => 'gold_delta:'.$character->id.':-'.$m[1].':'.$lineHash,
            ];
        }

        return null;
    }

    /**
     * @param  Collection<int, Character>  $characters
     */
    protected static function extractXp(string $line, string $lineHash, ?Character $character, Collection $characters): ?array
    {
        $character ??= self::characterMentioned($line, $characters);
        if (! $character) {
            return null;
        }

        if (! preg_match('/\b(xp|experience(?: points?)?)\b/i', $line)) {
            return null;
        }

        if (preg_match('/\b(?:now has|has)\s+(\d+)\s+(?:xp|experience(?: points?)?)\b/i', $line, $m)) {
            return [
                'type' => 'xp_set',
                'character_id' => $character->id,
                'value' => (int) $m[1],
                'fingerprint' => 'xp_set:'.$character->id.':'.$m[1].':'.$lineHash,
            ];
        }

        if (preg_match('/\b(?:gains?|gets?|earns?|is awarded|awarded)\s+(\d+)\s+(?:xp|experience(?: points?)?)\b/i', $line, $m)
            || preg_match('/\baward(?:s|ed)?\s+(\d+)\s+(?:xp|experience(?: points?)?)\s+to\b/i', $line, $m)) {
            return [
                'type' => 'xp_delta',
                'character_id' => $character->id,
                'delta' => (int) $m[1],
                'fingerprint' => 'xp_delta:'.$character->id.':+'.$m[1].':'.$lineHash,
            ];
        }

        return null;
    }

    /**
     * @param  Collection<int, Character>  $characters
     */
    protected static function extractInventory(string $line, string $lineHash, ?Character $character, Collection $characters): ?array
    {
        $character ??= self::characterMentioned($line, $characters);
        if (! $character) {
            return null;
        }

        if (preg_match('/\b(?:picks up|adds|puts)\s+(?:a |an |the )?(.+?)\s+(?:to (?:the )?(?:inventory|pack|bag)|in (?:the )?(?:inventory|pack|bag))\b/i', $line, $m)
            || preg_match('/\b(?:picks up|adds to inventory)\s+(?:a |an |the )?(.+)$/i', $line, $m)) {
            $name = self::sanitizeItemName($m[1] ?? '');
            if ($name === null) {
                return null;
            }

            return [
                'type' => 'inventory_gain',
                'character_id' => $character->id,
                'item_name' => $name,
                'fingerprint' => 'inv_gain:'.$character->id.':'.self::lineHash($name).':'.$lineHash,
            ];
        }

        if (preg_match('/\b(?:drops?|discards?|loses?|removes? from inventory)\s+(?:a |an |the )?(.+?)\s+(?:from (?:the )?(?:inventory|pack|bag))?\s*$/i', $line, $m)) {
            $name = self::sanitizeItemName($m[1] ?? '');
            if ($name === null || preg_match('/\b(gold|gp|hit points?|damage|xp|experience)\b/i', $name)) {
                return null;
            }

            return [
                'type' => 'inventory_lose',
                'character_id' => $character->id,
                'item_name' => $name,
                'fingerprint' => 'inv_lose:'.$character->id.':'.self::lineHash($name).':'.$lineHash,
            ];
        }

        return null;
    }

    protected static function sanitizeItemName(string $raw): ?string
    {
        $name = trim($raw, " \t.,;:\"'");
        $name = preg_replace('/\s+/', ' ', $name) ?? '';
        if (strlen($name) < 2 || strlen($name) > 40) {
            return null;
        }
        if (preg_match('/\b(spell|fireball|cast|damage|hit points?)\b/i', $name)) {
            return null;
        }

        return $name;
    }

    /**
     * @param  Collection<int, Character>  $characters
     */
    public static function characterMentioned(string $line, Collection $characters): ?Character
    {
        $best = null;
        $bestLen = 0;
        foreach ($characters as $character) {
            $name = trim((string) $character->name);
            if ($name === '') {
                continue;
            }
            if (preg_match('/\b'.self::namePattern($name).'\b/i', $line) && strlen($name) > $bestLen) {
                $best = $character;
                $bestLen = strlen($name);
            }
        }

        if ($best) {
            return $best;
        }

        $firstNames = [];
        foreach ($characters as $character) {
            $first = explode(' ', trim((string) $character->name))[0] ?? '';
            if ($first === '') {
                continue;
            }
            $firstNames[strtolower($first)][] = $character;
        }
        foreach ($firstNames as $first => $group) {
            if (count($group) === 1 && preg_match('/\b'.preg_quote($first, '/').'\b/i', $line)) {
                return $group[0];
            }
        }

        return null;
    }

    protected static function namePattern(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $quoted = array_map(fn ($p) => preg_quote($p, '/'), $parts);

        return implode('\s+', $quoted);
    }

    /**
     * @param  object  $spell
     * @return array<string, mixed>
     */
    protected static function spellAction(string $type, Character $character, object $spell, int $copies, string $lineHash): array
    {
        return [
            'type' => $type,
            'character_id' => $character->id,
            'spell_id' => $spell->id ?? null,
            'spell_name' => (string) $spell->name,
            'level' => (int) ($spell->level ?? 0),
            'copies' => max(1, $copies),
            'fingerprint' => $type.':'.$character->id.':'.self::lineHash((string) $spell->name).':'.$lineHash,
        ];
    }
}
