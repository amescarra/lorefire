<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Adnd2eCharacterTest extends TestCase
{
    use RefreshDatabase;

    public function test_warlock_class_is_rewritten_to_mage(): void
    {
        $campaign = Campaign::factory()->create();

        $this->post(route('campaigns.characters.store', $campaign), [
            'name' => 'Hexer',
            'race' => 'Human',
            'class' => 'Warlock',
            'level' => 1,
            'strength' => 10,
            'dexterity' => 10,
            'constitution' => 10,
            'intelligence' => 14,
            'wisdom' => 10,
            'charisma' => 16,
        ])->assertRedirect();

        $character = Character::query()->where('name', 'Hexer')->firstOrFail();
        $this->assertSame('Mage', $character->class);
        $this->assertSame('d4', $character->hit_die);
    }

    public function test_creating_a_character_applies_2e_defaults(): void
    {
        $campaign = Campaign::factory()->create();

        $response = $this->post(route('campaigns.characters.store', $campaign), [
            'name' => 'Elanor',
            'race' => 'Elf',
            'class' => 'Mage',
            'subclass' => 'Illusionist',
            'level' => 1,
            'alignment' => 'Chaotic Neutral',
            'background' => 'Sage',
            'strength' => 8,
            'dexterity' => 16,
            'constitution' => 12,
            'intelligence' => 16,
            'wisdom' => 10,
            'charisma' => 12,
        ]);

        $response->assertRedirect();

        $character = Character::query()->where('name', 'Elanor')->firstOrFail();
        $this->assertSame(20, $character->thac0);
        $this->assertSame('d4', $character->hit_die);
        $this->assertSame(12, $character->speed);
        $this->assertArrayHasKey('paralyzation', $character->saving_throws);
        $this->assertGreaterThanOrEqual(1, $character->memorization[1] ?? $character->memorization['1'] ?? 0);
        $this->assertArrayNotHasKey('proficiency_bonus', $character->getAttributes());
        $this->assertArrayNotHasKey('dnd_beyond_url', $character->getAttributes());
    }

    public function test_dnd_beyond_import_is_removed(): void
    {
        $this->assertFalse(class_exists(\App\Http\Controllers\DndBeyondImportController::class));

        $campaign = Campaign::factory()->create();

        $this->post("/campaigns/{$campaign->id}/characters/import-beyond", [
            'url' => 'https://www.dndbeyond.com/characters/1',
        ])->assertStatus(405);

        $this->post('/characters/import-beyond', [
            'url' => 'https://www.dndbeyond.com/characters/1',
        ])->assertStatus(405);
    }

    public function test_overnight_rest_heals_one_and_clears_cast_marks(): void
    {
        $character = Character::factory()->create([
            'max_hp' => 10,
            'current_hp' => 4,
            'memorization_used' => [1 => 2],
        ]);

        $this->post(route('characters.rest.overnight', $character))->assertRedirect();

        $character->refresh();
        $this->assertSame(5, $character->current_hp);
        $this->assertNull($character->memorization_used);
    }

    public function test_hit_points_can_drop_to_negative_ten(): void
    {
        $character = Character::factory()->create([
            'max_hp' => 10,
            'current_hp' => 2,
        ]);

        $this->patchJson(route('characters.hp.update', $character), [
            'current_hp' => -10,
        ])->assertOk()->assertJson([
            'current_hp' => -10,
            'vitality' => 'dead',
        ]);

        $character->refresh();
        $this->assertSame(-10, $character->current_hp);
        $this->assertSame('dead', $character->vitalityState());
    }

    public function test_number_needed_to_hit_uses_thac0_minus_ac(): void
    {
        $character = Character::factory()->create([
            'thac0' => 18,
        ]);

        $this->assertSame(14, $character->numberNeededToHit(4));
    }

    public function test_short_and_long_rest_routes_are_gone(): void
    {
        $character = Character::factory()->create();

        $this->post("/characters/{$character->id}/rest/short")->assertNotFound();
        $this->post("/characters/{$character->id}/rest/long")->assertNotFound();
    }

    public function test_attunement_route_is_gone(): void
    {
        $character = Character::factory()->create();

        $status = $this->patch("/characters/{$character->id}/inventory/1/attune")->status();
        $this->assertContains($status, [404, 405]);
    }

    public function test_creating_a_multi_class_character_combines_combat_stats(): void
    {
        $campaign = Campaign::factory()->create();

        $this->post(route('campaigns.characters.store', $campaign), [
            'name' => 'Elara',
            'race' => 'Elf',
            'class' => 'Fighter',
            'class_path' => 'multi',
            'class_levels' => [
                ['class' => 'Fighter', 'level' => 5],
                ['class' => 'Mage', 'level' => 5],
            ],
            'level' => 5,
            'alignment' => 'Chaotic Good',
            'strength' => 16,
            'dexterity' => 14,
            'constitution' => 12,
            'intelligence' => 16,
            'wisdom' => 10,
            'charisma' => 10,
        ])->assertRedirect();

        $character = Character::query()->where('name', 'Elara')->firstOrFail();
        $this->assertSame('multi', $character->class_path);
        $this->assertSame('Fighter/Mage', $character->class);
        $this->assertSame(16, $character->thac0);
        $this->assertSame('d10/d4', $character->hit_die);
        $this->assertArrayHasKey('rod', $character->saving_throws);
        $this->assertGreaterThanOrEqual(1, $character->memorization[1] ?? $character->memorization['1'] ?? 0);
    }

    public function test_elf_fighter_mage_can_store_bladesinger_kit(): void
    {
        $campaign = Campaign::factory()->create();

        $this->post(route('campaigns.characters.store', $campaign), [
            'name' => 'Aelindra',
            'race' => 'Elf',
            'class' => 'Fighter',
            'subclass' => 'Bladesinger',
            'class_path' => 'multi',
            'class_levels' => [
                ['class' => 'Fighter', 'level' => 3],
                ['class' => 'Mage', 'level' => 3],
            ],
            'level' => 3,
            'alignment' => 'Chaotic Good',
            'strength' => 16,
            'dexterity' => 16,
            'constitution' => 12,
            'intelligence' => 16,
            'wisdom' => 10,
            'charisma' => 12,
        ])->assertRedirect();

        $character = Character::query()->where('name', 'Aelindra')->firstOrFail();
        $this->assertSame('Bladesinger', $character->subclass);
        $this->assertSame('Elf', $character->race);
        $this->assertSame('Fighter/Mage', $character->class);
    }

    public function test_character_can_store_fighter_and_psionicist_levels(): void
    {
        $campaign = Campaign::factory()->create();

        $this->post(route('campaigns.characters.store', $campaign), [
            'name' => 'Logain',
            'race' => 'Human',
            'class' => 'Fighter',
            'class_path' => 'multi',
            'class_levels' => [
                ['class' => 'Fighter', 'level' => 10],
                ['class' => 'Psion', 'level' => 9],
            ],
            'level' => 10,
            'alignment' => 'Lawful Neutral',
            'strength' => 16,
            'dexterity' => 12,
            'constitution' => 14,
            'intelligence' => 14,
            'wisdom' => 16,
            'charisma' => 10,
            'psp_max' => 40,
            'psp_current' => 28,
            'psionic_powers' => ['Sight', 'Nudge'],
        ])->assertRedirect();

        $character = Character::query()->where('name', 'Logain')->firstOrFail();
        $this->assertSame('multi', $character->class_path);
        $this->assertSame('Fighter/Psionicist', $character->class);
        $this->assertSame([
            ['class' => 'Fighter', 'level' => 10],
            ['class' => 'Psionicist', 'level' => 9],
        ], $character->class_levels);
        $this->assertSame(11, $character->thac0);
        $this->assertSame('d10/d6', $character->hit_die);
        $this->assertSame(40, $character->psp_max);
        $this->assertSame(28, $character->psp_current);
        $this->assertSame(['Sight', 'Nudge'], $character->psionic_powers);

        $this->post(route('campaigns.characters.store', $campaign), [
            'name' => 'Logain Dual',
            'race' => 'Human',
            'class' => 'Fighter',
            'class_path' => 'dual',
            'class_levels' => [
                ['class' => 'Fighter', 'level' => 10],
                ['class' => 'Psionicist', 'level' => 9],
            ],
            'level' => 9,
            'alignment' => 'Lawful Neutral',
            'strength' => 16,
            'dexterity' => 12,
            'constitution' => 14,
            'intelligence' => 14,
            'wisdom' => 16,
            'charisma' => 10,
        ])->assertRedirect();

        $dual = Character::query()->where('name', 'Logain Dual')->firstOrFail();
        $this->assertSame('dual', $dual->class_path);
        $this->assertSame('Fighter → Psionicist', $dual->class);
        $this->assertSame(9, $dual->level);
        $this->assertSame([
            ['class' => 'Fighter', 'level' => 10],
            ['class' => 'Psionicist', 'level' => 9],
        ], $dual->class_levels);
        $this->assertSame(11, $dual->thac0);
    }

    public function test_condition_manager_adds_and_clears_2e_states(): void
    {
        $character = Character::factory()->create();

        $this->post(route('characters.conditions.store', $character), [
            'condition' => 'Held',
            'notes' => 'Hold Person',
        ])->assertRedirect();

        $this->assertDatabaseHas('character_conditions', [
            'character_id' => $character->id,
            'condition' => 'Held',
        ]);

        $this->post(route('characters.conditions.store', $character), [
            'condition' => 'Exhausted',
        ])->assertSessionHasErrors('condition');

        $condition = $character->conditions()->firstOrFail();
        $this->delete(route('characters.conditions.destroy', [$character, $condition]))->assertRedirect();
        $this->assertDatabaseMissing('character_conditions', ['id' => $condition->id]);
    }
}
