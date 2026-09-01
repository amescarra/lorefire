<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Character;
use App\Support\Adnd2e;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Character>
 */
class CharacterFactory extends Factory
{
    protected $model = Character::class;

    public function definition(): array
    {
        $defaults = Adnd2e::defaultsFor('Fighter', 1, 'Human', 10);

        return [
            'campaign_id' => Campaign::factory(),
            'name' => fake()->name(),
            'race' => 'Human',
            'class' => 'Fighter',
            'subclass' => null,
            'level' => 1,
            'background' => 'Soldier',
            'alignment' => 'Lawful Good',
            'armor_class' => 4,
            'max_hp' => 10,
            'current_hp' => 10,
            'speed' => $defaults['speed'],
            'strength' => 16,
            'dexterity' => 10,
            'constitution' => 10,
            'intelligence' => 10,
            'wisdom' => 10,
            'charisma' => 10,
            'exceptional_strength' => null,
            'thac0' => $defaults['thac0'],
            'hit_die' => $defaults['hit_die'],
            'saving_throws' => $defaults['saving_throws'],
            'class_path' => 'single',
            'class_levels' => [['class' => 'Fighter', 'level' => 1]],
            'memorization' => $defaults['memorization'],
            'memorization_used' => $defaults['memorization_used'],
            'weapon_proficiencies' => ['long sword', 'dagger'],
            'nonweapon_proficiencies' => [],
            'priest_spheres' => [],
            'gold' => 50,
        ];
    }
}
