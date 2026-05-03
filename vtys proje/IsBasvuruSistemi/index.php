<?php
// ============================================================
// DOSYA YOLU: IsBasvuruSistemi/index.php
// AÇIKLAMA  : Adayların başvuru yaptığı form.
//             ADAYLAR, BASVURULAR, EGITIM tablolarına kayıt atar.
// ============================================================
include("db.php");

$basari = "";
$hata   = "";

// POZISYONLAR tablosundan pozisyonları çek
$pozSorgu = sqlsrv_query($baglanti, "SELECT PozisyonId, PozisyonAdi, CalismaSekli FROM POZISYONLAR ORDER BY PozisyonAdi");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Form verilerini al
    $ad             = trim($_POST["ad"]);
    $soyad          = trim($_POST["soyad"]);
    $eposta         = trim($_POST["eposta"]);
    $telefon        = trim($_POST["telefon"]);
    $dogum_tarihi   = trim($_POST["dogum_tarihi"]) ?: null;
    $cinsiyet       = trim($_POST["cinsiyet"]) ?: null;
    $pozisyon_id    = (int)$_POST["pozisyon_id"];
    $maas_beklenti  = $_POST["maas_beklenti"] !== "" ? (float)$_POST["maas_beklenti"] : null;
    $baslangic      = trim($_POST["baslangic_tarihi"]) ?: null;
    $referans       = trim($_POST["referans_kaynagi"]) ?: null;
    $hakkinda_metin = trim($_POST["hakkinda"]) ?: null;
    $kvkk           = isset($_POST["kvkk"]) ? 1 : 0;
    $universite     = trim($_POST["universite"]);
    $bolum          = trim($_POST["bolum"]);
    $mezuniyet_durum= trim($_POST["mezuniyet_durum"]);
    $mezuniyet_yili = trim($_POST["mezuniyet_yili"]) ?: null;

    // AdminId: BASVURULAR için zorunlu — sistemdeki ilk admini kullan
    $adminSonuc = sqlsrv_query($baglanti, "SELECT TOP 1 AdminId FROM ADMIN");
    $adminRow   = sqlsrv_fetch_array($adminSonuc, SQLSRV_FETCH_ASSOC);
    $admin_id   = $adminRow ? $adminRow["AdminId"] : 1;

    if (!$kvkk) {
        $hata = "KVKK metnini onaylamanız zorunludur.";
    } else {
        // 1. ADAYLAR tablosuna ekle (eposta varsa güncelle)
        $adaySorgu = "SELECT AdayId FROM ADAYLAR WHERE Eposta = ?";
        $adaySonuc = sqlsrv_query($baglanti, $adaySorgu, array($eposta));
        $adayRow   = sqlsrv_fetch_array($adaySonuc, SQLSRV_FETCH_ASSOC);

        if ($adayRow) {
            $aday_id = $adayRow["AdayId"];
        } else {
            $adayEkle = "INSERT INTO ADAYLAR (Ad, Soyad, Eposta, Telefon, DogumTarihi, Cinsiyet)
                         VALUES (?, ?, ?, ?, ?, ?)";
            $adayParams = array($ad, $soyad, $eposta, $telefon, $dogum_tarihi, $cinsiyet);
            $adayResult = sqlsrv_query($baglanti, $adayEkle, $adayParams);

            if (!$adayResult) {
                $hata = "Aday kaydedilemedi: " . print_r(sqlsrv_errors(), true);
            } else {
                $idSonuc = sqlsrv_query($baglanti, "SELECT SCOPE_IDENTITY() AS AdayId");
                $idRow   = sqlsrv_fetch_array($idSonuc, SQLSRV_FETCH_ASSOC);
                $aday_id = (int)$idRow["AdayId"];
            }
        }

        if (!$hata) {
            // 2. BASVURULAR tablosuna ekle
            $basvuruEkle = "INSERT INTO BASVURULAR (AdayId, PozisyonId, AdminId, Durum, MaasBeklenti, BaslangicTarihi, ReferansKaynagi, Hakkinda, KvkkOnay)
                            VALUES (?, ?, ?, N'Beklemede', ?, ?, ?, ?, ?)";
            $basvuruParams = array($aday_id, $pozisyon_id, $admin_id, $maas_beklenti, $baslangic, $referans, $hakkinda_metin, $kvkk);
            $basvuruResult = sqlsrv_query($baglanti, $basvuruEkle, $basvuruParams);

            if (!$basvuruResult) {
                $hata = "Başvuru kaydedilemedi: " . print_r(sqlsrv_errors(), true);
            } else {
                // 3. EGITIM tablosuna ekle
                $egitimEkle = "INSERT INTO EGITIM (AdayId, Universite, Bolum, MezuniyetDurum, MezuniyetYili)
                               VALUES (?, ?, ?, ?, ?)";
                sqlsrv_query($baglanti, $egitimEkle, array($aday_id, $universite, $bolum, $mezuniyet_durum, $mezuniyet_yili));

                $basari = "Başvurunuz başarıyla alındı! En kısa sürede sizinle iletişime geçeceğiz.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İş Başvurusu — İş Başvuru Sistemi</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="navbar-logo">İş<span>Başvuru</span></a>
    <div class="navbar-menu">
        <a href="login.php">Admin Girişi</a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <h1>Kariyerine <span>Yeni Bir Adım</span> At</h1>
    <p>Açık pozisyonlarımıza başvurmak için formu doldurun. Ekibimiz en kısa sürede sizinle iletişime geçecek.</p>
</div>

<div class="kapsayici">
    <?php if ($basari): ?>
        <div class="mesaj mesaj-basari"><?= htmlspecialchars($basari) ?></div>
    <?php endif; ?>
    <?php if ($hata): ?>
        <div class="mesaj mesaj-hata"><?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php">

        <!-- KİŞİSEL BİLGİLER (ADAYLAR tablosu) -->
        <div class="kart">
            <div class="kart-baslik">👤 Kişisel Bilgiler</div>
            <div class="form-grid">
                <div class="form-grup">
                    <label>Ad *</label>
                    <input type="text" name="ad" required placeholder="Adınız">
                </div>
                <div class="form-grup">
                    <label>Soyad *</label>
                    <input type="text" name="soyad" required placeholder="Soyadınız">
                </div>
                <div class="form-grup">
                    <label>E-posta *</label>
                    <input type="email" name="eposta" required placeholder="ornek@email.com">
                </div>
                <div class="form-grup">
                    <label>Telefon</label>
                    <input type="tel" name="telefon" placeholder="05XX XXX XX XX">
                </div>
                <div class="form-grup">
                    <label>Doğum Tarihi</label>
                    <input type="date" name="dogum_tarihi">
                </div>
                <div class="form-grup">
                    <label>Cinsiyet</label>
                    <select name="cinsiyet">
                        <option value="">Seçiniz</option>
                        <option value="Erkek">Erkek</option>
                        <option value="Kadın">Kadın</option>
                        <option value="Diğer">Diğer</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- EĞİTİM BİLGİLERİ (EGITIM tablosu) -->
        <div class="kart">
            <div class="kart-baslik">🎓 Eğitim Bilgileri</div>
            <div class="form-grid">
                <div class="form-grup">
                    <label>Üniversite / Okul *</label>
                    <input type="text" name="universite" required placeholder="İstanbul Üniversitesi">
                </div>
                <div class="form-grup">
                    <label>Bölüm *</label>
                    <input type="text" name="bolum" required placeholder="Bilgisayar Mühendisliği">
                </div>
                <div class="form-grup">
                    <label>Mezuniyet Durumu *</label>
                    <select name="mezuniyet_durum" required>
                        <option value="">Seçiniz</option>
                        <option value="Lise">Lise</option>
                        <option value="Ön Lisans">Ön Lisans</option>
                        <option value="Lisans">Lisans</option>
                        <option value="Yüksek Lisans">Yüksek Lisans</option>
                        <option value="Doktora">Doktora</option>
                        <option value="Devam Ediyor">Devam Ediyor</option>
                    </select>
                </div>
                <div class="form-grup">
                    <label>Mezuniyet Yılı</label>
                    <input type="date" name="mezuniyet_yili">
                </div>
            </div>
        </div>

        <!-- BAŞVURU BİLGİLERİ (BASVURULAR tablosu) -->
        <div class="kart">
            <div class="kart-baslik">💼 Başvuru Bilgileri</div>
            <div class="form-grid">
                <div class="form-grup">
                    <label>Başvurduğunuz Pozisyon *</label>
                    <select name="pozisyon_id" required>
                        <option value="">Seçiniz</option>
                        <?php while ($poz = sqlsrv_fetch_array($pozSorgu, SQLSRV_FETCH_ASSOC)): ?>
                            <option value="<?= $poz['PozisyonId'] ?>">
                                <?= htmlspecialchars($poz['PozisyonAdi']) ?> — <?= htmlspecialchars($poz['CalismaSekli']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-grup">
                    <label>Maaş Beklentisi (₺)</label>
                    <input type="number" name="maas_beklenti" placeholder="30000" min="0">
                </div>
                <div class="form-grup">
                    <label>Başlangıç Tarihi</label>
                    <input type="date" name="baslangic_tarihi">
                </div>
                <div class="form-grup">
                    <label>Bizi Nereden Duydunuz?</label>
                    <select name="referans_kaynagi">
                        <option value="">Seçiniz</option>
                        <option value="LinkedIn">LinkedIn</option>
                        <option value="Kariyer.net">Kariyer.net</option>
                        <option value="Indeed">Indeed</option>
                        <option value="Tanıdık">Tanıdık</option>
                        <option value="Diğer">Diğer</option>
                    </select>
                </div>
            </div>
            <div class="form-grup">
                <label>Kendinizden Bahsedin</label>
                <textarea name="hakkinda" rows="4" placeholder="Deneyimleriniz, yetenekleriniz ve motivasyonunuz hakkında kısaca bilgi verin..."></textarea>
            </div>

            <div class="checkbox-grup">
                <input type="checkbox" name="kvkk" id="kvkk" required>
                <label for="kvkk">
                    Kişisel verilerimin işlenmesine ilişkin <strong>KVKK Aydınlatma Metni</strong>'ni okudum ve onaylıyorum. *
                </label>
            </div>
        </div>

        <div style="text-align:right">
            <button type="submit" class="btn btn-vurgu" style="font-size:1rem;padding:14px 40px">
                Başvuruyu Gönder →
            </button>
        </div>

    </form>
</div>

<footer style="text-align:center;padding:32px;color:var(--metin-acik);font-size:0.85rem;margin-top:40px">
    © <?= date('Y') ?> İş Başvuru Sistemi
</footer>

</body>
</html>
