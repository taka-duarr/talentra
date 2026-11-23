# Talentra — Dokumen Requirement

> **Tumbuh · Uji · Berkembang**
> Platform pembelajaran dan tes online untuk TKA (SD/SMP/SMA), UTBK-SNBT, CPNS, BUMN, Kedinasan, dan ujian lainnya, lengkap dengan analisis hasil berbasis AI.

| | |
|---|---|
| Versi dokumen | 0.3 (draf) |
| Tanggal | 23 November 2026 |
| Sumber | Proposal Konsep Bisnis Talentra (Kewirausahaan, ITATS) + kebutuhan fitur dari tim |
| Status | Menunggu review tim |

### Perubahan dari versi 0.2
- Fitur berikut **dikeluarkan dari cakupan** dan tidak dikerjakan: try out nasional terjadwal, referral, PWA, notifikasi WhatsApp, log aktivitas admin, dan login Google.
- Fase 1, Fase 2, dan import soal dari Excel sudah selesai dibangun. Sisa pekerjaan: integrasi payment gateway.

### Perubahan dari versi 0.1
- Role **admin institusi** dan **paket institusi** dihapus. Talentra berjalan independen dan hanya melayani pengguna perorangan.
- Paket **per try out** dihapus. Model penjualan hanya langganan.
- **Payment gateway**: UI checkout dibuat dulu, integrasi gateway menyusul. Selama itu, aktivasi langganan dilakukan admin secara manual.
- **Dashboard admin** diperinci.
- **Forum diskusi** ditambah: forum **global** dan forum **per kategori**, selain diskusi per materi.
- Fitur **ranking** ditambahkan sebagai modul tersendiri.

---

## 1. Ringkasan Produk

Talentra adalah website tempat pengguna **belajar materi**, **mengerjakan latihan/try out berbasis komputer**, lalu **mendapat analisis hasil** yang menjelaskan bagian mana yang kuat, mana yang lemah, dan apa yang harus dipelajari berikutnya.

**Pembeda utama:**
1. Banyak jenis ujian dalam satu akun, termasuk CPNS dan BUMN.
2. Analisis hasil otomatis (grafik, persentase, kategori penguasaan) + rekomendasi belajar oleh AI.
3. Komunitas belajar: forum global, forum per kategori, dan diskusi per materi.
4. Ranking antar peserta untuk memacu semangat belajar.
5. Harga kompetitif dibanding Ruangguru, Pahamify, dan Analitica.

**Target pengguna:** siswa SD/SMP/SMA, calon mahasiswa, pencari kerja (CPNS/BUMN), dan calon peserta sekolah kedinasan.

---

## 2. Teknologi

| Bagian | Pilihan |
|---|---|
| Backend | Laravel (PHP 8.3), MySQL 8 |
| Landing page, dashboard user, dashboard admin, forum | **Livewire 3** + Alpine.js + Tailwind CSS |
| Halaman ujian (mengerjakan soal) | **Inertia + Vue 3** |
| Build tool | Vite |
| Queue & cache | Database driver (awal) → Redis (saat pengguna ramai) |
| Pembayaran | UI dulu + aktivasi manual. Gateway (Midtrans/Xendit) menyusul |
| AI analisis | LLM API (provider dipilih saat implementasi), dipanggil lewat queue |
| Auth & role | Autentikasi Laravel (komponen Livewire sendiri), peran disimpan di kolom `users.role` |

### Catatan arsitektur Livewire + Inertia
- Semua halaman memakai layout Blade + Livewire, **kecuali** route `/ujian/*`.
- Route `/ujian/*` memakai middleware Inertia dan root view terpisah (`resources/views/exam.blade.php`), sehingga kedua sistem tidak saling bertabrakan.
- Session login sama untuk keduanya (cookie Laravel biasa), jadi user tidak perlu login dua kali.
- Setelah ujian selesai, user diarahkan kembali ke halaman hasil (Livewire).

---

## 3. Peran Pengguna (Role)

| Role | Akses |
|---|---|
| **Pengunjung** (belum login) | Landing page, info materi, daftar harga, lihat forum (baca saja), lihat ranking publik |
| **User gratis** | Demo test + materi terbatas, hasil demo + analisis AI ringkas, ikut forum |
| **User berlangganan** | Semua materi & test sesuai paket, analisis AI penuh, forum, masuk ranking |
| **Admin konten** | Kelola bank soal, materi, paket test, moderasi forum |
| **Super admin** | Semua akses admin konten + kelola harga, transaksi, aktivasi langganan, user, admin lain, laporan |

---

## 4. Fitur

### 4.1 Landing Page

- **Hero section**: tagline, nilai jual, tombol **"Coba Gratis"** dan **"Masuk"**.
- **Pengenalan web**: apa itu Talentra, alur 4 langkah (Daftar → Pilih paket → Kerjakan tes → Lihat analisis).
- **Kategori tes**: TKA SD, TKA SMP, TKA SMA, UTBK-SNBT, CPNS, BUMN, Kedinasan.
- **Pengenalan isi materi**: daftar materi per kategori (contoh: CPNS → TWK, TIU, TKP).
- **Contoh hasil analisis AI** (pembeda utama, harus terlihat jelas).
- **Cuplikan ranking & forum** untuk menunjukkan komunitasnya aktif.
- **Daftar harga langganan**: kartu paket per kategori + paket "Semua Akses".
- **Testimoni** dan **FAQ**.
- **Footer**: kontak, syarat & ketentuan, kebijakan privasi.

### 4.2 Autentikasi

- Tombol **Masuk** dan **Daftar** di navbar landing page.
- Daftar dengan email + password.
- Verifikasi email, lupa password.
- Saat daftar, user memilih **tujuan ujian** (contoh: UTBK) untuk personalisasi dashboard.
- Jika pengunjung menekan "Coba demo", "Langganan", atau "Tulis di forum" sebelum login, arahkan ke login lalu **kembali ke halaman semula** setelah berhasil.

### 4.3 Langganan

**Jenis paket:**

| Paket | Isi |
|---|---|
| Per kategori | Contoh: "TKA SMA", "UTBK", "CPNS". Akses semua materi & test di kategori tersebut. |
| Semua Akses (1 untuk semua) | Semua kategori. |

**Durasi:** 1, 3, 6, dan 12 bulan (harga per bulan makin murah untuk durasi panjang). Semua bisa diatur admin.

**Aturan bisnis:**
- Langganan punya tanggal `mulai` dan `berakhir`. Akses dicek di server setiap kali membuka materi/test.
- User boleh punya lebih dari satu langganan kategori sekaligus.
- Membeli paket yang sama saat masih aktif = masa aktif diperpanjang.
- Notifikasi email H-7 dan H-1 sebelum langganan berakhir.
- Kode voucher (diskon persen atau nominal, kuota, tanggal berlaku).

### 4.4 Pembayaran (UI dulu, gateway menyusul)

**Yang dibuat sekarang (fase 1):**
1. **Halaman pilih paket** → pilih kategori & durasi, masukkan voucher, lihat ringkasan harga.
2. **Halaman checkout** → pilihan metode pembayaran (QRIS, Virtual Account, e-wallet) **sebagai tampilan saja**.
3. Saat user menekan "Bayar", sistem membuat **pesanan berstatus `menunggu`** dan menampilkan **halaman instruksi pembayaran**.
4. Selama gateway belum terhubung, halaman instruksi berisi petunjuk **transfer manual + upload bukti bayar** (atau info kontak admin).
5. **Admin memverifikasi** pesanan dari dashboard admin → status menjadi `lunas` → langganan aktif otomatis.
6. **Halaman status pesanan & riwayat transaksi** di dashboard user, termasuk invoice yang bisa diunduh.

**Status pesanan:** `menunggu` → `lunas` / `kedaluwarsa` / `dibatalkan` / `ditolak`.

**Persiapan agar gateway mudah dipasang nanti:**
- Logika pembayaran dibungkus dalam satu interface `PaymentGateway` dengan implementasi `ManualPayment` sekarang, lalu `MidtransPayment`/`XenditPayment` nanti. Halaman dan alur pesanan tidak perlu diubah.
- Tabel `transactions` sudah menyiapkan kolom `gateway`, `gateway_reference`, `payment_method`, dan `payload` (respons gateway).
- Endpoint webhook disiapkan tapi belum aktif.
- Pesanan `menunggu` otomatis `kedaluwarsa` setelah 24 jam (scheduler).

### 4.5 Demo (Akses Gratis)

- Setiap kategori punya **1 paket demo test** (misalnya 10–20 soal) dan **beberapa materi bab pertama**.
- Hasil demo tetap menampilkan **analisis AI versi ringkas**, supaya user merasakan nilai jualnya.
- Konten terkunci ditampilkan dengan ikon gembok + tombol **"Buka dengan Langganan"**.
- Batas pengerjaan demo per paket bisa diatur admin (misalnya 1–3 kali).
- Hasil demo **tidak masuk ranking**.

### 4.6 Materi Pembelajaran

- Struktur: **Kategori → Mata uji → Bab → Materi**.
  Contoh: CPNS → TIU → Kemampuan Numerik → Deret Angka.
- Format: teks (rich text), gambar, rumus (KaTeX), video embed (YouTube unlisted/Vimeo), PDF.
- Latihan soal singkat di akhir bab.
- Penanda progres: materi yang sudah dibaca ditandai selesai.
- Kolom **diskusi per materi** di bawah setiap materi (lihat 4.10).

### 4.7 Ujian / Try Out (Inertia + Vue)

**Tampilan:**
- Timer di kanan atas.
- Soal + pilihan jawaban, ukuran huruf bisa diperbesar.
- Panel navigasi nomor soal dengan warna: **terjawab**, **ragu-ragu**, **belum dikerjakan**.
- Tombol "Tandai ragu-ragu", "Sebelumnya", "Berikutnya", "Selesai".
- Konfirmasi sebelum submit, menampilkan jumlah soal yang belum dijawab.

**Tipe soal:**
- Pilihan ganda (1 jawaban benar).
- Pilihan ganda kompleks (lebih dari 1 jawaban benar).
- Benar/salah per pernyataan (dipakai di UTBK dan TKA).
- Isian singkat (angka/teks pendek).
- Soal berbobot per opsi (contoh: **TKP CPNS** bernilai 1–5 per opsi).
- Soal dengan gambar, tabel, atau rumus.

**Aturan teknis wajib:**
- **Timer dihitung di server** (`started_at`, `ends_at`). Timer browser hanya tampilan. Saat waktu habis, server menolak jawaban baru dan otomatis menyelesaikan sesi.
- **Autosave** setiap jawaban dipilih, plus simpanan lokal sementara jika koneksi putus.
- **Resume**: jika browser tertutup, user bisa lanjut dengan sisa waktu yang benar.
- **Kunci jawaban tidak pernah dikirim ke browser** sebelum ujian selesai.
- **Urutan soal & opsi diacak per peserta** (bisa diatur per paket) dan disimpan agar konsisten setelah refresh.
- Satu sesi ujian aktif per user.
- Deteksi pindah tab dicatat sebagai log.
- Mendukung sub-tes dengan waktu terpisah (contoh: UTBK per subtes).

**Penilaian (diatur per paket):**

| Jenis | Contoh aturan |
|---|---|
| Benar/salah sederhana | Benar +4, salah 0 atau −1, kosong 0 |
| Bobot per opsi | TKP CPNS: nilai 1–5 per opsi |
| Passing grade per bagian | SKD CPNS: ambang batas TWK, TIU, TKP masing-masing (**disimpan sebagai pengaturan**, karena berubah tiap tahun) |
| Skor terskala | UTBK: skor dibobot berdasarkan tingkat kesulitan soal. Ditampilkan sebagai **estimasi**, bukan skor resmi |

### 4.8 Hasil & Analisis AI

**Hasil langsung (dihitung sistem):**
- Skor total, benar/salah/kosong, waktu pengerjaan.
- Rincian skor per bagian dibanding passing grade.
- Persentase penguasaan per topik + kategori (Sangat Baik / Baik / Cukup / Perlu Ditingkatkan).
- Grafik perkembangan dari beberapa percobaan.
- **Pembahasan per soal** (jawaban benar + penjelasan).
- **Posisi ranking** di paket tersebut (lihat 4.9).

**Analisis AI:**
- Input ke AI: **ringkasan statistik** (skor per topik, pola kesalahan, waktu per soal, riwayat percobaan, tujuan ujian). Nama, email, dan data pribadi lain **tidak dikirim**.
- Output AI dalam format terstruktur (JSON), ditampilkan sebagai:
  - Ringkasan performa (2–3 kalimat).
  - Kekuatan.
  - Kelemahan utama + kemungkinan penyebab.
  - **Rekomendasi belajar** yang terhubung ke materi Talentra (link ke bab terkait).
  - Rencana belajar singkat (misalnya 7 hari).
- Diproses lewat **queue**: halaman hasil tampil dulu, analisis AI muncul beberapa detik kemudian.
- **Hasil disimpan**, tidak memanggil AI ulang saat halaman dibuka lagi.
- **Fallback**: jika AI gagal, tampilkan analisis berbasis aturan + tombol "Coba analisis ulang".
- **Kuota analisis** per user per hari agar biaya terkontrol.
- Label kecil: "Analisis dibuat dengan bantuan AI dan dapat keliru."

### 4.9 Ranking

**Jenis ranking:**

| Ranking | Cara hitung |
|---|---|
| **Per paket try out** | Urut berdasarkan skor percobaan **pertama** setiap user di paket itu. Seri dipecah dengan waktu pengerjaan tercepat. |
| **Per kategori** | Rata-rata skor (dinormalisasi 0–100) dari semua paket try out di kategori itu. Minimal sudah mengerjakan sejumlah paket (misalnya 3) agar masuk. |
| **Global** | Poin aktivitas & prestasi lintas kategori (lihat poin di bawah). |

**Periode:** mingguan, bulanan, dan sepanjang waktu.

**Poin global (usulan, bisa diatur admin):**
- Menyelesaikan try out: +10
- Skor di atas passing grade: +20
- Menyelesaikan satu bab materi: +5
- Jawaban di forum ditandai terbaik: +5

**Tampilan:**
- Halaman ranking dengan tab Paket / Kategori / Global dan filter periode.
- Top 3 ditonjolkan (podium), lalu daftar peringkat berikutnya.
- **Posisi user sendiri selalu terlihat** (contoh: "Kamu peringkat 152 dari 3.240 · persentil 95").
- Di halaman hasil: "Skor kamu lebih baik dari 78% peserta".
- Badge di profil: Top 10 mingguan, Top 100 kategori, dll.

**Aturan:**
- Hanya **user berlangganan** dan hanya **percobaan pertama** yang dihitung di ranking paket (mencegah skor naik karena hafal soal).
- Hasil demo tidak dihitung.
- User bisa memilih **nama tampilan** atau menyembunyikan diri dari ranking publik (privasi, terutama pengguna di bawah umur).
- Admin bisa mengeluarkan user dari ranking jika terindikasi curang (misalnya waktu pengerjaan tidak wajar).
- Ranking dihitung ulang lewat scheduler/queue dan disimpan (cache), bukan dihitung setiap halaman dibuka.

### 4.10 Forum Diskusi

**Tiga tingkat forum:**

| Forum | Isi | Contoh |
|---|---|---|
| **Global** | Obrolan umum lintas kategori | Tips belajar, motivasi, info jadwal seleksi, perkenalan |
| **Per kategori** | Khusus satu kategori ujian | Forum CPNS: strategi TKP, info formasi |
| **Per materi** | Kolom diskusi di bawah tiap materi | Tanya cara mengerjakan soal deret angka |

**Fitur:**
- Buat thread (judul, isi rich text, gambar, rumus), balas, dan balasan bertingkat 1 level.
- Upvote thread & balasan, tandai **jawaban terbaik** (oleh pembuat thread atau admin).
- Tag/label thread (contoh: Tanya, Tips, Info, Diskusi Soal).
- Urutkan: terbaru, terpopuler, belum terjawab. Pencarian thread.
- Thread disematkan (pin) oleh admin, misalnya pengumuman.
- Notifikasi saat thread dibalas atau di-mention.
- **Laporkan** thread/balasan yang melanggar.
- Profil singkat penulis: nama tampilan, avatar, badge ranking.

**Aturan akses:**
- Pengunjung: baca saja.
- User gratis: forum global + kategori + materi demo.
- User berlangganan: semua forum, termasuk diskusi materi berbayar.
- Rate limit posting dan filter kata kasar sederhana untuk mencegah spam.
- **Pedoman komunitas** wajib disetujui saat pertama kali posting.

### 4.11 Dashboard User (Livewire)

- Ringkasan: langganan aktif + masa berlaku, tujuan ujian, progres belajar.
- Paket test yang tersedia & yang sedang dikerjakan (lanjutkan).
- Riwayat test + hasil + analisis AI.
- Grafik perkembangan skor.
- Posisi ranking & badge.
- Rekomendasi materi berikutnya (dari analisis AI terakhir).
- Aktivitas forum (thread saya, balasan terbaru).
- Pesanan & riwayat transaksi, invoice.
- Pengaturan profil, nama tampilan, privasi ranking, ganti password, preferensi notifikasi.

### 4.12 Dashboard Admin (Livewire)

**Beranda admin**
- Kartu ringkasan: user baru, user berlangganan aktif, pesanan menunggu verifikasi, pendapatan bulan ini, try out dikerjakan hari ini, laporan forum baru.
- Grafik pendapatan & pertumbuhan user.

**Konten**
- **Kategori & mata uji**: CRUD, urutan, ikon, status aktif.
- **Bank soal**: CRUD soal, tipe soal, topik, tingkat kesulitan, pembahasan, preview tampilan, import dari Excel, status **draf → review → terbit**, catatan sumber soal.
- **Paket test**: susun soal, durasi, sub-tes, aturan penilaian, passing grade, acak soal/opsi, demo atau berbayar, jadwal tayang, ikut ranking atau tidak.
- **Materi**: CRUD bab & materi, urutan, demo atau berbayar.

**Penjualan**
- **Paket langganan**: nama, kategori yang dicakup, durasi, harga, harga coret, status.
- **Voucher**: kode, jenis diskon, kuota, masa berlaku, paket yang berlaku.
- **Pesanan & transaksi**: daftar + filter status, lihat bukti bayar, **verifikasi / tolak**, aktivasi langganan manual, catatan admin.

**Pengguna**
- Daftar user + pencarian, detail profil, langganan, riwayat test, dan aktivitas forum.
- Tambah/perpanjang langganan manual, blokir/aktifkan akun.
- Kelola admin (super admin saja).

**Komunitas**
- **Moderasi forum**: antrean laporan, sembunyikan/hapus konten, pin thread, kunci thread, larang user posting sementara.
- **Ranking**: lihat ranking, keluarkan user yang curang, atur poin global, reset periode.

**Laporan & sistem**
- Laporan pendapatan, user aktif, test terpopuler.
- **Statistik soal**: soal yang paling sering salah atau terlalu mudah (kandidat perbaikan).
- Pemakaian & biaya AI (jumlah analisis, token terpakai).
- Pengaturan situs: nama, logo, kontak, rekening transfer manual, kuota AI, batas demo.

---

## 5. Saran Tambahan

| # | Saran | Alasan |
|---|---|---|
| 1 | **Pecah kategori TKA per jenjang** (SD, SMP, SMA) | Materinya berbeda. Bisa juga dibuat bundel per jenjang, misalnya "Paket SMA" = TKA SMA + UTBK. |
| 2 | **Demo wajib memperlihatkan analisis AI** | Analisis adalah pembeda utama. Jika demo hanya menampilkan skor, user tidak melihat alasan berlangganan. |
| 3 | **Hitung biaya AI di model harga** | Setiap analisis ada biayanya. Batasi kuota, simpan hasil, kirim ringkasan statistik saja. |
| 4 | **Alur review soal** (draf → review → terbit) | Mengurangi risiko soal salah dan masalah hak cipta, seperti yang disebut di proposal. |
| 5 | **Kisi-kisi disimpan sebagai pengaturan** | Passing grade, jumlah soal, dan durasi berubah tiap tahun, jadi jangan ditulis di kode. |
| 6 | **Syarat & ketentuan, kebijakan privasi, pedoman komunitas** | Wajib untuk pembayaran online, forum publik, dan sesuai UU PDP. Pengguna SD/SMP di bawah umur sebaiknya butuh persetujuan orang tua. |
| 7 | **Latihan per topik dari rekomendasi AI** | User langsung latihan 10 soal di topik yang lemah. |
| 8 | **Tombol "Jelaskan lebih sederhana" di pembahasan** | Memperkuat positioning AI. |
| 9 | **Fokus 1–2 kategori untuk rilis pertama** | Contoh UTBK + CPNS. Kategori lain ditambah setelah bank soal siap. |

---

## 6. Rancangan Data Awal (Garis Besar)

```
users ─┬─ subscriptions ── plans ── plan_category (pivot) ── categories
       ├─ orders ── transactions (gateway, status, bukti bayar)
       │        └─ vouchers
       ├─ exam_attempts ─┬─ attempt_answers ── questions
       │                 └─ ai_analyses
       ├─ material_progress ── materials
       ├─ forum_threads ─┬─ forum_replies
       │                 └─ forum_votes / forum_reports
       ├─ user_points (log poin ranking)
       └─ user_badges

categories ── subjects (mata uji) ── chapters ── materials
                                  └─ topics ── questions ── question_options

exam_packages ─┬─ exam_sections (sub-tes, durasi, aturan nilai)
               └─ exam_package_question (pivot, urutan, bobot)

forum_threads.scope = global | category | material  (+ scope_id)

leaderboard_snapshots (jenis, scope_id, periode, user_id, skor, peringkat)
```

Penjelasan singkat:
- `plans`: paket langganan. Relasi ke `categories` menentukan akses; paket Semua Akses terhubung ke semua kategori.
- `orders` / `transactions`: pesanan dan pembayarannya. Kolom gateway sudah disiapkan walaupun sekarang masih manual.
- `exam_attempts`: satu kali pengerjaan (mulai, berakhir, status, skor per bagian, urutan soal acak, `is_first_attempt`).
- `ai_analyses`: hasil analisis AI (JSON, status, model, token terpakai).
- `forum_threads`: satu tabel untuk tiga tingkat forum, dibedakan lewat kolom `scope`.
- `leaderboard_snapshots`: hasil perhitungan ranking yang disimpan per periode, supaya halaman ranking cepat.

---

## 7. Kebutuhan Non-Fungsional

| Aspek | Target |
|---|---|
| Performa | Halaman ujian lancar dengan ratusan peserta serentak. Ranking dan statistik dihitung di background, bukan saat halaman dibuka. |
| Keamanan | HTTPS, rate limit login/ujian/forum, CSRF, validasi upload bukti bayar, kunci jawaban hanya di server, signature webhook saat gateway aktif. |
| Privasi | Data pribadi tidak dikirim ke AI. Nama tampilan & opsi sembunyi dari ranking. Kebijakan privasi sesuai UU PDP. |
| Ketersediaan | Backup database harian. Monitoring error (contoh: Sentry). |
| Responsif | Semua halaman nyaman di HP, terutama halaman ujian dan forum. |
| Aksesibilitas | Kontras warna cukup, ukuran huruf soal bisa diperbesar. |

---

## 8. Tahapan Pengerjaan (Usulan)

### Fase 1 — MVP
1. Setup project (Laravel Boost, Livewire, Inertia + Vue untuk halaman ujian, Tailwind).
2. Landing page + autentikasi (email & password).
3. Dashboard admin: kategori, materi, bank soal, paket test, pengguna.
4. Halaman ujian (Inertia + Vue): timer server, autosave, resume, navigasi soal.
5. Penilaian + halaman hasil + pembahasan.
6. Demo gratis + penguncian konten.
7. Langganan + UI checkout + verifikasi pembayaran manual oleh admin.
8. Analisis AI (queue, cache, fallback).

### Fase 2 — Komunitas & Kompetisi
- Forum global, per kategori, dan per materi + moderasi admin.
- Ranking per paket, per kategori, dan global + badge.
- Grafik perkembangan, voucher, notifikasi email.
- Latihan per topik dari rekomendasi AI.

### Sisa pekerjaan
- Integrasi payment gateway (Midtrans/Xendit) menggantikan verifikasi manual.

### Di luar cakupan
Try out nasional terjadwal, referral, PWA, notifikasi WhatsApp, log aktivitas admin, dan login Google tidak dikerjakan.

---

## 9. Pertanyaan Terbuka

1. Harga tiap paket dan durasi langganan yang ditawarkan?
2. Selama gateway belum aktif, pembayaran manual lewat transfer bank apa saja? Siapa admin yang memverifikasi?
3. Siapa penyusun soal dan pembahasan awal? Berapa target jumlah soal per kategori saat rilis?
4. Apakah demo bisa dicoba **tanpa login**, atau wajib daftar dulu?
5. Kategori mana yang dikerjakan duluan untuk MVP?
6. Provider AI mana yang dipakai, dan berapa anggaran biaya AI per bulan?
7. Apakah ranking global memakai sistem poin seperti usulan di 4.9, atau cukup ranking per paket dan per kategori?
