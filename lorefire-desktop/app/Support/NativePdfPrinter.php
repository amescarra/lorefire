<?php

namespace App\Support;

use Native\Laravel\Facades\System;
use Throwable;
use TypeError;

/**
 * Thin wrapper around NativePHP System::printToPDF().
 *
 * NativePHP types printToPDF() as string, but Electron's print-to-pdf IPC
 * can return a JSON body with a null `result` (unavailable under
 * native:serve on some Windows ARM hosts, oversized data-URL HTML, etc.).
 * That becomes a TypeError. Callers must treat null as "use print preview".
 */
class NativePdfPrinter
{
    /**
     * @return string|null Base64-encoded PDF bytes, or null when NativePHP
     *                     print-to-pdf is unavailable or returned nothing.
     */
    public function printToPdf(string $html, array $settings = []): ?string
    {
        // This Electron plugin interpolates HTML into a data:text/html URL.
        // rawurlencode is required until NativePHP/desktop #53 (base64) ships.
        $payload = rawurlencode($html);

        try {
            $result = System::printToPDF($payload, $settings);
        } catch (TypeError $e) {
            return null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (! is_string($result) || $result === '') {
            return null;
        }

        return $result;
    }
}
