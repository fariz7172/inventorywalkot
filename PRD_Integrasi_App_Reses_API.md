# Product Requirements Document (PRD): Dual-Sync Integrasi Inventory & App-Reses API

**Aplikasi Klien:** Sistem Informasi Inventory & Laporan RAB SDA JU (`D:\program file\Project Kantor\Inventory`)  
**Aplikasi Server:** Sistem Manajemen Reses & Aspirasi (`D:\program file\Project Kantor\App-Reses`)  
**Target Produksi:** `https://app-reses.farizahmad.com/api`  
**Tanggal Update:** 18 Oktober 2026  
**Status:** Produksi / Aktif  
**Penulis:** Fariz & Geta  

---

## 1. Ringkasan & Tujuan (Executive Summary)
Memungkinkan aplikasi **Inventory SDA JU** menyinkronkan data Laporan RAB (Rencana Anggaran Biaya), data lapangan, dan dokumentasi foto secara langsung dan instan (**dual-sync**) ke sistem **App-Reses Production** (`https://app-reses.farizahmad.com/api`) tanpa harus menginput ulang manual di dashboard web App-Reses.

### Manfaat & Dampak:
1. **Efisiensi Kerja:** 1 kali klik simpan/sinkron di Laporan RAB Inventory otomatis membuat record `survei_reses`, `pekerjaan_sdas`, dan `surat_permohonans` di App-Reses.
2. **Kualitas Data Presisi:** Koordinat GPS di wilayah DKI Jakarta / Jakarta Utara (yang memiliki nilai Latitude negatif `-6.xxxx`) kini tersanitasi dengan benar tanpa membuang tanda minus.
3. **Resiliensi Integrasi:** Error validasi field dari API diparsing detail per field (bukan pesan generic), sehingga admin langsung mengetahui input apa yang perlu diperbaiki.

---

## 2. Alur Kerja Dual-Sync (Workflow)

```
[User di Inventory: Laporan RAB]
         │
         ▼
[Klik "Kirim ke App-Reses" / Update]
         │
         ├─► 1. Sanitasi Koordinat (Toleran tanda minus `-` untuk Lat/Lng)
         ├─► 2. Validasi Kecamatan & Kelurahan (Fallback otomatis jika mismatch)
         ├─► 3. Generate Kode Tracking Unik (`TRK-YYYYMMDD-RAB{id}-{uniq}`)
         │
         ▼
[AppResesApiService::sendSurveiReses / sendPekerjaanSda]
         │
         ▼ (Header: Bearer Token Sanctum Produksi)
[Target: https://app-reses.farizahmad.com/api]
         │
         ├─► POST /api/survei-reses (Sumber Data: Inventory, id_dewan opsional)
         │       └─► Otomatis tergenerate Surat Permohonan & Pekerjaan SDA
         │
         └─► POST /api/pekerjaan-sda (Detail fisik, pelaksana, volume, koordinat, Sumber Data: Inventory, foto WebP)
```

---

## 3. Spesifikasi Perubahan Teknis (Inventory)

### A. Sanitasi Koordinat GPS (`resources/views/livewire/laporan/rab.blade.php`)
- **Masalah Lama:** Regex sanitasi angka membersihkan semua karakter selain digit, koma, dan titik (`/[^0-9,\.]/`). Hal ini menghapus tanda minus (`-`) pada Latitude Jakarta (misal `-6.12345` menjadi `6.12345`), menyebabkan titik GPS melompat ke belahan bumi utara atau ditolak validasi batas wilayah.
- **Perbaikan:** Menambahkan flag `$allowNegative = true` pada helper `$cleanNum`:
  ```php
  $cleanNum = function($val, bool $allowNegative = false) {
      if ($val === null || $val === '') return null;
      $pattern = $allowNegative ? '/[^0-9,\.\-]/' : '/[^0-9,\.]/';
      $str = str_replace(',', '.', preg_replace($pattern, '', (string)$val));
      return is_numeric($str) ? (float)$str : null;
  };
  ```

### B. Pembaruan Master Pelaksana (`app/Services/AppResesApiService.php`)
- Penyesuaian pemetaan ID Pelaksana sesuai master data Sudin SDA Jakarta Utara:
  - `ID 8`: **SUDIN SDA JAKARTA UTARA (Default)**
  - `ID 2`: Seksi Drainase
  - `ID 1`: Slamet Riyanto (Satpel SDA Koja)
  - `ID 3`: Novita Kesnanda (Satpel SDA Pademangan)
  - `ID 4`: Pendi (Satpel SDA Penjaringan)
  - `ID 5`: Ichsan Nasution (Satpel SDA Cilincing)
  - `ID 6`: Neti Heriati (Satpel SDA Tanjung Priok)
  - `ID 7`: Supiyan (Satpel SDA Kelapa Gading)

### C. Standardisasi Error Handling API
- Response error HTTP 422 dari App-Reses dipetakan langsung dengan detail field:
  ```php
  $message .= ' (' . implode('; ', $formatted) . ')';
  // Contoh: "Validasi gagal (alamat: The alamat field is required; progress: The progress must not be greater than 100)"
  ```

### D. Konfigurasi Endpoint & Token Produksi (`config/services.php`)
- `APP_RESES_API_URL`: `https://app-reses.farizahmad.com/api`
- `APP_RESES_API_TOKEN`: `1|NOOazE9wfjBcGD7pt850sGuQ9gXC4HyURp8X6b2W3b61246b`

### E. Penyesuaian Sumber Data (`sumber_data = 'Inventory'`)
- **Latar Belakang:** Sebelumnya, nilai `sumber_data` secara default terisi sebagai `"Masyarakat"`. Berdasarkan kebutuhan identifikasi sumber data di App-Reses, data yang diinput dari sistem Inventory SDA JU harus secara eksplisit tercatat bersumber dari **"Inventory"** guna membedakan aspirasi permohonan warga langsung dengan pekerjaan fisik yang diinisiasi oleh sistem internal kantor.
- **Penerapan Teknis:**
  - **Tampilan Modal:** Kolom Sumber Data menampilkan teks statis **"Inventory"** dengan badge **"Aplikasi Inventory"**.
  - **Payload Pengiriman API:** Pada `surveiPayload` dan `pekerjaanPayload`, parameter `sumber_data` disetel menjadi `'Inventory'`.
  - **Database App-Reses:** Pada tabel `pekerjaan_sdas`, kolom `sumber_data` akan tersimpan dengan nilai **`"Inventory"`**.

---

## 4. Changelog Fitur
- **2026-10-22:**
  - `feat`: Mengubah parameter `sumber_data` dari **Masyarakat** menjadi **Inventory** pada modal RAB dan payload pengiriman API App-Reses.
  - `deploy`: Deployment pembaruan `sumber_data` sukses diterapkan ke server production `inventorysdaju.com`.
- **2026-10-18:**
  - `feat`: Integrasi dual-sync RAB ke endpoint production App-Reses Fariz Ahmad (`https://app-reses.farizahmad.com/api`).
  - `fix`: Perbaikan sanitasi koordinat GPS mempertahankan tanda negatif `-` pada Latitude belahan bumi selatan.
  - `fix`: Sinkronisasi master ID pelaksana `8` (Sudin SDA JU) sebagai default.
  - `fix`: Format pesan error API transparan menampilkan field yang konflik saat validasi 422.
