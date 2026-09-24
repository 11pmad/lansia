# SIPELA — Sistem Pelaporan Lansia Puskesmas Payolansek

> Baca file ini dulu. Urutan baca AI builder: 00 → 04 → 01 → 02 → 03 → 05 → 06 → 07 → 08 (prompt kerja).

## Tujuan
Web app mobile-first untuk petugas Puskesmas Payolansek (Kota Payakumbuh, 6 kelurahan) menggantikan pengisian manual Excel "Format Pencatatan dan Pelaporan Kesehatan Lanjut Usia Dinkes Kota TA 2026".
Petugas memotret laporan tulisan tangan → OCR mengisi form → petugas koreksi → simpan → sistem hitung otomatis → rekap bulanan, statistik, ekspor Excel format Dinkes.

## Stack (WAJIB)
Laravel 11 (PHP 8.3) + Inertia + React 18 + Tailwind + Recharts, MySQL 8, PhpSpreadsheet, queue `database`.

## Peta dokumen
| File | Isi |
|---|---|
| 01_SPEC.md | Kebutuhan, peran, halaman, alur, aturan bisnis |
| 02_DATABASE.md | Skema DB, penamaan kolom, rumus turunan |
| 03_EXCEL_MAPPING.md | Pemetaan kolom DB ↔ sel Excel template, aturan ekspor |
| 04_AGENT_RULES.md | Aturan wajib untuk semua AI agent |
| 05_SKILL_STACK.md | Skill Laravel + React (struktur, pola kode) |
| 06_SKILL_OCR.md | Skill OCR tulisan tangan + prompt vision model |
| 07_SKILL_DESIGN.md | Skill desain UI mobile untuk petugas |
| 08_PROMPTS.md | Prompt bertahap siap tempel (Fase 0–8) |

## File pendukung
Simpan file Excel asli di `storage/app/templates/laporan_lansia_template.xlsx` (dipakai ekspor).

## Istilah
- **Sasaran**: jumlah lansia target per kelompok umur & jenis kelamin (L/P).
- **Lama/Baru**: lansia kunjungan lama / baru pada bulan berjalan.
- **Abs**: angka absolut (lama + baru) bulan itu.
- **Komdat**: data yang dilaporkan ke Kemenkes (kolom "Jumlah data Komdat").
- **SPM / sesuai standar**: lansia >60 th yang dilayani sesuai standar pelayanan.
- **Kemandirian A/B/C**: tingkat kemandirian lansia (A mandiri, B ringan/sedang, C berat).

## Keputusan yang diasumsikan (ubah jika salah)
1. Hanya satu Puskesmas (Payolansek), tetapi tabel `puskesmas` tetap ada agar bisa berkembang.
2. Sasaran di sheet Kunjungan dan sheet Layanan di Excel berbeda angkanya → disimpan terpisah, meniru Excel.
3. OCR memakai vision LLM lewat API dari server (bukan Tesseract, karena tulisan tangan).
4. Semua UI dan label berbahasa Indonesia; kode/identifier berbahasa Inggris.
