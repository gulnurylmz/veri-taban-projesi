<?php
// ============================================================
// DOSYA YOLU: IsBasvuruSistemi/admin/basvurular.php
// AÇIKLAMA  : BASVURULAR tablosunu listeler, durum günceller.
//             ?id=X ile detay görünümü açılır.
// ============================================================
include("../db.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit();
}

$basari = $hata = "";

// DURUM GÜNCELLE (BASVURULAR tablosu)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["durum_guncelle"])) {
    $basvuru_id = (int)$_POST["basvuru_id"];
    $yeni_durum = $_POST["yeni_durum"];
    $izinliDurumlar = ["Beklemede", "İnceleniyor", "Kabul Edildi", "Reddedildi"];

    if (in_array($yeni_durum, $izinliDurumlar)) {
        $sql    = "UPDATE BASVURULAR SET Durum = ? WHERE BasvuruId = ?";
        $result = sqlsrv_query($baglanti, $sql, array($yeni_durum, $basvuru_id));
        $basari = $result ? "Durum güncellendi." : "Güncelleme hatası.";
    }
}

// BAŞVURU SİL
if (isset($_GET["sil"]) && is_numeric($_GET["sil"])) {
    $silId  = (int)$_GET["sil"];
    $result = sqlsrv_query($baglanti, "DELETE FROM BASVURULAR WHERE BasvuruId = ?", array($silId));
    $basari = $result ? "Başvuru silindi." : "Silme hatası.";
}

// DETAY GÖRÜNÜMÜ
$detay = null;
if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $detayId = (int)$_GET["id"];
    $detaySorgu = sqlsrv_query($baglanti,
        "SELECT b.*, a.Ad, a.Soyad, a.Eposta, a.Telefon, a.DogumTarihi, a.Cinsiyet,
                p.PozisyonAdi, p.CalismaSekli,
                e.Universite, e.Bolum, e.MezuniyetDurum, e.MezuniyetYili
         FROM BASVURULAR b
         INNER JOIN ADAYLAR a      ON b.AdayId    = a.AdayId
         INNER JOIN POZISYONLAR p  ON b.PozisyonId = p.PozisyonId
         LEFT  JOIN EGITIM e       ON e.AdayId    = a.AdayId
         WHERE b.BasvuruId = ?",
        array($detayId)
    );
    $detay = sqlsrv_fetch_array($detaySorgu, SQLSRV_FETCH_ASSOC);
}

// TÜM BAŞVURULAR LİSTESİ (filtre ile)
$filtre = $_GET["durum"] ?? "";
$filtreSql = $filtre
    ? "SELECT b.BasvuruId, b.BasvuruTarihi, b.Durum, b.MaasBeklenti,
              a.Ad, a.Soyad, a.Eposta, p.PozisyonAdi
       FROM BASVURULAR b
       INNER JOIN ADAYLAR a     ON b.AdayId    = a.AdayId
       INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
       WHERE b.Durum = ?
       ORDER BY b.BasvuruTarihi DESC"
    : "SELECT b.BasvuruId, b.BasvuruTarihi, b.Durum, b.MaasBeklenti,
              a.Ad, a.Soyad, a.Eposta, p.PozisyonAdi
       FROM BASVURULAR b
       INNER JOIN ADAYLAR a     ON b.AdayId    = a.AdayId
       INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
       ORDER BY b.BasvuruTarihi DESC";

$listeSonuc = $filtre
    ? sqlsrv_query($baglanti, $filtreSql, array($filtre))
    : sqlsrv_query($baglanti, $filtreSql);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Başvurular — Admin Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<nav class="navbar">
    <a href="panel.php" class="navbar-logo">İş<span>Başvuru</span> <small style="font-size:0.7rem;opacity:0.7;font-family:Outfit">Admin</small></a>
    <div class="navbar-menu">
        <a href="panel.php">Panel</a>
        <a href="basvurular.php" class="aktif">Başvurular</a>
        <a href="adaylar.php">Adaylar</a>
        <a href="pozisyonlar.php">Pozisyonlar</a>
        <a href="../cikis.php" class="cikis">Çıkış</a>
    </div>
</nav>

<div class="kapsayici">

    <?php if ($basari): ?>
        <div class="mesaj mesaj-basari"><?= htmlspecialchars($basari) ?></div>
    <?php endif; ?>

    <!-- DETAY GÖRÜNÜMÜ -->
    <?php if ($detay): ?>
    <div class="kart">
        <div class="kart-baslik">📋 Başvuru Detayı — #<?= $detay['BasvuruId'] ?></div>

        <div class="form-grid" style="margin-bottom:16px">
            <div><b>Ad Soyad:</b> <?= htmlspecialchars($detay['Ad'] . ' ' . $detay['Soyad']) ?></div>
            <div><b>E-posta:</b> <?= htmlspecialchars($detay['Eposta']) ?></div>
            <div><b>Telefon:</b> <?= htmlspecialchars($detay['Telefon'] ?? '-') ?></div>
            <div><b>Cinsiyet:</b> <?= htmlspecialchars($detay['Cinsiyet'] ?? '-') ?></div>
            <div><b>Pozisyon:</b> <?= htmlspecialchars($detay['PozisyonAdi']) ?></div>
            <div><b>Çalışma Şekli:</b> <?= htmlspecialchars($detay['CalismaSekli']) ?></div>
            <div><b>Maaş Beklentisi:</b> <?= $detay['MaasBeklenti'] ? number_format($detay['MaasBeklenti'], 0, ',', '.') . ' ₺' : '-' ?></div>
            <div><b>Başlangıç Tarihi:</b> <?= $detay['BaslangicTarihi'] instanceof DateTime ? $detay['BaslangicTarihi']->format('d.m.Y') : ($detay['BaslangicTarihi'] ?? '-') ?></div>
            <div><b>Referans:</b> <?= htmlspecialchars($detay['ReferansKaynagi'] ?? '-') ?></div>
            <div><b>KVKK:</b> <?= $detay['KvkkOnay'] ? '✅ Onaylandı' : '❌ Onaylanmadı' ?></div>
            <div><b>Üniversite:</b> <?= htmlspecialchars($detay['Universite'] ?? '-') ?></div>
            <div><b>Bölüm:</b> <?= htmlspecialchars($detay['Bolum'] ?? '-') ?></div>
            <div><b>Mezuniyet:</b> <?= htmlspecialchars($detay['MezuniyetDurum'] ?? '-') ?></div>
        </div>

        <?php if ($detay['Hakkinda']): ?>
            <div class="form-grup">
                <label>Hakkında</label>
                <textarea rows="4" readonly style="background:var(--acik)"><?= htmlspecialchars($detay['Hakkinda']) ?></textarea>
            </div>
        <?php endif; ?>

        <!-- DURUM GÜNCELLE -->
        <form method="POST" action="basvurular.php" style="display:flex;gap:12px;align-items:center;margin-top:16px">
            <input type="hidden" name="basvuru_id" value="<?= $detay['BasvuruId'] ?>">
            <select name="yeni_durum" class="form-grup" style="margin:0;width:auto;padding:8px 14px;border:1.5px solid var(--sinir);border-radius:8px">
                <option value="Beklemede"    <?= $detay['Durum']==='Beklemede'    ? 'selected':'' ?>>Beklemede</option>
                <option value="İnceleniyor"  <?= $detay['Durum']==='İnceleniyor'  ? 'selected':'' ?>>İnceleniyor</option>
                <option value="Kabul Edildi" <?= $detay['Durum']==='Kabul Edildi' ? 'selected':'' ?>>Kabul Edildi</option>
                <option value="Reddedildi"   <?= $detay['Durum']==='Reddedildi'   ? 'selected':'' ?>>Reddedildi</option>
            </select>
            <button type="submit" name="durum_guncelle" class="btn btn-ana btn-kucuk">Durumu Güncelle</button>
            <a href="basvurular.php" class="btn btn-kucuk" style="background:var(--sinir);color:var(--metin)">← Listeye Dön</a>
        </form>
    </div>
    <?php endif; ?>

    <!-- BAŞVURU LİSTESİ -->
    <div class="kart">
        <div class="kart-baslik" style="display:flex;justify-content:space-between;align-items:center">
            <span>📄 Başvurular</span>
            <div style="display:flex;gap:8px">
                <a href="basvurular.php" class="btn btn-kucuk <?= !$filtre ? 'btn-ana' : '' ?>" style="<?= !$filtre ? '' : 'background:var(--sinir);color:var(--metin)' ?>">Tümü</a>
                <a href="?durum=Beklemede"   class="btn btn-kucuk" style="background:#fef3c7;color:#92400e">Beklemede</a>
                <a href="?durum=İnceleniyor" class="btn btn-kucuk" style="background:#dbeafe;color:#1e40af">İnceleniyor</a>
                <a href="?durum=Kabul Edildi" class="btn btn-kucuk" style="background:#d1fae5;color:#065f46">Kabul</a>
                <a href="?durum=Reddedildi"  class="btn btn-kucuk" style="background:#fee2e2;color:#991b1b">Red</a>
            </div>
        </div>
        <div class="tablo-kapsayici">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Pozisyon</th>
                    <th>Maaş Beklenti</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
                <?php
                $satirVar = false;
                while ($row = sqlsrv_fetch_array($listeSonuc, SQLSRV_FETCH_ASSOC)):
                    $satirVar = true;
                    $badge = match($row['Durum']) {
                        'Beklemede'    => 'badge-beklemede',
                        'İnceleniyor'  => 'badge-inceleniyor',
                        'Kabul Edildi' => 'badge-kabul',
                        'Reddedildi'   => 'badge-red',
                        default        => 'badge-beklemede'
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
                    <td><?= $row['MaasBeklenti'] ? number_format($row['MaasBeklenti'],0,',','.') . ' ₺' : '-' ?></td>
                    <td><?= $tarih ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $row['Durum'] ?></span></td>
                    <td style="display:flex;gap:6px">
                        <a href="?id=<?= $row['BasvuruId'] ?>" class="btn btn-ana btn-kucuk">Detay</a>
                        <a href="?sil=<?= $row['BasvuruId'] ?>" class="btn btn-tehlike btn-kucuk"
                           onclick="return confirm('Bu başvuruyu silmek istediğinize emin misiniz?')">Sil</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$satirVar): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--metin-acik);padding:32px">Kayıt bulunamadı.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
