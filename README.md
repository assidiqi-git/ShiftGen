# Schedule Generator

Aplikasi web untuk membuat jadwal shift pegawai secara otomatis, lalu melakukan penyesuaian manual lewat tampilan grid (drag & drop), dan mem-publish jadwal yang sudah final.

## Fitur

- Master data **Pegawai** (nama + warna identitas).
- Master data **Shift** (jam mulai/selesai, durasi otomatis, urutan/sort order).
- **Validasi total durasi semua shift per hari ≤ 24 jam**.
- **Generate jadwal (draft)** untuk rentang tanggal (maks. 31 hari).
- Aturan penjadwalan:
  - Setiap shift per hari diisi 1 pegawai.
  - Jika pegawai < jumlah shift, sistem akan menandai penugasan tambahan sebagai **lembur (OT)**.
  - Pegawai **tidak boleh** mendapat shift yang **bersebelahan** pada hari yang sama (wajib ada jeda ≥ 1 shift).
- **Preview jadwal**:
  - Grid shift vs tanggal.
  - Drag & drop untuk memindahkan jadwal, atau drop ke kartu lain untuk **swap**.
  - **Publish semua** jadwal draft dalam rentang tanggal.
  - Ringkasan “Summary Shift Per Pegawai” (normal/lembur/total shift/total jam).
  - Export grid jadwal ke **PNG**.

## Teknologi

- Laravel 13
- Livewire 4 (Volt-style components di `resources/views/components`)
- Livewire Flux UI (`livewire/flux`)
- Vite + Tailwind CSS v4
- Alpine.js (interaksi drag & drop)
- Pest (testing)

## Prasyarat

- PHP ^8.3 + Composer
- Node.js (CI menggunakan Node 22)
- SQLite (default `.env.example` sudah memakai `DB_CONNECTION=sqlite`)
- Akses paket **Livewire Flux** (butuh kredensial Composer untuk domain `composer.fluxui.dev`)

## Instalasi (Local Development)

1. Clone repo
2. Tambahkan kredensial Flux ke Composer (contoh):

   ```bash
   composer config http-basic.composer.fluxui.dev "<FLUX_USERNAME>" "<FLUX_LICENSE_KEY>"
   ```

3. Siapkan database SQLite (buat file kosong `database/database.sqlite`):

   ```bash
   touch database/database.sqlite
   ```

   Windows (PowerShell):

   ```powershell
   New-Item -ItemType File -Force database/database.sqlite
   ```

4. Install dependency PHP & siapkan environment:

   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

5. Jalankan migrate:

   ```bash
   php artisan migrate
   ```

6. Install dependency frontend & build asset:

   ```bash
   npm install
   npm run build
   ```

Alternatif (1 perintah): setelah langkah (2) dan (3), kamu bisa menjalankan:

```bash
composer setup
```

## Menjalankan Aplikasi

Opsi 1 (disarankan): jalankan semua proses dev sekaligus:

```bash
composer dev
```

Opsi 2: jalankan manual di terminal terpisah:

```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1
```

Lalu buka `APP_URL` (default: `http://localhost:8000` saat memakai `php artisan serve`).

## Cara Pakai (Flow)

1. Tambahkan data pegawai di halaman **Employees**
2. Tambahkan data shift di halaman **Shifts**
3. Buka **Schedule → Generate** untuk membuat jadwal draft
4. Buka **Schedule → Preview** untuk:
   - memindahkan/menukar jadwal dengan drag & drop
   - mem-publish jadwal
   - export PNG

## Testing & Lint

- Menjalankan seluruh check (lint check + test):

  ```bash
  composer test
  ```

- Format kode (Laravel Pint):

  ```bash
  composer lint
  ```

- Cek format tanpa mengubah file:

  ```bash
  composer lint:check
  ```

## Catatan CI (GitHub Actions)

Workflow CI mengandalkan secret berikut untuk mengunduh paket Flux:

- `FLUX_USERNAME`
- `FLUX_LICENSE_KEY`
