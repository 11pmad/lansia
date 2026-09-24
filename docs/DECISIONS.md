# Keputusan Arsitektur & Teknis (DECISIONS.md)

Dokumen ini mencatat keputusan penting, asumsi, dan pemilihan dependensi untuk sistem SIPELA (Sistem Pelaporan Lansia Puskesmas Payolansek).

---

## 1. Lingkungan Runtime & Kontainerisasi (Fase 1)
- **Konteks**: Biner PHP dan ekstensi terkait tidak terpasang di host Linux CachyOS, sementara Docker Engine dan Docker Compose telah aktif dan digunakan proyek lain di mesin yang sama.
- **Keputusan**: Menggunakan Docker Compose dengan kontainer:
  - `sipela-app`: PHP 8.3-fpm dengan ekstensi `pdo_mysql`, `gd`, `zip`, `bcmath`, `pcntl`.
  - `sipela-web`: Nginx alpine serving public directory di port host `8001`.
  - `sipela-db`: MySQL 8.0 di port host `3307` (internal network `3306`).
- **Skrip Wrapper**: Dibuat folder `bin/` berisi `artisan`, `composer`, dan `php` yang secara otomatis terhubung ke kontainer `sipela-app`.

## 2. Pengelolaan Pengguna & Non-Registrasi Publik (Fase 1)
- **Konteks**: Sesuai `01_SPEC.md` dan `04_AGENT_RULES.md`, aplikasi hanya digunakan oleh staf Puskesmas Payolansek, tidak ada registrasi publik.
- **Keputusan**: Rute `/register` dinonaktifkan sepenuhnya. Model `User` dilengkapi kolom `role` (`admin` | `petugas`) dan `is_active` (boolean default `true`).
- **Otorisasi**: Gate `admin` didefinisikan di `AppServiceProvider` (`role === 'admin' && is_active`). Middleware `can:admin` melindungi seluruh rute master (`/kelurahan`, `/pengguna`).
- **Seeder Awal**: Akun bawaan `admin@sipela.test` (Administrator) dan `petugas@sipela.test` (Petugas Lansia) dengan kata sandi `password`.

## 3. UI Token & Bahasa Indonesia Terpusat (Fase 1)
- **Konteks**: Sesuai `07_SKILL_DESIGN.md` dan `04_AGENT_RULES.md`, teks antarmuka harus berbahasa Indonesia ramah dan terpusat dari satu sumber tanpa hardcode tersebar.
- **Keputusan**:
  - Dibuat `resources/js/lang.js` sebagai sumber tunggal teks UI frontend.
  - Warna primer Tailwind disetel ke Teal (`#0F766E` / hover `#115E59`).
  - Target sentuh minimal 48px (`min-h-[48px]`), teks input minimal 16px (mencegah zoom otomatis pada perangkat mobile Android).
  - Navigasi responsif: Bottom Navigation 64px di mobile (<1024px) dengan tombol Scan tengah menonjol, dan Sidebar navigasi tetap di desktop (≥1024px).

## 4. Skema Kolom & Layanan Kalkulator (Fase 2)
- **Konteks**: Sesuai `02_DATABASE.md` dan `04_AGENT_RULES.md`, kolom database berulang tidak boleh didefinisikan secara redundan di banyak tempat, dan rumus turunan harus berada di satu layanan backend teruji.
- **Keputusan**:
  - Dibuat `app/Support/LansiaFields.php` sebagai Single Source of Truth untuk seluruh kolom sasaran, kunjungan (24 kolom), kemandirian (6 kolom), kelainan (28 kolom), tindakan, dan section OCR.
  - Migrasi `kunjungan_rows` dan `layanan_rows` dibangun menggunakan loop foreach dari `LansiaFields`.
  - Model `KunjunganRow` dan `LayananRow` mengisi properti `$fillable` secara dinamis dari `LansiaFields`.
  - Seluruh rumus turunan dan uji emas resmi diimplementasikan di `app/Services/LansiaCalculator.php`.
  - Persentase baris JUMLAH dihitung ulang dari `spm_total_jumlah / total_lansia_60_jumlah * 100`, bukan rata-rata persentase per kelurahan.
  - Penanganan pembagi nol (`total_lansia_60 = 0`) menghasilkan 0.0% tanpa memicu galat PHP division by zero.

## 5. Alur Form & Penguncian Laporan (Fase 3)
- **Konteks**: Pelaporan dilakukan per bulan untuk 6 kelurahan, dengan alur draft autosave, verifikasi anomali data, dan penguncian final.
- **Keputusan**:
  - `MonthlyReport` memiliki status `draft` dan `final`. Finalisasi mengunci seluruh form edit (`forms.kunjungan.edit` dan `forms.layanan.edit`).
  - Pembuatan laporan bulan baru otomatis menyalin sasaran lansia dan posyandu/kader dari bulan sebelumnya.
  - Tab 6 kelurahan di form input menampilkan indikator status isi real-time.
  - Validasi kewajaran mendeteksi kelainan tanpa kunjungan, atau kunjungan melampaui sasaran sebagai catatan peringatan (warning) tanpa memblokir finalisasi.
  - Buka kunci (reopen) hanya diizinkan untuk peran `admin` via Policy `MonthlyReportPolicy`.

## 6. Statistik & Visualisasi Recharts (Fase 4)
- **Konteks**: Grafik tren kunjungan dan capaian SPM tahunan harus akurat, bebas galat visual ketika ada bulan kosong, dan mencerminkan angka uji emas.
- **Keputusan**:
  - Dibuat `app/Services/StatistikService.php` untuk mengagregasi 12 bulan secara konsisten.
  - Bulan yang belum ada datanya dikembalikan sebagai `null` (bukan `0`), sehingga Recharts merender visual gap alami via `connectNulls={false}` tanpa grafik jatuh ke angka 0.
  - Grafik capaian SPM menyajikan target garis SPM Dinkes (100%) dan bar bulanan (L/P dan Capaian %).

## 7. Ekspor Excel Format Baku Dinkes (Fase 5)
- **Konteks**: Laporan resmi Dinkes Kota Payakumbuh menggunakan template multi-sheet (`Lap. Kunjungan` dan `Lap. Layanan Lansia`) dengan rumus bawaan Dinkes yang kompleks.
- **Keputusan**:
  - Template `storage/app/templates/laporan_lansia_template.xlsx` dimuat menggunakan PhpSpreadsheet, sheet coretan `Sheet1` dihapus otomatis pada berkas hasil ekspor.
  - Seluruh rumus bawaan Excel (kolom N, AN–AY, AZ–BK, BL–BO pada Kunjungan; kolom I, AL–AN pada Layanan) tidak pernah ditimpa dan dibiarkan aktif.
  - Hanya sel input yang ditulis. Nilai `null` dibiarkan kosong (bukan angka 0) agar formula Excel tidak terdistorsi.
  - Seluruh sel input bawaan template yang mengandung data sisa draf tahun sebelumnya dibersihkan saat penyiapan template.
  - 5 perbaikan template resmi Dinkes diterapkan:
    1. Label bulan Agustus (C159) diperbaiki menjadi `: AGUSTUS`.
    2. Header baris 1 disesuaikan secara dinamis dengan tahun yang diekspor (`TA. {$year}`).
    3. Nomor urut kelurahan ke-6 (TALANG) disetel konsisten menjadi 6 (bukan 5) di seluruh 12 blok.
    4. Rumus kolom AM pada Sheet Layanan disesuaikan agar hanya menjumlahkan kolom kelainan perempuan.
    5. Teks draf bebas pada footer dibersihkan dan diganti rumus dinamis serta tanggal unduh terkini.
  - Berkas ditulis dengan `setPreCalculateFormulas(false)` agar Excel mengevaluasi rumus secara alami saat berkas dibuka oleh pengguna, menjamin kompatibilitas penuh dengan Microsoft Excel dan WPS Office.
