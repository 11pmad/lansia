# 05 — SKILL: Laravel + React (Inertia)

## Struktur folder
```
app/
  Support/LansiaFields.php        # SATU sumber daftar kolom (umur, sex, tipe, kelainan, dll.)
  Services/LansiaCalculator.php   # rumus turunan
  Services/ReportExporter.php     # ekspor Excel dari template
  Services/Ocr/OcrProvider.php    # interface
  Services/Ocr/VisionLlmProvider.php
  Jobs/ProcessOcrUpload.php
  Http/Controllers/  Dashboard, Report, KunjunganForm, LayananForm, Scan, Statistik, Export, Kelurahan, User
  Http/Requests/     SaveKunjunganRequest, SaveLayananRequest, UploadScanRequest
  Policies/          MonthlyReportPolicy
resources/js/
  Pages/   Dashboard, Reports/{Index,Show,Recap}, Forms/{Kunjungan,Layanan}, Scan/{Create,Preview}, Statistik, Export, Master/*
  Components/  BottomNav, NumberField, SectionCard, Stepper, StatCard, KelurahanTabs, ConfidenceCell, ChartTrend, ChartSpm
  lib/  calc.js (cermin rumus), format.js (angka & persen id-ID), fields.js (dari prop Inertia)
tests/{Unit,Feature}
docs/  (salinan folder dokumen ini)
```

## Pola backend
- **LansiaFields**: method statis `kunjunganColumns()`, `layananColumns()`, `sasaranColumns()`, `kelainanKeys()`, `label($col)` (label Indonesia). Dipakai migrasi (loop `foreach`), `$fillable`, aturan validasi (`integer|min:0|nullable`), skema OCR, ekspor.
- **Simpan form**: `PUT /laporan/{report}/kunjungan/{kelurahan}` → validasi → `updateOrCreate` pada `kunjungan_rows` → kembalikan Inertia response berisi hasil `LansiaCalculator`.
- **Autosave**: React memanggil PUT dengan debounce 1,5 dtk & saat pindah kelurahan; tampilkan "Tersimpan ✓ 14:02". Laporan `final` → 403.
- **Buat laporan**: Action `CreateMonthlyReport` membuat 6 baris × 2 form; salin sasaran/posyandu/kader dari bulan sebelumnya bila ada.
- **Statistik**: `StatistikService` mengembalikan array siap-chart:
  - `trend`: per bulan `{bulan, total, in, out}` (in/out = jumlah 24 kolom kunjungan dipisah lokasi).
  - `spm`: per bulan `{bulan, l_abs, p_abs, total, pct}`; opsi per kelurahan untuk bulan terpilih.
  - Hitung lewat SQL agregat (SUM) + Calculator; bulan tanpa data → `null`, bukan 0.
- **Ekspor**: lihat 03_EXCEL_MAPPING.md; unduh via `response()->download()->deleteFileAfterSend()`.
- **Queue**: `QUEUE_CONNECTION=database`; jalankan `php artisan queue:work` (catat di README; shared hosting → `schedule:run` + `queue:work --stop-when-empty` tiap menit).

## Pola frontend
- Inertia `useForm`; `NumberField` = `<input type="text" inputmode="numeric" pattern="[0-9]*">` (bukan `type=number`), tombol −/+ opsional, pilih-semua saat fokus, hanya angka.
- Form panjang dibagi **bagian (accordion)** per kelurahan; satu bagian terbuka pada satu waktu; progress "3 dari 5 bagian terisi".
- Ringkasan hitung otomatis (total, SPM, %) ditampilkan di kartu bawah, diperbarui langsung dari `lib/calc.js`.
- Grafik Recharts, `ResponsiveContainer`, tinggi 240px di mobile; tooltip besar; tabel data alternatif di bawah grafik (aksesibilitas).
  - `ChartTrend`: LineChart 12 bulan (seri: Total, Dalam Gedung, Luar Gedung).
  - `ChartSpm`: ComposedChart — Bar L Abs & P Abs (tumpuk/berdampingan), Line % pada sumbu kanan; kartu ringkas TOTAL.
- Loading: skeleton; error: pesan Indonesia ramah + tombol coba lagi.
- Tidak ada state global; gunakan props Inertia + state lokal.

## Konvensi
- Route name: `reports.index`, `reports.show`, `forms.kunjungan.edit`, `forms.layanan.edit`, `scan.create`, `scan.show`, `stats.index`, `export.download`, `kelurahan.*`, `users.*`.
- Commit kecil: `feat(kunjungan): form + autosave`.
- Seeder: admin (`admin@sipela.test`), petugas, 6 kelurahan, dan data demo Januari–Agustus 2026 dari Excel (minimal Januari yang cocok dengan uji emas).
