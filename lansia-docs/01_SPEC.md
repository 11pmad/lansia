# 01 — SPEC (gabungan kebutuhan + rancangan singkat)

## 1. Peran
| Peran | Hak |
|---|---|
| `petugas` | Login, buat/isi/edit laporan bulan berjalan, scan OCR, lihat statistik, ekspor |
| `admin` | Semua hak petugas + kelola pengguna, kelola kelurahan, buka kunci laporan final |

Pengguna utama: petugas Puskesmas, umumnya usia >35 th, memakai HP Android. Prioritas: jelas, sedikit langkah, tombol besar.

## 2. Fitur (MoSCoW)
**Must**
- F1 Login/logout (email + password), sesi aman, throttle login.
- F2 Laporan bulanan: satu laporan = 1 bulan × 1 tahun, berisi data 6 kelurahan untuk dua form (Kunjungan, Layanan).
- F3 CRUD input manual per kelurahan (form bertahap, autosave draft).
- F4 Scan OCR tulisan tangan → pratinjau → koreksi → simpan (lihat 06_SKILL_OCR.md).
- F5 Perhitungan otomatis semua kolom turunan (lihat 02_DATABASE.md §Rumus).
- F6 Rekap per bulan (12 bulan) + baris JUMLAH per bulan.
- F7 Statistik: (a) tren kunjungan per bulan, (b) grafik kolom "Lansia dilayani sesuai standar >60 th": L Abs, P Abs, TOTAL, %.
- F8 Ekspor Excel serupa template Dinkes (lihat 03_EXCEL_MAPPING.md).

**Should**
- F9 Kelola kelurahan & pengguna (admin).
- F10 Status laporan draft/final + kunci.
- F11 Validasi peringatan (angka janggal) sebelum finalisasi.

**Could**
- F12 Statistik tambahan: 10 kelainan terbanyak, distribusi kemandirian, perbandingan antar kelurahan.
- F13 Ekspor PDF ringkas.

## 3. Halaman & navigasi
Bottom navigation (mobile): **Beranda · Laporan · Scan · Statistik · Menu**.

| Rute | Halaman | Isi utama |
|---|---|---|
| `/login` | Login | email, password, tombol besar Masuk |
| `/` | Beranda | kartu ringkas bulan ini, status laporan, tren mini, tombol "Scan Laporan" & "Isi Manual" |
| `/laporan` | Daftar laporan | filter tahun; 12 kartu bulan (kosong/draft/final) |
| `/laporan/{y}/{m}` | Detail laporan | ringkasan bulan, dua tombol: Form Kunjungan, Form Layanan; tombol Finalisasi |
| `/laporan/{id}/kunjungan` | Form Kunjungan | pilih kelurahan (tab/geser) → bagian: Umum, Sasaran, Dalam Gedung, Luar Gedung, Kemandirian; hasil hitung otomatis di bawah |
| `/laporan/{id}/layanan` | Form Layanan | pilih kelurahan → Sasaran, Kelainan (L/P), Tindakan, Lain-lain |
| `/laporan/{id}/rekap` | Rekap | tabel seluruh kelurahan + JUMLAH, scroll horizontal, kolom nama kelurahan sticky |
| `/scan` | Scan | pilih bagian formulir → ambil foto/unggah → proses |
| `/scan/{id}` | Pratinjau OCR | form terisi, sel ragu-ragu ditandai kuning, foto di atas bisa di-zoom |
| `/statistik` | Statistik | filter tahun & kelurahan; grafik F7 |
| `/ekspor` | Ekspor | pilih tahun (& bulan) → unduh .xlsx |
| `/kelurahan`, `/pengguna` | Master (admin) | CRUD sederhana |
| `/profil` | Profil | ganti password |

## 4. Alur utama
**A. Scan**: Scan → pilih bagian formulir & kelurahan → foto → (queue) OCR → Pratinjau → koreksi → Simpan ke laporan (draft) → lanjut bagian berikut.
**B. Manual**: Laporan → bulan → Form → isi angka → autosave → Finalisasi.
**C. Bulan baru**: saat membuat laporan, sasaran/posyandu/kader disalin dari bulan sebelumnya; angka kunjungan mulai kosong.
**D. Finalisasi**: cek validasi → tampilkan peringatan → konfirmasi → status `final` (read-only). Admin bisa `reopen`.

## 5. Aturan bisnis
1. Semua angka bilangan bulat ≥ 0. Kosong = `null` di DB, dihitung 0 saat kalkulasi, tampil "0" atau "—" (kosong ≠ ditolak).
2. Kolom turunan TIDAK disimpan; dihitung di `LansiaCalculator` (backend) dan dicerminkan di UI untuk pratinjau.
3. Persentase = total_layanan_standar ÷ total_lansia_>60 × 100, 2 desimal; jika pembagi 0 → 0.
4. Baris JUMLAH = penjumlahan seluruh kelurahan; persentase JUMLAH dihitung ulang dari jumlah (bukan rata-rata).
5. Satu laporan unik per (puskesmas, tahun, bulan).
6. Laporan `final` tidak bisa diubah kecuali admin membuka kunci.
7. Peringatan (bukan error): persen >100, kunjungan >60 th lebih besar dari sasaran, jumlah kelainan > kunjungan, sasaran berubah >20% dari bulan lalu.
8. Semua perubahan dicatat: `created_by`, `updated_by`, `updated_at`.

## 6. Non-fungsional
- Mobile-first 360px, tetap nyaman di desktop; waktu muat <3 dtk di 4G.
- Data hanya agregat (tanpa nama/NIK lansia) → tidak ada data pribadi pasien.
- Foto OCR disimpan privat, dihapus otomatis setelah 30 hari (konfigurasi).
- Backup DB harian (dokumentasikan di README).
- Aplikasi tetap berfungsi penuh tanpa OCR (fallback manual).

## 7. Di luar cakupan
Multi-Puskesmas, data individu lansia, integrasi langsung ke Komdat/ASIK, aplikasi native.
