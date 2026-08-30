<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles direct HP patching during live play.
 * Called via fetch() from the Live Session page CharacterCard component.
 */
class CharacterHpController extends Controller
{
    /**
     * PATCH /characters/{character}/hp
     *
     * Accepts { current_hp } and persists it. Current HP may drop to −10 (death).
     * Returns JSON so the client can confirm the saved values.
     */
    public function update(Request $request, Character $character): JsonResponse
    {
        $validated = $request->validate([
            'current_hp' => ['required', 'integer', 'min:'.\App\Support\Adnd2e::DEATH_THRESHOLD],
        ]);

        $character->update([
            'current_hp' => $validated['current_hp'],
        ]);

        return response()->json([
            'current_hp' => $character->current_hp,
            'vitality' => $character->vitalityState(),
        ]);
    }
}
