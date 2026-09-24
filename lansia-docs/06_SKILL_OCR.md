# 06 — SKILL: OCR Laporan Tulisan Tangan

## Keputusan teknis
Tesseract buruk untuk tulisan tangan dan tabel lebar. Gunakan **vision LLM via API** dari server Laravel, dibungkus interface `OcrProvider` agar mudah diganti.
```
interface OcrProvider { public function extract(string $imagePath, array $sectionSchema): OcrResult; }
```
`.env`: `OCR_PROVIDER=anthropic`, `ANTHROPIC_API_KEY=`, `OCR_MODEL=claude-sonnet-5`, `OCR_MAX_IMAGE_PX=2000`. Model dapat diganti lewat env tanpa ubah kode.

## Bagian formulir (`section`)
Petugas memilih bagian sebelum memotret → daftar field yang dikirim ke model kecil dan akurat.
| section | Field |
|---|---|
| `kunjungan_umum_sasaran` | posyandu_count … jkn_count + 6 sasaran |
| `kunjungan_dalam` | 12 kolom `in_*` |
| `kunjungan_luar` | 12 kolom `out_*` |
| `kunjungan_mandiri` | 6 kolom `mandiri_*` |
| `layanan_sasaran_kelainan` | 6 sasaran + 28 `kel_*` |
| `layanan_tindakan` | pengobatan_*, konseling_*, penyuluhan, lansia_bekerja, panti_dibina, homecare |

Foto berisi tabel banyak baris (6 kelurahan): model diminta membaca **per baris kelurahan** (dicocokkan ke nama pada baris; jika nama tak terbaca, pakai urutan 1–6).

## Alur
1. `/scan`: pilih bagian → ambil foto (`<input type="file" accept="image/*" capture="environment">`) atau unggah.
2. Klien: perkecil sisi terpanjang ke ≤2000 px, JPEG 0,85, koreksi rotasi EXIF, kirim.
3. Server: validasi → simpan privat → buat `ocr_uploads(status=queued)` → job `ProcessOcrUpload`.
4. Job: bangun prompt + skema → panggil provider → parse JSON → validasi tipe → hitung `confidence` → simpan `result_json`, `confidence_json`, status `done` (gagal → `failed` + pesan).
5. Halaman pratinjau polling status tiap 2 dtk (maks 60 dtk), tampilkan progres "Sedang membaca formulir…".
6. Pratinjau: foto (bisa zoom) + form terisi. Sel `confidence < 0.8` atau `null` berwarna **kuning** + ikon; sel kosong wajib dilihat petugas. Tombol besar **Simpan ke laporan**; tombol **Foto ulang**; tombol **Isi manual saja**.
7. Simpan = tulis ke `kunjungan_rows`/`layanan_rows` (status laporan tetap draft). Sel yang tidak diubah petugas dan bernilai `null` tetap `null`.
8. Hapus foto sesuai retensi (default 30 hari) lewat scheduled command.

## Kontrak keluaran model (JSON ketat)
```json
{
  "rows": [
    {
      "row_index": 1,
      "kelurahan_name": "PAYOLANSEK",
      "fields": {
        "in_a6069_l_baru": {"value": 16, "confidence": 0.93},
        "in_a6069_p_baru": {"value": null, "confidence": 0.0}
      }
    }
  ],
  "notes": "opsional, mis. foto miring/buram"
}
```
Backend menolak/mengabaikan: key di luar skema, nilai bukan integer ≥ 0, `confidence` di luar 0–1. Nilai tak valid → `null` + ditandai kuning.

## Prompt sistem untuk model (simpan di `resources/prompts/ocr_system.txt`)
```
Anda membaca foto formulir pelaporan kesehatan lansia Puskesmas (Indonesia) berisi tabel angka tulisan tangan.
Tugas: ekstrak angka ke JSON sesuai skema field yang diberikan. Jangan menjelaskan apa pun di luar JSON.
Aturan:
- Hanya isi field yang ada di skema. Jangan menambah field.
- Satu baris tabel = satu kelurahan. Baca kelurahan dari kolom nama; jika tak terbaca, isi kelurahan_name null.
- Nilai harus bilangan bulat >= 0. Sel kosong, tercoret, atau tak terbaca => value null (BUKAN 0). Angka 0 hanya jika benar-benar tertulis.
- Jangan menebak. Jangan menghitung total; abaikan kolom total/persen yang sudah tercetak.
- Hati-hati: 1 vs 7, 0 vs 6, 4 vs 9, 5 vs 6. Coret dan tulis ulang: ambil angka terakhir.
- confidence 0–1 per field: 0.9+ jelas, 0.6–0.9 ragu, <0.6 sangat ragu.
- Pertahankan urutan baris dan kolom sesuai foto. Jika foto miring/buram, isi "notes".
```
Pesan pengguna: gambar + `section` + daftar field terurut sesuai kolom foto (label Indonesia + kode field) + jumlah baris yang diharapkan.

## Pemeriksaan pasca-OCR (peringatan, bukan penolakan)
- Baris tidak lengkap; jumlah baris ≠ jumlah kelurahan aktif.
- Nilai >5× median kolom yang sama.
- Sasaran menyimpang >20% dari bulan lalu.
- Setelah simpan, tampilkan hasil hitung otomatis (SPM & %) agar petugas bisa cek kewajaran.

## Kualitas & uji
- Siapkan 5–10 foto contoh di `tests/fixtures/ocr/` beserta JSON jawaban benar; skrip `php artisan ocr:eval` melaporkan akurasi per field (target ≥90% angka jelas).
- Tes otomatis memakai provider palsu (mock) — tidak memanggil API sungguhan.
- Panduan pengguna singkat di halaman Scan: foto tegak lurus, cahaya rata, seluruh tabel masuk bingkai, tanpa bayangan tangan.

## Privasi & biaya
- Kirim hanya foto formulir agregat; tanpa nama lansia. Jangan log isi gambar/hasil mentah ke log umum.
- Batasi 30 pemindaian/hari/pengguna (konfigurasi). Simpan `tokens_used` bila tersedia untuk pemantauan.
