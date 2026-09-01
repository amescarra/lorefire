<?php

namespace App\Jobs;

use App\Models\AppSetting;
use App\Models\Encounter;
use App\Models\EncounterTurn;
use App\Models\GameSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class AnalyzeCombat implements ShouldQueue
{
    use Queueable;

    /** @return list<string> */
    public function combatStartCues(): array
    {
        return $this->combatStartKeywords;
    }

    /** @return list<string> */
    public function combatActionCues(): array
    {
        return $this->actionKeywords;
    }

    protected array $combatStartKeywords = [
        'roll initiative', 'roll for initiative', 'initiative order',
        'combat begins', 'battle starts', 'initiative',
        'you enter combat', 'surprised', 'surprise round',
        'thac0', 'roll for surprise', 'check for surprise',
        'segment', 'weapon speed', 'morale check',
        'descending armor class', 'armor class',
    ];

    protected array $actionKeywords = [
        'attacks', 'casts', 'uses', 'misses', 'hits',
        'deals', 'damage', 'heals', 'healing', 'melee', 'missile',
        'saves', 'fails', 'succeeds', 'rolls', 'thac0', 'weapon speed',
        'segments', 'segment', 'parry', 'retreat', 'charge',
        'saving throw', 'surprise', 'initiative', 'morale',
        'number needed', 'to hit',
    ];

    public function __construct(public GameSession $session) {}

    public function handle(): void
    {
        if (! $this->session->transcript_path) {
            return;
        }

        $raw = Storage::get($this->session->transcript_path);
        if (! $raw) {
            return;
        }

        $transcript = json_decode($raw, true);
        $segments = $transcript['segments'] ?? [];

        $encounters = $this->detectEncounters($segments);

        foreach ($encounters as $encounterData) {
            $encounter = Encounter::create([
                'game_session_id'        => $this->session->id,
                'name'                   => $encounterData['name'],
                'round_count'            => $encounterData['round_count'],
                'status'                 => 'auto_detected',
                'transcript_start_second' => $encounterData['start_second'],
                'transcript_end_second'   => $encounterData['end_second'],
            ]);

            foreach ($encounterData['turns'] as $turnData) {
                EncounterTurn::create([
                    'encounter_id'       => $encounter->id,
                    'round_number'       => $turnData['round'],
                    'turn_order'         => $turnData['order'],
                    'actor_name'         => $turnData['actor'],
                    'actor_type'         => 'character',
                    'action_description' => $turnData['text'],
                    'transcript_second'  => $turnData['second'],
                ]);
            }
        }
    }

    protected function detectEncounters(array $segments): array
    {
        $encounters = [];
        $inCombat = false;
        $currentEncounter = null;
        $round = 1;
        $order = 1;

        foreach ($segments as $segment) {
            $text = strtolower($segment['text'] ?? '');
            $second = (int) ($segment['start'] ?? 0);
            $speaker = $segment['speaker'] ?? 'Unknown';

            // Detect combat start
            foreach ($this->combatStartKeywords as $keyword) {
                if (str_contains($text, $keyword) && ! $inCombat) {
                    $inCombat = true;
                    $currentEncounter = [
                        'name'         => 'Encounter ' . (count($encounters) + 1),
                        'start_second' => $second,
                        'end_second'   => $second,
                        'round_count'  => 1,
                        'turns'        => [],
                    ];
                    $round = 1;
                    $order = 1;
                    break;
                }
            }

            // Track rounds
            if ($inCombat && (str_contains($text, 'round') || str_contains($text, 'next round'))) {
                $round++;
                $order = 1;
                if ($currentEncounter) {
                    $currentEncounter['round_count'] = max($currentEncounter['round_count'], $round);
                }
            }

            // Detect combat actions
            if ($inCombat && $currentEncounter) {
                foreach ($this->actionKeywords as $keyword) {
                    if (str_contains($text, $keyword)) {
                        $currentEncounter['turns'][] = [
                            'round'  => $round,
                            'order'  => $order++,
                            'actor'  => $speaker,
                            'text'   => trim($segment['text'] ?? ''),
                            'second' => $second,
                        ];
                        $currentEncounter['end_second'] = $second;
                        break;
                    }
                }

                // Detect combat end
                if (str_contains($text, 'combat ends') || str_contains($text, 'battle is over')
                    || str_contains($text, 'enemies are dead') || str_contains($text, 'you win')) {
                    $encounters[] = $currentEncounter;
                    $inCombat = false;
                    $currentEncounter = null;
                }
            }
        }

        // Close any open encounter
        if ($inCombat && $currentEncounter) {
            $encounters[] = $currentEncounter;
        }

        return $encounters;
    }
}
