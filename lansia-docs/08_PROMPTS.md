# 08 — PROMPTS (tempel satu per sesi, berurutan)

Persiapan: buat repo kosong, salin folder ini ke `docs/`, taruh Excel asli di `docs/reference/Laporan_Lansia_2026.xlsx`.
Tempel **Prompt 0** sekali di awal (atau simpan sebagai `AGENTS.md`), lalu satu prompt fase per sesi.

---
## Prompt 0 — Konteks tetap
```
Anda adalah AI builder untuk aplikasi SIPELA (pelaporan lansia Puskesmas Payolansek).
Baca docs/00_INDEX.md dan docs/04_AGENT_RULES.md dan patuhi keduanya sepanjang proyek.
Dokumen di docs/ adalah sumber kebenaran. Kerjakan HANYA fase yang saya minta.
Akhiri tiap fase dengan: cara menjalankan, hasil tes, dan daftar asumsi (maks 10 baris).
```

## Fase 1 — Fondasi & Auth
```
Fase 1. Baca docs/01_SPEC.md (bagian 1, 3) dan docs/05_SKILL_STACK.md.
Buat proyek Laravel 11 + Breeze (React/Inertia), MySQL, Tailwind, tanpa registrasi publik.
Buat model User dengan role admin|petugas, seeder admin & petugas, halaman Login sesuai docs/07_SKILL_DESIGN.md, layout dengan BottomNav (mobile) dan sidebar (desktop), middleware auth, Policy dasar.
Selesai jika: login/logout jalan, rute admin ditolak untuk petugas (tes), tampilan 360px rapi.
```

## Fase 2 — Database & Kalkulator
```
Fase 2. Baca docs/02_DATABASE.md sepenuhnya.
Buat app/Support/LansiaFields.php sebagai sumber tunggal kolom, migrasi (loop) untuk semua tabel, model + relasi, seeder puskesmas & 6 kelurahan.
Buat app/Services/LansiaCalculator.php sesuai bagian Rumus dan tes unit termasuk uji emas (PAYOLANSEK Januari: 59, 84, 143, 23.21), pembagi nol, dan baris JUMLAH.
Selesai jika: php artisan migrate:fresh --seed dan php artisan test hijau.
```

## Fase 3 — Laporan bulanan & form manual
```
Fase 3. Baca docs/01_SPEC.md (2,3,4,5) dan docs/07_SKILL_DESIGN.md.
Buat: daftar laporan per tahun (12 kartu bulan), aksi Buat Laporan (salin sasaran/posyandu/kader dari bulan lalu), halaman detail, Form Kunjungan dan Form Layanan per kelurahan (accordion, NumberField, autosave debounce, kartu hasil otomatis via lib/calc.js), halaman Rekap dengan baris JUMLAH, Finalisasi/kunci + peringatan validasi, admin bisa reopen.
Form React harus dibangun dari daftar field yang dikirim backend (LansiaFields), bukan ditulis manual per kolom.
Selesai jika: mengisi satu kelurahan penuh di 360px nyaman, hasil hitung sama dengan backend, laporan final tidak bisa diubah (tes).
```

## Fase 4 — Statistik
```
Fase 4. Baca docs/01_SPEC.md (F7) dan docs/05_SKILL_STACK.md bagian Statistik.
Buat StatistikService dan halaman /statistik: (a) tren kunjungan per bulan (Total, Dalam, Luar gedung), (b) grafik "Lansia dilayani sesuai standar >60 th": batang L Abs & P Abs, kartu TOTAL, garis %. Filter tahun & kelurahan. Sertakan tabel data di bawah grafik. Beranda menampilkan tren mini dan % bulan ini.
Selesai jika: angka grafik = angka rekap (tes), bulan kosong tampil sebagai celah bukan 0, terbaca di 360px.
```

## Fase 5 — Ekspor Excel
```
Fase 5. Baca docs/03_EXCEL_MAPPING.md sepenuhnya.
Salin docs/reference/Laporan_Lansia_2026.xlsx ke storage/app/templates/laporan_lansia_template.xlsx, terapkan 5 perbaikan template yang tercantum, lalu buat ReportExporter dengan PhpSpreadsheet yang mengisi hanya sel input.
Selesai jika: ekspor Januari 2026 dari data seeder dibuka di Excel dengan BN12 = 143 dan BO12 ≈ 23,21; tes fitur memeriksa sel-sel kunci (mis. W12 = in_a6069_p_baru).
```

## Fase 6 — OCR
```
Fase 6. Baca docs/06_SKILL_OCR.md sepenuhnya dan docs/07_SKILL_DESIGN.md (ConfidenceCell).
Buat OcrProvider + VisionLlmProvider (API server-side, kunci dari .env), tabel/model ocr_uploads, job ProcessOcrUpload, halaman /scan (pilih bagian, kamera/unggah, perkecil di klien) dan /scan/{id} (pratinjau, sel kuning, simpan ke laporan draft, foto ulang, isi manual).
Provider harus bisa di-mock; sertakan prompt di resources/prompts/ocr_system.txt dan perintah ocr:eval.
Selesai jika: dengan provider mock alur scan→pratinjau→simpan lulus tes; tanpa kunci API aplikasi tetap jalan dan menampilkan pesan ramah.
```

## Fase 7 — Master data, keamanan, polish
```
Fase 7. CRUD kelurahan & pengguna (admin), ganti password, throttle, retensi foto terjadwal, batas scan harian, audit log sederhana, empty/error/loading state di semua halaman, checklist visual di docs/07_SKILL_DESIGN.md.
Selesai jika: seluruh Definition of Done di docs/04_AGENT_RULES.md terpenuhi.
```

## Fase 8 — Rilis
```
Fase 8. Tulis README.md (instalasi, .env, queue/cron, backup DB, cara update), seeder demo, panduan pengguna 1 halaman (Bahasa Indonesia, bergambar teks) untuk petugas, dan skrip deploy sederhana.
Jalankan review akhir: tes, build, cek keamanan (rute tanpa auth, upload, .env), lalu daftar isu tersisa.
```

---
## Prompt perbaikan (pakai bila hasil melenceng)
```
Hasil fase ini belum sesuai. Baca ulang docs/<file terkait> dan bandingkan dengan kode.
Daftar penyimpangan (maks 10) → perbaiki satu per satu → jalankan tes → laporkan. Jangan mengubah hal di luar daftar.
```

## Prompt audit lintas-dokumen (opsional, di akhir)
```
Audit: pastikan semua nama kolom di migrasi, model, validasi, OCR schema, ekspor Excel, dan form React berasal dari LansiaFields dan cocok dengan docs/02_DATABASE.md. Tampilkan daftar ketidakcocokan dan perbaiki.
```
