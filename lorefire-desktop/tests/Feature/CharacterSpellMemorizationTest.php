<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Support\Adnd2e;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CharacterSpellMemorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_spell_stays_listed_when_not_memorized(): void
    {
        $character = Character::factory()->create([
            'name' => 'Aldric',
            'class' => 'Mage',
        ]);

        $this->post(route('characters.spells.store', $character), [
            'name' => 'Magic Missile',
            'level' => 1,
            'school' => 'invocation',
            'times_memorized' => 0,
        ])->assertRedirect();

        $spell = $character->spells()->firstOrFail();
        $this->assertSame('Magic Missile', $spell->name);
        $this->assertSame(0, $spell->times_memorized);
        $this->assertFalse($spell->is_prepared);

        $this->get(route('characters.show', $character))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Characters/Show')
                ->where('character.spells.0.name', 'Magic Missile')
                ->where('character.spells.0.times_memorized', 0)
                ->where('character.spells.0.is_prepared', false)
            );
    }

    public function test_same_spell_can_be_memorized_twice(): void
    {
        $character = Character::factory()->create([
            'name' => 'Elara',
            'class' => 'Mage',
        ]);

        $this->post(route('characters.spells.store', $character), [
            'name' => 'Magic Missile',
            'level' => 1,
            'is_prepared' => true,
        ])->assertRedirect();

        $spell = $character->spells()->firstOrFail();
        $this->assertSame(1, $spell->times_memorized);
        $this->assertTrue($spell->is_prepared);

        $this->patch(route('characters.spells.prepare', [$character, $spell]), [
            'times_memorized' => 2,
        ])->assertRedirect();

        $spell->refresh();
        $this->assertSame(2, $spell->times_memorized);
        $this->assertSame(0, $spell->times_cast);
        $this->assertTrue($spell->is_prepared);
        $this->assertFalse($spell->is_cast);
        $this->assertSame(2, $spell->remaining_memorized);
    }

    public function test_priest_can_mark_memorized_copies(): void
    {
        $character = Character::factory()->create([
            'name' => 'Bran',
            'class' => 'Cleric',
        ]);

        $this->post(route('characters.spells.store', $character), [
            'name' => 'Cure Light Wounds',
            'level' => 1,
            'times_memorized' => 2,
        ])->assertRedirect();

        $spell = $character->spells()->firstOrFail();
        $this->assertSame(2, $spell->times_memorized);
        $this->assertTrue($spell->is_prepared);
    }

    public function test_cast_burns_one_memorized_copy(): void
    {
        $character = Character::factory()->create([
            'name' => 'Hexer',
            'class' => 'Mage',
        ]);
        $spell = $character->spells()->create([
            'name' => 'Magic Missile',
            'level' => 1,
            'times_memorized' => 2,
            'times_cast' => 0,
        ]);

        $this->patch(route('characters.spells.cast', [$character, $spell]), [
            'action' => 'use',
        ])->assertRedirect();

        $spell->refresh();
        $this->assertSame(1, $spell->times_cast);
        $this->assertFalse($spell->is_cast);
        $this->assertSame(1, $spell->remaining_memorized);

        $this->patch(route('characters.spells.cast', [$character, $spell]), [
            'action' => 'use',
        ])->assertRedirect();

        $spell->refresh();
        $this->assertSame(2, $spell->times_cast);
        $this->assertTrue($spell->is_cast);
        $this->assertSame(0, $spell->remaining_memorized);

        $this->patch(route('characters.spells.cast', [$character, $spell]), [
            'action' => 'use',
        ])->assertRedirect();
        $this->assertSame(2, $spell->fresh()->times_cast);

        $this->patch(route('characters.spells.cast', [$character, $spell]), [
            'action' => 'recover',
        ])->assertRedirect();
        $this->assertSame(1, $spell->fresh()->times_cast);
        $this->assertFalse($spell->fresh()->is_cast);
    }

    public function test_unmemorized_spell_cannot_be_cast(): void
    {
        $character = Character::factory()->create(['class' => 'Mage']);
        $spell = $character->spells()->create([
            'name' => 'Sleep',
            'level' => 1,
            'times_memorized' => 0,
        ]);

        $this->patch(route('characters.spells.cast', [$character, $spell]))->assertRedirect();

        $this->assertSame(0, $spell->fresh()->times_cast);
        $this->assertFalse($spell->fresh()->is_cast);
    }

    public function test_memorized_count_is_capped(): void
    {
        $character = Character::factory()->create(['class' => 'Mage']);
        $spell = $character->spells()->create([
            'name' => 'Sleep',
            'level' => 1,
        ]);

        $this->patch(route('characters.spells.prepare', [$character, $spell]), [
            'times_memorized' => Adnd2e::MAX_TIMES_MEMORIZED + 4,
        ])->assertSessionHasErrors('times_memorized');
    }
}
