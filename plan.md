# Project Plan: Smart Shift Scheduling System

## 1. Project Overview
Membuat sistem rekomendasi dan manajemen jadwal kerja pegawai mingguan/bulanan. Sistem akan menghasilkan draf jadwal otomatis berdasarkan aturan bisnis (ketersediaan, lembur, jeda shift) yang kemudian dapat diubah secara interaktif oleh pengguna sebelum diekspor.

## 2. Tech Stack
* **TALL Stack:**
    * **T**ailwind CSS (Styling & UI)
    * **A**lpine.js (Interaktivitas frontend, Drag & Drop, DOM manipulation)
    * **L**aravel (Backend framework, routing, database ORM)
    * **L**ivewire (Komunikasi reaktif antara frontend dan backend tanpa reload)
* **Libraries Tambahan:**
    * `html2canvas` / `dom-to-image` (Untuk fitur Export to .png via JavaScript)
    * `SortableJS` (Opsional, diintegrasikan dengan Alpine/Livewire untuk Drag & Drop yang mulus)

## 3. Database Schema (Migration Plan)
Agent harus membuat model dan migration berikut:

* **`employees`**
    * `id`, `name`, `created_at`, `updated_at`
* **`shifts` (Master Shift dalam 1 hari)**
    * `id`, `name` (Pagi, Siang, Malam, dll), `start_time`, `end_time`, `duration_hours`
* **`schedules` (Jadwal Aktual)**
    * `id`, `employee_id` (foreign key), `shift_id` (foreign key), `date` (date), `is_overtime` (boolean), `status` (draft/published)

## 4. Core Business Rules & Algorithm
Saat membuat fungsi "Generate Schedule", agent harus mengimplementasikan logika berikut:
1.  **Validasi Shift Harian:** Total durasi semua shift dalam 1 hari tidak boleh melebihi 24 jam. Shift bisa dicustom durasinya.
2.  **Distribusi Pegawai:** Assign pegawai ke shift yang tersedia pada hari tersebut.
3.  **Aturan Lembur (Overtime):** * Jika `jumlah_pegawai < jumlah_shift_per_hari`, sistem mengizinkan lembur.
    * Pemilihan lembur bisa manual (dipilih user nanti) atau otomatis oleh sistem (mengutamakan pegawai dengan total jam kerja terendah minggu itu).
4.  **Aturan Multi-Shift & Jeda:**
    * Seorang pegawai **BOLEH** memiliki > 1 shift dalam hari yang sama.
    * **TIDAK BOLEH** berturut-turut. Harus ada *gap* minimal 1 shift di antaranya. (Contoh: Boleh Shift 1 dan Shift 3. Tidak boleh Shift 1 dan Shift 2).

## 5. UI/UX & Features Development Phases

### Phase 1: Setup & Master Data
- [ ] Install Laravel, Livewire, Alpine, dan Tailwind.
- [ ] Buat CRUD sederhana untuk `Employees`.
- [ ] Buat UI untuk mengatur `Shifts` harian (tambah shift, set jam mulai/selesai, validasi total max 24 jam).

### Phase 2: Schedule Generator Engine
- [ ] Buat form input parameter jadwal (Pilih rentang tanggal: Mingguan/Bulanan).
- [ ] Buat *Action/Service class* di Laravel yang menjalankan algoritma pembagian shift sesuai **Core Business Rules**.
- [ ] Simpan hasil *generate* ke tabel `schedules` dengan status `draft`.

### Phase 3: Interactive Preview & Drag-and-Drop
- [ ] Buat Livewire component `SchedulePreview`.
- [ ] Tampilkan UI berbentuk kalender atau grid tabel (Baris = Shift, Kolom = Hari/Tanggal).
- [ ] Tampilkan `name` pegawai dan indikator `is_overtime` di setiap kotak jadwal.
- [ ] **Drag & Drop Implementation:**
    * Gunakan Alpine.js (dan SortableJS jika perlu) pada elemen *card* pegawai.
    * Saat pegawai dipindahkan dari satu shift ke shift lain (pada UI), trigger fungsi Livewire (misal: `updateSchedule($scheduleId, $newShiftId, $newDate)`) untuk mengupdate database secara *real-time*.
    * Tambahkan validasi di backend saat *drop*: Cegah *drop* jika melanggar aturan jeda 1 shift.

### Phase 4: Export Feature
- [ ] Tambahkan tombol "Export to PNG" di halaman Preview.
- [ ] Implementasikan script Alpine/JS menggunakan `html2canvas` yang menargetkan ID dari *container* tabel/grid jadwal.
- [ ] Konversi canvas menjadi format gambar (.png) dan picu *download* otomatis di browser.

## 6. Testing & Edge Cases Handling
Agent harus memastikan untuk menangani skenario berikut:
- Bagaimana jika jumlah pegawai sangat sedikit sehingga aturan jeda 1 shift tidak mungkin terpenuhi secara matematis? (Berikan alert error atau tawarkan penambahan pegawai/pengurangan shift).
- Responsivitas tabel preview saat data bulanan ditampilkan (gunakan *horizontal scroll* pada Tailwind).