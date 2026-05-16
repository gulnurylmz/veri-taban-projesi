<?php
// admin/kullanicilar.php — Kayıtlı Kullanıcı Yönetimi
include("../db.php");

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

$basari = $hata = "";

// SİL
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    $sid = (int)$_GET['sil'];
    // KULLANICILAR tablosundan sil
    $r = sqlsrv_query($baglanti, "DELETE FROM KULLANICILAR WHERE KullaniciId=?", [[$sid, SQLSRV_PARAM_IN]]);
    $basari = $r ? "Kullanıcı silindi." : "Silme hatası.";
}

// ARAMA + LİSTE
$arama = trim($_GET['ara'] ?? '');
$sql   = "SELECT k.KullaniciId, k.Ad, k.Soyad, k.Eposta, k.Telefon, k.OlusturmaTarihi,
                 COUNT(b.BasvuruId) AS BasvuruSayisi
          FROM KULLANICILAR k
          LEFT JOIN ADAYLAR a    ON a.Eposta = k.Eposta
          LEFT JOIN BASVURULAR b ON b.AdayId = a.AdayId
          WHERE 1=1";
$p = [];
if ($arama) {
    $sql .= " AND (k.Ad LIKE ? OR k.Soyad LIKE ? OR k.Eposta LIKE ?)";
    $p    = ["%$arama%", "%$arama%", "%$arama%"];
}
$sql  .= " GROUP BY k.KullaniciId,k.Ad,k.Soyad,k.Eposta,k.Telefon,k.OlusturmaTarihi ORDER BY k.OlusturmaTarihi DESC";
$liste = $p ? sqlsrv_query($baglanti, $sql, $p) : sqlsrv_query($baglanti, $sql);

$toplamKullanici = dbGetOne($baglanti, "SELECT COUNT(*) FROM KULLANICILAR");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kullanıcılar — Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<nav class="navbar">
    <a href="panel.php" class="navbar-logo">İş<span>Başvuru</span> <small style="font-size:.65rem;opacity:.6">Admin</small></a>
    <div class="navbar-menu">
        <a href="panel.php">Panel</a>
        <a href="basvurular.php">Başvurular</a>
        <a href="adaylar.php">Adaylar</a>
        <a href="pozisyonlar.php">Pozisyonlar</a>
        <a href="kullanicilar.php" class="aktif">Kullanıcılar</a>
        <a href="../cikis.php" class="cikis">Çıkış</a>
    </div>
</nav>

<div class="kapsayici">
    <?php if ($basari): ?><div class="mesaj mesaj-basari">✅ <?= htmlspecialchars($basari) ?></div><?php endif; ?>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <div>
            <h1 style="font-size:1.4rem;margin-bottom:4px">👤 Kayıtlı Kullanıcılar</h1>
            <p style="color:var(--gri-3);font-size:.85rem">Toplam <?= $toplamKullanici ?> kullanıcı kayıtlı</p>
        </div>
    </div>

    <div class="kart">
        <form method="GET" style="display:flex;gap:10px;margin-bottom:20px">
            <div class="form-grup" style="margin:0;flex:1">
                <input type="text" name="ara" placeholder="🔍 Ad, soyad veya e-posta ara…"
                       value="<?= htmlspecialchars($arama) ?>">
            </div>
            <button type="submit" class="btn btn-ana btn-kucuk" style="align-self:flex-end">Ara</button>
            <?php if ($arama): ?>
            <a href="kullanicilar.php" class="btn btn-ikincil btn-kucuk" style="align-self:flex-end">Temizle</a>
            <?php endif; ?>
        </form>

        <div class="tablo-kapsayici">
            <table>
                <tr><th>#</th><th>Ad Soyad</th><th>E-posta</th><th>Telefon</th><th>Başvuru</th><th>Kayıt Tarihi</th><th>İşlem</th></tr>
                <?php $var=false; while ($liste && $r = sqlsrv_fetch_array($liste, SQLSRV_FETCH_ASSOC)): $var=true; ?>
                <tr>
                    <td style="color:var(--gri-4)"><?= $r['KullaniciId'] ?></td>
                    <td>
                        <div style="font-weight:500;color:var(--beyaz)"><?= htmlspecialchars($r['Ad'].' '.$r['Soyad']) ?></div>
                    </td>
                    <td style="color:var(--gri-3)"><?= htmlspecialchars($r['Eposta']) ?></td>
                    <td style="color:var(--gri-3)"><?= htmlspecialchars($r['Telefon'] ?? '-') ?></td>
                    <td style="text-align:center;font-weight:700;color:var(--vurgu)"><?= $r['BasvuruSayisi'] ?></td>
                    <td style="font-size:.8rem;color:var(--gri-4)"><?= tarihFormatla($r['OlusturmaTarihi']) ?></td>
                    <td>
                        <a href="?sil=<?= $r['KullaniciId'] ?>"
                           onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')"
                           class="btn btn-tehlike btn-kucuk">Sil</a>
                    </td>
                </tr>
                <?php endwhile; if (!$var): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--gri-4);padding:40px">Kayıt bulunamadı.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<footer class="footer"><p>© <?= date('Y') ?> İş Başvuru Sistemi — Admin</p></footer>
</body>
</html>
