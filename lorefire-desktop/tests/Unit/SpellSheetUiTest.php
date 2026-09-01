<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SpellSheetUiTest extends TestCase
{
    public function test_spell_rows_use_labeled_memorized_control_not_prepared(): void
    {
        $control = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/MemorizedControl.tsx');
        $tab = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/SpellsTab.tsx');
        $show = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/Characters/Show.tsx');
        $live = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/Sessions/Live.tsx');

        $this->assertIsString($control);
        $this->assertIsString($tab);
        $this->assertIsString($show);
        $this->assertIsString($live);

        $this->assertStringContainsString('data-testid="memorized-control"', $control);
        $this->assertStringContainsString('Memorized', $control);
        $this->assertStringContainsString('aria-label="Memorized"', $control);
        $this->assertStringNotContainsString('Prepared', $control);

        $this->assertStringContainsString('MemorizedControl', $tab);
        $this->assertStringContainsString('spell-filter-${key}', $tab);
        $this->assertStringContainsString("'memorized'", $tab);
        $this->assertStringContainsString('All known', $tab);
        $this->assertStringContainsString('times_memorized', $tab);
        $this->assertStringNotContainsString('Prepared', $tab);
        $this->assertStringNotContainsString('togglePrepared', $tab);

        $this->assertStringContainsString('SpellsTab', $show);
        $this->assertStringNotContainsString('Prepared', $show);

        $this->assertStringContainsString('timesMemorizedOf', $live);
        $this->assertStringNotContainsString('Prepared', $live);
    }
}
