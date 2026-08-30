<?php

namespace App\Models;

use App\Support\Adnd2e;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'name',
        'player_name',
        'race',
        'subrace',
        'class',
        'subclass',
        'level',
        'background',
        'alignment',
        'experience_points',
        'strength',
        'exceptional_strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
        'max_hp',
        'current_hp',
        'armor_class',
        'thac0',
        'speed',
        'hit_die',
        'saving_throws',
        'weapon_proficiencies',
        'nonweapon_proficiencies',
        'priest_spheres',
        'copper',
        'silver',
        'electrum',
        'gold',
        'platinum',
        'spellcasting_ability',
        'spell_slots',
        'spell_slots_used',
        'personality_traits',
        'ideals',
        'bonds',
        'flaws',
        'backstory',
        'appearance_description',
        'portrait_path',
        'portrait_generation_status',
        'portrait_style',
        'imported_data',
        'class_features',
    ];

    protected $casts = [
        'saving_throws' => 'array',
        'weapon_proficiencies' => 'array',
        'nonweapon_proficiencies' => 'array',
        'priest_spheres' => 'array',
        'spell_slots' => 'array',
        'spell_slots_used' => 'array',
        'imported_data' => 'array',
        'class_features' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function spells(): HasMany
    {
        return $this->hasMany(CharacterSpell::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function inventorySnapshots(): HasMany
    {
        return $this->hasMany(InventorySnapshot::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(CharacterFeature::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(CharacterCondition::class);
    }

    public function speakerProfiles(): HasMany
    {
        return $this->hasMany(SpeakerProfile::class);
    }

    /**
     * Primary combat-facing adjustment for an ability (2E tables).
     */
    public function getModifier(string $ability): int
    {
        $exceptional = $ability === 'strength' ? $this->exceptional_strength : null;

        return Adnd2e::primaryAdjustment($ability, (int) $this->{$ability}, $exceptional, (string) $this->class);
    }

    public function resolvedThac0(): int
    {
        return $this->thac0 ?? Adnd2e::thac0((string) $this->class, (int) $this->level);
    }

    public function resolvedSavingThrows(): array
    {
        return $this->saving_throws ?: Adnd2e::savingThrows((string) $this->class, (int) $this->level);
    }

    public function vitalityState(): string
    {
        return Adnd2e::vitalityState((int) $this->current_hp);
    }

    public function numberNeededToHit(int $targetAc): int
    {
        return Adnd2e::numberNeededToHit($this->resolvedThac0(), $targetAc);
    }
}
