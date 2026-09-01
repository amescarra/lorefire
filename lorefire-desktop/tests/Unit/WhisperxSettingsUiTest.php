<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WhisperxSettingsUiTest extends TestCase
{
    public function test_settings_and_runner_use_allowlist_not_english_only_models(): void
    {
        $settings = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/Settings/Index.tsx');
        $onboarding = file_get_contents(dirname(__DIR__, 2).'/resources/js/Pages/Onboarding/Index.tsx');
        $transcribe = file_get_contents(dirname(__DIR__, 2).'/app/Jobs/TranscribeAudio.php');
        $runner = file_get_contents(dirname(__DIR__, 2).'/app/Support/WhisperxRunner.php');
        $py = file_get_contents(dirname(__DIR__, 2).'/resources/python/run_whisperx.py');

        $this->assertIsString($settings);
        $this->assertIsString($transcribe);
        $this->assertIsString($py);

        $this->assertStringContainsString('whisperx_languages', $settings);
        $this->assertStringContainsString('English', $settings);
        $this->assertStringContainsString('Spanish', $settings);
        $this->assertStringNotContainsString('tiny.en', $settings);
        $this->assertStringNotContainsString('medium.en', $settings);
        $this->assertStringNotContainsString('large-v3.en', $settings);
        $this->assertStringNotContainsString('value="auto"', $settings);
        $this->assertStringNotContainsString('value="fr"', $settings);
        $this->assertStringNotContainsString("whisperx_language ??", $settings);
        $this->assertStringNotContainsString("'whisperx_language'", $settings);

        $this->assertStringContainsString('whisperx_languages', $onboarding);
        $this->assertStringNotContainsString('tiny.en', $onboarding);
        $this->assertStringNotContainsString('medium.en', $onboarding);

        $this->assertStringContainsString('WhisperxLanguages::command', $transcribe);
        $this->assertStringNotContainsString("'--language'", $transcribe);
        $this->assertStringNotContainsString("'--language'", $runner);

        $this->assertStringContainsString('--languages', $py);
        $this->assertStringContainsString('clamp_detected_language', $py);
        $this->assertStringContainsString('task="transcribe"', $py);
        $this->assertStringContainsString('align_by_language', $py);
        $this->assertStringNotContainsString("language=args.language", $py);
    }
}
