<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\CharacterCondition;
use App\Support\Adnd2e;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CharacterConditionController extends Controller
{
    public function store(Request $request, Character $character): RedirectResponse
    {
        $data = $request->validate([
            'condition' => ['required', 'string', 'max:50', 'in:'.implode(',', Adnd2e::CONDITIONS)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $exists = $character->conditions()->where('condition', $data['condition'])->exists();
        if (! $exists) {
            $character->conditions()->create($data);
        }

        return back()->with('success', $data['condition'].' recorded.');
    }

    public function destroy(Character $character, CharacterCondition $condition): RedirectResponse
    {
        abort_if($condition->character_id !== $character->id, 403);
        $condition->delete();

        return back()->with('success', 'Condition cleared.');
    }
}
