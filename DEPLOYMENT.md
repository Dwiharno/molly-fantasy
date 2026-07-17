# Deployment gratis: Render + Neon

Konfigurasi ini mempertahankan MySQL untuk pengembangan lokal dan menggunakan
PostgreSQL saat online. Paket gratis cocok untuk uji coba operasional, bukan
untuk layanan produksi dengan jaminan uptime.

## 1. Buat database Neon

1. Buat akun di <https://console.neon.tech>.
2. Buat project baru dan pilih region Singapore bila tersedia.
3. Salin connection string PostgreSQL. Gunakan pooled connection string bila
   Neon menyediakannya.
4. Simpan connection string ini. Jangan masukkan ke repository.

## 2. Siapkan secret aplikasi

Jalankan di komputer lokal:

```bash
php artisan key:generate --show
```

Simpan hasil `base64:...` sebagai `APP_KEY` di Render.

Tentukan juga:

- `ADMIN_EMAIL`: email login Super Admin pertama.
- `ADMIN_PASSWORD`: password acak minimal 12 karakter.
- `DATABASE_URL`: connection string dari Neon.
- `APP_URL`: isi setelah Render memberikan URL, misalnya
  `https://molly-fantasy.onrender.com`.

## 3. Push repository ke GitHub

Repository sebaiknya **private**. Pastikan `.env` dan file JSON Google Service
Account tidak ikut di-commit.

File yang dibutuhkan Render sudah tersedia:

- `Dockerfile`
- `docker/entrypoint.sh`
- `render.yaml`
- `.dockerignore`

## 4. Buat Render Blueprint

1. Masuk ke <https://dashboard.render.com>.
2. Pilih **New > Blueprint** dan hubungkan repository GitHub.
3. Render membaca `render.yaml`.
4. Isi secret yang diminta:
   - `APP_KEY`
   - `APP_URL` (boleh URL sementara, perbarui setelah service dibuat)
   - `DATABASE_URL`
   - `ADMIN_EMAIL`
   - `ADMIN_PASSWORD`
5. Biarkan Google Sheets nonaktif pada deployment pertama.
6. Jalankan deployment dan buka endpoint `/up` untuk health check.

Saat container mulai, aplikasi otomatis menjalankan migration dan seeder yang
idempotent. Seeder tidak menimpa password akun yang sudah ada.

## 5. Aktifkan Google Sheets (opsional)

Jangan upload file credential ke GitHub. Encode JSON service account menjadi
base64, simpan hasilnya sebagai `GOOGLE_SHEETS_CREDENTIALS_BASE64`, lalu set:

```dotenv
GOOGLE_SHEETS_SYNC_ENABLED=true
GOOGLE_SHEETS_SPREADSHEET_ID=id_spreadsheet
```

Pada paket gratis, `QUEUE_CONNECTION=sync` digunakan agar sinkronisasi tidak
membutuhkan background worker terpisah.

## 6. Pemeriksaan setelah deploy

- Login dengan akun Super Admin.
- Segera ganti password awal.
- Buat satu item percobaan.
- Uji scan tiket, redeem, update qty, reset, selesai transaksi, dan struk.
- Pastikan History Redeem dan mutasi stok tersimpan setelah service tidur dan
  aktif kembali.

## Batasan gratis

- Render tidur setelah tidak ada trafik dan akses pertama dapat lambat.
- Filesystem container bersifat sementara. Logo atau gambar yang diupload ke
  disk lokal dapat hilang saat redeploy/restart.
- Database Neon tetap menjadi penyimpanan utama dan tidak berada di filesystem
  Render.
- Fitur backup/restore SQL bawaan aplikasi saat ini khusus MySQL. Untuk database
  Neon, lakukan export/restore melalui Neon atau `pg_dump` dari komputer admin.
- Lakukan backup berkala sebelum aplikasi dipakai untuk transaksi nyata.
