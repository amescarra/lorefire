<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Adnd2eCharacterTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertGreaterThanOrEqual(1, $character->spell_slots[1] ?? $character->spell_slots['1'] ?? 0);
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
            'spell_slots_used' => [1 => ['total' => 2, 'used' => 2]],
        ]);

        $this->post(route('characters.rest.overnight', $character))->assertRedirect();

        $character->refresh();
        $this->assertSame(5, $character->current_hp);
        $this->assertNull($character->spell_slots_used);
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
}
