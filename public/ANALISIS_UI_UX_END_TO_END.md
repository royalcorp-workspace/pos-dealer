# LAPORAN ANALISIS UI/UX END-TO-END
## E-Commerce Storefront (Royal / IMG Store) & CMS Integration

- **Tanggal Dokumen**: Oktober 2026
- **Status Audit**: Komprehensif (End-to-End User Journey)
- **Lingkup Evaluasi**: Storefront Customer Flow (`pos-dealer-web`) & Keselarasan Manajemen CMS (`cms-img-store`)
- **Fokus Utama**: Customer Experience (CX), Visual Hierarchy, Mobile Ergonomics, Micro-Interactions, Conversion Rate Optimization (CRO), Clarity & Transparency.

---

## DAFTAR ISI
1. [Executive Summary & Metrik Evaluasi UX](#1-executive-summary--metrik-evaluasi-ux)
2. [Audit Mendalam per Touchpoint / User Journey](#2-audit-mendalam-per-touchpoint--user-journey)
   - [2.1 Header, Navigasi Global & Mobile Drawer](#21-header-navigasi-global--mobile-drawer)
   - [2.2 Homepage (Beranda) & Showcase Konten](#22-homepage-beranda--showcase-konten)
   - [2.3 Katalog Produk, Filter & Pencarian (/products)](#23-katalog-produk-filter--pencarian-products)
   - [2.4 Halaman Detail Produk (PDP) & Selector Variasi Multi-Dimensi](#24-halaman-detail-produk-pdp--selector-variasi-multi-dimensi)
   - [2.5 Product Bundling Flow (/bundling)](#25-product-bundling-flow-bundling)
   - [2.6 Cart Drawer & Pengelolaan Keranjang](#26-cart-drawer--pengelolaan-keranjang)
   - [2.7 Checkout Flow (Alamat, Kurir & Voucher)](#27-checkout-flow-alamat-kurir--voucher)
   - [2.8 Payment Gateway Flow (QRIS, VA, CC, Direct Debit)](#28-payment-gateway-flow-qris-va-cc-direct-debit)
   - [2.9 Thank You Page & Konfirmasi Transaksi](#29-thank-you-page--konfirmasi-transaksi)
   - [2.10 Order Tracking & Riwayat Pesanan](#210-order-tracking--riwayat-pesanan)
   - [2.11 Customer Dashboard & Manajemen Sesi](#211-customer-dashboard--manajemen-sesi)
   - [2.12 Mobile Usability, Accessibility (a11y) & Feedback Interaksi](#212-mobile-usability-accessibility-a11y--feedback-interaksi)
3. [Matriks Temuan & Klasifikasi Prioritas (P1 - P4)](#3-matriks-temuan--klasifikasi-prioritas-p1---p4)
4. [Rekomendasi Solusi Desain UI/UX Konkret & Action Plan](#4-rekomendasi-solusi-desain-uiux-konkret--action-plan)

---

## 1. EXECUTIVE SUMMARY & METRIK EVALUASI UX

Aplikasi e-commerce Royal / IMG Store dirancang untuk melayani penjualan kasur, springbed, furnitur kamar tidur, dan perlengkapan rumah tangga berukuran besar. Karakteristik produk furnitur/springbed menuntut tingkat kepercayaan (*trust*) yang sangat tinggi dari calon pembeli karena nilai transaksi yang signifikan dan kompleksitas variasi produk (ukuran, kelengkapan, ketebalan, serta estimasi logistik kargo).

### Hasil Observasi Makro:
1. **Pondasi Desain Kuat**: Brand identity mewah (*gold-dark theme*), palet warna premium (`#FAF8F5`, `#1E293B`, `#C09D6B`), serta transisi Alpine.js yang responsif memberikan impresi berkelas.
2. **Titik Friksi Kritis (UX Bottlenecks)**:
   - **Benturan Elemen Mengambang di Mobile**: Terjadi kompetisi ruang vertikal antara Mobile Bottom Nav (tinggi 70px, z-index 90), Live Chat Widget floating (bottom-24), dan sticky action bar di PDP / Bundling.
   - **Kejelasan Status Variasi Out-of-Stock**: Pengunjung terkadang masih harus mengklik variasi terlebih dahulu sebelum mengetahui stok kosong atau kombinasi yang tidak valid.
   - **Kompensasi Jarak Pandang Informasi Checkout**: Pada layar checkout dan payment, hierarki informasi biaya (biaya layanan, potongan voucher, subsidi ongkir) perlu pemisahan yang lebih tegas agar customer tidak merasa ada biaya tersembunyi (*hidden fee*).
   - **Micro-Feedback yang Hilang**: Aksi seperti *Copy to Clipboard* nomor Virtual Account, tambah ke cart, atau hapus item di drawer memerlukan konfirmasi instan berbasis visual/haptik (toast notification) tanpa merusak konteks pengguna.

---

## 2. AUDIT MENDALAM PER TOUCHPOINT / USER JOURNEY

### 2.1 Header, Navigasi Global & Mobile Drawer

#### A. Temuan (Findings)
1. **Dropdown Brand Baru (2 Kolom Grid)**:
   - *Status*: Telah ditingkatkan menjadi format kartu 2 kolom dengan logo brand pada kontainer netral `#ECEEF2`, penulisan Title Case (Serenity, Lady, Elite, Royal, Moro, Tote), dan subteks counter produk.
   - *Potensi Masalah*: Pada resolusi layar tablet sempit (768px - 1024px), dropdown desktop memiliki kemungkinan terpotong tepi layar kanan jika posisi anchor tidak auto-aligning.
2. **Search Autocomplete / Live Search**:
   - Kolom pencarian di header sudah memiliki auto-suggest berbasis AJAX, namun belum menyediakan navigasi papan ketik penuh (panah atas `↑`, panah bawah `↓`, dan tombol `Escape` untuk menutup).
   - Saat hasil pencarian tidak ditemukan, popover langsung kosong tanpa memberikan saran produk populer (*recommended searches*) atau ejaan alternatif.
3. **Sticky Header & Layout Shift**:
   - Jika terdapat *Announcement Bar / Promo Marquee* di bagian paling atas, saat pengguna menggulir ke bawah (*scroll down*), perubahan posisi header kadang memicu sedikit *layout jump* sebesar tinggi bar promo.
4. **Indikator Badge Keranjang & Wishlist**:
   - Angka badge keranjang pada ikon header tampil baik, namun belum memiliki animasi *pulse* atau *scale-pop* saat item baru ditambahkan dari halaman produk atau katalog.

#### B. Saran (Recommendations)
- **Auto-Positioning Dropdown**: Tambahkan class utilitas penyesuaian dropdown (`right-0` atau script penghitung posisi horizontal) agar kartu brand selalu berada di dalam viewport.
- **Enhanced Search Experience**:
  - Sediakan section *"Pencarian Populer"* (misal: *Elite Orthopedic, Serenity Supreme, Kasur 160x200*) ketika user fokus ke kolom search saat input masih kosong.
  - Implementasikan keyboard accessibility (ArrowDown untuk memilih item, Enter untuk mengunjungi PDP langsung).
- **Micro-Interaction Badge**: Berikan efek `animate-bounce` atau `scale-125` transien selama 300ms pada badge cart saat ada event `cart-updated`.

---

### 2.2 Homepage (Beranda) & Showcase Konten

#### A. Temuan (Findings)
1. **Hero Carousel & Banner Rasio Aspek**:
   - Banner promosi utama terlihat dramatis di desktop, namun pada perangkat mobile teks promosi yang menjadi bagian dari grafik banner sering kali terpotong atau menjadi sangat kecil dan sulit dibaca jika banner menggunakan `object-cover`.
   - Tombol navigasi prev/next pada carousel terkadang menutupi elemen teks penjelas di layar kecil.
2. **Kategori Cepat (Quick Categories Bar)**:
   - Deretan kategori berbentuk pill atau lingkaran dapat digeser secara horizontal di mobile (`overflow-x-auto`). Namun, tidak ada efek *fade gradient shadow* di tepi kanan layar untuk mengindikasikan bahwa deretan tersebut dapat digeser (*scroll affordance*).
3. **Value Proposition / Trust Points**:
   - Ikon keunggulan (*Garansi Resmi s/d 15 Tahun*, *Gratis Ongkir*, *100% Original*) berada di bagian bawah beranda. Untuk produk kasur bernilai jutaan rupiah, penempatan trust badge ini terlalu rendah dan kurang memberikan ketenangan awal bagi pengunjung pertama (*first-time buyers*).

#### B. Saran (Recommendations)
- **Dual-Asset Hero Banner**: Pisahkan upload banner di CMS antara gambar rasio landscape (16:9 atau 21:9) untuk desktop dan rasio portrait/square (4:5 atau 1:1) khusus mobile.
- **Scroll Affordance**: Tambahkan pseudoclass gradient halus di sisi kanan container kategori horizontal:
  ```html
  <div class="relative">
    <div class="overflow-x-auto scrollbar-hide ...">...</div>
    <div class="pointer-events-none absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-white to-transparent md:hidden"></div>
  </div>
  ```
- **Elevasi Trust Badge**: Tampilkan strip trust badge ringkas tepat di bawah hero banner utama untuk membangun keyakinan belanja sejak detik pertama.

---

### 2.3 Katalog Produk, Filter & Pencarian (`/products`)

#### A. Temuan (Findings)
1. **Penyajian Gambar Kartu Produk (Product Card)**:
   - Sebagian besar kartu produk sudah memakai rasio yang baik, namun ada varian gambar furnitur berdimensi horizontal yang kepalanya terpotong bila dipaksa `object-cover`.
   - Mengikuti panduan `ui-ux-designer`, produk kasur memerlukan kontainer ambient dengan `object-contain` agar sandaran ranjang (*headboard*) dan sudut jahitan tampak utuh.
2. **Active Filter Tags / Chips**:
   - Saat pengguna memilih beberapa filter (misalnya: Brand = Elite, Kategori = Kasur Springbed, Harga < 5jt), tidak ada deretan *Active Filter Chips* di atas grid produk. Pengguna harus membuka kembali sidebar/drawer filter hanya untuk mengetahui atau membatalkan salah satu kriteria filter yang sedang aktif.
3. **Filter Drawer pada Mobile**:
   - Tombol "Terapkan Filter" di mobile drawer belum menampilkan kalkulasi instan jumlah produk yang cocok (contoh yang ideal: *"Tampilkan 24 Produk"*).
4. **Empty State Pencarian / Filter**:
   - Bila kombinasi filter menghasilkan nol produk, tampilan hanya menampilkan teks polos "Tidak ada produk". Kurang ada tombol *"Reset Semua Filter"* yang mencolok dan rekomendasi produk best seller.

#### B. Saran (Recommendations)
- **Active Filter Bar**: Pasang bar chip filter di atas katalog:
  - Format: `[Elite ✕] [Kasur Saja ✕] [Hapus Semua Filter]`.
  - Mengurangi jumlah klik pengguna saat ingin mereset satu preferensi.
- **Smart Mobile Filter Button**: Pada sticky footer drawer filter mobile, gunakan tombol dinamis dengan badge jumlah:
  ```html
  <button class="w-full py-3 bg-brand-dark text-brand-gold font-bold rounded-xl">
    Terapkan Filter (Menampilkan 18 Produk)
  </button>
  ```
- **Rich Empty State**: Tampilkan ilustrasi kasur kosong yang estetik disertai tombol *Reset Filter* dan carousel produk rekomendasi alternatif.

---

### 2.4 Halaman Detail Produk (PDP) & Selector Variasi Multi-Dimensi

#### A. Temuan (Findings)
1. **Selector Variasi Multi-Dimensi (Ukuran, Kelengkapan, Tebal, Warna)**:
   - Pemisahan atribut ke dalam kelompok terpisah (*Ukuran*, *Kelengkapan*, *Tebal*) sudah berjalan sangat baik dibanding menampilkan daftar panjang 20+ permutasi kaku.
   - *Kelemahan*: Saat user memilih Ukuran `180x200`, opsi Kelengkapan tertentu yang stoknya habis untuk ukuran tersebut masih tampak aktif seperti biasa. Pengguna baru menyadari stok habis setelah mengkliknya.
2. **Media Gallery (Luxury Studio Viewer)**:
   - Galeri foto utama sudah memiliki transisi thumbnail yang halus dan perlindungan dari dummy images.
   - *Kekurangan*: Belum ada fitur *Pinch-to-Zoom* pada mobile atau *Magnifier Lightbox* pada desktop. Detail kain kasur, jahitan quilting, dan tekstur busa merupakan faktor penentu kepuasan customer kasur premium.
3. **Informasi Harga Dinamis**:
   - Jika variasi belum dipilih, halaman menampilkan rentang harga (misal: *Rp 2.100.000 - Rp 5.400.000*). Ini sudah tepat.
   - Namun, label diskon *"Hemat Rp xxx"* baru muncul setelah variasi dipilih, padahal badge persentase diskon tertinggi bisa ditonjolkan lebih awal sebagai *hook*.
4. **Bentrokan Mobile Sticky Action Bar vs Bottom Navigation**:
   - Tombol utama *"Tambah ke Keranjang"* di PDP berada di dalam form konten utama. Di layar mobile dengan deskripsi produk panjang, tombol ini tenggelam ke bawah sehingga pengguna harus menggulir jauh.
   - Di sisi lain, jika dibuat *fixed bottom bar*, ada risiko menimpa Bottom Navigation Bar bawaan aplikasi (tinggi 70px) dan tombol Live Chat WhatsApp.

#### B. Saran (Recommendations)
- **Matrix Ketersediaan Visual (Disabled / Strikethrough State)**:
  - Jika kombinasi atribut tidak tersedia atau out-of-stock, ubah tombol opsi menjadi: border putus-putus (`border-dashed`), latar abu-abu pudar, teks dicoret atau disertai badge kecil *"Habis"*.
- **Pinch-to-Zoom & Fullscreen Gallery**: Pasang modal lightbox bersolusi tinggi saat foto utama diklik agar user dapat melihat kerapatan serat dan ketebalan kasur.
- **Konsolidasi Sticky Footer Mobile PDP**:
  - Khusus pada halaman PDP di perangkat mobile, **sembunyikan Mobile Bottom Nav 5-tab standar**, dan gantikan sepenuhnya dengan **Sticky PDP Action Bar**:
    ```html
    <!-- Hanya tampil di PDP Mobile -->
    <div class="md:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-gray-200 px-4 py-3 z-[95] flex items-center gap-3 shadow-lg">
      <button class="w-12 h-12 rounded-xl border border-gray-200 flex items-center justify-center text-gray-500">
        <i class="fa-regular fa-heart"></i>
      </button>
      <button class="w-12 h-12 rounded-xl border border-brand-gold text-brand-gold flex items-center justify-center">
        <i class="fa-solid fa-cart-plus"></i>
      </button>
      <button class="flex-1 h-12 bg-brand-dark text-brand-gold font-bold rounded-xl text-sm flex items-center justify-center">
        Beli Sekarang
      </button>
    </div>
    ```
  - Ini mencegah penumpukan bar bertingkat yang memakan sepertiga layar ponsel.

---

### 2.5 Product Bundling Flow (`/bundling`)

#### A. Temuan (Findings)
1. **Transparansi Nilai Penghematan**:
   - Paket bundling (contoh: *Beli Kasur + Gratis 2 Bantal + 1 Guling*) memiliki konversi tinggi, namun harga coret total sebelum diskon bundling terkadang kurang kontras dibanding harga satuan.
2. **Fleksibilitas Variasi dalam Bundle**:
   - Saat paket bundling mengizinkan user memilih ukuran kasur yang berbeda, UI pemilihan variasi di dalam halaman bundle detail masih memuat drop-down yang cukup padat dan memerlukan validasi berulang.

#### B. Saran (Recommendations)
- Buat kartu ringkasan komparasi:
  - *Harga Beli Terpisah*: Rp 4.500.000
  - *Harga Paket Bundling*: Rp 3.750.000
  - *Badge Khusus*: **Hemat Rp 750.000 (17%)** dalam kotak beraksen emas atau hijau emerald.
- Berikan indikator visual centang (*checkmark*) hijau pada setiap komponen bundle yang variasinya sudah berhasil dipilih lengkap.

---

### 2.6 Cart Drawer & Pengelolaan Keranjang

#### A. Temuan (Findings)
1. **Free Shipping Progress Bar**:
   - Terdapat bar progres yang memotivasi customer menambah belanjaan demi mendapatkan subsidi ongkir. Ini adalah fitur CRO yang luar biasa.
   - *Evaluasi Visual*: Warna bar progres saat mencapai 100% sebaiknya berubah dari gold menjadi emerald green disertai teks selebrasi: *"Selamat! Anda Mendapatkan Subsidi Ongkir"*.
2. **Hapus Item & Micro-Undo**:
   - Saat menekan tombol hapus (ikon tempat sampah), item langsung hilang atau memunculkan konfirmasi dialog default browser. Tidak ada opsi *Undo* sementara jika terjadi ketidaksengajaan klik (*accidental click*).
3. **Penyatuan Input Voucher**:
   - Ada input kode voucher di cart drawer, dan ada juga modal voucher di halaman checkout. Jika pengguna sudah memilih voucher di drawer, sinkronisasi ke checkout harus konsisten dan tidak meminta pengguna memilih ulang.

#### B. Saran (Recommendations)
- **Optimistic UI dengan Toast Undo**: Saat item dihapus dari keranjang, sembunyikan item dan munculkan toast notifikasi di bawah:
  *"1 Item dihapus dari keranjang. [Batal / Undo]"* berdurasi 4 detik sebelum benar-benar memproses API request ke backend.
- **Ringkasan Penghematan**: Tampilkan baris *"Total Penghematan Anda: Rp xxx"* di bawah subtotal untuk memicu kepuasan psikologis sebelum checkout.

---

### 2.7 Checkout Flow (Alamat, Kurir & Voucher)

#### A. Temuan (Findings)
1. **Wizard Stepper Visual**:
   - Stepper 3 tahap (*Keranjang → Alamat & Pengiriman → Pembayaran*) sudah sangat elegan dan memberikan navigasi mental yang jelas.
2. **Manajemen Alamat Pengiriman (Select2 vs Cascading Dropdown)**:
   - Pemilihan Provinsi → Kota → Kecamatan sudah terhubung dengan Select2 ber-styling Tailwind.
   - *Friksi*: Pada perangkat seluler, Select2 bawaan desktop kadang memicu popover dropdown yang terlalu tinggi atau memotong keyboard virtual (*virtual keyboard occlusion*).
3. **Pilihan Kurir & Layanan Biteship**:
   - Daftar kurir menampilkan logo, estimasi tiba (ETA), dan nominal ongkir.
   - *Catatan Desain*: Kartu opsi kurir (misal: JNE Kargo vs Sentral Cargo vs GoSend) menggunakan radio button kecil. Pada layar sentuh, seluruh area kartu (*full card hitbox*) seharusnya dapat diklik dengan mudah (*touch target* > 48px).
4. **Voucher Selector Modal**:
   - Modal voucher telah mendukung pemisahan voucher ongkir dan voucher produk.
   - Pesan error saat voucher tidak memenuhi syarat (misal: subtotal kurang Rp 50.000) perlu ditulis dalam bahasa yang bersahabat dan memotivasi pengguna menambah belanjaan.

#### B. Saran (Recommendations)
- **Full-Card Clickable Courier Selection**: Pastikan seluruh kontainer kartu kurir memiliki efek active-state dengan ring border tebal (`border-brand-gold ring-2 ring-brand-gold/20 bg-brand-light/10`).
- **Peta Pinpoint Opsional**: Untuk pengiriman instan/sameday di area perkotaan, sediakan tombol buka peta untuk menentukan titik koordinat (*lat, long*) agar driver tidak tersesat membawa kasur besar.
- **Sticky Order Summary pada Desktop**: Pada layar desktop lebar, pastikan kolom kanan ringkasan pesanan menggunakan `sticky top-28` agar customer selalu melihat total akhir saat mengisi formulir panjang di sisi kiri.

---

### 2.8 Payment Gateway Flow (QRIS, VA, CC, Direct Debit)

#### A. Temuan (Findings)
1. **Struktur Tab Metode Pembayaran**:
   - Metode pembayaran terbagi jelas: *Virtual Account*, *QRIS*, *Kartu Kredit*, dan *Direct Debit*.
   - Tab aktif menggunakan aksen warna brand yang tegas.
2. **Pengalaman Pengguna QRIS**:
   - Barcode QRIS tampil dengan tombol cetak/unduh.
   - *Area Penyempurnaan*: Timer masa berlaku pembayaran QRIS (umumnya 15–30 menit) membutuhkan hitung mundur (*countdown*) dengan kontras tinggi (misal badge merah/amber) agar pembeli segera menyelesaikan scan dari e-wallet (BCA Mobile, GoPay, OVO, ShopeePay).
3. **Interaksi Salin Nomor Virtual Account**:
   - Tombol "Salin Nomor Rekening / VA" sudah ada, namun feedback saat diklik masih perlu toast yang jelas: *"Nomor VA berhasil disalin ke papan klip!"* disertai ikon checklist hijau selama 2 detik.
4. **Form Keamanan Kartu Kredit**:
   - Diperlukan penambahan lencana keamanan resmi (*Verified by Visa, Mastercard ID Check, 256-Bit SSL Encryption*) tepat di bawah tombol bayar untuk menepis keraguan customer memasukkan informasi kartu berharga tinggi.

#### B. Saran (Recommendations)
- **Interactive Copy Feedback**:
  ```javascript
  navigator.clipboard.writeText(vaNumber);
  // Ubah ikon tombol menjadi fa-check dan tampilkan teks 'Tersalin!' seketika
  ```
- **Instruksi Khusus Aplikasi Mobile Banking**: Sediakan akordion panduan pembayaran bertahap (Cara Bayar via ATM, Mobile Banking, dan Internet Banking) yang bisa dilipat-buka agar halaman tidak terlihat menakutkan (*overwhelming*).

---

### 2.9 Thank You Page & Konfirmasi Transaksi

#### A. Temuan (Findings)
1. **Penyederhanaan Rincian Pembayaran**:
   - Halaman `thankyou.blade.php` telah dirapikan untuk fokus pada Status Pembayaran, Total Tagihan, dan Metode Bayar.
   - *Catatan Positif*: Mengurangi kebingungan customer yang sebelumnya melihat rincian ganda yang membingungkan.
2. **Hirarki Tombol Aksi (Call-to-Action)**:
   - Pengguna yang baru saja selesai membayar membutuhkan dua hal utama:
     1. Mengetahui ke mana harus memantau status barang (*"Lacak Pesanan"*).
     2. Menghubungi CS jika butuh konfirmasi jadwal kirim kasur (*"Konfirmasi via WhatsApp"*).
   - Tombol *"Belanja Lagi"* sebaiknya menjadi aksi sekunder, bukan primer.

#### B. Saran (Recommendations)
- **Tombol WhatsApp Konfirmasi Cepat**: Letakkan tombol hijau ramah:
  `[Hubungi WhatsApp Pengiriman]` dengan template pesan otomatis:
  *"Halo Admin Royal/IMG, saya ingin konfirmasi jadwal kirim untuk No. Pesanan ORD-XXXXX"*.
- **Unduh Bukti Transaksi (PDF / Cetak)**: Tombol cetak invoice sudah tersedia dengan styling print khusus (`@media print`), ini sangat profesional dan perlu dipertahankan.

---

### 2.10 Order Tracking & Riwayat Pesanan

#### A. Temuan (Findings)
1. **Aksi Batalkan Pesanan & Order Ulang (Baru Saja Diperbaiki)**:
   - *Sebelumnya*: Menggunakan alert browser standar (`confirm()`) dan tombol order ulang sempat mengarah ke halaman error.
   - *Kondisi Terkini*: Telah diganti dengan modal konfirmasi UI native yang selaras dengan tema aplikasi dan backend order ulang telah diarahkan dengan aman ke keranjang.
2. **Visual Stepper Logistik**:
   - Tampilan pelacakan resi kurir menggunakan garis vertikal (*tracking timeline*).
   - *Penyempurnaan*: Status terakhir (paling atas) perlu memiliki indikator titik bercahaya/pulsing (`animate-ping`) untuk menunjukkan bahwa paket sedang aktif bergerak.

#### B. Saran (Recommendations)
- **Empty State Riwayat**: Bagi customer baru yang membuka riwayat pesanan namun belum pernah bertransaksi, tampilkan ilustrasi riwayat kosong yang ramah dengan CTA *"Mulai Belanja Produk Impian"*.
- **Filter Tab Status Pesanan**: Pada desktop dan mobile, tab status (*Semua, Menunggu Pembayaran, Diproses, Dikirim, Selesai, Dibatalkan*) harus dapat digeser horizontal tanpa merusak tata letak kontainer.

---

### 2.11 Customer Dashboard & Manajemen Sesi

#### A. Temuan (Findings)
1. **Manajemen Perangkat / Sesi Login (Max 5 Sesi)**:
   - Fitur keamanan ini sangat modern. Terdapat daftar sesi aktif beserta IP, browser, dan tombol hapus sesi.
   - Kartu sesi perangkat sudah memiliki badge status perangkat saat ini (*"Perangkat Ini"*).
2. **Buku Alamat Pengguna**:
   - Pengguna dapat menyimpan beberapa alamat dan menandai *"Alamat Utama"*.
   - Saat checkout, alamat utama ini otomatis terpilih sebagai default. Ini memangkas waktu checkout (*checkout friction*) secara signifikan.

#### B. Saran (Recommendations)
- **Peringatan Kuota Sesi**: Saat user mencapai 5 sesi login, tampilkan informasi bantuan yang menjelaskan bahwa sesi paling lawas akan otomatis digantikan bila login di perangkat baru.

---

### 2.12 Mobile Usability, Accessibility (a11y) & Feedback Interaksi

#### A. Temuan (Findings)
1. **Area Sentuh (Touch Targets)**:
   - Beberapa ikon penutup modal (`✕`), tombol kuantitas plus/minus stepper, dan tombol opsi variasi kecil berukuran di bawah standar minimum Apple/Google (44px × 44px).
2. **Kontras Warna Teks Aksesibilitas**:
   - Penggunaan teks abu-abu terang (`text-gray-400` pada latar putih) pada spesifikasi produk atau footer memiliki rasio kontras di bawah 4.5:1 (WCAG AA). Pengguna lanjut usia atau yang melihat layar di bawah terik matahari akan kesulitan membaca.
3. **Kompetisi Elemen Melayang (Floating Elements Stack)**:
   - Di mobile: Bottom Navigation (70px) + Live Chat Button (bottom-24) + Notifikasi Toast. Bila tidak diatur z-index dan koordinat vertikalnya dengan disiplin, elemen-elemen ini saling menumpuk.

#### B. Saran (Recommendations)
- **Standardisasi Ukuran Kontrol**: Pastikan seluruh tombol interaktif memiliki `min-h-[44px]` dan `min-w-[44px]`.
- **Tingkatkan Kontras Teks**: Ubah teks penjelas sekunder dari `text-gray-400` menjadi minimal `text-gray-500` atau `text-gray-600`.
- **Z-Index Registry**: Buat panduan urutan lapisan tampilan:
  - Base Content: `z-0`
  - Sticky Headers: `z-30`
  - Floating Live Chat / WhatsApp: `z-40`
  - Mobile Bottom Nav: `z-50`
  - Modals / Drawers: `z-[100]`
  - Toasts / Global Alerts: `z-[110]`

---

## 3. MATRIKS TEMUAN & KLASIFIKASI PRIORITAS (P1 - P4)

| ID | Touchpoint | Temuan Masalah | Dampak Pengguna | Prioritas | Rekomendasi Solusi |
|---|---|---|---|---|---|
| **F-01** | PDP & Mobile Navigation | Tombol *"Tambah ke Keranjang"* tenggelam di mobile jika konten panjang; terjadi tumpang tindih bila memakai floating bar bersamaan dengan Bottom Nav. | Konversi mobile turun, pengguna bingung mencari tombol beli. | **P1 (Kritis)** | Sembunyikan Bottom Nav standar di PDP mobile, gantikan dengan Single Sticky PDP Action Bar (`Beli Sekarang` + `Cart`). |
| **F-02** | PDP Variasi | Variasi out-of-stock atau kombinasi ukuran-kelengkapan tidak valid tidak memiliki visual disabled/coret sebelum diklik. | *Trial-and-error* yang membuat frustrasi calon pembeli. | **P1 (Kritis)** | Render state `disabled` dengan border putus-putus (`border-dashed`), opacity 40%, dan badge *"Habis"*. |
| **F-03** | Checkout & Logistik | Seluruh area kartu kurir pengiriman belum menjadi target klik penuh (hanya bulatan radio button kecil). | Sulit diklik dengan jempol di layar ponsel (*misclick*). | **P2 (Tinggi)** | Jadikan seluruh kontainer kartu kurir sebagai label klik penuh (`cursor-pointer`) dengan highlight ring saat aktif. |
| **F-04** | Payment Gateway | Nomor Virtual Account dan Kode Pembayaran belum memiliki feedback visual seketika saat disalin (*toast feedback*). | Pengguna ragu apakah nomor rekening sudah tersalin ke clipboard. | **P2 (Tinggi)** | Terapkan animasi tombol *"Tersalin! ✓"* dan munculkan micro-toast hijau selama 2 detik. |
| **F-05** | Header & Search | Popover pencarian belum mendukung navigasi keyboard (`ArrowUp`, `ArrowDown`, `Esc`) dan tidak memiliki section *"Pencarian Populer"*. | Pengalaman pencarian terasa kaku untuk pengguna power-user desktop. | **P2 (Tinggi)** | Tambahkan event listener keyboard dan state pencarian populer saat input kosong. |
| **F-06** | Katalog (/products) | Belum ada deretan *Active Filter Chips* di atas grid produk saat filter kategori/brand/harga diterapkan. | Pengguna kesulitan memantau dan membatalkan filter aktif tanpa membuka ulang drawer. | **P2 (Tinggi)** | Pasang komponen *Active Filter Bar* dengan tombol silang (*X*) per kriteria dan tombol *Reset*. |
| **F-07** | Cart Drawer | Penghapusan item keranjang langsung menghapus tanpa opsi *Undo* 4 detik. | Kehilangan produk yang tidak sengaja tertekan saat mengatur jumlah barang. | **P3 (Sedang)** | Terapkan optimistic deletion dengan snackbar *"Item dihapus. [Batal]"*. |
| **F-08** | Beranda & Media | Banner hero mobile berpotensi memotong teks grafis jika hanya mengandalkan banner desktop horizontal. | Keterbacaan promo menurun tajam pada resolusi ponsel < 400px. | **P3 (Sedang)** | Sediakan input terpisah di CMS untuk Banner Mobile (1:1 / 4:5) dan Desktop (16:9). |
| **F-09** | Order Tracking | Titik status logistik kurir paling atas belum memiliki efek visual bahwa paket sedang dalam perjalanan aktif. | Tampilan riwayat statis kurang terasa real-time. | **P4 (Rendah)** | Berikan titik pulsing `animate-ping` hijau pada status ekspedisi teranyar. |
| **F-10** | Keamanan & Trust | Form kartu kredit dan checkout belum menyertakan lencana keamanan perbankan (SSL, 3D Secure, Visa/Mastercard). | Pengunjung ragu menyelesaikan pembayaran nominal besar. | **P4 (Rendah)** | Tambahkan footer trust strip dengan icon bank dan enkripsi data di bawah ringkasan bayar. |

---

## 4. REKOMENDASI SOLUSI DESAIN UI/UX KONKRET & ACTION PLAN

### Fase 1: Perbaikan Konversi Mobile & PDP (Quick Wins - Dampak Maksimal)
1. **Harmonisasi Sticky Bar PDP di Mobile**:
   - Nonaktifkan `nav.md:hidden fixed bottom-0` khusus di route `products.show`.
   - Pasang sticky action bar khusus PDP dengan tombol *"Tambah Keranjang"* dan *"Beli Sekarang"* yang selalu berada di atas thumb-zone pengguna.
2. **Visual Disabled State Variasi Produk**:
   - Di JavaScript pemilihan atribut, bila suatu ukuran tidak memiliki stok pada kelengkapan tertentu, tambahkan class `opacity-30 cursor-not-allowed border-dashed line-through` secara otomatis.
3. **Feedback Salin Nomor VA**:
   - Ganti teks tombol dari *"Salin"* menjadi *"Tersalin! ✓"* selama 2.000 milidetik saat diklik.

### Fase 2: Peningkatan Pengalaman Checkout & Katalog (Tahap Lanjutan)
1. **Full-Hitbox Courier Selection**:
   - Perluas area klik kartu kurir Biteship sehingga sentuhan pada nama kurir, logo, atau tarif langsung mencentang opsi tersebut.
2. **Active Filter Chips di Halaman Katalog**:
   - Tambahkan bar horizontal di atas daftar produk yang memuat filter yang sedang aktif, memudahkan user menghapus salah satu filter dengan satu ketukan.
3. **Pencarian Populer di Header Search**:
   - Saat pengguna mengetuk input pencarian di header, tampilkan 4–5 pill kata kunci yang paling sering dicari sebelum mereka mulai mengetik.

### Fase 3: Polish Visual, Trust & Konten CMS (Tahap Estetika & Kepercayaan)
1. **CMS Dual-Banner Support**:
   - Pada CMS modul banner, tambahkan field opsional `mobile_image` agar tim pemasaran dapat mengunggah banner dengan komposisi visual yang ramah layar tegak (*portrait*).
2. **Trust & Security Badges**:
   - Pasang ikon Garansi Resmi Pabrik, Gratis Konsultasi Tidur, dan Jaminan 100% Original di dekat tombol beli PDP dan di halaman pembayaran.

---

*Laporan ini disusun secara komprehensif sebagai rujukan perbaikan antarmuka (UI) dan alur pengalaman pengguna (UX) untuk meningkatkan kepuasan pelanggan serta memaksimalkan tingkat konversi (Conversion Rate).*
