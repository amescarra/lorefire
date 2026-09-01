<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Support\WhisperxLanguages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhisperxSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_english_only_model_coerces_to_multilingual_twin(): void
    {
        $this->from('/settings')->post('/settings', [
            'whisperx_model' => 'medium.en',
            'whisperx_languages' => 'en,es',
        ])->assertRedirect('/settings');

        $this->assertSame('medium', AppSetting::get('whisperx_model'));
        $this->assertSame('en,es', AppSetting::get('whisperx_languages'));
        $this->assertFalse(WhisperxLanguages::isEnglishOnlyModel(AppSetting::get('whisperx_model')));
    }

    public function test_saving_large_v3_en_coerces(): void
    {
        $this->from('/settings')->post('/settings', [
            'whisperx_model' => 'large-v3.en',
            'whisperx_languages' => 'es',
        ])->assertRedirect('/settings');

        $this->assertSame('large-v3', AppSetting::get('whisperx_model'));
        $this->assertSame('es', AppSetting::get('whisperx_languages'));
    }

    public function test_onboarding_rejects_dot_en_model(): void
    {
        $this->post('/onboarding/settings', [
            'whisperx_model' => 'tiny.en',
            'whisperx_languages' => 'en,es',
        ])->assertRedirect();

        $this->assertSame('tiny', AppSetting::get('whisperx_model'));
        $this->assertSame('en,es', AppSetting::get('whisperx_languages'));
    }
}
