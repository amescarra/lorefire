<?php

namespace Tests\Unit;

use App\Support\Adnd2e;
use PHPUnit\Framework\TestCase;

class Adnd2eTest extends TestCase
{
    public function test_thac0_follows_class_group_tables(): void
    {
        $this->assertSame(20, Adnd2e::thac0('Fighter', 1));
        $this->assertSame(19, Adnd2e::thac0('Fighter', 2));
        $this->assertSame(18, Adnd2e::thac0('Paladin', 3));
        $this->assertSame(20, Adnd2e::thac0('Mage', 1));
        $this->assertSame(20, Adnd2e::thac0('Mage', 3));
        $this->assertSame(18, Adnd2e::thac0('Cleric', 4));
        $this->assertSame(20, Adnd2e::thac0('Thief', 1));
        $this->assertSame(19, Adnd2e::thac0('Thief', 5));
    }

    public function test_attack_uses_thac0_minus_descending_ac(): void
    {
        $hit = Adnd2e::resolveAttack(20, 4, 16);
        $this->assertTrue($hit['hit']);
        $this->assertSame(16, $hit['needed']);

        $miss = Adnd2e::resolveAttack(20, 4, 15);
        $this->assertFalse($miss['hit']);

        $this->assertFalse(Adnd2e::resolveAttack(20, 0, 1)['hit']);
        $this->assertTrue(Adnd2e::resolveAttack(20, 0, 20)['hit']);
    }

    public function test_saving_throws_and_hit_dice(): void
    {
        $saves = Adnd2e::savingThrows('Fighter', 1);
        $this->assertSame(14, $saves['paralyzation']);
        $this->assertSame(16, $saves['rod']);
        $this->assertSame(15, $saves['petrification']);
        $this->assertSame(17, $saves['breath']);
        $this->assertSame(17, $saves['spell']);

        $this->assertSame('d10', Adnd2e::hitDie('Fighter'));
        $this->assertSame('d4', Adnd2e::hitDie('Mage'));
        $this->assertSame('d8', Adnd2e::hitDie('Cleric'));
        $this->assertSame('d6', Adnd2e::hitDie('Thief'));
    }

    public function test_memorization_capacity_includes_specialist_bonus(): void
    {
        $mage = Adnd2e::memorizationCapacity('Mage', 1, 10);
        $this->assertSame(1, $mage[1]);

        $illusionist = Adnd2e::memorizationCapacity('Mage', 1, 10, 'Illusionist');
        $this->assertSame(2, $illusionist[1]);

        $cleric = Adnd2e::memorizationCapacity('Cleric', 1, 17);
        $this->assertGreaterThan(1, $cleric[1]);
    }

    public function test_overnight_rest_recovers_one_hit_point_and_resets_daily_abilities(): void
    {
        $result = Adnd2e::overnightRest(4, 10, 'Paladin', 5, [
            'lay_on_hands_current' => 0,
        ]);

        $this->assertSame(5, $result['current_hp']);
        $this->assertNull($result['spell_slots_used']);
        $this->assertSame(10, $result['class_features']['lay_on_hands_max']);
        $this->assertSame(10, $result['class_features']['lay_on_hands_current']);
        $this->assertTrue($result['class_features']['detect_evil_ready']);
        $this->assertTrue($result['class_features']['turn_undead_ready']);
    }

    public function test_vitality_states(): void
    {
        $this->assertSame('ok', Adnd2e::vitalityState(1));
        $this->assertSame('unconscious', Adnd2e::vitalityState(0));
        $this->assertSame('dying', Adnd2e::vitalityState(-3));
        $this->assertSame('dead', Adnd2e::vitalityState(-10));
    }

    public function test_defaults_for_new_character(): void
    {
        $defaults = Adnd2e::defaultsFor('Mage', 1, 'Elf', 10);
        $this->assertSame(20, $defaults['thac0']);
        $this->assertSame('d4', $defaults['hit_die']);
        $this->assertSame(12, $defaults['speed']);
        $this->assertSame(1, $defaults['spell_slots'][1]);
    }
}
