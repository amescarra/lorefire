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
}
