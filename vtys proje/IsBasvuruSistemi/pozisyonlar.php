<?php
// ============================================================
// DOSYA YOLU: IsBasvuruSistemi/admin/pozisyonlar.php
// AÇIKLAMA  : POZISYONLAR tablosuna ekle, listele, sil.
// ============================================================
include("../db.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit();
}

$basari = $hata = "";

// POZİSYON EKLE
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["pozisyon_ekle"])) {
    $pozisyon_adi  = trim($_POST["pozisyon_adi"]);
    $calisma_sekli = trim($_POST["calisma_sekli"]);
    $aciklama      = trim($_POST["aciklama"]) ?: null;

    $izinli = ["Tam Zamanlı", "Yarı Zamanlı", "Uzaktan", "Hibrit", "Staj"];
    if (!in_array($calisma_sekli, $izinli)) {
        $hata = "Geçersiz çalışma şekli.";
    } elseif (empty($pozisyon_adi)) {
        $hata = "Pozisyon adı boş bırakılamaz.";
    } else {
        $sql    = "INSERT INTO POZISYONLAR (PozisyonAdi, CalismaSekli, Aciklama) VALUES (?, ?, ?)";
        $result = sqlsrv_query($baglanti, $sql, array($pozisyon_adi, $calisma_sekli, $aciklama));
        $basari = $result ? "Pozisyon başarıyla eklendi." : "Ekleme hatası: " . print_r(sqlsrv_errors(), true);
    }
}

// POZİSYON SİL
if (isset($_GET["sil"]) && is_numeric($_GET["sil"])) {
    $silId  = (int)$_GET["sil"];
    // Bağlı başvuru var mı kontrol et
    $kontrol = sqlsrv_query($baglanti, "SELECT COUNT(*) AS Sayi FROM BASVURULAR WHERE PozisyonId = ?", array($silId));
    $kontrolRow = sqlsrv_fetch_array($kontrol, SQLSRV_FETCH_ASSOC);
    if ($kontrolRow['Sayi'] > 0) {
        $hata = "Bu pozisyona ait başvurular var. Önce başvuruları silin.";
    } else {
        $result = sqlsrv_query($baglanti, "DELETE FROM POZISYONLAR WHERE PozisyonId = ?", array($silId));
        $basari = $result ? "Pozisyon silindi." : "Silme hatası.";
    }
}

// Tüm pozisyonları listele
$sorgu = sqlsrv_query($baglanti,
    "SELECT p.PozisyonId, p.PozisyonAdi, p.CalismaSekli, p.Aciklama, p.OlusturmaTarihi,
            COUNT(b.BasvuruId) AS BasvuruSayisi
     FROM POZISYONLAR p
     LEFT JOIN BASVURULAR b ON p.PozisyonId = b.PozisyonId
     GROUP BY p.PozisyonId, p.PozisyonAdi, p.CalismaSekli, p.Aciklama, p.OlusturmaTarihi
     ORDER BY p.OlusturmaTarihi DESC"
);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pozisyonlar — Admin Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<nav class="navbar">
    <a href="panel.php" class="navbar-logo">İş<span>Başvuru</span> <small style="font-size:0.7rem;opacity:0.7;font-family:Outfit">Admin</small></a>
    <div class="navbar-menu">
        <a href="panel.php">Panel</a>
        <a href="basvurular.php">Başvurular</a>
        <a href="adaylar.php">Adaylar</a>
        <a href="pozisyonlar.php" class="aktif">Pozisyonlar</a>
        <a href="../cikis.php" class="cikis">Çıkış</a>
    </div>
</nav>

<div class="kapsayici">
    <?php if ($basari): ?>
        <div class="mesaj mesaj-basari"><?= htmlspecialchars($basari) ?></div>
    <?php endif; ?>
    <?php if ($hata): ?>
        <div class="mesaj mesaj-hata"><?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>

    <!-- POZİSYON EKLE FORMU -->
    <div class="kart">
        <div class="kart-baslik">➕ Yeni Pozisyon Ekle</div>
        <form method="POST" action="pozisyonlar.php">
            <div class="form-grid-3">
                <div class="form-grup">
                    <label>Pozisyon Adı *</label>
                    <input type="text" name="pozisyon_adi" required placeholder="PHP Geliştirici">
                </div>
                <div class="form-grup">
                    <label>Çalışma Şekli *</label>
                    <select name="calisma_sekli" required>
                        <option value="">Seçiniz</option>
                        <option value="Tam Zamanlı">Tam Zamanlı</option>
                        <option value="Yarı Zamanlı">Yarı Zamanlı</option>
                        <option value="Uzaktan">Uzaktan</option>
                        <option value="Hibrit">Hibrit</option>
                        <option value="Staj">Staj</option>
                    </select>
                </div>
                <div class="form-grup">
                    <label>Açıklama</label>
                    <input type="text" name="aciklama" placeholder="Kısa açıklama (isteğe bağlı)">
                </div>
            </div>
            <button type="submit" name="pozisyon_ekle" class="btn btn-vurgu">Pozisyon Ekle</button>
        </form>
    </div>

    <!-- POZİSYON LİSTESİ -->
    <div class="kart">
        <div class="kart-baslik">💼 Mevcut Pozisyonlar</div>
        <div class="tablo-kapsayici">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Pozisyon Adı</th>
                    <th>Çalışma Şekli</th>
                    <th>Açıklama</th>
                    <th>Başvuru Sayısı</th>
                    <th>Eklenme Tarihi</th>
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
                    <td>#<?= $row['PozisyonId'] ?></td>
                    <td><?= htmlspecialchars($row['PozisyonAdi']) ?></td>
                    <td><span class="badge badge-inceleniyor"><?= htmlspecialchars($row['CalismaSekli']) ?></span></td>
                    <td><?= htmlspecialchars($row['Aciklama'] ?? '-') ?></td>
                    <td style="text-align:center"><?= $row['BasvuruSayisi'] ?></td>
                    <td><?= $tarih ?></td>
                    <td>
                        <a href="?sil=<?= $row['PozisyonId'] ?>" class="btn btn-tehlike btn-kucuk"
                           onclick="return confirm('Bu pozisyonu silmek istediğinize emin misiniz?')">Sil</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$satirVar): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--metin-acik);padding:32px">Henüz pozisyon eklenmemiş.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
