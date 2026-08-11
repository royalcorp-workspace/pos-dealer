# Analisis Perubahan & Rencana Implementasi Fitur IMG Store

Dokumen ini membedah 12 poin perubahan/fitur baru serta struktur relasi yang akan diimplementasikan secara kolaboratif pada ekosistem **IMG Store** yang melibatkan tiga sistem utama:

---

## 🏛️ Arsitektur Kolaborasi Tiga Sistem (Triple-System Architecture)
Seluruh rancangan fitur dalam ekosistem ini didelegasikan pada tiga repositori terpisah:
1. **`pos-dealer-web` (Frontend Web)**: Antarmuka utama customer pada website. Melakukan rendering katalog, proses transaksi checkout, dan langsung berinteraksi dengan database bersama (`img-db`).
2. **`cms-img-store` (Admin Panel / CMS)**: Panel administrasi terpusat. Digunakan oleh admin untuk mengelola katalog, persetujuan level member/reseller, pengaturan promo, konten banner, dan monitoring transaksi.
3. **`backend-img` (REST API Server)**: Gerbang API terpusat untuk aplikasi Mobile. Semua fitur pada web (seperti Katalog, Bundling, Wishlist, Live Chatting, dan Notifikasi) wajib dibuatkan API endpoint-nya di sini agar dapat ditarik dan dioperasikan secara penuh oleh aplikasi Mobile.

---

## 1. Product Bundling
Fitur untuk menjual paket produk (misal: Kasur + 2 Bantal + 1 Guling) dengan harga paket khusus.

### Rencana Backend (`cms-img-store`)
* **Migrasi Database**:
  * Membuat tabel `products_bundling`: `id` (UUID), `name`, `slug`, `description`, `price` (decimal), `is_active` (boolean), timestamps.
  * Membuat tabel `products_bundling_items` (pivot): `id` (UUID), `product_bundling_id` (foreign key to `products_bundling`), `product_id` (foreign key to `products`), `quantity` (integer), `variant_id` (optional, foreign key to `product_variants`).
* **Integrasi Sistem Promo (`price_product_settings`)**:
  * Struktur tabel promo dihubungkan dengan tabel bundling sehingga diskon (baik persentase maupun nominal langsung) dapat diaplikasikan langsung ke harga utama (harga header) pada `products_bundling`.
* **CMS Backend**:
  * Form pembuatan bundle di mana admin dapat memilih produk-produk penyusun bundle, kuantitasnya, menentukan harga bundel global, dan mengatur diskon promo melalui menu price product settings.

### Rencana Frontend (`pos-dealer-web`)
* **Menu Khusus (Dedicated Menu)**: Dibuat menu/halaman khusus "Bundling" di navigasi utama (navbar) untuk menampilkan semua paket bundling aktif yang ditawarkan.
* **Tampilan Katalog**: Menampilkan bundle layaknya produk biasa dengan badge "Bundling Hemat".
* **Halaman Detail Bundle**: Menampilkan daftar produk yang masuk dalam paket beserta spesifikasinya.
* **Keranjang Belanja (Cart)**: Menambahkan item tipe `bundle` ke keranjang belanja dengan penghitungan harga bundle khusus.

---

## 2. Memiliki Guest Mode (Direct Checkout & Auto-Account)
Memungkinkan pengguna untuk langsung melakukan checkout tanpa harus dipaksa mendaftar/login terlebih dahulu lewat tombol khusus.

### Alur Fitur Guest Mode & Direct Checkout
* **Tanpa Pilihan "Checkout sebagai Tamu"**: Pengguna dapat langsung mengisi formulir checkout. Sistem akan otomatis mendaftarkan/membuat akun customer baru di latar belakang menggunakan data email/nomor telepon yang dimasukkan saat checkout.
* **Smart Autocomplete (Auto-Fill)**: Saat customer memasukkan email atau nomor telepon pada halaman checkout, sistem akan mendeteksi apakah data tersebut sudah terdaftar di database. Jika sudah ada, sisa kolom formulir pengiriman (seperti Nama Lengkap, Alamat, Kota, dll.) akan terisi secara otomatis (*autocomplete*).
* **Integrasi Google Login**: Ketika pengguna login menggunakan Google, sistem akan memeriksa apakah email Google tersebut sudah ada di tabel customer (yang mungkin dibuat otomatis dari transaksi sebelumnya). Jika sudah ada, sistem akan langsung menghubungkan akun dan memproses login ke data customer tersebut.
* **Tidak Bisa Cancel Order**: Semua customer, baik yang bertransaksi menggunakan **Guest Mode** maupun **User yang sudah login**, **tidak dapat melakukan pembatalan pesanan secara mandiri (No Cancel Order)** melalui frontend web. Pembatalan pesanan hanya dapat dilakukan oleh Admin melalui panel CMS.

### Rencana Backend (`cms-img-store`)
* **Migrasi Database**:
  * Menambahkan kolom `is_guest` (boolean, default false) pada tabel `users` / `customers` untuk mengidentifikasi akun yang terbuat otomatis dari checkout langsung (tanpa password).
  * Pesanan tetap terhubung secara standar menggunakan `customer_id` yang merujuk ke tabel customer, tanpa memerlukan kolom email/telepon tambahan di tabel orders.
* **Proses Checkout**:
  * Memodifikasi API Order untuk otomatis mendeteksi kecocokan email/no telepon, auto-create customer record jika belum ada, dan mereturn data alamat lama untuk autocomplete.

### Rencana Frontend (`pos-dealer-web`)
* **Checkout Flow & Autocomplete**: Halaman checkout mendengarkan input email/telepon. Menggunakan AJAX call untuk memicu autocomplete jika data ditemukan.
* **Google Auth Handler**: Handler Google Auth mencocokkan email dengan database customer yang ada sebelum membuat record baru.

---

## 3. Images Scroll Left (Product Gallery Showcase)
Desain foto produk pada Halaman Detail Produk seperti di Shopee atau [img.id](https://img.id/product/lady-americana-bantal-femto-fiber-bulu-angsa-goose-down/).
* **Sistem Visual**: Terdapat 1 gambar besar utama (*Main Image*) di bagian atas, dan deretan gambar kecil (*Thumbnails*) di bawahnya yang dapat digeser (scroll) ke kiri-kanan secara horizontal dan dipilih/diklik untuk mengganti gambar utama.
* **Product Suggest (Rekomendasi Produk)**: Pada setiap halaman detail produk, ditambahkan bagian rekomendasi produk sejenis atau pelengkap (*Product Suggest* / *Related Products*) di bagian bawah halaman detail.

### Rencana Tampilan Panel Admin (Setting di CMS)
Agar admin memiliki gambaran jelas saat mengunggah dan mengatur foto produk di CMS:
* **Upload Area Multi-Image**:
  * Menggunakan modul drag-and-drop file uploader (seperti Dropzone.js or FilePond) pada form tambah/edit produk.
* **Fitur Pengaturan Gambar**:
  * **Sort Order (Reorder)**: Admin dapat menyeret (drag & drop) gambar untuk mengatur urutan tampil dari kiri ke kanan di frontend.
  * **Set Main/Primary**: Ada tombol radio/bintang untuk menentukan gambar mana yang menjadi gambar utama (muncul pertama kali).
  * **Alt Text Input**: Input teks alternatif di setiap gambar untuk kepentingan SEO Gambar.

### Tampilan di Frontend Web (User View)
* **Desktop**: Area Gambar Utama (resolusi tinggi) + fitur Zoom. Di bawahnya terdapat daftar thumbnail horizontal yang jika di-hover atau diklik, otomatis mengganti gambar utama dengan transisi memudar halus (*fade-in*).
* **Mobile/Tablet**: Gambar utama dapat di-swipe langsung ke kiri/kanan (touch gesture swipe). Di bawahnya terdapat indikator posisi slide (misal: `1/5`) beserta deretan thumbnail kecil yang dapat di-scroll secara horizontal tanpa memakan banyak tempat.

---

## 4. Custom Posisi Homepages, dibuat per Section
Membuat tata letak (layout) homepage dinamis, di mana urutan section (Hero, New Arrival, Best Seller, Brands, Promo, Spotlight) dapat diatur posisisinya dari CMS.

### Rencana Backend (`cms-img-store`)
* **Migrasi Database**:
  * Membuat tabel `homepage_sections`: `id` (UUID), `section_key` (string, unik, misal: `hero`, `new_arrival`, `best_seller`, `brands`, `promo_brand`), `title` (string), `sort_order` (integer), `is_visible` (boolean).
* **CMS Backend**:
  * Halaman pengaturan drag-and-drop / input nomor urutan untuk mengatur urutan `sort_order` section.

### Rencana Frontend (`pos-dealer-web`)
* **Blade Logic**:
  * Di `HomeController@index`, memuat daftar section dari tabel `homepage_sections` yang aktif secara berurutan berdasarkan `sort_order`.
  * Di `home.blade.php`, render layout secara dinamis menggunakan `@foreach` atau pengecekan dinamis, memuat sub-view blade masing-masing section.

---

## 5. Live Chatting (Firebase & Cache-based Auto-Reply)
Menyediakan fitur obrolan langsung (live chat) bagi customer untuk berkonsultasi secara real-time dengan Admin.

### Arsitektur Sistem Chat
* **Real-time Chat via Firebase**: Menggunakan layanan Firebase (Realtime Database / Firestore) untuk menyinkronkan pesan chat antara customer dan agen secara langsung (instant messaging).
* **Auto-Reply Hemat Kuota (Offline/Cache-based)**:
  * Untuk pesan selamat datang (*welcome message*), jawaban otomatis (*auto-replies*), dan daftar pertanyaan umum (*FAQ*), data akan disimpan dalam **Cache Browser (LocalStorage/SessionStorage)** atau dimuat dari file konfigurasi statis JSON di server lokal.
  * Teks pesan otomatis akan langsung muncul di UI chat customer tanpa melakukan baca/tulis (*read/write*) ke Firebase.
  * Koneksi dan transaksi token Firebase baru akan dipicu saat customer pertama kali mengetikkan pesan khusus yang membutuhkan respon agen manusia (human agent), sehingga menghemat kuota token Firebase secara signifikan.

### Rencana Backend (`cms-img-store`)
* **Konfigurasi Autoreply**: Menyediakan form di CMS untuk mengedit teks sambutan otomatis dan daftar tanya-jawab FAQ yang nantinya di-cache di frontend.

### Rencana API Server (`backend-img`)
* **API Chat Integration**: Menyediakan REST API/WebSocket endpoint bagi aplikasi Mobile untuk mengambil riwayat percakapan (*chat history*) serta melakukan sinkronisasi sesi chat Firebase dari sisi Mobile.

### Rencana Frontend (`pos-dealer-web`)
* **Chat Widget**: Integrasi Firebase SDK untuk menangani chatting setelah user melewati penyaringan auto-reply lokal. Membaca pesan sambutan otomatis dari cache sebelum mengaktifkan listener database Firebase.

---

## 6. Multi Language (ID & ENG)
Dukungan penuh dua bahasa pada web, terutama untuk katalog, deskripsi produk, informasi menu, dan panduan transaksi.

### Rencana Backend (`cms-img-store`)
* **Lokalisasi Konten**:
  * Membuat tabel translasi (misal `product_translations`, `category_translations`) atau menggunakan penyimpanan tipe JSON translatable untuk kolom nama dan deskripsi di tabel utama.

### Rencana Frontend (`pos-dealer-web`)
* **Laravel Localization**:
  * Memanfaatkan file bahasa Laravel di folder `/lang/id/` dan `/lang/en/` untuk static text.
  * Selector bahasa di navbar (ID / EN) yang mengganti locale session.
  * Mengarahkan route dengan prefix bahasa (opsional, misal `/en/products` or `/id/products`).

---

## 7. Manajemen Customer: Flag Reseller, Multi-Alamat, Level Membership & Alur Registrasi
Peningkatan fitur pengelolaan customer mulai dari pendaftaran, klasifikasi level, hingga alamat pengiriman yang lengkap.

### 1. Level Membership & Tipe Customer
* **Flag Tipe Customer**: Pembedaan jenis user untuk mengakomodasi harga khusus bagi akun Reseller/Dropshipper.
  * Kolom `customer_type` / `flag` (smallInteger, default 1) dengan comment `'Tipe customer (1 = biasa, 2 = reseller)'`.
* **Level Membership**: Terdapat klasifikasi level member (misalnya: *Bronze*, *Silver*, *Gold*) berdasarkan akumulasi jumlah transaksi atau poin belanja, yang memberikan diskon tambahan otomatis saat checkout.
* **Proses Approval Reseller di CMS**:
  * Pengajuan upgrade status Reseller diajukan oleh customer dari web, namun persetujuan (*approval*) dikonfirmasi secara manual oleh Admin di CMS (`cms-img-store`) sebelum harga khusus reseller (flag = 2) diaktifkan.

### 2. Manajemen Multi-Alamat (Address Book)
* Mengatasi fitur alamat lama yang terlalu sederhana. Sistem baru mendukung pembuatan buku alamat (*Address Book*) di mana customer bisa menyimpan lebih dari satu alamat (Alamat Utama, Kantor, Rumah, dll.) dengan kolom yang lengkap (Provinsi, Kota/Kabupaten, Kecamatan, Kode Pos, Detail Alamat, Nama Penerima, No. Telepon Penerima).

### 3. Alur Registrasi Akun
* **Registrasi Manual**: Formulir pendaftaran dilengkapi kolom wajib secara komprehensif (Nama Lengkap, Email, No. Telepon, dan Alamat Utama) untuk langsung membentuk profil customer yang lengkap saat akun terdaftar.
* **Registrasi via Google (Google OAuth)**:
  * Menghapus input password dan isian form manual lainnya saat mendaftar menggunakan tombol Google.
  * Akun langsung dibuat secara instan berdasarkan email Google, sementara data kata sandi (password) dan profil alamat dapat dilengkapi nanti oleh customer melalui halaman Profil Akun atau saat melakukan transaksi Checkout pertama kali.

### 4. Peningkatan Fitur Form Customer di CMS Admin Panel
* **Form Pembuatan Customer Komprehensif**: Mengubah form "Tambah/Edit Customer" di admin panel `cms-img-store` yang sebelumnya terlalu sederhana. Form baru kini mendukung:
  * Detail Akun: Nama Lengkap, Email, No. Telepon, Status Akun (Aktif/Suspen), Kata Sandi (Password).
  * Pengaturan Bisnis: Pilihan Tipe Customer (1 = Biasa, 2 = Reseller), Pilihan Level Membership (Bronze, Silver, Gold).
  * Pengelolaan Multi-Alamat Langsung: Admin dapat menambahkan satu atau lebih alamat pengiriman untuk customer tersebut secara dinamis (mengatur Nama Penerima, Telepon, Provinsi, Kota, Kecamatan, Kode Pos, Detail Alamat, serta menentukan Alamat Utama) langsung di halaman edit/detail customer.

### Rencana Backend (`cms-img-store`)
* **Migrasi Database**:
  * Menambahkan kolom `customer_type` / `flag` (smallInteger, default 1) dan `membership_level` (string/smallInteger) pada tabel `users`/`customers`.
  * Menambahkan kolom `reseller_price` pada tabel `product_variants`.
  * Membuat tabel `customer_addresses`: `id` (UUID), `customer_id` (foreign key), `label` (Rumah/Kantor), `receiver_name`, `receiver_phone`, `province_id`, `city_id`, `district_id`, `postal_code`, `address_detail`, `is_primary` (boolean).
* **CMS Backend**:
  * Mengembangkan ulang antarmuka CRUD Customer di CMS untuk mendukung form detail lengkap, seting level member/reseller flag, dan sub-form dinamis buku alamat (multi-address setup).
* **Logika Harga**:
  * Menggunakan `reseller_price` atau diskon level membership jika customer memenuhi kriteria saat memproses subtotal order.

### Rencana Frontend (`pos-dealer-web`)
* **Halaman Profil & Buku Alamat**: Menyediakan antarmuka untuk menambah, mengedit, menghapus, serta menyetel alamat utama (*set primary address*).
* **Halaman Register**: Penyesuaian formulir registrasi manual (komprehensif) dan tombol Google OAuth (cepat, tanpa password).

## 8. Popup Event yang sedang berlangsung (Unified Campaign/Event Setup)
Menampilkan jendela pop-up promo / event diskon besar ketika pengguna pertama kali membuka website.

### Relasi & Hierarki Kampanye (Campaign)
* **Hierarki Relasi**: `Event (Kampanye)` -> Terhubung ke -> `Price Product Setting (Aturan Diskon/Promo)` -> Terhubung ke -> `Event Popup (Visual Pop-up di Frontend)`.
* **Fitur Tombol Aksi (CTA Button)**: Setiap pop-up dibekali tombol aksi utama (seperti "Lihat Detail", "Klaim Promo") yang jika diklik akan mengarahkan pengguna ke halaman event atau produk promo.
* **Tombol Buka Kembali (Reopen Trigger Button)**: Menyediakan tombol/ikon notifikasi kecil melayang (floating notification button) di pojok halaman untuk memudahkan pengguna membuka kembali pop-up event tersebut jika mereka berubah pikiran setelah sebelumnya menutupnya.

### Efisiensi UX Admin (Unified Setup Form)
* **One-Step Campaign Creation**: Untuk efisiensi waktu admin, di CMS (`cms-img-store`) tidak dibuat menu terpisah-pisah. Disediakan **satu halaman/form terpadu** di mana ketika Admin membuat/mengedit suatu Event, Admin dapat sekaligus:
  1. Mengisi detail kampanye **Event** (Nama event, deskripsi, tanggal aktif).
  2. Menyusun **Price Product Setting** (menentukan produk yang diskon serta nilai persen/nominal potongannya).
  3. Mengunggah gambar **Event Popup** beserta konfigurasi teks tombol aksinya.
* Sistem di backend akan memproses input tersebut dan menyimpannya secara otomatis ke relasi tabel-tabel terkait di latar belakang.

### Rencana Backend (`cms-img-store`)
* **Migrasi Database**:
  * Membuat tabel `events`: `id` (UUID), `title`, `slug`, `start_date`, `end_date`, `is_active` (boolean).
  * Menambahkan kolom `event_id` (UUID, nullable) pada tabel `price_product_settings` untuk merelasikan aturan diskon ke event tertentu.
  * Membuat tabel `event_popups`: `id` (UUID), `event_id` (UUID, foreign key), `title`, `image_url`, `link_url`, `button_text` (string, default "Lihat Promo"), `is_active` (boolean).

### Rencana Frontend (`pos-dealer-web`)
* Menampilkan modal pop-up secara otomatis di halaman utama jika ada event aktif yang memiliki relasi popup.
* Menggunakan **LocalStorage** / **Session Cookie** (`event_popup_dismissed`) agar pop-up tidak muncul berulang-ulang di setiap refresh halaman demi kenyamanan pengguna.

---

## 9. Notifikasi jika ada Promo Terbaru ataupun informasi lainnya
Sistem pemberitahuan kepada user mengenai promo terbaru, informasi stok, atau status pesanan.

### Rencana Backend (`cms-img-store`)
* Database table `notifications` untuk merekam notifikasi sistem.

### Rencana Frontend (`pos-dealer-web`)
* **Notification Bell (Header)**: Menampilkan jumlah notifikasi belum dibaca.
* **Toast Notification**: Pesan pop-up melayang kecil (toast) jika ada promo kilat baru saat user sedang aktif menjelajah website.

---

## 10. Banner, Running Banner dengan Flag Web & Mobile (Termasuk Banner per Brand & Kategori)
Banner utama, banner khusus halaman brand, banner halaman kategori, dan teks berjalan (running banner) yang dapat dipisahkan tampilannya berdasarkan perangkat user (Desktop/Web vs Mobile/Responsive).

### Fitur Kustomisasi Banner
* **Banner per Brand & Kategori**: Setiap Brand dan Kategori memiliki banner khusus yang tampil di bagian atas halaman katalog mereka masing-masing (misal: halaman Brand "Lady Americana" atau Kategori "Kasur Spring" memiliki visual banner unik yang relevan).
* **Flag-Based untuk Mobile**: Mobile developer akan menyesuaikan visual di aplikasi mobile secara mandiri. API server hanya perlu mengembalikan flag target perangkat dan flag penempatan agar tim mobile dapat mem-filter data secara dinamis.
* **Metode Konten Fleksibel (Upload vs Embed)**: Admin dapat menentukan tipe input banner di CMS:
  1. **Upload File**: Mengunggah gambar banner langsung ke penyimpanan lokal/cloud.
  2. **Embed Code / URL**: Memasukkan kode embed HTML (iframe/tag custom) atau tautan URL eksternal secara langsung tanpa unggah file.
* **Responsive Layout (Web & Mobile)**: Setiap jenis banner (Home Slider, Brand Banner, Category Banner) diunggah dalam dua ukuran terpisah: satu untuk desktop/wide screen (lebar landscape) dan satu untuk smartphone (kotak/portrait) demi kenyamanan pembacaan teks promo.

### Rencana Backend (`cms-img-store`)
* **Migrasi Database**:
  * Modifikasi / buat tabel `banners`:
    * `id` (UUID), `title`, `link_url`, `is_active` (boolean), `sort_order`.
    * `type` (smallInteger, default 1) dengan comment `'Tipe banner (1 = slider, 2 = running banner)'`.
    * `device_flag` (smallInteger, default 1) dengan comment `'Flag perangkat (1 = all, 2 = web only, 3 = mobile only)'`.
    * `placement_size` (smallInteger, default 1) dengan comment `'Ukuran/Penempatan (1 = Main Banner 1920x500, 2 = Square 300x300, 3 = Custom)'`.
    * `content_type` (smallInteger, default 1) dengan comment `'Metode Konten (1 = File Upload, 2 = Embed Code / External URL)'`.
    * `image_web_url` & `image_mobile_url` (string, 500, nullable) -> untuk tipe Upload Gambar.
    * `embed_web_content` & `embed_mobile_content` (text, nullable) -> untuk tipe Embed Code/HTML.
  * Modifikasi tabel `brands`: Menambahkan kolom `banner_web` (string, 500, nullable) dan `banner_mobile` (string, 500, nullable) untuk menyimpan banner spesifik brand.
  * Modifikasi tabel `product_category`: Menambahkan kolom `banner_web` (string, 500, nullable) dan `banner_mobile` (string, 500, nullable) untuk menyimpan banner spesifik kategori.
* **CMS Backend**:
  * Form pengelolaan brand & kategori dilengkapi dengan input unggah file gambar terpisah untuk Banner Web (Desktop) dan Banner Mobile.
  * Form pengelolaan banner dilengkapi pilihan radio button `Content Type` (Upload vs Embed), kolom input teks untuk kode HTML embed/URL, serta kolom pilihan `Placement Size`.

### Rencana API Server (`backend-img`)
* Menyediakan data URL `banner_web` dan `banner_mobile` serta `device_flag` pada response JSON API detail brand/kategori dan list banner untuk disaring oleh aplikasi Mobile.

### Rencana Frontend (`pos-dealer-web`)
* **Responsive Slider & Header**:
  * Di homepage, merender main slider menggunakan gambar banner desktop (`hidden md:block`) dan mobile (`block md:hidden`). Jika bertipe `embed`, render HTML/iframe secara aman.
  * Di halaman produk per brand/kategori, merender header banner khusus yang responsive di bagian atas daftar katalog produk.
  * Teks berjalan di bagian atas halaman (announcement bar) dinamis menyesuaikan data `running_banner`.ner`.

---

## 11. Struktur Hubungan Brand & Kategori (Brand Sub-Categories)
Beberapa brand memiliki kategori/anak produk yang spesifik. Berikut struktur relasinya:

* **Lady Americana**
  * Kasur Spring
  * Bed Linen
  * Accessories
* **Elite Springbed**
  * Kasur Spring
  * Bed Linen
  * Accessories
* **Royal Foam**
  * Kasur Busa
  * Accessories
* **Tote Bed**
  * Kasur Spring
  * Bed Linen
  * Sofabed
  * Accessories
* **Moro Baby**
  * Bolster
  * Pillow
  * Blanket
  * Pyjamas
* **Serenity**
  * Kasur Spring
  * Kasur Busa

### Pilihan Teknik Implementasi Database
#### Opsi A: Relasi Dinamis (Implicit Relation via Products)
Tidak memerlukan perubahan struktur tabel. Kategori di bawah brand diambil secara dinamis dari produk yang aktif di database:
```php
// Query dinamis mendapatkan kategori yang aktif untuk suatu Brand
$categoriesForBrand = ProductCategory::whereHas('products', function($query) use ($brandId) {
    $query->where('brand_id', $brandId);
})->get();
```

#### Opsi B: Tabel Relasi Eksplisit (Pivot Table)
Membuat tabel relasi eksplisit di `cms-img-store` agar admin dapat memasangkan kategori ke brand melalui CMS secara statis (walaupun produknya belum diupload):
* **Migrasi Baru di `cms-img-store`**:
  ```php
  Schema::create('brand_category_relations', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->uuid('brand_id');
      $table->uuid('category_id');
      $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
      $table->foreign('category_id')->references('id')->on('product_category')->onDelete('cascade');
  });
  ```

---

## 12. Wishlist Disimpan di Database
Daftar keinginan (wishlist) disimpan secara persisten di database (bukan di session/cookies lokal frontend) agar data tersimpan aman dan sinkron.

### Rencana Backend (`cms-img-store` / `backend-img`)
* **Migrasi Database**:
  * Membuat tabel `wishlists`: `id` (UUID), `user_id` (foreign key to `users`/`customers`), `product_id` (foreign key to `products`), `created_at`, `updated_at`.
* **API Endpoints**:
  * Pembuatan REST API untuk kebutuhan external/mobile app akan diimplementasikan secara terpisah di repository **`backend-img`** di kemudian hari.

### Rencana Frontend (`pos-dealer-web`)
* **Direct Database Write**: Karena frontend terhubung ke database yang sama, tombol hati (wishlist) akan langsung memproses penambahan/penghapusan data ke tabel `wishlists` di database menggunakan model Eloquent / Query Builder Laravel secara langsung, tanpa melalui perantara API HTTP request untuk saat ini.

---

## 13. Fitur Penyaringan & Pengurutan Katalog Produk (Filtering & Sorting)
Peningkatan fitur pencarian, filter, dan pengurutan (*sorting*) produk pada halaman katalog untuk memudahkan customer menemukan produk yang sesuai.

### A. Fitur Sorting (Pengurutan)
Menyediakan pilihan dropdown pengurutan produk di halaman katalog:
* **Harga Terendah ke Tertinggi (Price: Low to High)**.
* **Harga Tertinggi ke Terendah (Price: High to Low)**.
* **Terpopuler / Best Seller**: Berdasarkan flag `best_seller` atau volume penjualan produk.
* **Terbaru / New Arrival**: Mengurutkan berdasarkan tanggal upload terbaru (`created_at`) atau flag `is_new`.

### B. Fitur Filtering (Penyaringan)
Menyediakan filter sidebar interaktif:
* **Filter Rentang Harga (Price Range Filter)**: Input manual harga minimal & maksimal atau slider rentang harga.
* **Filter Brand**: Pilihan checkbox brand kasur/bedding (Lady Americana, Elite, Royal Foam, dsb.).
* **Filter Kategori**: Filter cepat berdasarkan tipe barang (Kasur Spring, Bantal, Bed Linen, dsb.).
* **Filter Ketersediaan Stok**: Menyaring produk yang siap kirim (*in-stock*) atau *pre-order*.

### Rencana Backend & API Server (`cms-img-store` / `backend-img`)
* **Parameter Query (URL Query Params)**:
  * Query database dioptimalkan untuk membaca filter parameter seperti: `?sort=price_asc|price_desc|newest|popular`, `?min_price=1000000`, `?max_price=5000000`, `?brands=uuid1,uuid2`, `?categories=uuid3`.
  * REST API di **`backend-img`** untuk endpoint list produk (`GET /api/products`) wajib menerapkan dukungan filter dan sort parameter yang sama agar aplikasi Mobile dapat merender data filter yang identik.

### Rencana Frontend (`pos-dealer-web`)
* **Responsive Sidebar Filter**: Desain filter sidebar collapsible di mobile.
* **AJAX / URL Reload**: Menyinkronkan hasil pencarian produk secara instan begitu filter diubah, baik menggunakan AJAX reload komponen kartu produk atau dengan merefresh halaman menggunakan query string Laravel.

---

## 14. Desain Navigasi Submenu Horizontal di CMS Admin Panel
Penyederhanaan tata letak menu pada CMS (`cms-img-store`) untuk meningkatkan efisiensi navigasi dan kerapian visual admin panel.

### Standar Tata Letak Submenu
* **Submenu Horizontal di Header**:
  * Menu utama tingkat atas tetap berada di sidebar (misalnya menu utama **Products**).
  * Menu anak (child/submenu) seperti **Product**, **Category**, dan **Brand** tidak ditampilkan menumpuk di sidebar, melainkan dijajarkan ke arah kiri secara horizontal di bagian atas halaman (tepat di bawah header utama, sekitar area breadcrumbs).
  * Desain ini memudahkan admin untuk beralih antar manajemen data yang serumpun tanpa harus membuka-tutup dropdown sidebar.
* **Visual Styling**:
  * Menggunakan gaya tabs/pills horizontal yang elegan dengan indikator aktif (active state highlight) yang jelas untuk menandai halaman yang sedang dibuka admin.

