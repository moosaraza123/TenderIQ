<?php

namespace App\Modules\Scraper\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class PdfExtractorService
{
    public function extractFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);
            if (! $response->successful()) {
                return null;
            }

            $tmpFile = tempnam(sys_get_temp_dir(), 'tr_pdf_');
            file_put_contents($tmpFile, $response->body());

            $text = $this->extractFromFile($tmpFile);

            @unlink($tmpFile);

            return $text;
        } catch (\Throwable $e) {
            Log::warning("PDF download failed: {$url}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function extractFromFile(string $path): ?string
    {
        $text = $this->tryPdfParser($path);
        if ($text && strlen(trim($text)) > 100) {
            return $text;
        }

        return $this->tryPdfToText($path);
    }

    private function tryPdfParser(string $path): ?string
    {
        try {
            $parser = new Parser();
            $pdf    = $parser->parseFile($path);
            return $pdf->getText();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function tryPdfToText(string $path): ?string
    {
        $escapedPath = escapeshellarg($path);
        $output = shell_exec("pdftotext {$escapedPath} - 2>/dev/null");
        return $output ?: null;
    }
}
