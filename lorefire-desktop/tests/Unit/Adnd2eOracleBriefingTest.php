<?php

namespace Tests\Unit;

use App\Support\Adnd2eOracleBriefing;
use PHPUnit\Framework\TestCase;

class Adnd2eOracleBriefingTest extends TestCase
{
    public function test_briefing_is_present_with_empty_context(): void
    {
        $prompt = Adnd2eOracleBriefing::systemPrompt([]);

        $this->assertStringContainsString('You are the Oracle', $prompt);
        $this->assertStringContainsString('do not quote copyrighted rulebook text', $prompt);
        $this->assertStringContainsString('Do not answer as if the table were using 5th Edition', $prompt);
        $this->assertStringContainsString('Lorefire 2E procedures', $prompt);
        $this->assertStringContainsString('THAC0', $prompt);
        $this->assertStringContainsString('descending', $prompt);
        $this->assertStringContainsString('Vancian memorization', $prompt);
        $this->assertStringContainsString('Overnight rest', $prompt);
        $this->assertStringContainsString('-10', $prompt);
        $this->assertStringContainsString('Bladesinger', $prompt);
        $this->assertStringNotContainsString('## Campaign Data', $prompt);
        $this->assertLessThan(1500, str_word_count($prompt));
    }

    public function test_briefing_is_present_with_campaign_context(): void
    {
        $prompt = Adnd2eOracleBriefing::systemPrompt([
            'campaigns' => [[
                'name' => 'Moonshae Run',
                'description' => 'A coastal chronicle.',
                'characters' => [[
                    'name' => 'Ailduin',
                    'race' => 'Elf',
                    'class' => 'Fighter/Mage',
                    'level' => 1,
                    'class_path' => 'multi',
                    'subclass' => 'Bladesinger',
                    'current_hp' => 6,
                    'max_hp' => 6,
                    'armor_class' => 5,
                    'thac0' => 20,
                ]],
                'game_sessions' => [],
            ]],
        ]);

        $this->assertStringContainsString('Lorefire 2E procedures', $prompt);
        $this->assertStringContainsString('## Campaign Data', $prompt);
        $this->assertStringContainsString('Moonshae Run', $prompt);
        $this->assertStringContainsString('Ailduin', $prompt);
        $this->assertStringContainsString('kit: Bladesinger', $prompt);
        $this->assertStringContainsString('[multi]', $prompt);
        $this->assertStringContainsString('THAC0: 20', $prompt);
        $this->assertStringContainsString('AC: 5 (descending)', $prompt);
        $this->assertStringContainsString('HP: 6/6', $prompt);
    }

    public function test_briefing_avoids_fifth_edition_terms(): void
    {
        $briefing = Adnd2eOracleBriefing::markdown();
        $prompt = Adnd2eOracleBriefing::systemPrompt([]);

        foreach ([$briefing, $prompt] as $text) {
            $this->assertStringNotContainsStringIgnoringCase('spell slots', $text);
            $this->assertStringNotContainsStringIgnoringCase('spell slot', $text);
            $this->assertStringNotContainsStringIgnoringCase('proficiency bonus', $text);
            $this->assertStringNotContainsStringIgnoringCase('death save', $text);
            $this->assertStringNotContainsStringIgnoringCase('armor class 10 +', $text);
            $this->assertDoesNotMatchRegularExpression('/\bascending AC\b/i', $text);
            $this->assertStringContainsString('descending', $text);
        }
    }

    public function test_character_line_prefers_stored_sheet_values(): void
    {
        $line = Adnd2eOracleBriefing::formatCharacterLine([
            'name' => 'Bran',
            'race' => 'Dwarf',
            'class' => 'Fighter',
            'level' => 3,
            'class_path' => 'single',
            'subclass' => 'Battlerager',
            'current_hp' => 18,
            'max_hp' => 22,
            'armor_class' => 2,
            'thac0' => 18,
        ]);

        $this->assertStringContainsString('THAC0: 18', $line);
        $this->assertStringContainsString('AC: 2 (descending)', $line);
        $this->assertStringContainsString('HP: 18/22', $line);
        $this->assertStringContainsString('kit: Battlerager', $line);
        $this->assertStringContainsString('[single]', $line);
    }
}
