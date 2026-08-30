<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Adnd2e;

trait ValidatesAdnd2eCharacter
{
    /**
     * @return array<string, mixed>
     */
    protected function characterStoreRules(): array
    {
        return [
            'campaign_id' => 'nullable|exists:campaigns,id',
            'name' => 'required|string|max:255',
            'player_name' => 'nullable|string|max:255',
            'race' => 'required|string|max:255',
            'subrace' => 'nullable|string|max:255',
            'class' => 'required|string|max:255',
            'subclass' => 'nullable|string|max:255',
            'level' => 'required|integer|min:1|max:20',
            'background' => 'nullable|string|max:255',
            'alignment' => 'nullable|string|max:50',
            'strength' => 'integer|min:1|max:25',
            'exceptional_strength' => 'nullable|string|max:3',
            'dexterity' => 'integer|min:1|max:25',
            'constitution' => 'integer|min:1|max:25',
            'intelligence' => 'integer|min:1|max:25',
            'wisdom' => 'integer|min:1|max:25',
            'charisma' => 'integer|min:1|max:25',
            'max_hp' => 'integer|min:0',
            'current_hp' => 'integer|min:'.Adnd2e::DEATH_THRESHOLD,
            'armor_class' => 'integer|min:-20|max:15',
            'speed' => 'integer|min:0',
            'portrait' => 'nullable|file|image|max:10240',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function characterUpdateRules(): array
    {
        return array_merge($this->characterStoreRules(), [
            'experience_points' => 'integer|min:0',
            'thac0' => 'integer|min:-10|max:25',
            'hit_die' => 'nullable|string|max:10',
            'saving_throws' => 'nullable|array',
            'weapon_proficiencies' => 'nullable|array',
            'nonweapon_proficiencies' => 'nullable|array',
            'priest_spheres' => 'nullable|array',
            'spellcasting_ability' => 'nullable|string|max:50',
            'personality_traits' => 'nullable|string',
            'ideals' => 'nullable|string',
            'bonds' => 'nullable|string',
            'flaws' => 'nullable|string',
            'backstory' => 'nullable|string',
            'appearance_description' => 'nullable|string',
            'copper' => 'integer|min:0',
            'silver' => 'integer|min:0',
            'electrum' => 'integer|min:0',
            'gold' => 'integer|min:0',
            'platinum' => 'integer|min:0',
            'class_features' => 'nullable|array',
            'spell_slots' => 'nullable|array',
            'portrait_style' => 'nullable|in:lifelike,renaissance,comic',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyEditionDefaults(array $data): array
    {
        $defaults = Adnd2e::defaultsFor(
            (string) ($data['class'] ?? 'Fighter'),
            (int) ($data['level'] ?? 1),
            (string) ($data['race'] ?? 'Human'),
            (int) ($data['wisdom'] ?? 10),
            $data['subclass'] ?? null,
        );

        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                $data[$key] = $value;
            }
        }

        if (! isset($data['current_hp']) && isset($data['max_hp'])) {
            $data['current_hp'] = $data['max_hp'];
        }

        return $data;
    }
}
