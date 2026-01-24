<?php

namespace App\Http\Controllers;

use App\Models\TransactionAttachment;
use App\Models\User;
use App\Services\GoogleVisionOcrService;
use App\Services\ReceiptParseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReceiptScanController extends Controller
{
    public function __invoke(Request $request, GoogleVisionOcrService $ocr, ReceiptParseService $parser)
    {
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $validated = $request->validate([
            'receipt_image' => ['required', 'image', 'max:5120'],
            'timezone' => ['nullable', 'string', 'max:64']
        ]);

        $timezone = $validated['timezone'] ?? config('app.timezone', 'Asia/Jakarta');

        $file = $request->file('receipt_image');
        $originalName = $file->getClientOriginalName();
        $mime = $file->getMimeType();
        $size = $file->getSize();

        $disk = 'private';
        $path = $file->storeAs(
            'transaction_attachments/' . now()->format('Y/m'),
            Str::uuid()->toString() . '.' . $file->getClientOriginalExtension(),
            $disk
        );

        $attachment = TransactionAttachment::create([
            'transaction_id' => null,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $originalName,
            'mime' => $mime,
            'size' => $size,
            'uploaded_by' => $user->id,
        ]);

        // OCR
        $rawText = $ocr->extractTextFromStorageImage($disk, $path);

        if (blank($rawText)) {
            return response()->json([
                'message' => 'OCR gagal membaca teks dari struk. Coba foto lebih jelas.',
            ], 422);
        }

        // Parse to structured data
        $parsed = $parser->parseReceiptTextToExpensePrefill(
            rawText: $rawText,
            timezone: $timezone
        );

        // Normalize occurred_at for HTML datetime-local (YYYY-MM-DDTHH:MM)
        $occurredAt = null;
        if (!empty($parsed['occurred_at'])) {
            try {
                $occurredAt = Carbon::parse($parsed['occurred_at'], $timezone)
                    ->setTimezone($timezone)
                    ->format('Y-m-d\TH:i');
            } catch (\Throwable $e) {
                $occurredAt = null;
            }
        }

        return response()->json([
            'attachment_id' => $attachment->id,
            'prefill' => [
                'occurred_at' => $occurredAt,
                'amount' => $parsed['total'] ?? null,
                'description' => $parsed['description'] ?? null,
                'merchant' => $parsed['merchant'] ?? null,
            ],
            'debug' => [
                // Anda bisa matikan ini kalau tidak mau kirim OCR text ke browser
                'raw_text' => $rawText,
            ],
        ]);
    }
}
