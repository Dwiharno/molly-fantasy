# Molly Fantasy Indonesia — Sistem Inventory & Redeem Hadiah

Sistem manajemen inventory, stock opname, dan redeem hadiah berbasis Laravel 12 / PHP 8.3.

## Status Pembangunan

Proyek ini dibangun **bertahap** langsung di chat (bukan digenerate sekali jadi) agar setiap
modul benar-benar lengkap dan berfungsi, bukan skeleton kosong.

**Tahap 1 — SELESAI (fondasi):**
- Struktur project Laravel 12 lengkap (composer.json, konfigurasi, bootstrap)
- Database: migration untuk seluruh tabel (users, items, kategori, stock opname, redeem,
  activity log, settings, google sheets sync log, dst.)
- Autentikasi: Login, Remember Me, Forgot/Reset Password, session login
- 4 Role: Super Admin, Admin, Staff, Viewer (role middleware + read-only enforcement untuk Viewer)
- Arsitektur: Service Layer + Repository Pattern (contoh: `ItemRepository`)
- Dashboard: query real ke database (bukan dummy) — total item, redeem hari ini,
  opname hari ini, tiket diredeem hari ini, top 10 hadiah, 4 grafik Chart.js, log aktivitas
- Layout utama: sidebar, navbar, dark mode toggle, Bootstrap 5 + Font Awesome
- Seeder: 1 akun Super Admin wajib (bukan data dummy)

## Status Pembangunan: SEMUA MODUL SELESAI

Seluruh fitur pada spesifikasi telah dibangun sebagai kode nyata dan berfungsi (bukan
dummy/mockup). Ringkasan tiap modul:

**Login & Autentikasi**
- Login email+password, Remember Me, Forgot/Reset Password, session login, rate limiting

**Dashboard**
- Query real: jumlah item, redeem hari ini, stock opname hari ini, tiket diredeem hari ini
- Top 10 hadiah, 4 grafik Chart.js (redeem, stock opname, barang masuk, barang keluar), log aktivitas

**Master Item**
- CRUD lengkap, upload gambar, DataTables server-side, filter kategori/status/stok minimum
- Import Excel (dengan template) + Export Excel/CSV/PDF
- Quick-add item dari halaman Stock Opname saat barcode belum terdaftar

**Master Kategori, Brand, Supplier**
- CRUD modal masing-masing, validasi tidak bisa dihapus jika masih dipakai item

**Master User**
- CRUD, reset password oleh admin, hak akses berlapis (Admin tidak bisa kelola akun Super Admin)
- Toggle status aktif/nonaktif, tidak bisa hapus akun sendiri

**Stock Opname**
- Halaman scan barcode real-time (mode scan cepat), undo scan, reset scan
- Perbandingan expected vs actual vs selisih, penyesuaian stok otomatis saat disimpan
- Generate Berita Acara (PDF dengan tanda tangan), export PDF/Excel

**Redeem Hadiah**
- Scan tiket 16 digit → ekstrak 5 digit sebelum digit terakhir → dijumlahkan ke pool tiket
- Scan barcode hadiah → validasi tiket cukup & stok tersedia → redeem otomatis
- Popup "Tiket Tidak Mencukupi" / "Stock Habis", cetak struk thermal 58mm

**History Redeem**
- Filter tanggal, kasir, nama barang, barcode; export Excel & PDF

**Google Sheets Sync**
- Service + Queue Job asinkron, sync ke 5 sheet (Master Item, Redeem, Stock Opname, User, Log)
- Log status sync ke tabel `google_sheets_sync_logs`, retry otomatis, notifikasi jika gagal permanen

**Log Aktivitas**
- Semua aksi (login, logout, tambah, edit, delete, redeem, stock opname) tercatat otomatis
- Halaman listing dengan filter user/modul/aksi/tanggal

**Laporan**
- 6 jenis laporan (Redeem, Stock, Barang Masuk, Barang Keluar, User, Selisih Stock)
- Filter tanggal/kategori/kasir, export Excel & PDF di setiap jenis laporan

**Notifikasi**
- Stock minimum, Redeem berhasil/gagal, Google Sheets offline — tersimpan di database,
  ditampilkan lewat bell dropdown di navbar (auto polling) + halaman notifikasi lengkap

**Setting**
- Nama outlet, alamat, logo, jam operasional
- Backup database (download .sql) & Restore database (upload .sql) — implementasi PHP murni,
  tidak bergantung pada binary `mysqldump` agar jalan di semua hosting

**Keamanan & Arsitektur**
- CSRF, XSS protection (Blade escaping), SQL Injection protection (query builder/Eloquent)
- Role & Policy per modul (ItemPolicy, UserPolicy, StockOpnamePolicy)
- Service Layer + Repository Pattern konsisten di seluruh modul
- Transaction DB + try/catch di operasi kritikal (redeem, stock opname, backup/restore)
- Observer (ItemObserver) untuk audit log otomatis

## Catatan Desain & Keterbatasan yang Perlu Diketahui

1. **Pool tiket redeem** dikelola melalui Laravel session (per browser/tab). Jika kasir
   membuka beberapa tab bersamaan, gunakan satu tab per sesi redeem untuk menghindari
   kebingungan saldo tiket.
2. **Backup/restore database** memakai implementasi PHP murni (bukan shell `mysqldump`)
   agar kompatibel di semua hosting shared. Untuk database sangat besar, pertimbangkan
   backup manual via phpMyAdmin/CLI MySQL untuk performa lebih baik.
3. **Google Sheets sync** memerlukan setup Service Account terlebih dahulu (lihat bagian
   di bawah). Tanpa itu, job akan mencatat status "skipped" di log tanpa mengganggu
   operasional aplikasi utama.
4. Paket `spatie/laravel-permission` disertakan di `composer.json` sebagai fondasi jika
   ke depannya dibutuhkan hak akses granular per-fitur (saat ini role sistem memakai
   kolom `role` sederhana di tabel `users` yang sudah mencakup 4 level sesuai spesifikasi).

Katakan modul mana yang ingin dilanjutkan berikutnya.

## Struktur Folder

```
app/
  Http/Controllers/       Controller (Auth, Dashboard, akan bertambah per modul)
  Http/Middleware/        EnsureUserHasRole, EnsureCanWrite (read-only Viewer)
  Http/Requests/          Form Request validation
  Models/                 Eloquent Models
  Repositories/           Repository Pattern (Contracts + implementasi)
  Services/               Service Layer (business logic)
database/
  migrations/             Skema seluruh tabel
  seeders/                DatabaseSeeder (akun Super Admin wajib)
resources/views/          Blade views (layout, auth, dashboard, ...)
routes/                   web.php, api.php, console.php
public/                   index.php, css/app.css, js/app.js
```

## Panduan Instalasi

### 1. Persyaratan
- PHP 8.3+
- Composer 2.x
- MySQL 8.0+
- Node.js (opsional, untuk build asset lanjutan)

### 2. Clone / extract project, lalu install dependency
```bash
composer install
```

### 3. Konfigurasi environment
```bash
cp .env.example .env
php artisan key:generate
```
Edit `.env` dan sesuaikan `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` dengan MySQL Anda.

### 4. Buat database
```sql
CREATE DATABASE molly_fantasy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Jalankan migration & seeder
```bash
php artisan migrate --seed
```
Ini akan membuat seluruh tabel dan **1 akun Super Admin wajib**:
- Email: `superadmin@mollyfantasy.co.id`
- Password: `ChangeMe123!`

⚠️ **Wajib ganti password ini setelah login pertama kali** (fitur ganti password akan
ada di modul Master User / Profile pada tahap berikutnya).

### 6. Buat symlink storage (untuk upload gambar item nantinya)
```bash
php artisan storage:link
```

### 7. Jalankan queue worker (untuk sinkronisasi Google Sheets nanti)
```bash
php artisan queue:work
```

### 8. Jalankan server
```bash
php artisan serve
```
Buka `http://localhost:8000` — akan redirect ke halaman Login.

## Setup Google Sheets API (untuk tahap sinkronisasi nanti)
1. Buka [Google Cloud Console](https://console.cloud.google.com), buat project baru.
2. Aktifkan **Google Sheets API**.
3. Buat **Service Account**, download file JSON credential.
4. Simpan file tersebut di `storage/app/google/service-account.json`.
5. Buka Google Spreadsheet tujuan, klik Share, tambahkan email service account
   (ada di file JSON, field `client_email`) sebagai **Editor**.
6. Isi `GOOGLE_SHEETS_SPREADSHEET_ID` di `.env` dengan ID spreadsheet
   (bagian URL di antara `/d/` dan `/edit`).

## Role & Hak Akses
| Role        | Akses                                                    |
|-------------|-----------------------------------------------------------|
| Super Admin | Seluruh modul termasuk Setting & Master User               |
| Admin       | Seluruh modul operasional, tidak termasuk Setting sistem   |
| Staff       | Input transaksi (Redeem, Stock Opname), tanpa hapus data   |
| Viewer      | Hanya baca (read-only) — semua request non-GET diblokir    |

## Arsitektur Kode
Setiap modul mengikuti pola:
```
Route → Controller → Service (business logic) → Repository (query data) → Model
```
Ini memisahkan logika bisnis dari query database, sehingga mudah diuji dan dikembangkan.

## Keamanan
- CSRF protection aktif di semua form (`@csrf`)
- Password di-hash otomatis (`'password' => 'hashed'` cast)
- Rate limiting pada percobaan login (5x lalu terkunci sementara)
- Role middleware + read-only enforcement untuk role Viewer
- Query builder / Eloquent digunakan di seluruh aplikasi (aman dari SQL Injection)
