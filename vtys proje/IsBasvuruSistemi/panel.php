<?php
// ============================================================
// DOSYA YOLU: IsBasvuruSistemi/admin/panel.php
// AÇIKLAMA  : Admin ana sayfası. Özet istatistikler gösterir.
// ============================================================
include("../db.php");

// Oturum kontrolü
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit();
}

// İstatistikler
function sayiGetir($baglanti, $sql) {
    $s = sqlsrv_query($baglanti, $sql);
    $r = sqlsrv_fetch_array($s, SQLSRV_FETCH_ASSOC);
    return $r ? array_values($r)[0] : 0;
}

$toplamBasvuru  = sayiGetir($baglanti, "SELECT COUNT(*) FROM BASVURULAR");
$toplamAday     = sayiGetir($baglanti, "SELECT COUNT(*) FROM ADAYLAR");
$toplamPozisyon = sayiGetir($baglanti, "SELECT COUNT(*) FROM POZISYONLAR");
$bekleyen       = sayiGetir($baglanti, "SELECT COUNT(*) FROM BASVURULAR WHERE Durum = N'Beklemede'");

// Son 5 başvuru
$sonBasvurular = sqlsrv_query($baglanti,
    "SELECT TOP 5
        b.BasvuruId, b.BasvuruTarihi, b.Durum,
        a.Ad, a.Soyad, a.Eposta,
        p.PozisyonAdi
     FROM BASVURULAR b
     INNER JOIN ADAYLAR a    ON b.AdayId    = a.AdayId
     INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
     ORDER BY b.BasvuruTarihi DESC"
);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — İş Başvuru Sistemi</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<nav class="navbar">
    <a href="panel.php" class="navbar-logo">İş<span>Başvuru</span> <small style="font-size:0.7rem;opacity:0.7;font-family:Outfit">Admin</small></a>
    <div class="navbar-menu">
        <a href="panel.php" class="aktif">Panel</a>
        <a href="basvurular.php">Başvurular</a>
        <a href="adaylar.php">Adaylar</a>
        <a href="pozisyonlar.php">Pozisyonlar</a>
        <a href="../cikis.php" class="cikis">Çıkış</a>
    </div>
</nav>

<div class="kapsayici">
    <div style="margin-bottom:24px">
        <h2 style="color:var(--ana-renk)">Hoş geldin, <?= htmlspecialchars($_SESSION["admin_ad"]) ?> 👋</h2>
        <p style="color:var(--metin-acik);font-size:0.9rem"><?= date('d F Y, l') ?></p>
    </div>

    <!-- İSTATİSTİKLER -->
    <div class="stat-grid">
        <div class="stat-kart">
            <div class="sayi"><?= $toplamBasvuru ?></div>
            <div class="etiket">Toplam Başvuru</div>
        </div>
        <div class="stat-kart" style="border-left-color:var(--vurgu)">
            <div class="sayi" style="color:var(--vurgu)"><?= $bekleyen ?></div>
            <div class="etiket">Bekleyen Başvuru</div>
        </div>
        <div class="stat-kart" style="border-left-color:var(--basari)">
            <div class="sayi" style="color:var(--basari)"><?= $toplamAday ?></div>
            <div class="etiket">Toplam Aday</div>
        </div>
        <div class="stat-kart" style="border-left-color:#805ad5">
            <div class="sayi" style="color:#805ad5"><?= $toplamPozisyon ?></div>
            <div class="etiket">Açık Pozisyon</div>
        </div>
    </div>

    <!-- SON BAŞVURULAR -->
    <div class="kart">
        <div class="kart-baslik">🕐 Son Başvurular</div>
        <div class="tablo-kapsayici">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Pozisyon</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
                <?php while ($row = sqlsrv_fetch_array($sonBasvurular, SQLSRV_FETCH_ASSOC)): ?>
                    <?php
                        $durum = $row['Durum'];
                        $badge = match($durum) {
                            'Beklemede'   => 'badge-beklemede',
                            'İnceleniyor' => 'badge-inceleniyor',
                            'Kabul Edildi'=> 'badge-kabul',
                            'Reddedildi'  => 'badge-red',
                            default       => 'badge-beklemede'
                        };
                        $tarih = $row['BasvuruTarihi'] instanceof DateTime
                            ? $row['BasvuruTarihi']->format('d.m.Y')
                            : $row['BasvuruTarihi'];
                    ?>
                    <tr>
                        <td>#<?= $row['BasvuruId'] ?></td>
                        <td><?= htmlspecialchars($row['Ad'] . ' ' . $row['Soyad']) ?></td>
                        <td><?= htmlspecialchars($row['Eposta']) ?></td>
                        <td><?= htmlspecialchars($row['PozisyonAdi']) ?></td>
                        <td><?= $tarih ?></td>
                        <td><span class="badge <?= $badge ?>"><?= $durum ?></span></td>
                        <td>
                            <a href="basvurular.php?id=<?= $row['BasvuruId'] ?>" class="btn btn-ana btn-kucuk">Detay</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <div style="margin-top:16px">
            <a href="basvurular.php" class="btn btn-ana btn-kucuk">Tüm Başvuruları Gör →</a>
        </div>
    </div>
</div>

</body>
</html>
