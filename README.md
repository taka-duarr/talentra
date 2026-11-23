# Talentra

> **Tumbuh · Uji · Berkembang**

Talentra adalah platform belajar dan try out online untuk **TKA (SD/SMP/SMA), UTBK-SNBT, CPNS, BUMN, dan Kedinasan**. Peserta belajar materi, mengerjakan simulasi ujian berbasis komputer, lalu mendapat analisis hasil dan rekomendasi belajar.

Dokumen kebutuhan lengkap ada di [`docs/REQUIREMENTS.md`](docs/REQUIREMENTS.md).

---

## Daftar isi

- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Persyaratan](#persyaratan)
- [Instalasi lokal](#instalasi-lokal)
- [Menjalankan aplikasi](#menjalankan-aplikasi)
- [Akun demo](#akun-demo)
- [Konfigurasi](#konfigurasi)
- [Peran pengguna](#peran-pengguna)
- [Import soal dari Excel](#import-soal-dari-excel)
- [Tes](#tes)
- [Struktur proyek](#struktur-proyek)
- [Deployment](#deployment)
- [Belum dikerjakan](#belum-dikerjakan)

---

## Fitur

### Siswa
- **Landing page** dengan toga 3D (Three.js), daftar kategori, fitur, dan harga.
- **Akun**: daftar (memilih tujuan ujian), masuk, lupa/reset password, dan profil.
- **Dashboard**:
  - Statistik nilai, akurasi, materi, dan poin.
  - Grafik perkembangan nilai, rencana belajar, dan kalender aktivitas.
- **Materi**: kategori → mata uji → bab → materi. Ada materi gratis dan berbayar, serta dukungan video YouTube/Vimeo.
- **Try out** (halaman ujian dengan Inertia + Vue):
  - Timer dihitung di server, autosave dengan cadangan offline, dan resume setelah browser tertutup.
  - Navigasi soal berwarna, tanda ragu-ragu, dan pencatatan pindah tab.
  - Lima tipe soal: pilihan ganda, pilihan ganda kompleks, benar/salah per pernyataan, isian singkat, dan TKP berbobot 1–5.
  - Aturan nilai per paket, ambang batas per bagian, dan acak soal/opsi.
- **Hasil dan pembahasan**: skor per bagian, penguasaan per topik, peringkat, dan pembahasan per soal.
- **Analisis**:
  - Memakai aturan otomatis, atau AI (Claude) jika API key diisi.
  - Ada fallback otomatis kalau AI gagal, dan kuota harian per pengguna.
- **Latihan per topik**: 10 soal acak dari topik yang masih lemah.
- **Langganan**:
  - Paket per kategori atau Semua Akses, dengan durasi 1/3/6/12 bulan.
  - Voucher diskon.
  - Pembayaran lewat transfer manual (diverifikasi admin) atau Midtrans.
- **Forum**: forum umum, forum per kategori, dan diskusi per materi. Ada vote, jawaban terbaik, laporan, dan pedoman komunitas.
- **Ranking**: per try out, per kategori, dan global berbasis poin. Ada badge, dan pengguna bisa menyembunyikan diri dari ranking.
- **Notifikasi**: di dalam aplikasi dan lewat email (balasan forum, status pembayaran, pengingat langganan H-7/H-1).

### Admin
- **Ringkasan**: statistik, hasil terbaru, grafik pendapatan, dan soal yang paling sering salah.
- **Hasil peserta**: daftar semua pengerjaan dengan filter, plus detail per pengerjaan (jawaban per soal, topik, pindah tab).
- **Konten**:
  - Kategori, mata uji, bab, dan topik.
  - Materi (editor HTML dengan pratinjau).
  - Bank soal dengan alur draf → review → terbit, plus import dari Excel/CSV.
  - Paket try out beserta bagian, ambang batas, dan pemilih soal.
- **Komunitas**: moderasi forum dan pengelolaan ranking (keluarkan peserta yang curang).
- **Penjualan** (khusus super admin): verifikasi pesanan, paket langganan dan harga, serta voucher.
- **Pengguna** (khusus super admin): beri langganan manual, ubah peran, dan blokir akun.

---

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 13 (PHP 8.3) |
| Database | MySQL 8 |
| Halaman aplikasi dan admin | Livewire 4 + Alpine.js + Tailwind CSS 4 |
| Halaman ujian | Inertia 3 + Vue 3 |
| Landing page 3D | Three.js (CDN) |
| Analisis AI | Anthropic PHP SDK (Claude) |
| Import Excel | OpenSpout |
| Build frontend | Vite |
| Tes | PHPUnit |

---

## Persyaratan

- PHP 8.3 dengan ekstensi `pdo_mysql`, `mbstring`, `zip`, `xml`, `dom`, `fileinfo`
- Composer 2
- Node.js 20 atau lebih baru, dan npm
- MySQL 8

> Di Windows, [Laragon](https://laragon.org) sudah menyediakan semuanya. Nyalakan MySQL lewat **Start All** di Laragon.

---

## Instalasi lokal

```bash
# 1. Pasang dependensi
composer install
npm install

# 2. Siapkan file lingkungan
cp .env.example .env
php artisan key:generate
```

Buat database MySQL, lalu sesuaikan `.env`:

```sql
CREATE DATABASE talentra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=talentra
DB_USERNAME=root
DB_PASSWORD=
```

> Gunakan `127.0.0.1`, bukan `localhost`. Di beberapa instalasi Laragon, koneksi lewat `localhost` ditolak.

Lanjutkan dengan migrasi, data contoh, dan build aset:

```bash
php artisan migrate --seed
npm run build
```

Seeder mengisi 7 kategori, contoh materi, 25 soal orisinal CPNS dan UTBK, paket demo dan paket berbayar, paket langganan, akun demo, dan thread sambutan di forum.

---

## Menjalankan aplikasi

```bash
php artisan serve
```

Buka `http://localhost:8000`.

Selama mengubah tampilan (CSS, JS, atau Vue), jalankan Vite di terminal lain supaya perubahan langsung terlihat tanpa build ulang:

```bash
npm run dev
```

Proses tambahan yang dibutuhkan:

| Perintah | Kegunaan | Kapan perlu |
|---|---|---|
| `php artisan schedule:work` | Menandai pesanan kedaluwarsa (setiap jam) dan mengirim pengingat langganan (setiap hari jam 08.00) | Selalu di server. Opsional saat development |
| `php artisan queue:work` | Menjalankan analisis lewat antrean | Hanya jika `TALENTRA_ANALYSIS_QUEUE=true` |

### Kapan perlu reload atau build ulang

| Yang diubah | Yang perlu dilakukan |
|---|---|
| PHP atau Blade | Cukup reload halaman |
| CSS, JS, atau Vue | `npm run build` lalu reload, atau biarkan `npm run dev` berjalan |
| `.env` atau `config/` | Reload. Jika pernah menjalankan `config:cache`, jalankan `php artisan config:clear` |

Halaman belum real-time. Data baru dari pengguna lain muncul setelah halaman dimuat ulang. Pengecualian: halaman hasil try out mengecek analisis setiap 3 detik, dan lonceng notifikasi memperbarui diri setiap 60 detik.

---

## Akun demo

Semua akun memakai password **`password`**. Akun ini dibuat oleh seeder dan **hanya untuk lingkungan lokal**.

| Email | Peran |
|---|---|
| `admin@talentra.test` | Super admin |
| `konten@talentra.test` | Admin konten |
| `siswa@talentra.test` | Siswa (tujuan CPNS, akun gratis) |

---

## Konfigurasi

Pengaturan khusus Talentra ada di [`config/talentra.php`](config/talentra.php) dan bisa diubah lewat `.env`.

### Pembayaran

| Variabel | Keterangan |
|---|---|
| `TALENTRA_PAYMENT_GATEWAY` | `manual` (transfer + verifikasi admin) atau `midtrans` |
| `TALENTRA_BCA_ACCOUNT`, `TALENTRA_MANDIRI_ACCOUNT` | Nomor rekening untuk transfer manual |
| `TALENTRA_ACCOUNT_HOLDER` | Nama pemilik rekening |
| `TALENTRA_PAYMENT_CONTACT` | Kontak yang ditampilkan jika ada kendala pembayaran |
| `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_MERCHANT_ID` | Kunci API Midtrans |
| `MIDTRANS_IS_PRODUCTION` | `false` untuk sandbox |

Untuk Midtrans, atur URL notifikasi di dashboard Midtrans ke `https://domain-kamu/midtrans/webhook`.

### Analisis AI

| Variabel | Default | Keterangan |
|---|---|---|
| `TALENTRA_ANALYSIS_DRIVER` | `rules` | `anthropic` untuk memakai Claude |
| `ANTHROPIC_API_KEY` | — | Wajib jika driver `anthropic` |
| `TALENTRA_ANALYSIS_MODEL` | `claude-opus-5-5` | Model Claude yang dipakai. Ganti ke model yang lebih murah (misalnya `claude-haiku-5-5`) untuk menghemat biaya |
| `TALENTRA_ANALYSIS_DAILY_LIMIT` | `10` | Batas analisis AI per pengguna per hari. Lewat dari itu, analisis memakai aturan |
| `TALENTRA_ANALYSIS_QUEUE` | `false` | `true` agar analisis berjalan di antrean (butuh `queue:work`) |

Hanya ringkasan statistik yang dikirim ke AI. Nama, email, dan data pribadi lain tidak pernah dikirim.

### Lainnya

| Variabel | Keterangan |
|---|---|
| `TALENTRA_RANKING_CATEGORY_MIN` | Minimal jumlah try out agar peserta masuk ranking kategori (default `1`) |
| `MAIL_MAILER` dan pengaturan `MAIL_*` | Default `log`: email hanya dicatat di `storage/logs/laravel.log`. Isi SMTP untuk mengirim sungguhan |

Nilai poin, batas posting forum, daftar kata terlarang, dan level penguasaan diatur langsung di `config/talentra.php`.

---

## Peran pengguna

| Peran | Akses |
|---|---|
| Pengunjung | Landing page |
| Siswa (gratis) | Demo try out, materi gratis, analisis versi ringkas, forum |
| Siswa (berlangganan) | Semua materi dan try out sesuai paket, analisis penuh, pembahasan lengkap, ranking, latihan per topik |
| Admin konten | Panel admin: konten, bank soal, paket try out, hasil peserta, moderasi forum, ranking |
| Super admin | Semua akses admin konten + pesanan, paket langganan, voucher, pengguna |

Admin yang login langsung diarahkan ke panel admin (`/admin`).

---

## Import soal dari Excel

Buka **Admin → Bank soal → Import Excel**, unduh templatenya, isi, lalu unggah. Setiap baris diperiksa dulu sebelum disimpan, dan baris yang bermasalah dilewati beserta alasannya.

| Kolom | Isi |
|---|---|
| `kategori`, `mata_uji` | Wajib. Nama harus sesuai sheet "Daftar mata uji" di template |
| `topik` | Opsional. Topik baru dibuat otomatis |
| `tipe` | `pg`, `pgk`, `bs`, `isian`, atau `tkp` |
| `soal` | Teks biasa (baris kosong = paragraf baru) atau HTML sederhana |
| `opsi_a` … `opsi_f` | Opsi jawaban. Untuk tipe `bs`, setiap kolom adalah satu pernyataan |
| `kunci` | `pg`: `B` · `pgk`: `A,C` · `bs`: `B,S,B` · `isian`: `36` (alternatif dipisah `\|`) · `tkp`: `5,3,2,4,1` |
| `pembahasan`, `kesulitan`, `sumber` | Opsional. Kesulitan: `1`/`mudah`, `2`/`sedang`, `3`/`sulit` |

Batasnya 1.000 soal dan 5 MB per file. Soal yang sudah ada di bank soal otomatis dilewati.

---

## Tes

Tes memakai database MySQL terpisah bernama **`talentra_testing`** (lihat [`phpunit.xml`](phpunit.xml)). Buat sekali:

```sql
CREATE DATABASE talentra_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Lalu jalankan:

```bash
php artisan test
```

Untuk menjalankan satu file saja: `php artisan test tests/Feature/ExamFlowTest.php`.

Format kode PHP dengan Pint sebelum commit:

```bash
vendor/bin/pint --dirty
```

---

## Struktur proyek

```
app/
├── Enums/                 Status dan tipe (soal, pesanan, peran, forum, ...)
├── Http/Controllers/      Ujian (Inertia), latihan topik, bukti bayar, webhook Midtrans, template Excel
├── Jobs/                  GenerateAttemptAnalysis
├── Livewire/              Halaman siswa (Dashboard, Learn, Tryout, Results, Billing, Forum, Ranking, ...)
│   └── Admin/             Halaman admin
├── Models/
├── Notifications/         Balasan forum, status pesanan, pengingat langganan
├── Payments/              PaymentGateway, ManualPaymentGateway, MidtransPaymentGateway
├── Services/
│   ├── Analysis/          Analisis berbasis aturan dan Claude
│   ├── Community/         Forum, poin, ranking
│   ├── Exam/              Sesi ujian, penilaian, latihan topik
│   ├── Questions/         Import dan template Excel
│   └── OrderService.php   Pesanan, aktivasi langganan, pengingat
└── Support/               Html (sanitizer), Money (format rupiah)

resources/
├── js/Pages/Exam/Take.vue Halaman ujian
├── views/landing.blade.php
├── views/layouts/         app (siswa), admin, guest, exam
└── views/livewire/        Tampilan komponen Livewire

config/talentra.php        Pengaturan Talentra
docs/REQUIREMENTS.md       Dokumen kebutuhan
```

---

## Deployment

Checklist sebelum rilis:

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` sesuai domain (HTTPS)
- [ ] Database MySQL produksi dan backup harian
- [ ] Jalankan `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, dan `php artisan migrate --force`
- [ ] Jalankan `php artisan optimize` setelah setiap deploy
- [ ] Cron: `* * * * * php /path/ke/talentra/artisan schedule:run`
- [ ] Jika `TALENTRA_ANALYSIS_QUEUE=true`, jalankan `php artisan queue:work` lewat supervisor
- [ ] SMTP untuk email
- [ ] Nomor rekening asli atau kunci Midtrans produksi, plus URL webhook Midtrans
- [ ] `ANTHROPIC_API_KEY` jika memakai analisis AI
- [ ] Ganti harga contoh di Admin → Paket langganan
- [ ] Jangan menjalankan seeder akun demo di produksi, atau segera ganti password-nya
- [ ] Tinjau soal contoh bersama ahli materi

---

## Belum dikerjakan

- Halaman Syarat & Ketentuan, Kebijakan Privasi, dan Pedoman Komunitas (tautannya sudah ada, halamannya belum)
- Wajib verifikasi email
- Notifikasi mention (@nama) di forum
- Pengaturan situs dari panel admin (sekarang lewat `.env`)
- Pembaruan real-time (websocket)

Di luar cakupan (sengaja tidak dibuat): try out nasional terjadwal, referral, PWA, notifikasi WhatsApp, log aktivitas admin, dan login Google.
