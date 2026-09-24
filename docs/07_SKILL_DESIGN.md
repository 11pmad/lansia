# 07 — SKILL: Desain UI (mobile-first, sederhana, jelas)

Pengguna: petugas Puskesmas, umumnya >35 th, HP Android, kadang cahaya terang/luar ruangan. Rancang untuk **terbaca, tidak salah tekan, sedikit langkah**.

## Prinsip
1. **Satu tugas per layar.** Jangan menampilkan tabel lebar 70 kolom di mobile; pecah menjadi bagian kecil.
2. **Besar dan jelas.** Teks dasar 16–18px, label 15px minimum, angka input 20px. Target sentuh ≥48×48px, jarak antar tombol ≥8px.
3. **Kontras tinggi** (WCAG AA ≥4,5:1). Jangan mengandalkan warna saja: sertakan ikon/teks.
4. **Bahasa sehari-hari.** "Simpan", "Foto ulang", "Lanjut" — bukan istilah teknis. Singkatan Dinkes (Abs, Komdat) hanya di rekap/ekspor, dengan penjelasan ringan.
5. **Umpan balik selalu terlihat**: "Tersimpan ✓", loading, error yang menyebut cara memperbaiki.
6. **Aman dari salah**: konfirmasi hanya untuk aksi tak bisa dibatalkan (Finalisasi, Hapus); lainnya beri "Urungkan".

## Token desain (Tailwind config)
| Token | Nilai |
|---|---|
| Primer | teal `#0F766E` (hover `#115E59`), teks di atas primer putih |
| Latar | `#F8FAFC`; kartu putih, border `#E2E8F0`, radius 12px |
| Teks | utama `#0F172A`, sekunder `#475569` |
| Sukses / Peringatan / Bahaya | `#15803D` / `#B45309` bg `#FEF3C7` / `#B91C1C` |
| Grafik | Total `#0F766E`, Dalam gedung `#0EA5E9`, Luar gedung `#F59E0B`, L `#2563EB`, P `#DB2777`, % garis `#0F172A` |
| Font | `Inter` atau system-ui; angka `tabular-nums` |
| Spasi | skala 4/8/12/16/24; padding halaman 16px |
| Bayangan | minimal (`shadow-sm`) |

## Layout
- Header 56px (judul halaman + tombol kembali). **Bottom nav 64px** dengan 5 item (ikon+label): Beranda, Laporan, Scan (tombol tengah menonjol), Statistik, Menu.
- Desktop ≥1024px: sidebar kiri menggantikan bottom nav; konten maks 1100px; form dua kolom.
- Tombol aksi utama menempel di bawah (sticky, di atas bottom nav): satu tombol primer per layar.

## Komponen kunci
- **NumberField**: label di atas, input besar rata kanan, `inputmode="numeric"`, tombol −/+ (opsional pada kolom sasaran), isi seluruhnya terseleksi saat fokus, batas merah bila tidak valid, tidak menerima huruf.
- **SectionCard (accordion)**: judul + status (kosong / sebagian / lengkap). Hanya satu terbuka.
- **KelurahanTabs**: chip horizontal bisa digeser, kelurahan aktif berwarna primer, titik hijau bila lengkap.
- **Grup L/P**: dua kolom bersampingan berlabel "Laki-laki" (biru) & "Perempuan" (merah muda) + ikon, agar tidak tertukar; untuk kunjungan susun per kelompok umur → per lama/baru.
- **Kartu hasil otomatis**: latar abu muda, tulisan "Dihitung otomatis", tidak bisa diedit.
- **ConfidenceCell** (OCR): latar kuning `#FEF3C7` + ikon ⚠, ketuk untuk melihat potongan foto bila tersedia.
- **StatCard**: angka besar 28px + label + selisih vs bulan lalu.
- **Empty state**: ilustrasi sederhana + satu kalimat + tombol aksi ("Belum ada laporan Agustus — Buat sekarang").
- **Toast**: bawah layar di atas bottom nav, 3 detik, bisa ditutup.

## Halaman penting (wireframe teks)
**Beranda**: sapaan singkat → kartu "Laporan Agustus 2026 · Draft · 4/6 kelurahan terisi" → dua tombol besar [📷 Scan Laporan] [✍️ Isi Manual] → kartu tren mini → kartu SPM bulan ini (% besar).
**Form Kunjungan**: chip kelurahan → accordion (Umum · Sasaran · Dalam Gedung · Luar Gedung · Kemandirian) → kartu hasil otomatis → sticky [Simpan & Lanjut].
**Pratinjau OCR**: foto (tinggi 30% layar, bisa perbesar) → form terisi dengan sel kuning → sticky [Simpan ke laporan] + tautan "Foto ulang".
**Statistik**: filter tahun/kelurahan (dropdown besar) → grafik Tren Kunjungan → grafik "Dilayani Sesuai Standar >60 th" (L Abs, P Abs, TOTAL, %) → tabel data di bawah (scroll horizontal, kolom pertama sticky).
**Rekap**: tabel dengan kolom nama sticky, header sticky, baris JUMLAH tebal, tombol Ekspor.

## Aksesibilitas
Label terhubung ke input, fokus terlihat (ring 2px), urutan tab logis, `aria-live` pada "Tersimpan", dukung zoom teks 200% tanpa layout pecah, ikon selalu berteks.

## Jangan
Jangan pakai dropdown kecil untuk angka, modal bertumpuk, tabel lebar tanpa sticky, teks <14px, warna merah/hijau tanpa ikon, animasi berlebihan, atau menyembunyikan aksi penting di menu titik tiga.

## Checklist visual sebelum selesai
- [ ] 360×640: tidak ada scroll horizontal di luar tabel rekap
- [ ] Semua tombol ≥48px, teks ≥16px
- [ ] Kontras AA, tanpa info hanya lewat warna
- [ ] Ada state loading, kosong, error
- [ ] Bisa isi satu kelurahan penuh dengan satu tangan (ibu jari)
