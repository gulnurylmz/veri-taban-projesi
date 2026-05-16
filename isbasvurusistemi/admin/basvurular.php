<?php
// admin/basvurular.php — Başvuru yönetimi
include("../db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); exit();
}

$basari = $hata = "";

// DURUM GÜNCELLE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['durum_guncelle'])) {
    $bid  = (int)$_POST['basvuru_id'];
    $yeni = $_POST['yeni_durum'];
    $izinli = ['Beklemede','İnceleniyor','Kabul Edildi','Reddedildi'];
    if (in_array($yeni, $izinli)) {
        $r = sqlsrv_query($baglanti,
            "UPDATE BASVURULAR SET Durum = ? WHERE BasvuruId = ?", [$yeni, $bid]);
        $basari = $r ? "Durum güncellendi." : "Güncelleme hatası.";
    }
}

// SİL
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    $sid = (int)$_GET['sil'];
    $r   = sqlsrv_query($baglanti, "DELETE FROM BASVURULAR WHERE BasvuruId = ?", [$sid]);
    $basari = $r ? "Başvuru silindi." : "Silme hatası.";
}

// DETAY
$detay = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $did = (int)$_GET['id'];
    $ds  = sqlsrv_query($baglanti,
        "SELECT b.*, a.Ad, a.Soyad, a.Eposta, a.Telefon, a.DogumTarihi, a.Cinsiyet,
                p.PozisyonAdi, p.CalismaSekli,
                e.Universite, e.Bolum, e.MezuniyetDurum, e.MezuniyetYili,
                d.DosyaAdi
         FROM BASVURULAR b
         INNER JOIN ADAYLAR a     ON b.AdayId    = a.AdayId
         INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
         LEFT  JOIN EGITIM e      ON e.AdayId    = a.AdayId
         LEFT  JOIN DOSYALAR d    ON d.AdayId    = a.AdayId
         WHERE b.BasvuruId = ?",
        [$did]
    );
    $detay = $ds ? sqlsrv_fetch_array($ds, SQLSRV_FETCH_ASSOC) : null;
}

// LİSTE
$filtre = $_GET['durum'] ?? '';
$arama  = trim($_GET['ara'] ?? '');

$sql  = "SELECT b.BasvuruId, b.BasvuruTarihi, b.Durum, b.MaasBeklenti,
                a.Ad, a.Soyad, a.Eposta, p.PozisyonAdi
         FROM BASVURULAR b
         INNER JOIN ADAYLAR a     ON b.AdayId    = a.AdayId
         INNER JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
         WHERE 1=1";
$p = [];

if ($filtre) { $sql .= " AND b.Durum = ?";                   $p[] = $filtre; }
if ($arama)  { $sql .= " AND (a.Ad LIKE ? OR a.Soyad LIKE ? OR a.Eposta LIKE ? OR p.PozisyonAdi LIKE ?)";
               $p = array_merge($p, ["%$arama%","%$arama%","%$arama%","%$arama%"]); }

$sql .= " ORDER BY b.BasvuruTarihi DESC";
$liste = $p ? sqlsrv_query($baglanti, $sql, $p) : sqlsrv_query($baglanti, $sql);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Başvurular — Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .arama-bar { display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap }
        .arama-bar input { flex:1;min-width:200px }
        .filtre-bar { display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap }
        .detay-modal { background:var(--lacivert-3);border:1px solid var(--sinir-2);border-radius:var(--r-buyuk);padding:28px;margin-bottom:24px }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="panel.php" class="navbar-logo">İş<span>Başvuru</span> <small style="font-size:0.65rem;opacity:0.6">Admin</small></a>
    <div class="navbar-menu">
        <a href="panel.php">Panel</a>
        <a href="basvurular.php" class="aktif">Başvurular</a>
        <a href="adaylar.php">Adaylar</a>
        <a href="pozisyonlar.php">Pozisyonlar</a>
        <a href="kullanicilar.php" class="gizle-mobil">Kullanıcılar</a>
        <a href="../cikis.php" class="cikis">Çıkış</a>
    </div>
</nav>

<div class="kapsayici">
    <?php if ($basari): ?><div class="mesaj mesaj-basari">✅ <?= htmlspecialchars($basari) ?></div><?php endif; ?>
    <?php if ($hata):   ?><div class="mesaj mesaj-hata">⚠️ <?= htmlspecialchars($hata) ?></div><?php endif; ?>

    <!-- DETAY PANELİ -->
    <?php if ($detay): ?>
    <div class="detay-modal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h2 style="font-size:1.1rem">📋 Başvuru Detayı — #<?= $detay['BasvuruId'] ?></h2>
            <a href="basvurular.php" class="btn btn-ikincil btn-kucuk">← Listeye Dön</a>
        </div>

        <div class="detay-grid">
            <div class="detay-satir"><div class="etiket">Ad Soyad</div><div class="deger"><?= htmlspecialchars($detay['Ad'].' '.$detay['Soyad']) ?></div></div>
            <div class="detay-satir"><div class="etiket">E-posta</div><div class="deger"><?= htmlspecialchars($detay['Eposta']) ?></div></div>
            <div class="detay-satir"><div class="etiket">Telefon</div><div class="deger"><?= htmlspecialchars($detay['Telefon'] ?? '-') ?></div></div>
            <div class="detay-satir"><div class="etiket">Cinsiyet</div><div class="deger"><?= htmlspecialchars($detay['Cinsiyet'] ?? '-') ?></div></div>
            <div class="detay-satir"><div class="etiket">Pozisyon</div><div class="deger"><?= htmlspecialchars($detay['PozisyonAdi']) ?></div></div>
            <div class="detay-satir"><div class="etiket">Çalışma Şekli</div><div class="deger"><?= htmlspecialchars($detay['CalismaSekli']) ?></div></div>
            <div class="detay-satir"><div class="etiket">Maaş Beklentisi</div><div class="deger"><?= $detay['MaasBeklenti'] ? number_format($detay['MaasBeklenti'],0,',','.').' ₺' : '-' ?></div></div>
            <div class="detay-satir"><div class="etiket">Başlangıç Tarihi</div><div class="deger"><?= tarihFormatla($detay['BaslangicTarihi']) ?></div></div>
            <div class="detay-satir"><div class="etiket">Referans</div><div class="deger"><?= htmlspecialchars($detay['ReferansKaynagi'] ?? '-') ?></div></div>
            <div class="detay-satir"><div class="etiket">KVKK</div><div class="deger"><?= $detay['KvkkOnay'] ? '✅ Onaylandı' : '❌' ?></div></div>
            <?php if ($detay['Universite']): ?>
            <div class="detay-satir"><div class="etiket">Üniversite</div><div class="deger"><?= htmlspecialchars($detay['Universite']) ?></div></div>
            <div class="detay-satir"><div class="etiket">Bölüm / Derece</div><div class="deger"><?= htmlspecialchars($detay['Bolum'].' / '.$detay['MezuniyetDurum']) ?></div></div>
            <?php endif; ?>
        </div>

        <?php if ($detay['Hakkinda']): ?>
        <div class="form-grup" style="margin-top:12px">
            <label>Hakkında</label>
            <textarea rows="4" readonly style="background:var(--lacivert-2)"><?= htmlspecialchars($detay['Hakkinda']) ?></textarea>
        </div>
        <?php endif; ?>

        <?php if ($detay['DosyaAdi']): ?>
        <div style="margin-top:12px">
            <a href="../uploads/<?= htmlspecialchars($detay['DosyaAdi']) ?>" target="_blank"
               class="btn btn-ikincil btn-kucuk">📎 CV / Dosya İndir</a>
        </div>
        <?php endif; ?>

        <!-- Durum güncelle -->
        <form method="POST" action="basvurular.php" style="display:flex;gap:10px;align-items:center;margin-top:20px;padding-top:20px;border-top:1px solid var(--sinir)">
            <input type="hidden" name="basvuru_id" value="<?= $detay['BasvuruId'] ?>">
            <select name="yeni_durum" style="background:var(--lacivert-3);border:1.5px solid var(--sinir-2);border-radius:8px;padding:8px 14px;color:var(--beyaz);font-family:var(--font-govde)">
                <?php foreach (['Beklemede','İnceleniyor','Kabul Edildi','Reddedildi'] as $ds): ?>
                <option value="<?= $ds ?>" <?= $detay['Durum']===$ds ? 'selected':'' ?>><?= $ds ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="durum_guncelle" class="btn btn-ana btn-kucuk">Durumu Güncelle</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- LISTE -->
    <div class="kart">
        <div class="kart-baslik" style="justify-content:space-between">
            <span>📄 Başvurular</span>
        </div>

        <!-- Arama -->
        <form method="GET" class="arama-bar">
            <div class="form-grup" style="margin:0;flex:1">
                <input type="text" name="ara" placeholder="🔍 Ad, soyad, e-posta veya pozisyon ara…"
                       value="<?= htmlspecialchars($arama) ?>" style="margin:0">
            </div>
            <?php if ($filtre): ?><input type="hidden" name="durum" value="<?= htmlspecialchars($filtre) ?>"><?php endif; ?>
            <button type="submit" class="btn btn-ana btn-kucuk" style="align-self:flex-end">Ara</button>
            <?php if ($arama || $filtre): ?><a href="basvurular.php" class="btn btn-ikincil btn-kucuk" style="align-self:flex-end">Temizle</a><?php endif; ?>
        </form>

        <!-- Filtre butonları -->
        <div class="filtre-bar">
            <?php
            $durumlar = ['' => 'Tümü', 'Beklemede' => 'Beklemede',
                         'İnceleniyor' => 'İnceleniyor', 'Kabul Edildi' => 'Kabul Edildi', 'Reddedildi' => 'Reddedildi'];
            foreach ($durumlar as $val => $etiket):
            ?>
            <a href="?durum=<?= urlencode($val) ?><?= $arama ? '&ara='.urlencode($arama) : '' ?>"
               class="filtre-btn <?= $filtre===$val ? 'aktif':'' ?>"><?= $etiket ?></a>
            <?php endforeach; ?>
        </div>

        <div class="tablo-kapsayici">
            <table>
                <tr>
                    <th>ID</th><th>Ad Soyad</th><th>Pozisyon</th>
                    <th>Maaş Beklenti</th><th>Tarih</th><th>Durum</th><th>İşlem</th>
                </tr>
                <?php $var = false;
                while ($row = sqlsrv_fetch_array($liste, SQLSRV_FETCH_ASSOC)):
                    $var = true; $tarih = tarihFormatla($row['BasvuruTarihi']); ?>
                <tr>
                    <td style="color:var(--gri-4)">#<?= $row['BasvuruId'] ?></td>
                    <td>
                        <div style="font-weight:500;color:var(--beyaz)"><?= htmlspecialchars($row['Ad'].' '.$row['Soyad']) ?></div>
                        <div style="font-size:0.75rem;color:var(--gri-4)"><?= htmlspecialchars($row['Eposta']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($row['PozisyonAdi']) ?></td>
                    <td><?= $row['MaasBeklenti'] ? number_format($row['MaasBeklenti'],0,',','.').' ₺' : '-' ?></td>
                    <td style="color:var(--gri-4);font-size:0.82rem;white-space:nowrap"><?= $tarih ?></td>
                    <td><span class="badge <?= durumBadge($row['Durum']) ?>"><?= $row['Durum'] ?></span></td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        <a href="?id=<?= $row['BasvuruId'] ?><?= $filtre ? '&durum='.urlencode($filtre):'' ?>"
                           class="btn btn-ana btn-kucuk">Detay</a>
                        <a href="?sil=<?= $row['BasvuruId'] ?>"
                           onclick="return confirm('Bu başvuruyu silmek istediğinize emin misiniz?')"
                           class="btn btn-tehlike btn-kucuk">Sil</a>
                    </td>
                </tr>
                <?php endwhile;
                if (!$var): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--gri-4);padding:40px">Kayıt bulunamadı.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<footer class="footer"><p>© <?= date('Y') ?> İş Başvuru Sistemi — Admin</p></footer>
</body>
</html>
