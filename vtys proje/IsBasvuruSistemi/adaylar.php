<?php
// ============================================================
// DOSYA YOLU: IsBasvuruSistemi/admin/adaylar.php
// AÇIKLAMA  : ADAYLAR tablosunu listeler.
// ============================================================
include("../db.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit();
}

$basari = $hata = "";

// ADAY SİL
if (isset($_GET["sil"]) && is_numeric($_GET["sil"])) {
    $silId = (int)$_GET["sil"];
    // Önce bağlı kayıtları sil
    sqlsrv_query($baglanti, "DELETE FROM ADAY_YETENEK WHERE AdayId = ?", array($silId));
    sqlsrv_query($baglanti, "DELETE FROM EGITIM WHERE AdayId = ?", array($silId));
    sqlsrv_query($baglanti, "DELETE FROM DOSYALAR WHERE AdayId = ?", array($silId));
    sqlsrv_query($baglanti, "DELETE FROM BASVURULAR WHERE AdayId = ?", array($silId));
    $result = sqlsrv_query($baglanti, "DELETE FROM ADAYLAR WHERE AdayId = ?", array($silId));
    $basari = $result ? "Aday ve ilgili tüm kayıtlar silindi." : "Silme hatası.";
}

// Adayları listele (başvuru sayısıyla birlikte)
$sorgu = sqlsrv_query($baglanti,
    "SELECT a.AdayId, a.Ad, a.Soyad, a.Eposta, a.Telefon, a.Cinsiyet, a.OlusturmaTarihi,
            COUNT(b.BasvuruId) AS BasvuruSayisi
     FROM ADAYLAR a
     LEFT JOIN BASVURULAR b ON a.AdayId = b.AdayId
     GROUP BY a.AdayId, a.Ad, a.Soyad, a.Eposta, a.Telefon, a.Cinsiyet, a.OlusturmaTarihi
     ORDER BY a.OlusturmaTarihi DESC"
);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaylar — Admin Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<nav class="navbar">
    <a href="panel.php" class="navbar-logo">İş<span>Başvuru</span> <small style="font-size:0.7rem;opacity:0.7;font-family:Outfit">Admin</small></a>
    <div class="navbar-menu">
        <a href="panel.php">Panel</a>
        <a href="basvurular.php">Başvurular</a>
        <a href="adaylar.php" class="aktif">Adaylar</a>
        <a href="pozisyonlar.php">Pozisyonlar</a>
        <a href="../cikis.php" class="cikis">Çıkış</a>
    </div>
</nav>

<div class="kapsayici">
    <?php if ($basari): ?>
        <div class="mesaj mesaj-basari"><?= htmlspecialchars($basari) ?></div>
    <?php endif; ?>

    <div class="kart">
        <div class="kart-baslik">👥 Adaylar</div>
        <div class="tablo-kapsayici">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Telefon</th>
                    <th>Cinsiyet</th>
                    <th>Başvuru Sayısı</th>
                    <th>Kayıt Tarihi</th>
                    <th>İşlem</th>
                </tr>
                <?php
                $satirVar = false;
                while ($row = sqlsrv_fetch_array($sorgu, SQLSRV_FETCH_ASSOC)):
                    $satirVar = true;
                    $tarih = $row['OlusturmaTarihi'] instanceof DateTime
                        ? $row['OlusturmaTarihi']->format('d.m.Y')
                        : $row['OlusturmaTarihi'];
                ?>
                <tr>
                    <td>#<?= $row['AdayId'] ?></td>
                    <td><?= htmlspecialchars($row['Ad'] . ' ' . $row['Soyad']) ?></td>
                    <td><?= htmlspecialchars($row['Eposta']) ?></td>
                    <td><?= htmlspecialchars($row['Telefon'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['Cinsiyet'] ?? '-') ?></td>
                    <td style="text-align:center">
                        <span class="badge badge-inceleniyor"><?= $row['BasvuruSayisi'] ?></span>
                    </td>
                    <td><?= $tarih ?></td>
                    <td>
                        <a href="?sil=<?= $row['AdayId'] ?>" class="btn btn-tehlike btn-kucuk"
                           onclick="return confirm('Bu adayı ve tüm başvurularını silmek istediğinize emin misiniz?')">Sil</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$satirVar): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--metin-acik);padding:32px">Henüz aday bulunmuyor.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
