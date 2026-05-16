# İş Başvuru Sistemi — Kurulum Kılavuzu

## Gereksinimler
- PHP 8.0+ (SQLSRV extension etkin)
- SQL Server 2019+ veya SQL Server Express
- Apache / IIS / XAMPP (PHP SQLSRV desteği ile)

---

## 1. Veritabanı Kurulumu

1. **SQL Server Management Studio (SSMS)** açın.
2. `sql_schema.sql` dosyasını açıp **Execute** edin.
   - `IsBasvuruSistemi` veritabanı ve tüm tablolar otomatik oluşturulur.
   - Örnek pozisyonlar ve admin hesabı da eklenir.

---

## 2. Bağlantı Ayarları

`db.php` dosyasını açın ve sunucu adını düzenleyin:

```php
$sunucu = ".\SQLEXPRESS";   // SSMS sol üstte görünen sunucu adı
```

**Windows Authentication** kullanıyorsanız UID/PWD satırlarını yorum bırakın.  
**SQL Server Authentication** kullanıyorsanız yorum kaldırın ve bilgileri girin:

```php
"UID" => "sa",
"PWD" => "sifreniz",
```

---

## 3. PHP SQLSRV Extension

`php.ini` dosyasında aşağıdakilerin aktif olduğunu kontrol edin:

```
extension=php_sqlsrv_82_ts_x64.dll
extension=php_pdo_sqlsrv_82_ts_x64.dll
```

> PHP sürümünüze göre dosya adı değişir. [https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server](adresinden) indirin.

---

## 4. Proje Dizini

Tüm dosyaları web sunucunuzun kök dizinine (örn. `C:\xampp\htdocs\isbasvuru\`) kopyalayın.

```
isbasvuru/
├── db.php
├── index.php          ← Ana sayfa + ilanlar
├── giris.php          ← Kullanıcı giriş / kayıt
├── basvuru.php        ← Başvuru formu
├── hesabim.php        ← Kullanıcı başvuru takibi
├── cikis.php          ← Çıkış
├── sql_schema.sql     ← Veritabanı şeması
├── assets/
│   └── style.css
├── uploads/           ← CV dosyaları (yazma izni gerekli)
└── admin/
    ├── login.php      ← Admin giriş
    ├── panel.php      ← Dashboard
    ├── basvurular.php ← Başvuru yönetimi
    ├── adaylar.php    ← Aday yönetimi
    ├── pozisyonlar.php← İlan yönetimi
    ├── kullanicilar.php← Kullanıcı yönetimi
    └── cikis.php
```

---

## 5. uploads/ Klasörü

CV dosyaları bu klasöre yüklenir. Yazma izni verin:

```bash
chmod 777 uploads/   # Linux
# veya Windows'ta klasör özelliklerinden "Herkes - Tam Denetim" verin
```

---

## 6. Giriş Bilgileri

### Admin
| Alan | Değer |
|------|-------|
| Kullanıcı Adı | `admin` |
| Şifre | `admin123` |
| URL | `/admin/login.php` |

### Örnek Kullanıcı
| Alan | Değer |
|------|-------|
| E-posta | `ahmet@example.com` |
| Şifre | `test123` |
| URL | `/giris.php` |

> ⚠️ **Üretim ortamında** şifreleri `password_hash()` ile şifreleyin!

---

## 7. Sayfa Akışı

```
index.php (anasayfa + ilanlar)
    ↓ "Hemen Başvur"
basvuru.php (giriş şart değil)
    ↓ başvuru gönderildi

giris.php (giriş/kayıt sekmeleri)
    ↓ giriş başarılı
hesabim.php (başvuru durumu takibi)

admin/login.php
    ↓
admin/panel.php (dashboard)
    ├── basvurular.php (durum güncelle, detay)
    ├── adaylar.php
    ├── pozisyonlar.php (ekle/düzenle/pasif)
    └── kullanicilar.php
```

---

## Notlar

- Tüm metinler ve etiketler Türkçedir (UTF-8).
- CSS tamamen özel yazılmıştır, dış bağımlılık yoktur.
- Google Fonts (Syne + DM Sans) internet bağlantısı gerektirir.
