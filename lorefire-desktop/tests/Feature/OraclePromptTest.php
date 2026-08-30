<?php

namespace Tests\Feature;

use App\Http\Controllers\OracleController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OraclePromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_controller_prompt_includes_briefing_without_campaigns(): void
    {
        $prompt = app(OracleController::class)->buildSystemPrompt([]);

        $this->assertStringContainsString('Lorefire 2E procedures', $prompt);
        $this->assertStringContainsString('You are the Oracle', $prompt);
        $this->assertStringNotContainsString('## Campaign Data', $prompt);
        $this->assertStringNotContainsStringIgnoringCase('spell slots', $prompt);
        $this->assertStringNotContainsStringIgnoringCase('proficiency bonus', $prompt);
    }

    public function test_controller_prompt_keeps_briefing_when_campaigns_exist(): void
    {
        $prompt = app(OracleController::class)->buildSystemPrompt([
            'campaigns' => [[
                'name' => 'Empty Table',
                'characters' => [],
            ]],
        ]);

        $this->assertStringContainsString('Lorefire 2E procedures', $prompt);
        $this->assertStringContainsString('## Campaign Data', $prompt);
        $this->assertStringContainsString('Empty Table', $prompt);
        $this->assertStringContainsString('do not quote copyrighted rulebook text', $prompt);
    }

    public function test_controller_prompt_includes_notes_and_backstory(): void
    {
        $prompt = app(OracleController::class)->buildSystemPrompt([
            'campaigns' => [[
                'name' => 'Suor Nor',
                'description' => 'The fighter house chronicle.',
                'notes' => 'Full master doc: origin of the six PCs and the broken oath.',
                'characters' => [[
                    'name' => 'Ailduin',
                    'race' => 'Elf',
                    'class' => 'Fighter/Mage',
                    'backstory' => 'Born in the Suor Nor fighter house.',
                    'thac0' => 20,
                    'armor_class' => 5,
                ]],
            ]],
        ]);

        $this->assertStringContainsString('Lorefire 2E procedures', $prompt);
        $this->assertStringContainsString('**Campaign notes:**', $prompt);
        $this->assertStringContainsString('Full master doc: origin of the six PCs and the broken oath.', $prompt);
        $this->assertStringContainsString('**Backstory:**', $prompt);
        $this->assertStringContainsString('Born in the Suor Nor fighter house.', $prompt);
        $this->assertStringNotContainsStringIgnoringCase('spell slots', $prompt);
        $this->assertStringNotContainsStringIgnoringCase('proficiency bonus', $prompt);
    }
}
