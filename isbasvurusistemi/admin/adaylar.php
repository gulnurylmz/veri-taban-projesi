<?php
// admin/adaylar.php — Aday Yönetimi
include("../db.php");

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

$basari = $hata = "";

// SİL
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    $sid = (int)$_GET['sil'];
    // Önce bağlı verileri sil
    sqlsrv_query($baglanti, "DELETE FROM BASVURULAR WHERE AdayId=?",  [$sid]);
    sqlsrv_query($baglanti, "DELETE FROM EGITIM     WHERE AdayId=?",  [$sid]);
    sqlsrv_query($baglanti, "DELETE FROM DOSYALAR   WHERE AdayId=?",  [$sid]);
    sqlsrv_query($baglanti, "DELETE FROM ADAY_YETENEK WHERE AdayId=?",[$sid]);
    $r = sqlsrv_query($baglanti, "DELETE FROM ADAYLAR WHERE AdayId=?",[$sid]);
    $basari = $r ? "Aday silindi." : "Silme hatası.";
}

// DETAY
$detay = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $did = (int)$_GET['id'];
    $ds  = sqlsrv_query($baglanti,
        "SELECT a.*,
                e.Universite, e.Bolum, e.MezuniyetDurum, e.MezuniyetYili,
                d.DosyaAdi
         FROM ADAYLAR a
         LEFT JOIN EGITIM   e ON e.AdayId=a.AdayId
         LEFT JOIN DOSYALAR d ON d.AdayId=a.AdayId
         WHERE a.AdayId=?", [$did]);
    $detay = $ds ? sqlsrv_fetch_array($ds, SQLSRV_FETCH_ASSOC) : null;

    if ($detay) {
        $bas_sorgu = sqlsrv_query($baglanti,
            "SELECT b.BasvuruId, b.BasvuruTarihi, b.Durum, b.MaasBeklenti,
                    p.PozisyonAdi, p.CalismaSekli
             FROM BASVURULAR b
             INNER JOIN POZISYONLAR p ON b.PozisyonId=p.PozisyonId
             WHERE b.AdayId=?
             ORDER BY b.BasvuruTarihi DESC", [$did]);
        $detay_basvurular = [];
        while ($r = sqlsrv_fetch_array($bas_sorgu, SQLSRV_FETCH_ASSOC)) {
            $detay_basvurular[] = $r;
        }
    }
}

// ARAMA + LİSTE
$arama = trim($_GET['ara'] ?? '');
$sql   = "SELECT a.AdayId, a.Ad, a.Soyad, a.Eposta, a.Telefon, a.OlusturmaTarihi,
                 COUNT(b.BasvuruId) AS BasvuruSayisi
          FROM ADAYLAR a
          LEFT JOIN BASVURULAR b ON a.AdayId=b.AdayId
          WHERE 1=1";
$p = [];
if ($arama) {
    $sql .= " AND (a.Ad LIKE ? OR a.Soyad LIKE ? OR a.Eposta LIKE ?)";
    $p    = ["%$arama%", "%$arama%", "%$arama%"];
}
$sql  .= " GROUP BY a.AdayId,a.Ad,a.Soyad,a.Eposta,a.Telefon,a.OlusturmaTarihi ORDER BY a.OlusturmaTarihi DESC";
$liste = $p ? sqlsrv_query($baglanti, $sql, $p) : sqlsrv_query($baglanti, $sql);

function calismaRenk($s){return match($s){'Uzaktan'=>'badge-uzaktan','Hibrit'=>'badge-hibrit','Tam Zamanlı'=>'badge-tam','Yarı Zamanlı'=>'badge-yari','Staj'=>'badge-staj',default=>'badge-inceleniyor'};}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Adaylar — Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<nav class="navbar">
    <a href="panel.php" class="navbar-logo">İş<span>Başvuru</span> <small style="font-size:.65rem;opacity:.6">Admin</small></a>
    <div class="navbar-menu">
        <a href="panel.php">Panel</a>
        <a href="basvurular.php">Başvurular</a>
        <a href="adaylar.php" class="aktif">Adaylar</a>
        <a href="pozisyonlar.php">Pozisyonlar</a>
        <a href="kullanicilar.php" class="gizle-mobil">Kullanıcılar</a>
        <a href="../cikis.php" class="cikis">Çıkış</a>
    </div>
</nav>

<div class="kapsayici">
    <?php if ($basari): ?><div class="mesaj mesaj-basari">✅ <?= htmlspecialchars($basari) ?></div><?php endif; ?>

    <!-- DETAY -->
    <?php if ($detay): ?>
    <div class="kart" style="border-color:rgba(79,142,247,.3)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h2 style="font-size:1.1rem">👤 Aday Detayı — <?= htmlspecialchars($detay['Ad'].' '.$detay['Soyad']) ?></h2>
            <a href="adaylar.php" class="btn btn-ikincil btn-kucuk">← Listeye Dön</a>
        </div>
        <div class="detay-grid">
            <div class="detay-satir"><div class="etiket">Ad Soyad</div><div class="deger"><?= htmlspecialchars($detay['Ad'].' '.$detay['Soyad']) ?></div></div>
            <div class="detay-satir"><div class="etiket">E-posta</div><div class="deger"><?= htmlspecialchars($detay['Eposta']) ?></div></div>
            <div class="detay-satir"><div class="etiket">Telefon</div><div class="deger"><?= htmlspecialchars($detay['Telefon'] ?? '-') ?></div></div>
            <div class="detay-satir"><div class="etiket">Cinsiyet</div><div class="deger"><?= htmlspecialchars($detay['Cinsiyet'] ?? '-') ?></div></div>
            <div class="detay-satir"><div class="etiket">Doğum Tarihi</div><div class="deger"><?= tarihFormatla($detay['DogumTarihi']) ?></div></div>
            <div class="detay-satir"><div class="etiket">Kayıt Tarihi</div><div class="deger"><?= tarihFormatla($detay['OlusturmaTarihi']) ?></div></div>
            <?php if ($detay['Universite']): ?>
            <div class="detay-satir"><div class="etiket">Üniversite</div><div class="deger"><?= htmlspecialchars($detay['Universite']) ?></div></div>
            <div class="detay-satir"><div class="etiket">Bölüm / Derece</div><div class="deger"><?= htmlspecialchars($detay['Bolum'].' / '.$detay['MezuniyetDurum']) ?></div></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($detay['DosyaAdi'])): ?>
        <div style="margin:12px 0">
            <a href="../uploads/<?= htmlspecialchars($detay['DosyaAdi']) ?>" target="_blank" class="btn btn-ikincil btn-kucuk">📎 CV İndir</a>
        </div>
        <?php endif; ?>

        <?php if (!empty($detay_basvurular)): ?>
        <hr class="ayirici">
        <div class="kart-baslik" style="font-size:.85rem;margin-bottom:12px">📋 Başvuruları</div>
        <div class="tablo-kapsayici">
            <table>
                <tr><th>Pozisyon</th><th>Tür</th><th>Tarih</th><th>Maaş Beklenti</th><th>Durum</th><th></th></tr>
                <?php foreach ($detay_basvurular as $b): ?>
                <tr>
                    <td style="color:var(--beyaz);font-weight:500"><?= htmlspecialchars($b['PozisyonAdi']) ?></td>
                    <td><span class="badge <?= calismaRenk($b['CalismaSekli']) ?>"><?= $b['CalismaSekli'] ?></span></td>
                    <td style="font-size:.8rem;color:var(--gri-4)"><?= tarihFormatla($b['BasvuruTarihi']) ?></td>
                    <td><?= $b['MaasBeklenti'] ? number_format($b['MaasBeklenti'],0,',','.').' ₺' : '-' ?></td>
                    <td><span class="badge <?= durumBadge($b['Durum']) ?>"><?= $b['Durum'] ?></span></td>
                    <td><a href="basvurular.php?id=<?= $b['BasvuruId'] ?>" class="btn btn-ana btn-kucuk">Detay</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <div style="margin-top:16px">
            <a href="?sil=<?= $detay['AdayId'] ?>"
               onclick="return confirm('Adayı ve tüm başvurularını silmek istediğinize emin misiniz?')"
               class="btn btn-tehlike btn-kucuk">🗑 Adayı Sil</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ARAMA + LİSTE -->
    <div class="kart">
        <div class="kart-baslik" style="justify-content:space-between">
            <span>👥 Aday Listesi</span>
        </div>
        <form method="GET" style="display:flex;gap:10px;margin-bottom:20px">
            <div class="form-grup" style="margin:0;flex:1">
                <input type="text" name="ara" placeholder="🔍 Ad, soyad veya e-posta ara…"
                       value="<?= htmlspecialchars($arama) ?>" style="margin:0">
            </div>
            <button type="submit" class="btn btn-ana btn-kucuk" style="align-self:flex-end">Ara</button>
            <?php if ($arama): ?>
            <a href="adaylar.php" class="btn btn-ikincil btn-kucuk" style="align-self:flex-end">Temizle</a>
            <?php endif; ?>
        </form>

        <div class="tablo-kapsayici">
            <table>
                <tr><th>Ad Soyad</th><th>E-posta</th><th>Telefon</th><th>Başvuru</th><th>Kayıt Tarihi</th><th>İşlem</th></tr>
                <?php $var=false; while ($r = sqlsrv_fetch_array($liste, SQLSRV_FETCH_ASSOC)): $var=true; ?>
                <tr>
                    <td style="font-weight:500;color:var(--beyaz)"><?= htmlspecialchars($r['Ad'].' '.$r['Soyad']) ?></td>
                    <td style="color:var(--gri-3)"><?= htmlspecialchars($r['Eposta']) ?></td>
                    <td style="color:var(--gri-3)"><?= htmlspecialchars($r['Telefon'] ?? '-') ?></td>
                    <td style="text-align:center">
                        <span style="font-weight:700;color:var(--vurgu)"><?= $r['BasvuruSayisi'] ?></span>
                    </td>
                    <td style="font-size:.8rem;color:var(--gri-4)"><?= tarihFormatla($r['OlusturmaTarihi']) ?></td>
                    <td style="display:flex;gap:6px">
                        <a href="?id=<?= $r['AdayId'] ?>" class="btn btn-ana btn-kucuk">Detay</a>
                        <a href="?sil=<?= $r['AdayId'] ?>"
                           onclick="return confirm('Adayı ve tüm başvurularını silmek istediğinize emin misiniz?')"
                           class="btn btn-tehlike btn-kucuk">Sil</a>
                    </td>
                </tr>
                <?php endwhile; if (!$var): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--gri-4);padding:40px">Kayıt bulunamadı.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<footer class="footer"><p>© <?= date('Y') ?> İş Başvuru Sistemi — Admin</p></footer>
</body>
</html>
