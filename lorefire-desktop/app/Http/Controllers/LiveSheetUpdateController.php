<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Support\SessionSheetUpdates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Confirmed live sheet writes (Oracle "mark Fireball cast" and tests).
 * The default during play is still the live transcript, not this endpoint.
 */
class LiveSheetUpdateController extends Controller
{
    public function apply(Request $request, GameSession $session): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:8000',
        ]);

        $applied = SessionSheetUpdates::applyFromText(
            $session,
            $validated['text'],
            'oracle-'.uniqid('', true)
        );

        return response()->json([
            'applied' => $applied,
        ]);
    }
}
