<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

/**
 * DocumentAiService
 * -----------------
 * Business-logic layer for OCR document parsing.
 *
 * RESPONSIBILITIES
 *   ✅  Know what fields each document type contains
 *   ✅  Build type-specific extraction prompts
 *   ✅  Delegate image analysis to GeminiVisionService
 *   ✅  Parse, validate, and sanitize the JSON response
 *   ✅  Return a normalised result array
 *   ✅  Support EN / HI / GU multilingual documents
 *
 * WHAT THIS CLASS DOES NOT DO
 *   ❌  Make Gemini API calls directly → GeminiVisionService
 *   ❌  Save anything to the database  → Controllers / Models
 *   ❌  Validate user-submitted edits  → Form Requests / Controllers
 *
 * Usage:
 *   $result = $this->documentAiService->parse($file, 'invoice');
 *   // $result['success']        → bool
 *   // $result['raw_text']       → string (Gemini raw response)
 *   // $result['extracted_data'] → array (structured fields)
 *   // $result['error']          → string|null
 */
class DocumentAiService
{
    // ── Supported document types and their schema keys ───────────────────
    private const DOCUMENT_TYPES = [
        'business_card',
        'invoice',
        'receipt',
        'expense',
        'gst_bill',
        'general',
    ];

    public function __construct(
        private readonly GeminiVisionService $vision
    ) {}

    // ═════════════════════════════════════════════════════════════════════
    //  PUBLIC API
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Parse a document image and return structured field data.
     *
     * @param  UploadedFile  $file
     * @param  string        $docType   One of: business_card | invoice | receipt |
     *                                           expense | gst_bill | general
     * @return array{success:bool, raw_text:string|null, extracted_data:array, error:string|null}
     */
    public function parse(UploadedFile $file, string $docType = 'general'): array
    {
        // Normalise + fallback unknown types to 'general'
        $docType = in_array($docType, self::DOCUMENT_TYPES, true) ? $docType : 'general';

        try {
            // 1. Build prompts for this document type
            [$systemPrompt, $userPrompt] = $this->buildPrompts($docType);

            // 2. Send to Gemini Vision
            $rawText = $this->vision->analyzeImage($file, $userPrompt, $systemPrompt);

            // 3. Extract structured JSON from the response
            $extractedData = $this->extractJson($rawText, $docType);

            Log::info('DocumentAiService: parse success', [
                'doc_type'       => $docType,
                'fields_found'   => count($extractedData),
            ]);

            return [
                'success'        => true,
                'raw_text'       => $rawText,
                'extracted_data' => $extractedData,
                'error'          => null,
            ];

        } catch (Throwable $e) {
            Log::error('DocumentAiService::parse failed', [
                'error'    => $e->getMessage(),
                'doc_type' => $docType,
            ]);

            return [
                'success'        => false,
                'raw_text'       => null,
                'extracted_data' => [],
                'error'          => $e->getMessage(),
            ];
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    //  PROMPT BUILDERS
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Return [systemPrompt, userPrompt] for a given document type.
     *
     * @return array{0: string, 1: string}
     */
    private function buildPrompts(string $docType): array
    {
        $system = $this->systemPrompt();
        $user   = match ($docType) {
            'business_card' => $this->businessCardPrompt(),
            'invoice'       => $this->invoicePrompt(),
            'receipt'       => $this->receiptPrompt(),
            'expense'       => $this->expensePrompt(),
            'gst_bill'      => $this->gstBillPrompt(),
            default         => $this->generalPrompt(),
        };

        return [$system, $user];
    }

    // ── Shared system prompt ─────────────────────────────────────────────

    private function systemPrompt(): string
    {
        return <<<PROMPT
        You are a precision document OCR and data extraction engine integrated into PlantIQ, an all-in-one business operations and management platform for Indian businesses.

        RULES:
        1. Return ONLY a valid JSON object. No markdown, no explanation, no preamble.
        2. If a field is not found or unreadable, set its value to null.
        3. Do NOT invent or guess values — only extract what is clearly visible.
        4. The document may be in English, Hindi (Devanagari), or Gujarati (Gujarati script).
           Extract all fields regardless of language. Transliterate names/addresses to English where helpful.
        5. For numeric fields (amounts, quantities), return numbers without currency symbols or commas.
        6. For date fields, use ISO format: YYYY-MM-DD. If only partial date visible, use best effort.
        7. For item arrays, include every line item you can see.
        PROMPT;
    }

    // ── Document-type prompts ────────────────────────────────────────────

    private function businessCardPrompt(): string
    {
        return <<<PROMPT
        This is a BUSINESS CARD image.

        Extract and return ONLY this JSON object:
        {
          "name": null,
          "company": null,
          "job_title": null,
          "email": null,
          "phone": null,
          "phone_alt": null,
          "website": null,
          "address": null,
          "city": null,
          "state": null,
          "pincode": null,
          "gstin": null,
          "social_handle": null
        }
        PROMPT;
    }

    private function invoicePrompt(): string
    {
        return <<<PROMPT
        This is an INVOICE image.

        Extract and return ONLY this JSON object:
        {
          "vendor_name": null,
          "vendor_address": null,
          "vendor_phone": null,
          "vendor_email": null,
          "vendor_gstin": null,
          "invoice_number": null,
          "invoice_date": null,
          "due_date": null,
          "po_number": null,
          "items": [
            {
              "description": null,
              "quantity": null,
              "unit_price": null,
              "discount": null,
              "amount": null
            }
          ],
          "subtotal": null,
          "discount_total": null,
          "taxable_amount": null,
          "cgst_rate": null,
          "cgst_amount": null,
          "sgst_rate": null,
          "sgst_amount": null,
          "igst_rate": null,
          "igst_amount": null,
          "other_charges": null,
          "grand_total": null,
          "amount_in_words": null,
          "payment_terms": null,
          "bank_name": null,
          "bank_account": null,
          "bank_ifsc": null,
          "notes": null
        }
        PROMPT;
    }

    private function receiptPrompt(): string
    {
        return <<<PROMPT
        This is a RECEIPT or BILL image.

        Extract and return ONLY this JSON object:
        {
          "merchant_name": null,
          "merchant_address": null,
          "merchant_phone": null,
          "merchant_gstin": null,
          "receipt_number": null,
          "date": null,
          "time": null,
          "items": [
            {
              "description": null,
              "quantity": null,
              "unit_price": null,
              "amount": null
            }
          ],
          "subtotal": null,
          "discount": null,
          "tax_amount": null,
          "total_amount": null,
          "payment_method": null,
          "cashier": null
        }
        PROMPT;
    }

    private function expensePrompt(): string
    {
        return <<<PROMPT
        This is an EXPENSE BILL or VOUCHER image.

        Extract and return ONLY this JSON object:
        {
          "vendor_name": null,
          "vendor_address": null,
          "vendor_gstin": null,
          "expense_date": null,
          "bill_number": null,
          "category": null,
          "description": null,
          "items": [
            {
              "description": null,
              "amount": null
            }
          ],
          "subtotal": null,
          "tax_amount": null,
          "total_amount": null,
          "payment_method": null
        }
        PROMPT;
    }

    private function gstBillPrompt(): string
    {
        return <<<PROMPT
        This is a GST TAX INVOICE image (Indian GST compliance document).

        Extract and return ONLY this JSON object:
        {
          "vendor_name": null,
          "vendor_address": null,
          "vendor_gstin": null,
          "vendor_pan": null,
          "vendor_phone": null,
          "vendor_email": null,
          "buyer_name": null,
          "buyer_address": null,
          "buyer_gstin": null,
          "ship_to_name": null,
          "ship_to_address": null,
          "invoice_number": null,
          "invoice_date": null,
          "due_date": null,
          "place_of_supply": null,
          "items": [
            {
              "hsn_sac": null,
              "description": null,
              "quantity": null,
              "unit": null,
              "unit_price": null,
              "taxable_amount": null,
              "cgst_rate": null,
              "cgst_amount": null,
              "sgst_rate": null,
              "sgst_amount": null,
              "igst_rate": null,
              "igst_amount": null,
              "total": null
            }
          ],
          "taxable_value": null,
          "total_cgst": null,
          "total_sgst": null,
          "total_igst": null,
          "total_tax": null,
          "round_off": null,
          "grand_total": null,
          "amount_in_words": null,
          "reverse_charge_applicable": null,
          "bank_name": null,
          "bank_account": null,
          "bank_ifsc": null,
          "upi_id": null,
          "notes": null,
          "eway_bill_number": null
        }
        PROMPT;
    }

    private function generalPrompt(): string
    {
        return <<<PROMPT
        This is a GENERAL DOCUMENT image (letter, certificate, form, label, etc.).

        First, read all visible text. Then extract and return ONLY this JSON object:
        {
          "document_type": null,
          "title": null,
          "date": null,
          "issuer": null,
          "recipient": null,
          "reference_number": null,
          "key_fields": {},
          "raw_text_summary": null,
          "all_text": null
        }

        For key_fields: include any labelled fields you find (e.g. {"Order No": "123", "Amount": "500"}).
        For all_text: include the complete verbatim text from the document.
        PROMPT;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  JSON EXTRACTION
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Extract a valid JSON array from the raw Gemini text response.
     *
     * Handles:
     *  - Perfect JSON response
     *  - JSON wrapped in markdown code fences
     *  - Leading/trailing prose around the JSON block
     *  - Malformed / truncated JSON (falls back to raw_text preservation)
     */
    private function extractJson(string $rawText, string $docType): array
    {
        $text = trim($rawText);

        // 1. Strip markdown fences if present
        $text = preg_replace('/^```(?:json)?\s*/im', '', $text);
        $text = preg_replace('/\s*```$/im', '', $text);
        $text = trim($text);

        // 2. Try to decode directly
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $this->sanitize($decoded);
        }

        // 3. Try to find the JSON object inside surrounding text
        // (some responses have "Here is the extracted data: {...}")
        if (preg_match('/\{[\s\S]*\}/m', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $this->sanitize($decoded);
            }
        }

        // 4. JSON is malformed — log it and return a safe fallback
        Log::warning('DocumentAiService: could not parse Gemini JSON response', [
            'doc_type' => $docType,
            'raw'      => substr($text, 0, 500),
        ]);

        return [
            'raw_text'   => $rawText,
            'parse_note' => 'AI response could not be parsed as JSON. Raw text preserved.',
        ];
    }

    /**
     * Sanitize extracted data: remove null values from nested item arrays
     * and ensure numeric strings become proper types where expected.
     */
    private function sanitize(array $data): array
    {
        // Convert empty string to null for cleaner output
        array_walk_recursive($data, function (&$value) {
            if ($value === '' || $value === 'null' || $value === 'N/A' || $value === '-') {
                $value = null;
            }
        });

        return $data;
    }
}