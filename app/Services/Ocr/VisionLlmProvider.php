<?php

namespace App\Services\Ocr;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * VisionLlmProvider — Implementasi OCR menggunakan Vision LLM Server-Side API.
 * Mendukung Anthropic (Claude), OpenAI (GPT-4o), dan Google Gemini.
 * Sesuai panduan docs/06_SKILL_OCR.md.
 */
class VisionLlmProvider implements OcrProvider
{
    public function __construct(
        protected string $provider = 'anthropic',
        protected ?string $apiKey = null,
        protected string $model = 'claude-3-5-sonnet-20241022',
        protected ?string $systemPromptPath = null
    ) {
        $this->systemPromptPath = $systemPromptPath ?? resource_path('prompts/ocr_system.txt');
    }

    public function extract(string $imagePath, array $sectionSchema): OcrResult
    {
        if (! file_exists($imagePath)) {
            return OcrResult::failure("Berkas gambar tidak ditemukan pada path: {$imagePath}");
        }

        // Cek ketersediaan API key
        if (empty($this->apiKey)) {
            $providerName = ucfirst($this->provider);
            return OcrResult::failure(
                "Kunci API {$providerName} belum dikonfigurasi pada .env. " .
                "Silakan atur kunci API atau gunakan pengisian manual."
            );
        }

        $systemPrompt = $this->loadSystemPrompt();
        $userPrompt = $this->buildUserPrompt($sectionSchema);

        $imageData = file_get_contents($imagePath);
        $mimeType = $this->detectMimeType($imagePath);

        try {
            $response = match ($this->provider) {
                'anthropic' => $this->callAnthropic($imageData, $mimeType, $systemPrompt, $userPrompt),
                'openai'    => $this->callOpenAi($imageData, $mimeType, $systemPrompt, $userPrompt),
                'gemini'    => $this->callGemini($imageData, $mimeType, $systemPrompt, $userPrompt),
                default     => throw new Exception("Provider Vision LLM '{$this->provider}' tidak didukung."),
            };

            return $this->parseResponse($response, $sectionSchema);
        } catch (Exception $e) {
            Log::error("VisionLlmProvider error [{$this->provider}]: " . $e->getMessage());
            return OcrResult::failure("Gagal menghubungi layanan AI ({$this->provider}): " . $e->getMessage());
        }
    }

    protected function loadSystemPrompt(): string
    {
        if (file_exists($this->systemPromptPath)) {
            return trim(file_get_contents($this->systemPromptPath));
        }

        return "Anda membaca foto formulir pelaporan kesehatan lansia Puskesmas berisi tabel angka tulisan tangan. " .
            "Ekstrak angka ke JSON sesuai skema. Nilai harus integer >= 0 atau null jika kosong/tak terbaca.";
    }

    protected function buildUserPrompt(array $schema): string
    {
        $section = $schema['section'] ?? 'kunjungan';
        $kelurahans = $schema['kelurahans'] ?? ['PAYOLANSEK', 'BULBA', 'PAKAN SINAYAN', 'KUBU GADANG', 'KOTO TANGAH', 'TALANG'];
        $fields = $schema['fields'] ?? [];
        $fieldMeta = $schema['field_meta'] ?? [];

        $fieldListText = '';
        foreach ($fields as $field) {
            $label = $fieldMeta[$field] ?? $field;
            $fieldListText .= "- {$field} ({$label})\n";
        }

        $kelurahanListText = implode(', ', $kelurahans);

        return <<<PROMPT
Bagian Formulir: {$section}
Daftar Kelurahan (baris 1 sampai 6): {$kelurahanListText}

Daftar field yang harus diekstrak (berurutan sesuai kolom):
{$fieldListText}

Ekstrak tabel pada foto ke JSON persis dengan format berikut:
{
  "rows": [
    {
      "row_index": 1,
      "kelurahan_name": "PAYOLANSEK",
      "fields": {
        "field_name": { "value": 12, "confidence": 0.95 }
      }
    }
  ],
  "notes": "catatan jika foto miring atau sel tak terbaca"
}
Jangan tambahkan teks apapun di luar blok JSON.
PROMPT;
    }

    protected function detectMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'png'        => 'image/png',
            'webp'       => 'image/webp',
            'jpg', 'jpeg'=> 'image/jpeg',
            default      => 'image/jpeg',
        };
    }

    /**
     * Panggilan ke API Anthropic Claude
     */
    protected function callAnthropic(string $imageData, string $mimeType, string $systemPrompt, string $userPrompt): array
    {
        $base64 = base64_encode($imageData);

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model ?: 'claude-3-5-sonnet-20241022',
            'max_tokens' => 4000,
            'system'     => $systemPrompt,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mimeType,
                                'data'       => $base64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $userPrompt,
                        ],
                    ],
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception("HTTP {$response->status()}: " . $response->body());
        }

        $body = $response->json();
        $text = $body['content'][0]['text'] ?? '';
        $tokens = ($body['usage']['input_tokens'] ?? 0) + ($body['usage']['output_tokens'] ?? 0);

        return [
            'text'   => $text,
            'tokens' => $tokens,
            'raw'    => $response->body(),
        ];
    }

    /**
     * Panggilan ke API OpenAI Chat Completions
     */
    protected function callOpenAi(string $imageData, string $mimeType, string $systemPrompt, string $userPrompt): array
    {
        $base64 = base64_encode($imageData);
        $imageUrl = "data:{$mimeType};base64,{$base64}";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => $this->model ?: 'gpt-4o',
            'max_tokens'  => 4000,
            'temperature' => 0.1,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                [
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $userPrompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $imageUrl, 'detail' => 'high']],
                    ],
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception("HTTP {$response->status()}: " . $response->body());
        }

        $body = $response->json();
        $text = $body['choices'][0]['message']['content'] ?? '';
        $tokens = $body['usage']['total_tokens'] ?? 0;

        return [
            'text'   => $text,
            'tokens' => $tokens,
            'raw'    => $response->body(),
        ];
    }

    /**
     * Panggilan ke API Google Gemini
     */
    protected function callGemini(string $imageData, string $mimeType, string $systemPrompt, string $userPrompt): array
    {
        $base64 = base64_encode($imageData);
        $model = $this->model ?: 'gemini-1.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($url, [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data'     => $base64,
                            ],
                        ],
                        ['text' => $userPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature'     => 0.1,
                'maxOutputTokens' => 4000,
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception("HTTP {$response->status()}: " . $response->body());
        }

        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $tokens = $body['usageMetadata']['totalTokenCount'] ?? 0;

        return [
            'text'   => $text,
            'tokens' => $tokens,
            'raw'    => $response->body(),
        ];
    }

    /**
     * Parse keluaran JSON dari model dan validasi skema ketat
     */
    public function parseResponse(array $apiResponse, array $sectionSchema): OcrResult
    {
        $text = $apiResponse['text'] ?? '';
        $tokens = $apiResponse['tokens'] ?? null;
        $raw = $apiResponse['raw'] ?? $text;

        // Ambil blok JSON di antara kurung kurawal pertama dan terakhir
        $jsonStr = $this->extractJsonString($text);

        if (! $jsonStr) {
            return OcrResult::failure("Format respons model tidak valid atau tidak memuat JSON.", $raw);
        }

        $data = json_decode($jsonStr, true);
        if (! is_array($data) || ! isset($data['rows']) || ! is_array($data['rows'])) {
            return OcrResult::failure("Struktur JSON tidak memuat kunci 'rows' yang valid.", $raw);
        }

        $allowedFields = array_flip($sectionSchema['fields'] ?? []);
        $sanitizedRows = [];

        foreach ($data['rows'] as $rIdx => $rawRow) {
            $rowIndex = (int) ($rawRow['row_index'] ?? ($rIdx + 1));
            $kelurahanName = isset($rawRow['kelurahan_name']) ? (string) $rawRow['kelurahan_name'] : null;

            $fields = [];
            foreach ($rawRow['fields'] ?? [] as $fieldName => $fieldData) {
                // Abaikan field di luar skema
                if (! isset($allowedFields[$fieldName])) {
                    continue;
                }

                $val = null;
                $conf = 0.0;

                if (is_array($fieldData)) {
                    $rawVal = $fieldData['value'] ?? null;
                    if ($rawVal !== null && is_numeric($rawVal) && (int) $rawVal >= 0) {
                        $val = (int) $rawVal;
                    }

                    $rawConf = $fieldData['confidence'] ?? 0.0;
                    if (is_numeric($rawConf)) {
                        $conf = max(0.0, min(1.0, (float) $rawConf));
                    }
                } elseif (is_numeric($fieldData) && (int) $fieldData >= 0) {
                    $val = (int) $fieldData;
                    $conf = 0.9;
                }

                $fields[$fieldName] = [
                    'value'      => $val,
                    'confidence' => $val !== null ? $conf : 0.0,
                ];
            }

            // Pastikan seluruh field yang diminta ada dalam baris (meski model melewatkannya)
            foreach (array_keys($allowedFields) as $f) {
                if (! isset($fields[$f])) {
                    $fields[$f] = [
                        'value'      => null,
                        'confidence' => 0.0,
                    ];
                }
            }

            $sanitizedRows[] = [
                'row_index'      => $rowIndex,
                'kelurahan_name' => $kelurahanName,
                'fields'         => $fields,
            ];
        }

        return OcrResult::success(
            rows: $sanitizedRows,
            notes: $data['notes'] ?? null,
            tokensUsed: $tokens,
            rawResponse: $raw
        );
    }

    /**
     * Ekstrak teks JSON dari respons teks yang mungkin memuat markdown.
     */
    protected function extractJsonString(string $text): ?string
    {
        // 1. Cek bila dibungkus ```json ... ```
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*\})\s*```/i', $text, $matches)) {
            return trim($matches[1]);
        }

        // 2. Ambil substring antara { pertama dan } terakhir
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end !== false && $end > $start) {
            return trim(substr($text, $start, $end - $start + 1));
        }

        return null;
    }
}
