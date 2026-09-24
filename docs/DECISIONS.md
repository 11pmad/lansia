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
