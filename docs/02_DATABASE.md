# 02 — DATABASE

MySQL 8, charset utf8mb4. Semua tabel: `id` bigint PK, `created_at`, `updated_at`.
Kolom input bertipe `unsignedInteger nullable`. Migrasi kolom berulang dibuat dengan **loop**, bukan ditulis manual.

## Kode penamaan (WAJIB konsisten di DB, API, OCR JSON, React)
- Umur: `a4559` (45-59), `a6069` (60-69), `a70` (>70)
- Jenis kelamin: `l`, `p`
- Tipe kunjungan: `lama`, `baru`
- Lokasi: `in` (dalam gedung/Puskesmas), `out` (luar gedung/Posyandu)

## Tabel

### users
`name`, `email` unique, `password`, `role` enum(admin,petugas), `is_active` bool default 1.

### puskesmas
`name` ("PAYOLANSEK"), `city` ("PAYAKUMBUH"). Seed 1 baris.

### kelurahans
`puskesmas_id` FK, `name`, `sort_order`, `is_active`.
Seed berurutan: PAYOLANSEK, BULBA, PAKAN SINAYAN, KUBU GADANG, KOTO TANGAH, TALANG.

### monthly_reports
`puskesmas_id` FK, `year` smallint, `month` tinyint(1-12), `status` enum(draft,final) default draft, `finalized_at` nullable, `created_by`, `updated_by`.
Unique (`puskesmas_id`,`year`,`month`).

### kunjungan_rows  (1 baris = 1 kelurahan × 1 laporan; unique `monthly_report_id`+`kelurahan_id`)
`monthly_report_id` FK cascade, `kelurahan_id` FK.
Umum: `posyandu_count`, `pemberdayaan_count`, `posyandu_ptm_count`, `kader_count`, `kader_trained_count`, `jkn_count`.
Sasaran (6): `sas_{age}_{sex}` → `sas_a4559_l, sas_a4559_p, sas_a6069_l, sas_a6069_p, sas_a70_l, sas_a70_p`.
Kunjungan (24): `{loc}_{age}_{sex}_{type}` → contoh `in_a4559_l_lama`, `in_a4559_l_baru`, … `out_a70_p_baru`. (2 lokasi × 3 umur × 2 sex × 2 tipe.)
Kemandirian (6): `mandiri_6069_a, mandiri_6069_b, mandiri_6069_c, mandiri_70_a, mandiri_70_b, mandiri_70_c`.

### layanan_rows  (unique `monthly_report_id`+`kelurahan_id`)
`monthly_report_id`, `kelurahan_id`.
Sasaran (6): `sas_{age}_{sex}` (sama pola; disimpan terpisah dari kunjungan_rows, meniru Excel).
Kelainan (28): `kel_{key}_{sex}` dengan key:
`gg_me` (gangguan mental emosional), `imt_lebih`, `imt_kurang`, `td_tinggi`, `td_rendah`, `hb_kurang`, `kolesterol`, `dm`, `asam_urat`, `ginjal`, `kognitif`, `penglihatan`, `pendengaran`, `lainnya` → 14 key × l/p.
Tindakan: `pengobatan_edukasi`, `pengobatan_obati`, `pengobatan_rujuk`, `konseling_baru`, `konseling_lama`, `konseling_selesai`, `penyuluhan`.
Lain: `lansia_bekerja`, `panti_dibina`, `homecare`, `keterangan` (text nullable).

### ocr_uploads
`monthly_report_id` nullable FK, `kelurahan_id` nullable FK, `section` string (lihat 06), `image_path`, `status` enum(queued,processing,done,failed,applied), `result_json` json nullable, `confidence_json` json nullable, `error` text nullable, `created_by`, `applied_at`.

### audit_logs (opsional, Should)
`user_id`, `action`, `subject_type`, `subject_id`, `changes` json.

## Rumus turunan (LansiaCalculator — satu-satunya sumber kebenaran)
Notasi: A ∈ {a4559,a6069,a70}, S ∈ {l,p}.

```
total_lansia_60      = sas_a6069_l + sas_a6069_p + sas_a70_l + sas_a70_p
total_visit[A,S,T]   = in_A_S_T + out_A_S_T            (T = lama|baru)   → 12 nilai
std_baru[A,S]        = total_visit[A,S,baru]
std_abs[A,S]         = total_visit[A,S,lama] + total_visit[A,S,baru]
spm_l_abs            = std_abs[a6069,l] + std_abs[a70,l]
spm_p_abs            = std_abs[a6069,p] + std_abs[a70,p]
spm_total            = spm_l_abs + spm_p_abs
spm_pct              = total_lansia_60 > 0 ? round(spm_total / total_lansia_60 * 100, 2) : 0
total_kunjungan_bulan= jumlah seluruh 24 kolom kunjungan
kelainan_total_l     = Σ kel_*_l   ;  kelainan_total_p = Σ kel_*_p ;  kelainan_total = l + p
```
Baris JUMLAH: jumlahkan semua input dan turunan per kelurahan; `spm_pct` JUMLAH = `spm_total_jumlah / total_lansia_60_jumlah * 100`.

### Uji emas (golden test) — wajib lulus
Kelurahan PAYOLANSEK, Januari 2026 (dari Excel):
sasaran 45-59 L/P = 200/243; 60-69 = 180/209; >70 = 110/117 → `total_lansia_60 = 616`.
Kunjungan baru dalam gedung: 45-59 L 20, P 50; 60-69 L 16, P 40; >70 L 29, P 26.
Kunjungan baru luar gedung: 45-59 L 5, P 10; 60-69 L 14, P 16; >70 L 0, P 2. (Semua "lama" = 0.)
Hasil: `spm_l_abs = 59`, `spm_p_abs = 84`, `spm_total = 143`, `spm_pct = 23.21`.

## Indeks & integritas
Index (`monthly_report_id`), (`kelurahan_id`); FK `restrictOnDelete` untuk kelurahan, `cascadeOnDelete` untuk laporan → baris.
Kelurahan tidak dihapus jika sudah punya data; gunakan `is_active=false`.
