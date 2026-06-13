# Standar Operasional Prosedur (SOP) - Web App Satu Seduh

**Nama Sistem:** Aplikasi Point of Sales (POS) & Profil Perusahaan Satu Seduh  
**Lokasi:** Kedai Kopi Premium - Medan, Sumatera Utara  
**Dikelola oleh:** Kelompok 8  

---

## 1. Tujuan
SOP ini disusun sebagai panduan langkah demi langkah dalam menggunakan, mengelola, dan memelihara sistem aplikasi web Satu Seduh. Tujuannya adalah untuk memastikan operasional pelayanan pelanggan (pemesanan menu & reservasi) dan pengelolaan data oleh admin berjalan lancar dan terstruktur.

## 2. Ruang Lingkup
Panduan ini mencakup:
- Prosedur Pemesanan Menu oleh Pelanggan (Frontend)
- Prosedur Reservasi Ruangan oleh Pelanggan (Frontend)
- Prosedur Pembayaran (Frontend)
- Panduan Instalasi dan Teknis (Untuk Developer/IT)

---

## 3. Prosedur Penggunaan Aplikasi (Untuk Pelanggan)

### A. Cara Memesan Menu (Sistem POS)
1. **Buka Halaman Utama:** Pelanggan mengakses halaman utama web Satu Seduh.
2. **Pilih Menu:** Gulir ke bagian **"Menu Pilihan Kami"** atau **"Produk Unggulan"**.
3. **Kustomisasi Pesanan (Khusus Minuman):** 
   - Klik ikon `+` (Tambah) pada menu minuman yang dipilih.
   - Akan muncul *pop-up* kustomisasi.
   - Pelanggan memilih *Temperature* (Hot/Iced), *Ice Level*, *Sugar Level*, dan *Size*.
   - Pelanggan dapat menambahkan *Add-ons* opsional (seperti Caramel Drizzle, dll).
   - Klik tombol **"Masukkan ke Keranjang"**.
4. **Makanan/Produk Lain:** Klik ikon `+` untuk langsung memasukkan menu makanan atau biji kopi ke keranjang tanpa kustomisasi.
5. **Cek Keranjang:** Klik ikon tas belanja (keranjang) di pojok kanan atas layar untuk melihat rincian pesanan.
6. **Checkout:** Klik **"Checkout Sekarang"** dari dalam keranjang belanja.

### B. Proses Pembayaran (Checkout)
1. Pada halaman/modal Checkout, pelanggan wajib mengisi **Informasi Pemesan** (Nama, Nomor Telepon, Nomor Meja, dan Catatan Opsional).
2. **Pilih Metode Pembayaran:**
   - **QRIS / E-Wallet (GoPay, DANA, OVO, ShopeePay):** Akan menampilkan *barcode* QRIS yang harus dipindai oleh pelanggan melalui aplikasi dompet digital.
   - **M-Banking:** Instruksi transfer (opsional) atau *barcode* QRIS universal.
3. Klik **"Checkout Sekarang"**.
4. **Selesaikan Pembayaran:** Pelanggan memindai QRIS (atau menunjukkan nomor pesanan ke kasir jika sistem diatur demikian) dalam batas waktu yang ditentukan.
5. Pelanggan akan mendapatkan rincian nomor pesanan.

### C. Cara Melakukan Reservasi Ruangan
1. Gulir ke bagian **"Reservasi Space Impianmu"** di halaman utama.
2. Pada form reservasi, pilih **Ruangan** yang tersedia (sesuaikan dengan kapasitas).
3. Isi kelengkapan data diri: **Nama Lengkap** dan **No. WhatsApp**.
4. Tentukan detail reservasi:
   - **Tanggal** pelaksanaan.
   - **Waktu Mulai** (Jam & Menit).
   - **Durasi** penyewaan (1 Jam, 2 Jam, dll).
   - **Jumlah Orang** (Tamu).
5. Isi **Keperluan / Catatan** jika membutuhkan fasilitas tambahan (misal: proyektor, layout meja).
6. Klik **"Kirim Reservasi"**.
7. Akan muncul *pop-up* **Reservasi Terkirim**. Pelanggan dapat mengklik tombol **"Cetak / Simpan Bukti"** atau **"Kirim via WhatsApp"** untuk melampirkan konfirmasi ke admin Satu Seduh. Admin akan merespons konfirmasi dalam batas waktu (maksimal 30 menit).

---

## 4. Panduan Teknis & Instalasi Server (Untuk Developer / Admin IT)

Prosedur berikut digunakan jika sistem perlu di-install ulang atau dipindahkan ke server baru.

### A. Persiapan Lingkungan (Environment)
1. Pastikan server (atau komputer lokal) telah terpasang aplikasi tumpukan (stack) **XAMPP**, **WAMP**, atau **MAMP**.
2. Pastikan layanan **Apache** dan **MySQL** dalam keadaan *Running*.

### B. Pemasangan Aplikasi
1. Salin seluruh *folder* aplikasi `satu_seduh_php` ke dalam direktori *web server*:
   - Pada XAMPP: Masukkan ke `C:\xampp\htdocs\`
2. Buka aplikasi manajemen database melalui browser: `http://localhost/phpmyadmin`
3. Buat satu database baru dengan nama: `satu_seduh_db` (atau sesuai konfigurasi).

### C. Konfigurasi Database & Seeding Data
1. Buka browser dan jalankan script *Database Seed* (untuk mengisi tabel dan data awal):
   - Ketik: `http://localhost/satu_seduh_php/db_seed.php`
2. Pastikan muncul pesan sukses bahwa database dan tabel telah berhasil dibuat/diperbarui.
3. (Opsional) Jika ada perubahan struktur tabel baru, jalankan juga skrip pembaruan database: `http://localhost/satu_seduh_php/db_update.php`.
4. Untuk memastikan koneksi backend dan database telah tersambung, verifikasi file konfigurasi yang terletak di `includes/config.php`.

### D. Menjalankan Website
1. Buka browser.
2. Ketik URL alamat website: `http://localhost/satu_seduh_php`
3. Sistem aplikasi siap digunakan oleh pengguna dan pelanggan.


