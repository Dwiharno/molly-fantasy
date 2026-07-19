# Deployment gratis: Replit + Neon

## Deployment otomatis InfinityFree melalui GitHub Actions

Workflow `.github/workflows/deploy-infinityfree.yml` menjalankan test, membangun paket production, lalu mengunggahnya ke `/htdocs/` setiap kali branch `main` menerima push.

Tambahkan repository secrets melalui GitHub → **Settings → Secrets and variables → Actions → New repository secret**:

- `FTP_SERVER`: `ftpupload.net`
- `FTP_USERNAME`: username akun InfinityFree
- `FTP_PASSWORD`: password akun InfinityFree
- `DB_PASSWORD`: password MySQL/hosting InfinityFree
- `APP_KEY`: nilai `APP_KEY` production yang sekarang dipakai website

Jangan menulis nilai rahasia tersebut di workflow, commit, issue, atau chat.

Migration database tidak dapat dijalankan dari GitHub runner karena MySQL InfinityFree hanya menerima koneksi dari lingkungan hosting. Untuk rilis Member + Offline, impor satu kali file `deployment/infinityfree/migrations/2026_07_19_member_offline_redeem.sql` melalui phpMyAdmin InfinityFree.

Setelah secrets dan migration siap, workflow dapat dijalankan dari tab **Actions → Deploy InfinityFree → Run workflow**, atau otomatis melalui push ke `main`.

Target utama saat ini adalah Replit Autoscale dengan Neon PostgreSQL sebagai
penyimpanan permanen. Konfigurasi Render tetap tersedia sebagai alternatif.

## Deployment melalui Replit

1. Masuk ke <https://replit.com> dan pilih **Create App > Import from GitHub**.
2. Impor repository `Dwiharno/molly-fantasy` dari branch `main`.
3. Replit membaca `.replit` dan `replit.nix`, kemudian menyediakan PHP,
   Composer, Node.js, serta ekstensi PostgreSQL.
4. Buka **Secrets** dan tambahkan seluruh variable pada bagian berikut.
5. Klik **Run** dan pastikan Preview dapat membuka aplikasi.
6. Buka **Publishing**, pilih **Autoscale**, salin secrets ke Deployment
   Secrets, kemudian Publish.

### Secrets Replit

```dotenv
APP_NAME=Molly Fantasy
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_URL=https://alamat-app.replit.app
APP_KEY=base64:hasil_dari_php_artisan_key_generate_show
DATABASE_URL=pooled_connection_string_neon
DB_CONNECTION=pgsql
DB_SSLMODE=require
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
RUN_SEEDER=true
ADMIN_EMAIL=email_admin
ADMIN_PASSWORD=password_admin_yang_kuat
GOOGLE_SHEETS_SYNC_ENABLED=false
LOG_CHANNEL=stderr
LOG_LEVEL=warning
```

Untuk membuat `APP_KEY`, jalankan `php artisan key:generate --show` di terminal
lokal dan simpan hasil lengkap yang diawali `base64:` sebagai Secret.

Build command dan Run command sudah berasal dari `.replit`:

```text
Build: bash replit/build.sh
Run:   bash replit/run.sh
```

Preview menggunakan port 8000 dan deployment memetakan port tersebut ke port
publik 80.

---

## Alternatif: Render + Neon

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

`APP_KEY_SECRET` dibuat otomatis oleh Render dan startup script mengubahnya ke
format encryption key Laravel. Tentukan juga:

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
