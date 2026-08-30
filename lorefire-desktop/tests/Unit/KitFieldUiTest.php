<?php

namespace Tests\Unit;

use App\Support\Adnd2e;
use PHPUnit\Framework\TestCase;

class KitFieldUiTest extends TestCase
{
    public function test_visible_racial_kits_section_lists_bladesinger_for_elf_fighter_mage(): void
    {
        $tsx = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/KitField.tsx');
        $this->assertIsString($tsx);

        $this->assertStringContainsString('data-testid="racial-handbook-kits"', $tsx);
        $this->assertStringContainsString('Racial handbook kits', $tsx);
        $this->assertStringContainsString('data-kit={k}', $tsx);
        $this->assertStringContainsString('onClick={() => onChange(k)}', $tsx);
        $this->assertStringContainsString('kits.length > 0 &&', $tsx);

        $racialGroup = strpos($tsx, 'optgroup label="Racial handbook kits"');
        $schoolGroup = strpos($tsx, 'optgroup label="Specialist school"');
        $this->assertNotFalse($racialGroup);
        $this->assertNotFalse($schoolGroup);
        $this->assertLessThan(
            $schoolGroup,
            $racialGroup,
            'Racial handbook kits must appear above specialist schools in the select.'
        );

        $elfFm = Adnd2e::suggestedRacialKits('Elf', [
            ['class' => 'Fighter', 'level' => 1],
            ['class' => 'Mage', 'level' => 1],
        ]);
        $this->assertContains('Bladesinger', $elfFm);
        $this->assertContains('War Wizard', $elfFm);
    }

    public function test_human_mage_has_no_visible_racial_handbook_kits(): void
    {
        $human = Adnd2e::suggestedRacialKits('Human', [
            ['class' => 'Mage', 'level' => 1],
        ]);
        $this->assertSame([], $human);
        $this->assertNotContains('Bladesinger', $human);

        $options = Adnd2e::suggestedSubclassOptions('Human', [
            ['class' => 'Mage', 'level' => 1],
        ]);
        $this->assertContains('Illusionist', $options);
        $this->assertNotContains('Bladesinger', $options);
    }

    public function test_psionicist_kit_offers_discipline_labels_only(): void
    {
        $tsx = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/KitField.tsx');
        $this->assertIsString($tsx);
        $this->assertStringContainsString('optgroup label="Psionic disciplines"', $tsx);
        $this->assertStringNotContainsString('science', strtolower($tsx));
        $this->assertStringNotContainsString('devotion', strtolower($tsx));
        $this->assertStringNotContainsString('power score', strtolower($tsx));

        $options = Adnd2e::suggestedSubclassOptions('Human', [
            ['class' => 'Psionicist', 'level' => 9],
        ]);
        $this->assertContains('Telepathy', $options);
        $this->assertContains('Metapsionics', $options);
        $this->assertNotContains('Bladesinger', $options);

        $php = file_get_contents(dirname(__DIR__, 2).'/app/Support/Adnd2e.php');
        $this->assertIsString($php);
        $this->assertStringNotContainsString('Total Disciplines', $php);
        $this->assertStringNotContainsString('Total Sciences', $php);
        $this->assertStringNotContainsString('Def. Modes', $php);
    }
}
