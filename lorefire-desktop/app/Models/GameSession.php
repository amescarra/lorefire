<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    protected $table = 'game_sessions';

    protected $fillable = [
        'campaign_id',
        'title',
        'session_number',
        'played_at',
        'summary',
        'session_notes',
        'summary_status',
        'dm_notes',
        'key_events',
        'next_session_notes',
        'participant_character_ids',
        'audio_path',
        'transcript_path',
        'transcription_status',
        'duration_seconds',
        'art_prompts_status',
        'extraction_status',
        'sheet_update_cursor',
        'sheet_update_hashes',
        'live_chunk_index',
        'live_audio_seconds',
    ];

    protected $casts = [
        'played_at'                 => 'date',
        'participant_character_ids' => 'array',
        'sheet_update_hashes'       => 'array',
        'sheet_update_cursor'       => 'integer',
        'live_chunk_index'          => 'integer',
        'live_audio_seconds'        => 'float',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SessionEvent::class);
    }

    public function sceneArtPrompts(): HasMany
    {
        return $this->hasMany(SceneArtPrompt::class);
    }

    public function speakerProfiles(): HasMany
    {
        return $this->hasMany(SpeakerProfile::class);
    }
}
