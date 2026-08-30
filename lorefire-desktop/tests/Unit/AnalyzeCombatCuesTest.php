<?php

namespace Tests\Unit;

use App\Jobs\AnalyzeCombat;
use App\Models\GameSession;
use PHPUnit\Framework\TestCase;

class AnalyzeCombatCuesTest extends TestCase
{
    public function test_auto_detect_includes_2e_combat_cues(): void
    {
        $job = new AnalyzeCombat(new GameSession);
        $start = implode(' ', $job->combatStartCues());
        $actions = implode(' ', $job->combatActionCues());

        foreach (['thac0', 'surprise', 'initiative', 'segment', 'weapon speed'] as $cue) {
            $this->assertTrue(
                str_contains($start, $cue) || str_contains($actions, $cue),
                "Missing 2E combat cue: {$cue}"
            );
        }
    }
}
