<?php

namespace App\Jobs;

use App\Models\GameSession;
use App\Support\IncrementalSheetExtractor;
use App\Support\LiveTranscript;
use App\Support\SessionSheetUpdates;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Apply sheet updates from new live transcript segments only.
 */
class ExtractLiveSheetUpdates implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(public GameSession $session) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('live-extract-'.$this->session->id))
                ->releaseAfter(5)
                ->expireAfter(120),
        ];
    }

    public function handle(): void
    {
        $session = $this->session->fresh();
        if (! $session) {
            return;
        }

        $new = LiveTranscript::unprocessedSegments($session);
        if ($new === []) {
            return;
        }

        $characters = SessionSheetUpdates::participants($session);
        $actions = [];
        $hashes = [];
        foreach ($new as $segment) {
            $extracted = IncrementalSheetExtractor::extract(
                (string) ($segment['text'] ?? ''),
                $characters,
                'live-'.($segment['id'] ?? uniqid())
            );
            $actions = array_merge($actions, $extracted['actions']);
            $hashes = array_merge($hashes, $extracted['line_hashes']);
        }

        SessionSheetUpdates::apply($session, $actions, $hashes);

        $session->refresh();
        $session->update([
            'sheet_update_cursor' => (int) ($session->sheet_update_cursor ?? 0) + count($new),
        ]);
    }
}
