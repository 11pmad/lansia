<?php

namespace App\Services\Ocr;

/**
 * Interface OcrProvider — Kontrak layanan pembacaan OCR formulir tulisan tangan.
 * Sesuai docs/06_SKILL_OCR.md.
 */
interface OcrProvider
{
    /**
     * Ekstrak angka dan data tabel dari foto formulir.
     *
     * @param string $imagePath Path absolut ke file gambar
     * @param array $sectionSchema Skema field yang diminta:
     *   [
     *     'section'    => string, // e.g. 'kunjungan_dalam'
     *     'fields'     => array,  // array of field names e.g. ['in_a4559_l_lama', ...]
     *     'field_meta' => array,  // ['field_name' => 'Label Bahasa Indonesia']
     *     'kelurahans' => array,  // array of names e.g. ['PAYOLANSEK', 'BULBA', ...]
     *   ]
     * @return OcrResult
     */
    public function extract(string $imagePath, array $sectionSchema): OcrResult;
}
