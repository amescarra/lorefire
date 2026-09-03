<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ExportPdf;
use Tests\TestCase;

class BatchSheetTest extends TestCase
{
    use RefreshDatabase;

    // ── Selection UI ──────────────────────────────────────────────────────────

    public function test_batch_sheets_index_returns_inertia_page(): void
    {
        $this->get(route('batch-sheets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('BatchSheets/Index'));
    }

    public function test_batch_sheets_index_lists_all_characters(): void
    {
        Character::factory()->count(3)->create();

        $this->get(route('batch-sheets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) =>
                $page->component('BatchSheets/Index')
                     ->has('characters', 3)
            );
    }

    public function test_batch_sheets_index_filters_by_campaign(): void
    {
        $campaign = Campaign::factory()->create();
        Character::factory()->count(2)->create(['campaign_id' => $campaign->id]);
        Character::factory()->count(2)->create(['campaign_id' => null]);

        $this->get(route('batch-sheets.index', ['campaign' => $campaign->id]))
            ->assertOk()
            ->assertInertia(fn ($page) =>
                $page->component('BatchSheets/Index')
                     ->has('characters', 2)
                     ->where('selectedCampaign.id', $campaign->id)
            );
    }

    public function test_batch_sheets_index_includes_campaigns_list(): void
    {
        Campaign::factory()->count(2)->create();

        $this->get(route('batch-sheets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) =>
                $page->component('BatchSheets/Index')
                     ->has('campaigns', 2)
            );
    }

    // ── Export route ──────────────────────────────────────────────────────────

    public function test_export_requires_character_ids(): void
    {
        $this->postJson(route('batch-sheets.export'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['character_ids']);
    }

    public function test_export_rejects_empty_character_ids_array(): void
    {
        $this->postJson(route('batch-sheets.export'), ['character_ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['character_ids']);
    }

    public function test_export_rejects_nonexistent_character_ids(): void
    {
        $this->postJson(route('batch-sheets.export'), ['character_ids' => [9999]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['character_ids.0']);
    }

    public function test_export_dispatches_job_and_returns_key(): void
    {
        Bus::fake();

        $characters = Character::factory()->count(2)->create();
        $ids = $characters->pluck('id')->all();

        $response = $this->postJson(route('batch-sheets.export'), [
            'character_ids' => $ids,
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['key']);

        Bus::assertDispatched(ExportPdf::class);
    }

    public function test_export_dispatches_exactly_one_job_per_request(): void
    {
        Bus::fake();

        $character = Character::factory()->create();

        $this->postJson(route('batch-sheets.export'), [
            'character_ids' => [$character->id],
        ])->assertOk();

        Bus::assertDispatchedTimes(ExportPdf::class, 1);
    }

    public function test_export_with_multiple_characters_dispatches_one_combined_job(): void
    {
        Bus::fake();

        $characters = Character::factory()->count(5)->create();
        $ids = $characters->pluck('id')->all();

        $this->postJson(route('batch-sheets.export'), [
            'character_ids' => $ids,
        ])->assertOk();

        // Only ONE combined PDF, not one per character
        Bus::assertDispatchedTimes(ExportPdf::class, 1);
    }

    public function test_export_filename_contains_character_names(): void
    {
        Bus::fake();

        $character = Character::factory()->create(['name' => 'Thorin']);

        $this->postJson(route('batch-sheets.export'), [
            'character_ids' => [$character->id],
        ])->assertOk();

        Bus::assertDispatched(ExportPdf::class, function (ExportPdf $job) {
            return str_contains($job->filename, 'thorin');
        });
    }

    // ── Blade template rendering ───────────────────────────────────────────────

    public function test_batch_sheet_blade_view_renders_character_name(): void
    {
        $character = Character::factory()->create([
            'name'        => 'Aelindra',
            'race'        => 'Elf',
            'class'       => 'Mage',
            'class_levels' => [['class' => 'Mage', 'level' => 5]],
            'level'       => 5,
            'max_hp'      => 20,
            'current_hp'  => 15,
        ]);

        $html = view('pdf.batch-sheets', [
            'characters' => collect([$character->load(['spells', 'inventoryItems', 'features', 'conditions', 'campaign'])]),
            'baseUrl'    => 'http://localhost',
        ])->render();

        $this->assertStringContainsString('Aelindra', $html);
        $this->assertStringContainsString('Elf', $html);
        $this->assertStringContainsString('Mage', $html);
    }

    public function test_batch_sheet_blade_renders_hp_as_fraction(): void
    {
        $character = Character::factory()->create([
            'name'       => 'Brom',
            'max_hp'     => 30,
            'current_hp' => 18,
        ]);

        $html = view('pdf.batch-sheets', [
            'characters' => collect([$character->load(['spells', 'inventoryItems', 'features', 'conditions', 'campaign'])]),
            'baseUrl'    => 'http://localhost',
        ])->render();

        $this->assertStringContainsString('18', $html);
        $this->assertStringContainsString('30', $html);
    }

    public function test_batch_sheet_blade_renders_saving_throws_when_present(): void
    {
        $character = Character::factory()->create([
            'name'          => 'Cirin',
            'saving_throws' => ['paralysis_poison_death' => 10, 'rod_staff_wand' => 14],
        ]);

        $html = view('pdf.batch-sheets', [
            'characters' => collect([$character->load(['spells', 'inventoryItems', 'features', 'conditions', 'campaign'])]),
            'baseUrl'    => 'http://localhost',
        ])->render();

        $this->assertStringContainsString('Saving Throws', $html);
    }

    public function test_batch_sheet_blade_omits_spells_section_when_no_spells(): void
    {
        $character = Character::factory()->create(['name' => 'Davon']);
        $character->load(['spells', 'inventoryItems', 'features', 'conditions', 'campaign']);
        // No spells loaded — spells relation is empty collection

        $html = view('pdf.batch-sheets', [
            'characters' => collect([$character]),
            'baseUrl'    => 'http://localhost',
        ])->render();

        $this->assertStringNotContainsString('Memorized Spells', $html);
        $this->assertStringNotContainsString('box-title">Spells', $html);
    }

    public function test_batch_sheet_renders_multiple_characters_on_separate_pages(): void
    {
        $characters = Character::factory()->count(3)->create();
        $loaded = $characters->map(fn($c) => $c->load(['spells', 'inventoryItems', 'features', 'conditions', 'campaign']));

        $html = view('pdf.batch-sheets', [
            'characters' => $loaded,
            'baseUrl'    => 'http://localhost',
        ])->render();

        foreach ($characters as $c) {
            $this->assertStringContainsString($c->name, $html);
        }
        // Three .sheet divs expected
        $this->assertSame(3, substr_count($html, 'class="sheet"'));
    }

    public function test_batch_sheet_uses_traditional_letter_form_layout(): void
    {
        $character = Character::factory()->create([
            'name' => 'Elanor',
            'class' => 'Mage',
            'class_levels' => [['class' => 'Mage', 'level' => 3]],
        ]);

        $html = view('pdf.batch-sheets', [
            'characters' => collect([$character->load(['spells', 'inventoryItems', 'features', 'conditions', 'campaign'])]),
            'baseUrl'    => 'http://localhost',
        ])->render();

        $this->assertStringContainsString('Character Record Sheet', $html);
        $this->assertStringContainsString('size: letter', $html);
        $this->assertStringContainsString('#fffef8', $html);
        $this->assertStringContainsString('Ability Scores', $html);
        $this->assertStringContainsString('THAC0', $html);
        $this->assertStringNotContainsString('#0e0c0a', $html);
        $this->assertStringNotContainsString('Cinzel', $html);
        $this->assertStringNotContainsString('#c9963a', $html);
    }
}
