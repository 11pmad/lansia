<?php

namespace App\Services\Ocr;

class OcrResult
{
    /**
     * @param array $rows Array of rows: [['row_index' => int, 'kelurahan_name' => ?string, 'fields' => array]]
     * @param string|null $notes
     * @param int|null $tokensUsed
     * @param bool $isSuccess
     * @param string|null $errorMessage
     * @param string|null $rawResponse
     */
    public function __construct(
        public array $rows = [],
        public ?string $notes = null,
        public ?int $tokensUsed = null,
        public bool $isSuccess = true,
        public ?string $errorMessage = null,
        public ?string $rawResponse = null
    ) {
    }

    public static function failure(string $errorMessage, ?string $rawResponse = null): self
    {
        return new self(
            rows: [],
            notes: null,
            tokensUsed: null,
            isSuccess: false,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse
        );
    }

    public static function success(array $rows, ?string $notes = null, ?int $tokensUsed = null, ?string $rawResponse = null): self
    {
        return new self(
            rows: $rows,
            notes: $notes,
            tokensUsed: $tokensUsed,
            isSuccess: true,
            errorMessage: null,
            rawResponse: $rawResponse
        );
    }

    /**
     * Format result to simplified array for saving:
     * [rowIndex => [fieldName => value]]
     */
    public function toResultMap(): array
    {
        $map = [];
        foreach ($this->rows as $row) {
            $idx = $row['row_index'] ?? 0;
            $fields = [];
            foreach ($row['fields'] ?? [] as $field => $data) {
                $fields[$field] = $data['value'] ?? null;
            }
            $map[$idx] = $fields;
        }
        return $map;
    }

    /**
     * Format confidence to simplified array:
     * [rowIndex => [fieldName => confidence]]
     */
    public function toConfidenceMap(): array
    {
        $map = [];
        foreach ($this->rows as $row) {
            $idx = $row['row_index'] ?? 0;
            $confidences = [];
            foreach ($row['fields'] ?? [] as $field => $data) {
                $confidences[$field] = $data['confidence'] ?? 0.0;
            }
            $map[$idx] = $confidences;
        }
        return $map;
    }
}
