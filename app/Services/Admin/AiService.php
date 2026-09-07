<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AiService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;

    /**
     * Token usage from the most recent ask() call.
     * @var array{prompt:int,output:int,total:int}
     */
    protected array $lastUsage = ['prompt' => 0, 'output' => 0, 'total' => 0];

    /**
     * Cumulative token usage since the last resetUsage().
     * A single chatbot turn may call ask() twice (intent + format); this lets
     * the caller record the combined cost with one read.
     * @var array{prompt:int,output:int,total:int}
     */
    protected array $totalUsage = ['prompt' => 0, 'output' => 0, 'total' => 0];

    public function __construct()
    {
        $this->apiKey       = (string) config('services.gemini.api_key');
        $this->baseUrl      = rtrim((string) config('services.gemini.base_url'), '/');
        $this->defaultModel = (string) config('services.gemini.model');
    }

    /**
     * Tokens used by the most recent ask() call.
     * @return array{prompt:int,output:int,total:int}
     */
    public function lastUsage(): array
    {
        return $this->lastUsage;
    }

    /**
     * Cumulative tokens used since the last resetUsage().
     * @return array{prompt:int,output:int,total:int}
     */
    public function totalUsage(): array
    {
        return $this->totalUsage;
    }

    /**
     * Zero the cumulative counter. Call once at the start of each chatbot turn.
     */
    public function resetUsage(): void
    {
        $this->totalUsage = ['prompt' => 0, 'output' => 0, 'total' => 0];
    }

    /**
     * Record usage from a Gemini response payload's usageMetadata block.
     */
    protected function recordUsage(array $data): void
    {
        $meta = $data['usageMetadata'] ?? [];

        $prompt = (int) ($meta['promptTokenCount'] ?? 0);
        $output = (int) ($meta['candidatesTokenCount'] ?? 0);
        $total  = (int) ($meta['totalTokenCount'] ?? ($prompt + $output));

        $this->lastUsage = ['prompt' => $prompt, 'output' => $output, 'total' => $total];

        $this->totalUsage['prompt'] += $prompt;
        $this->totalUsage['output'] += $output;
        $this->totalUsage['total']  += $total;
    }

    /**
     * Send a prompt to Gemini and return the text response.
     *
     * @throws Exception
     */
    public function ask(
        string  $prompt,
        ?string $systemPrompt = null,
        ?string $model        = null,
        bool    $expectJson   = false
    ): string {

        if (empty($this->apiKey)) {
            throw new Exception('GEMINI_API_KEY is missing. Please add it to your .env file.');
        }

        $selectedModel = $model ?? $this->defaultModel;

        /*
        |----------------------------------------------------------------------
        | Build Gemini-native request body
        |----------------------------------------------------------------------
        | Gemini uses "systemInstruction" (separate field) instead of mixing
        | the system prompt into the user message. This gives much better
        | instruction-following, especially for JSON-only responses.
        */

        $payload = [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [['text' => trim($prompt)]],
                ],
            ],
            'generationConfig' => [
                'temperature'     => $expectJson ? 0.0 : 0.2,
                'topP'            => 0.9,
                'maxOutputTokens' => $expectJson ? 500 : 1000,
            ],
        ];

        // Attach system instruction via the correct Gemini field
        if (!empty($systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => trim($systemPrompt)]],
            ];
        }

        /*
        |----------------------------------------------------------------------
        | DO NOT use responseMimeType: application/json
        |----------------------------------------------------------------------
        | It is unsupported on some Gemini model versions and causes a silent
        | 400 error that gets caught and returns module:unknown.
        | Instead: use temperature=0 + a strict system prompt that says
        | "Return ONLY valid JSON". This works reliably across all models.
        */

        /*
        |----------------------------------------------------------------------
        | Gemini REST endpoint:
        |   POST {base_url}/{model}:generateContent?key={api_key}
        |----------------------------------------------------------------------
        */
        $url = $this->baseUrl . '/' . $selectedModel . ':generateContent?key=' . $this->apiKey;

        try {
            $response = Http::timeout(120)->post($url, $payload);

            /*
            |------------------------------------------------------------------
            | HTTP-level error (4xx / 5xx)
            |------------------------------------------------------------------
            */
            if ($response->failed()) {
                $body = $response->json();

                // Surface the real Gemini error message (e.g. "API key not valid")
                $errorMsg = $body['error']['message']
                    ?? ('Gemini API Error: HTTP ' . $response->status());

                Log::error('Gemini HTTP Error', [
                    'status'  => $response->status(),
                    'message' => $errorMsg,
                    'model'   => $selectedModel,
                ]);

                throw new Exception($errorMsg);
            }

            $data = $response->json();

            // Capture token usage immediately — tokens are billed even when the
            // candidate is later rejected (safety/empty), so record before any
            // further validation can throw.
            $this->recordUsage($data);

            Log::debug('Gemini Raw Response', ['response' => $data]);

            /*
            |------------------------------------------------------------------
            | Extract text from response
            |------------------------------------------------------------------
            | Gemini response structure:
            | candidates[0].content.parts[0].text
            |
            | Possible failure modes:
            | - candidates empty (SAFETY block, RECITATION, etc.)
            | - finishReason != STOP
            */

            $candidate = $data['candidates'][0] ?? null;

            if (!$candidate) {
                // Check for prompt feedback (e.g. safety block before generating)
                $blockReason = $data['promptFeedback']['blockReason'] ?? null;
                throw new Exception(
                    $blockReason
                        ? "Request blocked by Gemini safety filter: {$blockReason}"
                        : 'Gemini returned no candidates.'
                );
            }

            $finishReason = $candidate['finishReason'] ?? 'STOP';

            if ($finishReason === 'SAFETY') {
                throw new Exception('Response blocked by Gemini safety filter.');
            }

            $content = $candidate['content']['parts'][0]['text'] ?? null;

            if ($content === null || $content === '') {
                Log::error('Gemini Empty Content', $data);
                throw new Exception('Gemini returned an empty response.');
            }

            /*
            |------------------------------------------------------------------
            | Strip accidental markdown code fences
            |------------------------------------------------------------------
            | Even with responseMimeType=application/json some models still
            | wrap the output in ```json ... ```.
            */
            $content = trim($content);
            $content = preg_replace('/^```json\s*/im', '', $content);
            $content = preg_replace('/\s*```$/im', '', $content);

            return trim($content);

        } catch (Exception $e) {
            Log::error('AiService Exception', [
                'message' => $e->getMessage(),
                'model'   => $selectedModel,
            ]);

            throw $e;
        }
    }
}
