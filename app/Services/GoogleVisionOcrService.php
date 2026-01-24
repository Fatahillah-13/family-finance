<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GoogleVisionOcrService
{
    public function extractTextFromStorageImage(string $disk, string $path): string
    {
        $apiKey = config('services.google_vision.key');

        if (!$apiKey) {
            throw new \RuntimeException('GOOGLE_CLOUD_VISION_API_KEY belum diset.');
        }

        $bytes = Storage::disk($disk)->get($path);
        $base64 = base64_encode($bytes);

        $url = 'https://vision.googleapis.com/v1/images:annotate?key=' . urlencode($apiKey);

        $payload = [
            'requests' => [
                [
                    'image' => ['content' => $base64],
                    'features' => [
                        ['type' => 'DOCUMENT_TEXT_DETECTION'],
                    ],
                ],
            ],
        ];

        /** @var Response $resp */
        $resp = Http::timeout(30)->post($url, $payload);
        $resp->throw();

        $json = $resp->json();
        $text = $json['responses'][0]['fullTextAnnotation']['text'] ?? '';

        $text = preg_replace("/[ \t]+/", " ", $text);
        $text = preg_replace("/\r\n|\r/", "\n", $text);

        return trim((string) $text);
    }
}
