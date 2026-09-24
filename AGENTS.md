# 04 — AGENT RULES (wajib untuk semua AI agent)

Salin isi file ini ke `AGENTS.md` / `CLAUDE.md` di root repo.

## Prinsip
1. **Dokumen di `docs/` adalah sumber kebenaran.** Jika kode dan dokumen bertentangan, ikuti dokumen; jika dokumen keliru/ambigu, tanyakan atau catat di `docs/DECISIONS.md`, jangan menebak diam-diam.
2. **Kerjakan satu fase per sesi** (08_PROMPTS.md). Jangan mengerjakan fase berikutnya sebelum kriteria selesai fase saat ini terpenuhi.
3. **Baca hanya file yang relevan.** Jangan memuat seluruh docs setiap kali: 00 + 04 + file fase terkait.
4. **Kecil dan bisa dijalankan.** Setiap fase berakhir dengan aplikasi yang bisa dijalankan dan tes hijau.

## Stack & batasan
- Laravel 11, PHP 8.3, Inertia + React 18 (JS, boleh TypeScript jika konsisten), Tailwind, Recharts, MySQL 8, PhpSpreadsheet. Auth: Laravel Breeze (React/Inertia), tanpa registrasi publik.
- Dilarang menambah library besar tanpa alasan tertulis di `DECISIONS.md` (contoh terlarang: Redux, UI kit berat, ORM lain).
- Konfigurasi rahasia hanya di `.env`; buat `.env.example` lengkap. Kunci API OCR tidak pernah dikirim ke browser.

## Aturan kode
- Penamaan kolom/field mengikuti 02_DATABASE.md persis (`in_a6069_p_baru`, `kel_dm_l`, dll.). Jangan mengarang nama baru.
- Kolom berulang dibuat dengan loop dari satu **konstanta sumber** (`app/Support/LansiaFields.php`) yang dipakai migrasi, model `$fillable`, validasi, OCR schema, ekspor Excel, dan (via Inertia prop) form React. Tidak boleh ada daftar kolom yang ditulis ulang di tempat lain.
- Rumus hanya di `app/Services/LansiaCalculator.php`. Frontend hanya mencerminkan untuk pratinjau; jika beda, backend yang benar. Tulis tes dari uji emas 02_DATABASE.md.
- Controller tipis → Service/Action. Validasi lewat FormRequest. Otorisasi lewat Policy (`petugas` vs `admin`).
- Semua query lewat Eloquent dengan eager loading; hindari N+1 di halaman rekap/statistik.
- Teks UI Bahasa Indonesia dari satu file bahasa (`lang/id/ui.php` atau `resources/js/lang.js`), bukan hard-code tersebar.
- Angka: bilangan bulat ≥ 0. Persen 2 desimal, format Indonesia (`23,21 %`).
- Tidak ada `console.log`, `dd`, atau kode mati pada commit.

## Keamanan
- Password bcrypt, throttle login, CSRF aktif, sesi httpOnly.
- Upload foto: hanya jpg/png/webp, maks 8 MB, simpan di disk privat, nama file acak, hapus terjadwal (30 hari).
- Data hanya agregat. **Jangan** menambah kolom nama/NIK lansia. Jangan mengirim data selain foto formulir ke API OCR.
- Setiap rute di belakang `auth`; rute admin di belakang `can:admin`.

## Tes minimal
- Unit: `LansiaCalculator` (uji emas + pembagi nol + JUMLAH).
- Feature: login, buat laporan, simpan form, finalisasi mengunci, petugas ditolak di rute admin, ekspor menghasilkan file dengan nilai benar.
- OCR: provider di-mock; tes parser JSON → validasi → pratinjau.
- Jalankan `php artisan test` dan `npm run build` sebelum menyatakan fase selesai.

## Definition of Done (per fase)
- [ ] Fitur sesuai 01_SPEC.md bagian terkait
- [ ] Tes hijau, build sukses, tanpa error console
- [ ] Diuji di lebar 360px (mobile) dan 1280px
- [ ] Seeder/demo data ada agar bisa dicoba
- [ ] `docs/DECISIONS.md` diperbarui jika ada asumsi
- [ ] Ringkasan perubahan singkat (≤10 baris) di akhir jawaban

## Gaya kerja & token
- Jawab ringkas: rencana singkat → kode → cara menjalankan/tes. Tanpa penjelasan berulang.
- Edit file yang ada, jangan menulis ulang seluruh file untuk perubahan kecil.
- Bila ragu pada aturan bisnis kesehatan (istilah, rumus), tanyakan; jangan mengubah rumus sendiri.
