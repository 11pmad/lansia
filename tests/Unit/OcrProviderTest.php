<?php

namespace Tests\Unit;

use App\Services\Ocr\MockOcrProvider;
use App\Services\Ocr\OcrResult;
use App\Services\Ocr\VisionLlmProvider;
use Tests\TestCase;

class OcrProviderTest extends TestCase
{
    /**
     * Memastikan MockOcrProvider mengembalikan data ekstraksi yang sesuai dengan skema.
     */
    public function test_mock_ocr_provider_generates_schema_compliant_data(): void
    {
        $provider = new MockOcrProvider();

        $schema = [
            'section'    => 'kunjungan_dalam',
            'fields'     => ['in_a6069_l_baru', 'in_a6069_p_baru'],
            'kelurahans' => ['PAYOLANSEK', 'BULBA'],
        ];

        $tempImage = tempnam(sys_get_temp_dir(), 'test_img_') . '.jpg';
        file_put_contents($tempImage, 'dummy_image_data');

        try {
            $result = $provider->extract($tempImage, $schema);

            $this->assertTrue($result->isSuccess);
            $this->assertCount(2, $result->rows);

            $payoRow = collect($result->rows)->firstWhere('kelurahan_name', 'PAYOLANSEK');
            $this->assertNotNull($payoRow);

            // Sesuai nilai uji emas pada mock
            $this->assertSame(40, $payoRow['fields']['in_a6069_p_baru']['value']);
            $this->assertSame(16, $payoRow['fields']['in_a6069_l_baru']['value']);
            $this->assertGreaterThanOrEqual(0.9, $payoRow['fields']['in_a6069_p_baru']['confidence']);
        } finally {
            @unlink($tempImage);
        }
    }

    /**
     * Memastikan MockOcrProvider menangani mode kegagalan (failure mode).
     */
    public function test_mock_ocr_provider_can_simulate_failure(): void
    {
        $provider = new MockOcrProvider();
        $provider->setShouldFail(true, 'Koneksi ke API terputus.');

        $result = $provider->extract('non_existent.jpg', ['fields' => ['f1']]);

        $this->assertFalse($result->isSuccess);
        $this->assertSame('Koneksi ke API terputus.', $result->errorMessage);
    }

    /**
     * Memastikan VisionLlmProvider mengembalikan pesan ramah jika kunci API kosong.
     */
    public function test_vision_llm_provider_fails_gracefully_without_api_key(): void
    {
        $provider = new VisionLlmProvider(provider: 'anthropic', apiKey: null);

        $tempImage = tempnam(sys_get_temp_dir(), 'test_img_') . '.jpg';
        file_put_contents($tempImage, 'dummy');

        try {
            $result = $provider->extract($tempImage, ['fields' => ['in_a6069_l_baru']]);

            $this->assertFalse($result->isSuccess);
            $this->assertStringContainsString('Kunci API Anthropic belum dikonfigurasi', $result->errorMessage);
        } finally {
            @unlink($tempImage);
        }
    }

    /**
     * Memastikan parser respons VisionLlmProvider memvalidasi skema ketat:
     * - Mengabaikan field di luar skema.
     * - Mengonversi teks atau angka negatif menjadi null dengan confidence 0.0.
     * - Menangani respons yang dibungkus markdown ```json ... ```.
     */
    public function test_vision_llm_parser_enforces_strict_schema(): void
    {
        $provider = new VisionLlmProvider(provider: 'anthropic', apiKey: 'test_key');

        $rawResponseText = <<<JSON
```json
{
  "rows": [
    {
      "row_index": 1,
      "kelurahan_name": "PAYOLANSEK",
      "fields": {
        "in_a6069_l_baru": { "value": 16, "confidence": 0.94 },
        "in_a6069_p_baru": { "value": 40, "confidence": 0.98 },
        "unwanted_column": { "value": 999, "confidence": 0.99 },
        "in_a4559_l_baru": { "value": "rusak/tidak jelas", "confidence": 0.1 }
      }
    }
  ],
  "notes": "Foto sedikit miring pada sudut kanan"
}
```
JSON;

        $schema = [
            'section' => 'kunjungan_dalam',
            'fields'  => ['in_a6069_l_baru', 'in_a6069_p_baru', 'in_a4559_l_baru', 'in_a4559_p_baru'],
        ];

        $result = $provider->parseResponse(['text' => $rawResponseText, 'tokens' => 350], $schema);

        $this->assertTrue($result->isSuccess);
        $this->assertSame(350, $result->tokensUsed);
        $this->assertSame('Foto sedikit miring pada sudut kanan', $result->notes);

        $row = $result->rows[0];
        $this->assertSame('PAYOLANSEK', $row['kelurahan_name']);

        // Field dalam skema
        $this->assertSame(16, $row['fields']['in_a6069_l_baru']['value']);
        $this->assertSame(0.94, $row['fields']['in_a6069_l_baru']['confidence']);
        $this->assertSame(40, $row['fields']['in_a6069_p_baru']['value']);

        // Field di luar skema harus dibuang
        $this->assertArrayNotHasKey('unwanted_column', $row['fields']);

        // Nilai bukan integer harus menjadi null dan confidence 0.0
        $this->assertNull($row['fields']['in_a4559_l_baru']['value']);
        $this->assertSame(0.0, $row['fields']['in_a4559_l_baru']['confidence']);

        // Field yang dilewatkan oleh model harus tetap diisi null
        $this->assertArrayHasKey('in_a4559_p_baru', $row['fields']);
        $this->assertNull($row['fields']['in_a4559_p_baru']['value']);
        $this->assertSame(0.0, $row['fields']['in_a4559_p_baru']['confidence']);
    }
}
