<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesAdnd2eCharacter;
use App\Models\AppSetting;
use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages characters outside the context of a specific campaign.
 */
class StandaloneCharacterController extends Controller
{
    use ValidatesAdnd2eCharacter;

    public function index(): Response
    {
        $characters = Character::with(['campaign', 'inventoryItems'])
            ->orderBy('name')
            ->get();

        $campaigns = Campaign::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Characters/Index', [
            'campaign' => null,
            'characters' => $characters,
            'campaigns' => $campaigns,
        ]);
    }

    public function create(): Response
    {
        $campaigns = Campaign::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Characters/Create', [
            'campaign' => null,
            'campaigns' => $campaigns,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->applyEditionDefaults($request->validate($this->characterStoreRules()));

        if ($request->hasFile('portrait')) {
            $data['portrait_path'] = $request->file('portrait')->store('characters/portraits', 'local');
        }
        unset($data['portrait']);

        $character = Character::create($data);

        return redirect("/characters/{$character->id}")
            ->with('success', "{$character->name} created.");
    }

    public function show(Character $character): Response
    {
        $character->load(['spells', 'inventoryItems', 'features', 'conditions', 'campaign', 'inventorySnapshots.gameSession']);

        return Inertia::render('Characters/Show', [
            'campaign' => $character->campaign,
            'character' => $character,
            'imageGenProvider' => AppSetting::get('image_gen_provider', 'none'),
        ]);
    }

    public function edit(Character $character): Response
    {
        $character->load('spells');
        $campaigns = Campaign::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Characters/Edit', [
            'campaign' => $character->campaign,
            'character' => $character,
            'campaigns' => $campaigns,
            'imageGenProvider' => AppSetting::get('image_gen_provider', 'none'),
        ]);
    }

    public function update(Request $request, Character $character): RedirectResponse
    {
        $data = $this->finalizeClassEntries(
            $this->normalizePsionicSheet($request->validate($this->characterUpdateRules()))
        );
        $this->assertSuorNorDualSwitch($data, $character);

        if ($request->hasFile('portrait')) {
            if ($character->portrait_path) {
                Storage::disk('local')->delete($character->portrait_path);
            }
            $data['portrait_path'] = $request->file('portrait')->store('characters/portraits', 'local');
        }
        unset($data['portrait']);

        $character->update($data);

        return redirect("/characters/{$character->id}")
            ->with('success', 'Character updated.');
    }

    public function destroy(Character $character): RedirectResponse
    {
        if ($character->portrait_path) {
            Storage::disk('local')->delete($character->portrait_path);
        }
        $character->delete();

        return redirect('/characters')
            ->with('success', 'Character deleted.');
    }
}
