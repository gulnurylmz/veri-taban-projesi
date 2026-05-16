<?php
// admin/pozisyonlar.php — Pozisyon / İlan Yönetimi
include("../db.php");

if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }

$basari = $hata = "";

// EKLE / GÜNCELLE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet'])) {
    $ad      = trim($_POST['ad']      ?? '');
    $calisma = trim($_POST['calisma'] ?? '');
    $aciklama= trim($_POST['aciklama']?? '') ?: null;
    $departman  = trim($_POST["departman"]  ?? "") ?: null;
    $maas       = trim($_POST["maas"]       ?? "") ?: null;
    $durum      = isset($_POST["aktif"]) ? "Aktif" : "Pasif";
    $pid        = (int)($_POST["poz_id"] ?? 0);

    $izinli = ['Tam Zamanlı','Yarı Zamanlı','Uzaktan','Hibrit','Staj'];
    if (empty($ad) || !in_array($calisma, $izinli)) {
        $hata = "Pozisyon adı ve çalışma şeklini doldurun.";
    } elseif ($pid > 0) {
        $r = sqlsrv_query($baglanti,
            "UPDATE POZISYONLAR SET PozisyonAdi=?,CalismaSekli=?,Aciklama=?,Departman=?,Maas=?,Durum=? WHERE PozisyonId=?",
            [$ad,$calisma,$aciklama,$departman,$maas,$durum,$pid]);
        $basari = $r ? "Pozisyon güncellendi." : "Güncelleme hatası.";
    } else {
        $r = sqlsrv_query($baglanti,
            "INSERT INTO POZISYONLAR (PozisyonAdi,CalismaSekli,Aciklama,Departman,Maas,Durum) VALUES (?,?,?,?,?,?)",
            [$ad,$calisma,$aciklama,$departman,$maas,$durum]);
        $basari = $r ? "Yeni pozisyon eklendi." : "Ekleme hatası.";
    }
}

// PASİFE AL
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    sqlsrv_query($baglanti, "UPDATE POZISYONLAR SET Durum='Pasif' WHERE PozisyonId=?", [(int)$_GET['sil']]);
    $basari = "Pozisyon pasife alındı.";
}
// AKTİF ET
if (isset($_GET['aktif']) && is_numeric($_GET['aktif'])) {
    sqlsrv_query($baglanti, "UPDATE POZISYONLAR SET Durum='Aktif' WHERE PozisyonId=?", [(int)$_GET['aktif']]);
    $basari = "Pozisyon aktif edildi.";
}

// DÜZENLE
$duzenle = null;
if (isset($_GET['duzenle']) && is_numeric($_GET['duzenle'])) {
    $ds = sqlsrv_query($baglanti, "SELECT * FROM POZISYONLAR WHERE PozisyonId=?", [(int)$_GET['duzenle']]);
    $duzenle = $ds ? sqlsrv_fetch_array($ds, SQLSRV_FETCH_ASSOC) : null;
}

$listeSql = "SELECT p.PozisyonId, p.PozisyonAdi, p.CalismaSekli, p.Departman, p.Maas, p.Durum, p.OlusturmaTarihi,
            COUNT(b.BasvuruId) AS BasvuruSayisi
     FROM POZISYONLAR p
     LEFT JOIN BASVURULAR b ON p.PozisyonId = b.PozisyonId
     GROUP BY p.PozisyonId, p.PozisyonAdi, p.CalismaSekli, p.Departman, p.Maas, p.Durum, p.OlusturmaTarihi
     ORDER BY p.OlusturmaTarihi DESC";
$liste = sqlsrv_query($baglanti, $listeSql);
if ($liste === false) {
    $hata = "Liste sorgu hatası: " . print_r(sqlsrv_errors(), true);
}

function calismaRenk($s) {
    return match($s) {
        'Uzaktan'=>'badge-uzaktan','Hibrit'=>'badge-hibrit',
        'Tam Zamanlı'=>'badge-tam','Yarı Zamanlı'=>'badge-yari','Staj'=>'badge-staj',
        default=>'badge-inceleniyor'
    };
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pozisyonlar — Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<nav class="navbar">
    <a href="panel.php" class="navbar-logo">İş<span>Başvuru</span> <small style="font-size:.65rem;opacity:.6">Admin</small></a>
    <div class="navbar-menu">
        <a href="panel.php">Panel</a>
        <a href="basvurular.php">Başvurular</a>
        <a href="adaylar.php">Adaylar</a>
        <a href="pozisyonlar.php" class="aktif">Pozisyonlar</a>
        <a href="kullanicilar.php" class="gizle-mobil">Kullanıcılar</a>
        <a href="../cikis.php" class="cikis">Çıkış</a>
    </div>
</nav>

<div class="kapsayici">
    <?php if ($basari): ?><div class="mesaj mesaj-basari">✅ <?= htmlspecialchars($basari) ?></div><?php endif; ?>
    <?php if ($hata):   ?><div class="mesaj mesaj-hata">⚠️ <?= htmlspecialchars($hata) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

        <!-- LİSTE -->
        <div class="kart">
            <div class="kart-baslik">💼 Tüm Pozisyonlar</div>
            <div class="tablo-kapsayici">
                <table>
                    <tr><th>Pozisyon</th><th>Tür</th><th>Departman</th><th>Maaş</th><th>Başvuru</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr>
                    <?php $var=false; while ($liste && $r = sqlsrv_fetch_array($liste, SQLSRV_FETCH_ASSOC)): $var=true; ?>
                    <tr>
                        <td style="font-weight:500;color:var(--beyaz)"><?= htmlspecialchars($r['PozisyonAdi']) ?></td>
                        <td><span class="badge <?= calismaRenk($r['CalismaSekli']) ?>"><?= htmlspecialchars($r['CalismaSekli']) ?></span></td>
                        <td style="color:var(--gri-3);font-size:.82rem"><?= htmlspecialchars($r['Departman'] ?? '-') ?></td>
                        <td style="color:var(--gri-3);font-size:.82rem"><?= htmlspecialchars($r['Maas'] ?? '-') ?></td>
                        <td style="text-align:center;font-weight:700;color:var(--vurgu)"><?= $r['BasvuruSayisi'] ?></td>
                        <td>
                            <?php if ($r['Durum'] === 'Aktif'): ?>
                                <span class="badge badge-kabul">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-red">Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.8rem;color:var(--gri-4)"><?= tarihFormatla($r['OlusturmaTarihi']) ?></td>
                        <td>
                            <div style="display:flex;gap:5px;flex-wrap:wrap">
                                <a href="?duzenle=<?= $r['PozisyonId'] ?>" class="btn btn-ikincil btn-kucuk">Düzenle</a>
                                <?php if ($r['Durum'] === 'Aktif'): ?>
                                    <a href="?sil=<?= $r['PozisyonId'] ?>"
                                       onclick="return confirm('Pasife almak istediğinize emin misiniz?')"
                                       class="btn btn-tehlike btn-kucuk">Pasif</a>
                                <?php else: ?>
                                    <a href="?aktif=<?= $r['PozisyonId'] ?>" class="btn btn-basari btn-kucuk">Aktif Et</a>
                                <?php endif; ?>
                                <a href="basvurular.php?pozisyon=<?= $r['PozisyonId'] ?>" class="btn btn-ana btn-kucuk">Başvurular</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; if (!$var): ?>
                        <tr><td colspan="6" style="text-align:center;color:var(--gri-4);padding:40px">Henüz pozisyon yok.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- FORM -->
        <div class="kart" style="position:sticky;top:80px">
            <div class="kart-baslik">
                <?= $duzenle ? '✏️ Pozisyon Düzenle' : '➕ Yeni Pozisyon Ekle' ?>
            </div>
            <form method="POST" action="pozisyonlar.php">
                <?php if ($duzenle): ?>
                    <input type="hidden" name="poz_id" value="<?= $duzenle['PozisyonId'] ?>">
                <?php endif; ?>
                <div class="form-grup">
                    <label>Pozisyon Adı *</label>
                    <input type="text" name="ad" required placeholder="Örn: PHP Backend Geliştirici"
                           value="<?= htmlspecialchars($duzenle['PozisyonAdi'] ?? '') ?>">
                </div>
                <div class="form-grup">
                    <label>Çalışma Şekli *</label>
                    <select name="calisma" required>
                        <option value="">— Seçin —</option>
                        <?php foreach (['Tam Zamanlı','Yarı Zamanlı','Uzaktan','Hibrit','Staj'] as $c): ?>
                        <option value="<?= $c ?>" <?= ($duzenle['CalismaSekli'] ?? '') === $c ? 'selected':'' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grup">
                    <label>Açıklama</label>
                    <textarea name="aciklama" rows="4" placeholder="Pozisyon gereksinimleri, görev tanımı…"><?= htmlspecialchars($duzenle['Aciklama'] ?? '') ?></textarea>
                </div>
                <div class="form-grup">
                    <label>Departman</label>
                    <input type="text" name="departman" placeholder="Örn: Yazılım, İnsan Kaynakları…"
                           value="<?= htmlspecialchars($duzenle['Departman'] ?? '') ?>">
                </div>
                <div class="form-grup">
                    <label>Maaş</label>
                    <input type="text" name="maas" placeholder="Örn: 50.000 TL"
                           value="<?= htmlspecialchars($duzenle['Maas'] ?? '') ?>">
                </div>
                <label class="checkbox-grup">
                    <input type="checkbox" name="aktif" <?= (!$duzenle || $duzenle['Durum'] === 'Aktif') ? 'checked':'' ?>>
                    <span>Aktif (anasayfada görünsün)</span>
                </label>
                <div style="display:flex;gap:10px;margin-top:8px">
                    <button type="submit" name="kaydet" class="btn btn-ana" style="flex:1;justify-content:center">
                        <?= $duzenle ? 'Güncelle' : 'Pozisyon Ekle' ?>
                    </button>
                    <?php if ($duzenle): ?>
                    <a href="pozisyonlar.php" class="btn btn-ikincil">İptal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="footer"><p>© <?= date('Y') ?> İş Başvuru Sistemi — Admin</p></footer>
</body>
</html>
