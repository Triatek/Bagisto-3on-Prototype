# Bagisto Multi-Channel Sync
### Sinkronisasi Produk & Stok Otomatis → Shopee & TikTok Shop

---

## Cara Kerja

```
Admin upload produk di Bagisto
         │
         ▼
Bagisto Event: catalog.product.create.after
         │
         ▼
MultiChannelSyncServiceProvider (listener terpicu)
         │
    ┌────┴────┐
    ▼         ▼
SyncToShopee  SyncToTikTok   ← berjalan di background (queue)
    Job           Job
    │              │
    ▼              ▼
Shopee API    TikTok API     ← produk muncul di kedua marketplace
    │              │
    ▼              ▼
channel_products table       ← mapping ID disimpan
```

---

## Instalasi

### 1. Install package dependencies

```bash
composer require laraditz/shopee laraditz/tiktok
```

### 2. Salin file extension ke Bagisto

Salin seluruh folder `src/` ke dalam proyek Bagisto Anda, lalu register
service provider di `config/app.php`:

```php
'providers' => [
    // ...
    Triatek\MultiChannelSync\Providers\MultiChannelSyncServiceProvider::class,
],
```

### 3. Jalankan migration

```bash
php artisan migrate
```

Tiga tabel akan dibuat:
- `channel_products`   — mapping ID produk Bagisto ↔ marketplace
- `channel_stock_logs` — log setiap perubahan stok
- `channel_orders`     — order masuk dari marketplace

### 4. Isi konfigurasi .env

Salin `.env.example` dan isi nilai berikut:

```env
# Shopee
SHOPEE_SANDBOX_MODE=true
SHOPEE_PARTNER_ID=1234567
SHOPEE_PARTNER_KEY=your_partner_key_here
SHOPEE_SHOP_ID=987654321

# TikTok Shop
TIKTOK_APP_KEY=your_app_key
TIKTOK_APP_SECRET=your_app_secret
TIKTOK_SHOP_ID=your_shop_id

# Queue
QUEUE_CONNECTION=database
```

### 5. Otorisasi akun Shopee

```bash
# Buka URL ini di browser untuk otorisasi toko Shopee:
# https://partner.shopeemobile.com/api/v2/shop/auth_partner
#   ?partner_id=YOUR_PARTNER_ID
#   &redirect=https://yourdomain.com/shopee/auth/get_access_token
#   &sign=GENERATED_SIGN
```

### 6. Otorisasi akun TikTok Shop

```bash
# Buka URL ini di browser untuk otorisasi toko TikTok:
# https://services.tiktokshop.com/open/authorize
#   ?service_id=YOUR_SERVICE_ID
```

### 7. Setup Webhook

Set URL webhook di dashboard masing-masing marketplace:

| Platform | URL Webhook |
|---|---|
| Shopee | `https://yourdomain.com/multichannel/webhook/shopee` |
| TikTok Shop | `https://yourdomain.com/multichannel/webhook/tiktok` |

### 8. Isi mapping kategori

Edit `config/multichannel.php` → bagian `category_map`:

```php
// Shopee
'category_map' => [
    1 => 100001,  // ID kategori Bagisto => ID kategori Shopee
    2 => 100002,
],

// TikTok
'category_map' => [
    1 => '600001',
    2 => '600002',
],
```

Untuk mendapatkan category_id Shopee:
```php
Shopee::product()->getCategory(language: 'id');
```

Untuk mendapatkan category_id TikTok:
```php
TikTok::product()->getCategories();
```

### 9. Jalankan Queue Worker

```bash
# Development
php artisan queue:work --queue=marketplace-sync

# Production (gunakan Supervisor)
# Buat file /etc/supervisor/conf.d/bagisto-queue.conf:
```

```ini
[program:bagisto-marketplace-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work database --queue=marketplace-sync --tries=3 --timeout=60
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/queue-marketplace.log
```

---

## Penggunaan

Setelah instalasi selesai, **tidak ada yang perlu dilakukan secara manual**.

- **Upload produk baru** di Bagisto Admin → otomatis muncul di Shopee & TikTok Shop
- **Edit produk** (nama/harga/deskripsi) → otomatis terupdate di semua marketplace
- **Ada order masuk** → stok Bagisto otomatis berkurang, marketplace tersinkronisasi
- **Adjustment stok** manual di Bagisto → otomatis tersinkronisasi ke semua channel

---

## Monitoring

Cek status sinkronisasi via database:

```sql
-- Produk yang berhasil sync
SELECT * FROM channel_products WHERE status = 'synced';

-- Produk yang gagal sync (perlu diperhatikan)
SELECT * FROM channel_products WHERE status = 'failed';

-- Log perubahan stok
SELECT * FROM channel_stock_logs ORDER BY created_at DESC LIMIT 50;

-- Order dari marketplace
SELECT * FROM channel_orders ORDER BY created_at DESC;
```

Atau via Artisan:
```bash
# Cek failed jobs
php artisan queue:failed

# Retry semua failed jobs
php artisan queue:retry all
```

---

## Troubleshooting

| Masalah | Solusi |
|---|---|
| Produk tidak muncul di Shopee/TikTok | Cek `channel_products.status` dan `last_error` |
| Queue tidak berjalan | Pastikan `php artisan queue:work` aktif |
| Stok tidak sinkron | Cek `channel_stock_logs` untuk detail error |
| Webhook tidak diterima | Verifikasi URL webhook di dashboard marketplace |
| Error "Unauthorized" | Token expired, ulangi proses otorisasi |
| Gambar tidak terupload | Pastikan path gambar benar dan file ada |

---

## Lisensi

MIT
