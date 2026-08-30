<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterSpell extends Model
{
    protected $fillable = [
        'character_id',
        'name',
        'level',
        'school',
        'casting_time',
        'range',
        'components',
        'duration',
        'description',
        'is_prepared',
        'is_cast',
    ];

    protected $casts = [
        'is_prepared' => 'boolean',
        'is_cast' => 'boolean',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
