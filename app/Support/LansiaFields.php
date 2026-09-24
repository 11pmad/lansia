<?php

namespace App\Support;

/**
 * LansiaFields — Satu-satunya sumber kebenaran (Single Source of Truth)
 * untuk seluruh daftar kolom laporan lansia SIPELA.
 * Digunakan oleh:
 * - Migrasi database (loop foreach)
 * - Model $fillable
 * - Validasi FormRequest
 * - Skema OCR Vision LLM
 * - Pemetaan Ekspor Excel
 * - Prop Inertia untuk form frontend React
 */
class LansiaFields
{
    public const AGES = ['a4559', 'a6069', 'a70'];
    public const GENDERS = ['l', 'p'];
    public const VISIT_TYPES = ['lama', 'baru'];
    public const LOCATIONS = ['in', 'out'];

    public const AGE_LABELS = [
        'a4559' => '45–59 Th',
        'a6069' => '60–69 Th',
        'a70'   => '>70 Th',
    ];

    public const GENDER_LABELS = [
        'l' => 'Laki-laki',
        'p' => 'Perempuan',
    ];

    public const VISIT_TYPE_LABELS = [
        'lama' => 'Lama',
        'baru' => 'Baru',
    ];

    public const LOCATION_LABELS = [
        'in'  => 'Dalam Gedung (Puskesmas)',
        'out' => 'Luar Gedung (Posyandu)',
    ];

    public const KUNJUNGAN_UMUM = [
        'posyandu_count'        => 'Jumlah Posyandu Lansia',
        'pemberdayaan_count'    => 'Posyandu Lansia dg Pemberdayaan Masyarakat',
        'posyandu_ptm_count'    => 'Posyandu Terintegrasi PTM',
        'kader_count'           => 'Jumlah Kader Posyandu Lansia',
        'kader_trained_count'   => 'Kader Posyandu Lansia Terlatih',
        'jkn_count'             => 'Lansia Memiliki JKN/Asuransi',
    ];

    public const MANDIRI_COLUMNS = [
        'mandiri_6069_a' => 'Kemandirian 60-69 Th Kategori A (Mandiri)',
        'mandiri_6069_b' => 'Kemandirian 60-69 Th Kategori B (Ringan/Sedang)',
        'mandiri_6069_c' => 'Kemandirian 60-69 Th Kategori C (Berat/Ketergantungan)',
        'mandiri_70_a'   => 'Kemandirian >70 Th Kategori A (Mandiri)',
        'mandiri_70_b'   => 'Kemandirian >70 Th Kategori B (Ringan/Sedang)',
        'mandiri_70_c'   => 'Kemandirian >70 Th Kategori C (Berat/Ketergantungan)',
    ];

    public const KELAINAN_KEYS = [
        'gg_me'         => 'Gangguan Mental Emosional',
        'imt_lebih'     => 'Penilaian IMT Lebih',
        'imt_kurang'    => 'Penilaian IMT Kurang',
        'td_tinggi'     => 'Tekanan Darah Tinggi',
        'td_rendah'     => 'Tekanan Darah Rendah',
        'hb_kurang'     => 'Kadar Hemoglobin (Hb) Kurang',
        'kolesterol'    => 'Kadar Kolesterol Tinggi',
        'dm'            => 'Diabetes Melitus / Gula Darah Tinggi',
        'asam_urat'     => 'Kadar Asam Urat Tinggi',
        'ginjal'        => 'Pemeriksaan Fungsi Ginjal',
        'kognitif'      => 'Penurunan Fungsi Kognitif',
        'penglihatan'   => 'Gangguan Penglihatan',
        'pendengaran'   => 'Gangguan Pendengaran',
        'lainnya'       => 'Kelainan Lainnya',
    ];

    public const LAYANAN_TINDAKAN = [
        'pengobatan_edukasi' => 'Pengobatan / Penyuluhan Edukasi',
        'pengobatan_obati'   => 'Pengobatan Diobati',
        'pengobatan_rujuk'   => 'Pengobatan Dirujuk',
        'konseling_baru'     => 'Konseling Baru',
        'konseling_lama'     => 'Konseling Lama',
        'konseling_selesai'  => 'Konseling Selesai Masalah',
        'penyuluhan'         => 'Penyuluhan Kelompok',
    ];

    public const LAYANAN_LAIN = [
        'lansia_bekerja' => 'Lansia Masih Bekerja',
        'panti_dibina'   => 'Panti / Panti Wreda Dibina',
        'homecare'       => 'Pelayanan Home Care Lansia',
        'keterangan'     => 'Keterangan Tambahan',
    ];

    /**
     * 6 Kolom sasaran: sas_{age}_{sex}
     * sas_a4559_l, sas_a4559_p, sas_a6069_l, sas_a6069_p, sas_a70_l, sas_a70_p
     */
    public static function sasaranColumns(): array
    {
        $cols = [];
        foreach (self::AGES as $age) {
            foreach (self::GENDERS as $sex) {
                $cols[] = "sas_{$age}_{$sex}";
            }
        }
        return $cols;
    }

    /**
     * 6 Kolom umum kunjungan
     */
    public static function kunjunganUmumColumns(): array
    {
        return array_keys(self::KUNJUNGAN_UMUM);
    }

    /**
     * 12 Kolom kunjungan dalam gedung: in_{age}_{sex}_{type}
     */
    public static function kunjunganInColumns(): array
    {
        $cols = [];
        foreach (self::AGES as $age) {
            foreach (self::GENDERS as $sex) {
                foreach (self::VISIT_TYPES as $type) {
                    $cols[] = "in_{$age}_{$sex}_{$type}";
                }
            }
        }
        return $cols;
    }

    /**
     * 12 Kolom kunjungan luar gedung: out_{age}_{sex}_{type}
     */
    public static function kunjunganOutColumns(): array
    {
        $cols = [];
        foreach (self::AGES as $age) {
            foreach (self::GENDERS as $sex) {
                foreach (self::VISIT_TYPES as $type) {
                    $cols[] = "out_{$age}_{$sex}_{$type}";
                }
            }
        }
        return $cols;
    }

    /**
     * 24 Kolom kunjungan (12 dalam gedung + 12 luar gedung)
     */
    public static function kunjunganColumns(): array
    {
        return array_merge(self::kunjunganInColumns(), self::kunjunganOutColumns());
    }

    /**
     * 6 Kolom kemandirian
     */
    public static function mandiriColumns(): array
    {
        return array_keys(self::MANDIRI_COLUMNS);
    }

    /**
     * Seluruh kolom untuk kunjungan_rows (umum + sasaran + 24 kunjungan + 6 mandiri)
     */
    public static function allKunjunganRowColumns(): array
    {
        return array_merge(
            self::kunjunganUmumColumns(),
            self::sasaranColumns(),
            self::kunjunganColumns(),
            self::mandiriColumns()
        );
    }

    /**
     * 14 Key kelainan
     */
    public static function kelainanKeys(): array
    {
        return array_keys(self::KELAINAN_KEYS);
    }

    /**
     * 28 Kolom kelainan: kel_{key}_{sex} (14 key x 2 sex)
     */
    public static function kelainanColumns(): array
    {
        $cols = [];
        foreach (self::kelainanKeys() as $key) {
            foreach (self::GENDERS as $sex) {
                $cols[] = "kel_{$key}_{$sex}";
            }
        }
        return $cols;
    }

    /**
     * 7 Kolom tindakan layanan
     */
    public static function layananTindakanColumns(): array
    {
        return array_keys(self::LAYANAN_TINDAKAN);
    }

    /**
     * 4 Kolom lain-lain layanan (termasuk keterangan text)
     */
    public static function layananLainColumns(): array
    {
        return array_keys(self::LAYANAN_LAIN);
    }

    /**
     * Seluruh kolom untuk layanan_rows (6 sasaran + 28 kelainan + 7 tindakan + 4 lain)
     */
    public static function allLayananRowColumns(): array
    {
        return array_merge(
            self::sasaranColumns(),
            self::kelainanColumns(),
            self::layananTindakanColumns(),
            self::layananLainColumns()
        );
    }

    /**
     * Kembalikan label ramah Bahasa Indonesia untuk field tertentu
     */
    public static function label(string $field): string
    {
        if (isset(self::KUNJUNGAN_UMUM[$field])) {
            return self::KUNJUNGAN_UMUM[$field];
        }

        if (isset(self::MANDIRI_COLUMNS[$field])) {
            return self::MANDIRI_COLUMNS[$field];
        }

        if (isset(self::LAYANAN_TINDAKAN[$field])) {
            return self::LAYANAN_TINDAKAN[$field];
        }

        if (isset(self::LAYANAN_LAIN[$field])) {
            return self::LAYANAN_LAIN[$field];
        }

        // Check sasaran: sas_{age}_{sex}
        if (preg_match('/^sas_([a-z0-9]+)_([lp])$/', $field, $m)) {
            $ageLabel = self::AGE_LABELS[$m[1]] ?? $m[1];
            $sexLabel = self::GENDER_LABELS[$m[2]] ?? $m[2];
            return "Sasaran {$ageLabel} ({$sexLabel})";
        }

        // Check kunjungan: {loc}_{age}_{sex}_{type}
        if (preg_match('/^(in|out)_([a-z0-9]+)_([lp])_(lama|baru)$/', $field, $m)) {
            $loc = $m[1] === 'in' ? 'Dalam Gedung' : 'Luar Gedung';
            $ageLabel = self::AGE_LABELS[$m[2]] ?? $m[2];
            $sexLabel = self::GENDER_LABELS[$m[3]] ?? $m[3];
            $typeLabel = self::VISIT_TYPE_LABELS[$m[4]] ?? $m[4];
            return "Kunjungan {$loc} {$ageLabel} ({$sexLabel} - {$typeLabel})";
        }

        // Check kelainan: kel_{key}_{sex}
        if (preg_match('/^kel_(.+)_(l|p)$/', $field, $m)) {
            $keyLabel = self::KELAINAN_KEYS[$m[1]] ?? $m[1];
            $sexLabel = self::GENDER_LABELS[$m[2]] ?? $m[2];
            return "{$keyLabel} ({$sexLabel})";
        }

        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Daftar field per bagian form / OCR section sesuai docs/06_SKILL_OCR.md
     */
    public static function sectionFields(string $section): array
    {
        return match ($section) {
            'kunjungan_umum_sasaran'    => array_merge(self::kunjunganUmumColumns(), self::sasaranColumns()),
            'kunjungan_dalam'           => self::kunjunganInColumns(),
            'kunjungan_luar'            => self::kunjunganOutColumns(),
            'kunjungan_mandiri'         => self::mandiriColumns(),
            'layanan_sasaran_kelainan'  => array_merge(self::sasaranColumns(), self::kelainanColumns()),
            'layanan_tindakan'          => array_merge(self::layananTindakanColumns(), self::layananLainColumns()),
            default                     => [],
        };
    }
}
