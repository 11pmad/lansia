<?php

namespace App\Services\Ocr;

class MockOcrProvider implements OcrProvider
{
    protected ?OcrResult $customResult = null;
    protected bool $shouldFail = false;
    protected string $failMessage = 'Mock OCR extraction failure';

    /**
     * Set a custom OcrResult to be returned on the next extract call.
     */
    public function setCustomResult(?OcrResult $result): self
    {
        $this->customResult = $result;
        return $this;
    }

    /**
     * Configure the mock to simulate a provider error.
     */
    public function setShouldFail(bool $shouldFail, string $message = 'Mock OCR extraction failure'): self
    {
        $this->shouldFail = $shouldFail;
        $this->failMessage = $message;
        return $this;
    }

    public function extract(string $imagePath, array $sectionSchema): OcrResult
    {
        if ($this->shouldFail) {
            return OcrResult::failure($this->failMessage);
        }

        if ($this->customResult !== null) {
            return $this->customResult;
        }

        $kelurahans = $sectionSchema['kelurahans'] ?? [
            'PAYOLANSEK',
            'BULBA',
            'PAKAN SINAYAN',
            'KUBU GADANG',
            'KOTO TANGAH',
            'TALANG',
        ];

        $fields = $sectionSchema['fields'] ?? [];
        $rows = [];

        foreach ($kelurahans as $idx => $kelName) {
            $rowFields = [];
            foreach ($fields as $fieldIdx => $field) {
                // Memberikan nilai tiruan realistis
                // Untuk PAYOLANSEK in_a6069_p_baru = 40 (sesuai uji emas)
                if ($kelName === 'PAYOLANSEK' && $field === 'in_a6069_p_baru') {
                    $val = 40;
                    $conf = 0.95;
                } elseif ($kelName === 'PAYOLANSEK' && $field === 'in_a6069_l_baru') {
                    $val = 16;
                    $conf = 0.93;
                } elseif (($idx + $fieldIdx) % 7 === 0) {
                    // Beberapa sel sengaja null / low confidence untuk menguji sel kuning
                    $val = null;
                    $conf = 0.0;
                } elseif (($idx + $fieldIdx) % 5 === 0) {
                    // Sel dengan confidence < 0.8
                    $val = ($idx * 3) + 2;
                    $conf = 0.72;
                } else {
                    $val = (($idx + 1) * 4) + ($fieldIdx % 5);
                    $conf = 0.94;
                }

                $rowFields[$field] = [
                    'value'      => $val,
                    'confidence' => $conf,
                ];
            }

            $rows[] = [
                'row_index'      => $idx + 1,
                'kelurahan_name' => $kelName,
                'fields'         => $rowFields,
            ];
        }

        return OcrResult::success(
            rows: $rows,
            notes: 'Mock OCR extraction completed successfully.',
            tokensUsed: 1250,
            rawResponse: json_encode(['rows' => $rows])
        );
    }
}
