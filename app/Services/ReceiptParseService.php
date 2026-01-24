<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ReceiptParseService
{
    public function parseReceiptTextToExpensePrefill(string $rawText, string $timezone = 'Asia/Jakarta'): array
    {
        $apiKey = config('services.openai.key');
        $model = config('services.openai.model', 'gpt-4o-mini');

        if (!$apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY belum diset.');
        }

        $system = <<<SYS
You extract structured data from shopping receipts.
The receipt text may be Indonesian or English (or mixed).
Return STRICT JSON only (no markdown).
SYS;

        $user = <<<USR
Extract:
- merchant (string or null)
- occurred_at (string in ISO 8601 if possible, otherwise null)
- total (integer, IDR; prefer "TOTAL BAYAR/GRAND TOTAL/TOTAL/AMOUNT DUE")
- items: array of {name, qty} (qty integer if detectable else 1). Keep names concise.
Then build description in Indonesian:
"{merchant} — item1 xqty; item2 xqty; ..."

Rules:
- Use timezone "{$timezone}" when interpreting date/time.
- If time is missing, set time to 12:00.
- If multiple totals appear, choose the final payable amount.
- Output JSON with keys: merchant, occurred_at, total, items, description.

Receipt text:
---
{$rawText}
---
USR;

        /** @var Response $resp */
        $resp = Http::timeout(45)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.1,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        $resp->throw();

        $content = $resp->json('choices.0.message.content');
        $data = json_decode((string) $content, true);

        if (!is_array($data)) {
            throw new \RuntimeException('LLM tidak mengembalikan JSON yang valid.');
        }

        return [
            'merchant' => $data['merchant'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? null,
            'total' => isset($data['total']) ? (int) $data['total'] : null,
            'items' => $data['items'] ?? [],
            'description' => $data['description'] ?? null,
        ];
    }
}
