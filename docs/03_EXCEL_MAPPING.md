# 03 — EXCEL MAPPING & EKSPOR

## Strategi
Jangan membuat workbook dari nol. **Muat template** `storage/app/templates/laporan_lansia_template.xlsx` dengan PhpSpreadsheet, isi HANYA sel input, biarkan rumus Excel asli tetap hidup, lalu simpan sebagai file baru. Hasilnya identik dengan format Dinkes dan tetap bisa diedit di Excel.

Template punya 2 sheet utama: `Lap. Kunjungan  ` dan `Lap. Layanan Lansia ` (perhatikan spasi di akhir nama sheet — cocokkan persis atau cari lewat `trim()`). Abaikan `Sheet1` (catatan coretan; hapus di file ekspor).

## Geometri blok bulanan (kedua sheet)
- Bulan ke-m (1–12): `offset = 22 × (m − 1)`.
- Sel bulan: kolom C baris `5 + offset` (isi mis. `: JANUARI`).
- Baris kelurahan 1..6: `12 + offset + (i − 1)`; baris JUMLAH: `18 + offset` (rumus sudah ada, jangan ditimpa).
- Urutan kelurahan mengikuti `sort_order`.
- Kolom A = No, B = nama kelurahan (tulis ulang dari DB).
- Judul tahun: ganti teks "TA. 20xx" di baris 1 sesuai tahun laporan. Baris 2–3: nama Puskesmas & kota dari tabel `puskesmas`.

## Sheet Kunjungan — kolom input
| Kolom | Field |
|---|---|
| C | posyandu_count |
| D | pemberdayaan_count |
| E | posyandu_ptm_count |
| F | kader_count |
| G | kader_trained_count |
| H, I | sas_a4559_l, sas_a4559_p |
| J, K | sas_a6069_l, sas_a6069_p |
| L, M | sas_a70_l, sas_a70_p |
| O | jkn_count |
| P … AA (12 kol) | kunjungan **dalam gedung** |
| AB … AM (12 kol) | kunjungan **luar gedung** |
| BP, BQ, BR | mandiri_6069_a, _b, _c |
| BS, BT, BU | mandiri_70_a, _b, _c |

Urutan 12 kolom kunjungan (dari kolom awal): `idx = umur_i×4 + sex_i×2 + tipe_i` dengan umur_i (a4559=0,a6069=1,a70=2), sex_i (l=0,p=1), tipe_i (lama=0,baru=1).
Kolom awal dalam gedung = P (16); luar gedung = AB (28).
Contoh: `in_a6069_p_baru` → idx 1×4+1×2+1 = 7 → kolom 16+7 = 23 = W.

**Jangan tulis** (rumus template): N, AN–AY (total), AZ–BK (standar), BL–BO (Komdat & %).

## Sheet Layanan — kolom input
| Kolom | Field |
|---|---|
| C, D | sas_a4559_l, sas_a4559_p |
| E, F | sas_a6069_l, sas_a6069_p |
| G, H | sas_a70_l, sas_a70_p |
| J … AK (28 kol) | kelainan, urutan key di bawah, tiap key = L lalu P |
| AO, AP, AQ | pengobatan_edukasi, pengobatan_obati, pengobatan_rujuk |
| AR, AS, AT | konseling_baru, konseling_lama, konseling_selesai |
| AU | penyuluhan |
| AV | lansia_bekerja |
| AW | panti_dibina |
| AX | homecare |
| AY | keterangan |

Urutan key kelainan mulai kolom J: gg_me, imt_lebih, imt_kurang, td_tinggi, td_rendah, hb_kurang, kolesterol, dm, asam_urat, ginjal, kognitif, penglihatan, pendengaran, lainnya.
Kolom = `10 + key_i×2 + sex_i` (J=10). **Jangan tulis** I, AL, AM, AN (rumus).

## Perbaikan yang WAJIB dilakukan pada salinan template
1. Sheet Kunjungan, blok Agustus: label bulan tertulis `kubugadang` → ganti `: AGUSTUS`.
2. Sheet Layanan: header baris 1 tertulis "TA. 2024" pada blok pertama → jadikan tahun dinamis.
3. Nomor urut kelurahan ke-6 (TALANG) tertulis 5 → tulis 1..6 dari kode.
4. Sheet Layanan kolom AM (jumlah kelainan P) rumusnya identik dengan kolom AL (menjumlah kolom L saja). Perbaiki: AM = `K+M+O+Q+S+U+W+Y+AA+AC+AE+AG+AI+AK` per baris.
5. Teks bebas `kunj bln ini : 71+65+...` dan tanggal "Payakumbuh, Januari 2024" → ganti dengan nilai otomatis (`total_kunjungan_bulan`) dan tanggal ekspor.
Catat perbaikan ini di komentar kode agar tidak dianggap kesalahan.

## Aturan ekspor
- Rute `GET /ekspor?year=2026[&month=8]`. Tanpa `month` → isi semua bulan yang ada datanya; bulan tanpa laporan dibiarkan kosong.
- Nilai `null` → sel dibiarkan kosong (bukan 0).
- Nama file: `Laporan_Lansia_Payakumbuh_{tahun}_{NAMA_BULAN|SEMUA}.xlsx`.
- Setelah mengisi, panggil kalkulasi ulang (`setPreCalculateFormulas(false)` agar Excel menghitung saat dibuka).
- Uji: ekspor Januari 2026 → buka → sel BN(baris 12) = 143 dan BO = 23,21 (uji emas 02_DATABASE.md).
