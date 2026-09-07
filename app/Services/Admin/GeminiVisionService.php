<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

/**
 * GeminiVisionService
 * -------------------
 * Low-level Gemini multimodal HTTP client.
 *
 * RESPONSIBILITIES
 *   ✅  Convert UploadedFile → base64 inlineData
 *   ✅  Build Gemini multimodal request (image + text parts)
 *   ✅  Execute HTTP request with retry on transient failures
 *   ✅  Extract & clean text from Gemini response
 *   ✅  Guard shared-hosting memory / file-size limits
 *   ✅  Multilingual support (English / Hindi / Gujarati)
 *
 * WHAT THIS CLASS DOES NOT DO
 *   ❌  Parse business-domain fields  →  DocumentAiService
 *   ❌  Save to database              →  Controllers / Models
 *   ❌  Chatbot / text-only AI        →  AiService
 *
 * Usage:
 *   $text = $this->visionService->analyzeImage($file, 'Extract all text from this document.');
 */
class GeminiVisionService
{
    // ── Config ────────────────────────────────────────────────────────────
    private const MAX_IMAGE_BYTES = 4_000_000;   // 4 MB — safe for shared hosting
    private const REQUEST_TIMEOUT = 90;           // seconds — Vision is slower than text-only
    private const MAX_RETRIES     = 1;            // 1 retry on transient 5xx errors
    private const RETRY_DELAY_MS  = 2000;         // 2 s between retries

    private const SUPPORTED_MIMES = [
        'image/jpeg' => 'image/jpeg',
        'image/jpg'  => 'image/jpeg',
        'image/png'  => 'image/png',
        'image/webp' => 'image/webp',
        'image/gif'  => 'image/gif',
    ];

    private string $apiKey;
    private string $baseUrl;
    private string $ocrModel;

    public function __construct()
    {
        $this->apiKey   = (string) config('services.gemini.api_key');
        $this->baseUrl  = rtrim((string) config('services.gemini.base_url'), '/');
        // Dedicated OCR model — flash (not lite) for better Vision quality
        $this->ocrModel = (string) config('services.gemini.ocr_model', 'gemini-2.5-flash');
    }

    // ═════════════════════════════════════════════════════════════════════
    //  PUBLIC API
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Send an image + prompt to Gemini Vision and return the raw text response.
     *
     * @param  UploadedFile  $file
     * @param  string        $prompt         User-facing instruction sent with the image.
     * @param  string|null   $systemPrompt   Optional system-level instruction.
     * @return string  Raw text from Gemini (may be JSON, plain text, etc.)
     *
     * @throws Exception  On validation failure or API error.
     */
    public function analyzeImage(
        UploadedFile $file,
        string $prompt,
        ?string $systemPrompt = null
    ): string {
        // 1. Guard: API key present
        if (empty($this->apiKey)) {
            throw new Exception('GEMINI_API_KEY is missing in .env.');
        }

        // 2. Guard: File size
        if ($file->getSize() > self::MAX_IMAGE_BYTES) {
            $mb = round(self::MAX_IMAGE_BYTES / 1_000_000);
            throw new Exception("Image exceeds {$mb} MB limit. Please compress before uploading.");
        }

        // 3. Guard: MIME type
        $mimeType = $this->resolveMime($file);

        // 4. Build payload
        $payload = $this->buildPayload($file, $mimeType, $prompt, $systemPrompt);

        // 5. Execute with retry
        return $this->executeWithRetry($payload);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  REQUEST BUILDER
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Build the Gemini multimodal request payload.
     *
     * Gemini Vision structure:
     *   contents[0].parts[0] = inlineData { mimeType, data (base64) }
     *   contents[0].parts[1] = text (the user prompt)
     *   systemInstruction    = optional system text
     */
    private function buildPayload(
        UploadedFile $file,
        string $mimeType,
        string $prompt,
        ?string $systemPrompt
    ): array {
        // Encode image to base64 — memory-safe for files up to MAX_IMAGE_BYTES
        $base64 = base64_encode($file->getContent());

        $payload = [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        // Part 1: the image
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data'     => $base64,
                            ],
                        ],
                        // Part 2: the text instruction
                        [
                            'text' => trim($prompt),
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature'     => 0.0,   // deterministic for OCR
                'topP'            => 0.95,
                'maxOutputTokens' => 2048,
            ],
        ];

        // Attach system instruction if provided
        if (!empty($systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => trim($systemPrompt)]],
            ];
        }

        return $payload;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  HTTP EXECUTION
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Execute the Gemini Vision HTTP request with retry on transient 5xx errors.
     */
    private function executeWithRetry(array $payload): string
    {
        $url      = $this->baseUrl . '/' . $this->ocrModel . ':generateContent?key=' . $this->apiKey;
        $attempts = 0;
        $lastError = null;

        while ($attempts <= self::MAX_RETRIES) {
            try {
                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                // Transient server error — retry
                if ($response->serverError() && $attempts < self::MAX_RETRIES) {
                    $attempts++;
                    Log::warning('GeminiVisionService: 5xx error, retrying…', [
                        'attempt' => $attempts,
                        'status'  => $response->status(),
                    ]);
                    usleep(self::RETRY_DELAY_MS * 1000);
                    continue;
                }

                // Client error (4xx) — fail immediately, no retry
                if ($response->failed()) {
                    $body     = $response->json();
                    $errorMsg = $body['error']['message']
                        ?? ('Gemini Vision API error: HTTP ' . $response->status());

                    Log::error('GeminiVisionService: HTTP error', [
                        'status'  => $response->status(),
                        'message' => $errorMsg,
                        'model'   => $this->ocrModel,
                    ]);

                    throw new Exception($errorMsg);
                }

                // Success — extract text
                return $this->extractText($response->json());

            } catch (Exception $e) {
                $lastError = $e;
                // Only retry on non-explicit Exception (e.g. connection timeout)
                if ($attempts >= self::MAX_RETRIES) {
                    break;
                }
                $attempts++;
                usleep(self::RETRY_DELAY_MS * 1000);
            }
        }

        throw $lastError ?? new Exception('Gemini Vision request failed after retries.');
    }

    /**
     * Extract the text string from a Gemini API response array.
     */
    private function extractText(array $data): string
    {
        $candidate = $data['candidates'][0] ?? null;

        if (!$candidate) {
            $blockReason = $data['promptFeedback']['blockReason'] ?? null;
            throw new Exception(
                $blockReason
                    ? "Gemini Vision blocked request: {$blockReason}"
                    : 'Gemini Vision returned no candidates.'
            );
        }

        $finishReason = $candidate['finishReason'] ?? 'STOP';

        if ($finishReason === 'SAFETY') {
            throw new Exception('Gemini Vision response blocked by safety filter.');
        }

        $text = $candidate['content']['parts'][0]['text'] ?? null;

        if ($text === null || $text === '') {
            Log::error('GeminiVisionService: empty content', ['response' => $data]);
            throw new Exception('Gemini Vision returned an empty response. The image may be unreadable.');
        }

        // Strip any accidental markdown fences (```json … ```)
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/im', '', $text);
        $text = preg_replace('/\s*```$/im', '', $text);

        return trim($text);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Resolve a safe MIME type string for the Gemini inlineData field.
     *
     * @throws Exception  For unsupported formats.
     */
    private function resolveMime(UploadedFile $file): string
    {
        $rawMime = strtolower($file->getMimeType() ?? '');

        if (isset(self::SUPPORTED_MIMES[$rawMime])) {
            return self::SUPPORTED_MIMES[$rawMime];
        }

        // Fallback: try extension-based detection
        $ext = strtolower($file->getClientOriginalExtension());
        $extMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

        if (isset($extMap[$ext])) {
            return $extMap[$ext];
        }

        throw new Exception("Unsupported image type: {$rawMime}. Allowed: JPG, PNG, WEBP.");
    }
}