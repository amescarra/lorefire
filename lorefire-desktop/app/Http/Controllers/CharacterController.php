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

class CharacterController extends Controller
{
    use ValidatesAdnd2eCharacter;

    public function index(Campaign $campaign): Response
    {
        return Inertia::render('Characters/Index', [
            'campaign' => $campaign,
            'characters' => $campaign->characters()->with('inventoryItems')->orderBy('name')->get(),
        ]);
    }

    public function create(Campaign $campaign): Response
    {
        return Inertia::render('Characters/Create', [
            'campaign' => $campaign,
        ]);
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $this->applyEditionDefaults($request->validate($this->characterStoreRules()));

        if ($request->hasFile('portrait')) {
            $data['portrait_path'] = $request->file('portrait')->store('characters/portraits', 'local');
        }
        unset($data['portrait']);

        $character = $campaign->characters()->create($data);

        return redirect()->route('campaigns.characters.show', [$campaign, $character]);
    }

    public function show(Campaign $campaign, Character $character): Response
    {
        $character->load(['spells', 'inventoryItems', 'features', 'conditions', 'inventorySnapshots.gameSession']);

        return Inertia::render('Characters/Show', [
            'campaign' => $campaign,
            'character' => $character,
            'imageGenProvider' => AppSetting::get('image_gen_provider', 'none'),
        ]);
    }

    public function edit(Campaign $campaign, Character $character): Response
    {
        $character->load('spells');

        return Inertia::render('Characters/Edit', [
            'campaign' => $campaign,
            'character' => $character,
            'imageGenProvider' => AppSetting::get('image_gen_provider', 'none'),
        ]);
    }

    public function update(Request $request, Campaign $campaign, Character $character): RedirectResponse
    {
        $data = $request->validate($this->characterUpdateRules());

        if ($request->hasFile('portrait')) {
            if ($character->portrait_path) {
                Storage::disk('local')->delete($character->portrait_path);
            }
            $data['portrait_path'] = $request->file('portrait')->store('characters/portraits', 'local');
        }
        unset($data['portrait']);

        $character->update($data);

        return redirect()->route('campaigns.characters.show', [$campaign, $character]);
    }

    public function destroy(Campaign $campaign, Character $character): RedirectResponse
    {
        if ($character->portrait_path) {
            Storage::disk('local')->delete($character->portrait_path);
        }
        $character->delete();

        return redirect()->route('campaigns.show', $campaign);
    }
}
