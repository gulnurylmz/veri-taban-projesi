<?php
// hesabim.php — Kullanıcı hesabı: başvuru takibi
session_start();
include("db.php");

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php?hedef=hesabim.php");
    exit();
}

$kullanici_eposta = $_SESSION['kullanici_eposta'];

// Kullanıcının adayını e-postaya göre bul (KullaniciId kolonu yok)
$aday_id = null;
$stmt = sqlsrv_query($baglanti,
    "SELECT AdayId FROM ADAYLAR WHERE Eposta = ?",
    [[$kullanici_eposta, SQLSRV_PARAM_IN]]
);
if ($stmt !== false) {
    $aday_row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $aday_id  = $aday_row ? $aday_row['AdayId'] : null;
}

// Başvurular
$basvurular = [];
if ($aday_id) {
    $q = sqlsrv_query($baglanti,
        "SELECT b.BasvuruId, b.BasvuruTarihi, b.Durum, b.MaasBeklenti,
                p.PozisyonAdi, p.CalismaSekli
         FROM BASVURULAR b
         INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
         WHERE b.AdayId = ?
         ORDER BY b.BasvuruTarihi DESC",
        [[$aday_id, SQLSRV_PARAM_IN]]
    );
    if ($q !== false) {
        while ($r = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC)) {
            $basvurular[] = $r;
        }
    }
} else {
    // Aday kaydı yoksa e-postaya göre başvuruları direkt getir (Ad/Soyad ile kaydedilenler)
    $q = sqlsrv_query($baglanti,
        "SELECT b.BasvuruId, b.BasvuruTarihi, b.Durum, b.MaasBeklenti,
                p.PozisyonAdi, p.CalismaSekli
         FROM BASVURULAR b
         INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
         INNER JOIN ADAYLAR a ON b.AdayId = a.AdayId
         WHERE a.Eposta = ?
         ORDER BY b.BasvuruTarihi DESC",
        [[$kullanici_eposta, SQLSRV_PARAM_IN]]
    );
    if ($q !== false) {
        while ($r = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC)) {
            $basvurular[] = $r;
        }
    }
}

$yeni_kayit = isset($_GET['yeni']) && $_GET['yeni'] === '1';

// Tüm yardımcı fonksiyonlar (calismaRenk, durumBadge, tarihFormatla) db.php'den geliyor
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabım — İş Başvuru Sistemi</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .durum-zincir {
            display: flex; gap: 0;
            margin: 20px 0; overflow-x: auto; padding-bottom: 4px;
        }
        .durum-adim {
            flex: 1; min-width: 100px;
            text-align: center; position: relative; padding: 10px 4px;
        }
        .durum-adim::after {
            content: '';
            position: absolute; top: 22px; left: 50%;
            width: calc(100% - 24px); height: 2px;
            background: var(--sinir-2); z-index: 0;
        }
        .durum-adim:last-child::after { display: none; }
        .durum-nokta {
            width: 28px; height: 28px; border-radius: 50%;
            border: 2px solid var(--sinir-2);
            background: var(--lacivert-3);
            margin: 0 auto 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; position: relative; z-index: 1;
            transition: all 0.3s;
        }
        .durum-adim.aktif .durum-nokta { border-color: var(--vurgu); background: var(--vurgu); }
        .durum-adim.gecti .durum-nokta { border-color: var(--basari); background: var(--basari); }
        .durum-adim.red   .durum-nokta { border-color: var(--hata); background: var(--hata); }
        .durum-adim .adim-etiket       { font-size: 0.72rem; color: var(--gri-3); }
        .durum-adim.aktif .adim-etiket { color: var(--vurgu-acik); font-weight: 600; }
        .durum-adim.gecti .adim-etiket { color: var(--basari); }
        .durum-adim.red   .adim-etiket { color: var(--hata); }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="navbar-logo">İş<span>Başvuru</span></a>
    <div class="navbar-menu">
        <a href="index.php" class="gizle-mobil">Ana Sayfa</a>
        <a href="hesabim.php" class="aktif">Hesabım</a>
        <a href="basvuru.php" class="btn-nav gizle-mobil">Yeni Başvuru</a>
        <a href="cikis.php" class="cikis">Çıkış</a>
    </div>
</nav>

<div class="kapsayici">

    <?php if ($yeni_kayit): ?>
    <div class="mesaj mesaj-basari" style="margin-bottom:24px">
        🎉 Hoş geldiniz, <?= htmlspecialchars($_SESSION['kullanici_ad']) ?>! Hesabınız oluşturuldu.
    </div>
    <?php endif; ?>

    <!-- Profil başlığı -->
    <div style="display:flex;align-items:center;gap:20px;margin-bottom:32px">
        <div style="width:60px;height:60px;border-radius:50%;
                    background:linear-gradient(135deg,var(--vurgu),var(--vurgu-koyu));
                    display:flex;align-items:center;justify-content:center;
                    font-size:1.5rem;font-family:var(--font-baslik);font-weight:800">
            <?= mb_strtoupper(mb_substr($_SESSION['kullanici_ad'], 0, 1)) ?>
        </div>
        <div>
            <h2 style="font-size:1.4rem;margin-bottom:2px"><?= htmlspecialchars($_SESSION['kullanici_ad']) ?></h2>
            <p style="color:var(--gri-3);font-size:0.85rem"><?= htmlspecialchars($_SESSION['kullanici_eposta']) ?></p>
        </div>
        <a href="basvuru.php" class="btn btn-ana" style="margin-left:auto">+ Yeni Başvuru</a>
    </div>

    <!-- İstatistik özet -->
    <?php
    $sayilar = ['Beklemede' => 0, 'İnceleniyor' => 0, 'Kabul Edildi' => 0, 'Reddedildi' => 0];
    foreach ($basvurular as $b) {
        if (isset($sayilar[$b['Durum']])) $sayilar[$b['Durum']]++;
    }
    ?>
    <div class="stat-grid" style="margin-bottom:28px">
        <div class="stat-kart">
            <div class="sayi"><?= count($basvurular) ?></div>
            <div class="etiket">Toplam Başvuru</div>
        </div>
        <div class="stat-kart" style="border-left-color:var(--uyari)">
            <div class="sayi" style="color:var(--uyari)"><?= $sayilar['Beklemede'] ?></div>
            <div class="etiket">Beklemede</div>
        </div>
        <div class="stat-kart" style="border-left-color:var(--vurgu-acik)">
            <div class="sayi" style="color:var(--vurgu-acik)"><?= $sayilar['İnceleniyor'] ?></div>
            <div class="etiket">İnceleniyor</div>
        </div>
        <div class="stat-kart" style="border-left-color:var(--basari)">
            <div class="sayi" style="color:var(--basari)"><?= $sayilar['Kabul Edildi'] ?></div>
            <div class="etiket">Kabul Edildi</div>
        </div>
    </div>

    <!-- Başvurular listesi -->
    <div class="kart">
        <div class="kart-baslik">📋 Başvurularım</div>

        <?php if (empty($basvurular)): ?>
            <div style="text-align:center;padding:60px 20px;color:var(--gri-4)">
                <div style="font-size:3rem;margin-bottom:12px">📭</div>
                <p>Henüz başvurunuz bulunmuyor.</p>
                <a href="basvuru.php" class="btn btn-ana" style="margin-top:16px;display:inline-flex">
                    İlk Başvuruyu Yap
                </a>
            </div>
        <?php else: ?>
        <?php foreach ($basvurular as $b):
            $badgeCls = durumBadge($b['Durum']);
            $tarih    = tarihFormatla($b['BasvuruTarihi']);
            $adimlar  = ['Beklemede', 'İnceleniyor', 'Kabul Edildi'];
            $mevcut   = array_search($b['Durum'], $adimlar);
            $red      = ($b['Durum'] === 'Reddedildi');
        ?>
        <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--sinir)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
                <div>
                    <div style="font-family:var(--font-baslik);font-size:1rem;font-weight:700;margin-bottom:4px">
                        <?= htmlspecialchars($b['PozisyonAdi']) ?>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <span class="badge <?= calismaRenk($b['CalismaSekli']) ?>">
                            <?= htmlspecialchars($b['CalismaSekli']) ?>
                        </span>
                        <span style="font-size:0.78rem;color:var(--gri-4)">📅 <?= $tarih ?></span>
                        <?php if ($b['MaasBeklenti']): ?>
                        <span style="font-size:0.78rem;color:var(--gri-4)">
                            💰 <?= number_format($b['MaasBeklenti'], 0, ',', '.') ?> ₺
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge <?= $badgeCls ?>" style="font-size:0.82rem;padding:5px 14px">
                    <?= htmlspecialchars($b['Durum']) ?>
                </span>
            </div>

            <!-- Durum zinciri -->
            <?php if (!$red): ?>
            <div class="durum-zincir">
                <?php foreach ($adimlar as $i => $adim):
                    $cls = '';
                    if ($mevcut !== false) {
                        if ($i < $mevcut)       $cls = 'gecti';
                        elseif ($i === $mevcut) $cls = 'aktif';
                    }
                ?>
                <div class="durum-adim <?= $cls ?>">
                    <div class="durum-nokta">
                        <?= ($mevcut !== false && $i < $mevcut) ? '✓' : ($i + 1) ?>
                    </div>
                    <div class="adim-etiket"><?= $adim ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="margin-top:10px">
                <span class="badge badge-red">✗ Başvurunuz reddedildi.</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<footer class="footer">
    <p>© <?= date('Y') ?> İş Başvuru Sistemi</p>
</footer>
</body>
</html>