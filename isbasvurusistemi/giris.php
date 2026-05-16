<?php
// giris.php — Kullanıcı Giriş & Kayıt (tek sayfa, iki sekme)
session_start();
include("db.php");

// Zaten giriş yapmışsa yönlendir
if (isset($_SESSION['kullanici_id'])) {
    header("Location: hesabim.php");
    exit();
}

$hata_giris = "";
$hata_kayit = "";
$aktif_tab  = isset($_GET['tab']) && $_GET['tab'] === 'kayit' ? 'kayit' : 'giris';

// ── GİRİŞ İŞLEMİ ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['giris_yap'])) {
    $aktif_tab = 'giris';
    $eposta = trim($_POST['eposta'] ?? '');
    $sifre  = trim($_POST['sifre']  ?? '');

    if (empty($eposta) || empty($sifre)) {
        $hata_giris = "Lütfen tüm alanları doldurun.";
    } else {
        $sql  = "SELECT KullaniciId, Ad, Soyad, Eposta, Sifre FROM KULLANICILAR WHERE Eposta = ?";
        $stmt = sqlsrv_query($baglanti, $sql, [[$eposta, SQLSRV_PARAM_IN]]);

        if ($stmt === false) {
            $hata_giris = "Veritabanı hatası: " . print_r(sqlsrv_errors(), true);
        } else {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($row && $row['Sifre'] === $sifre) {
                $_SESSION['kullanici_id']     = $row['KullaniciId'];
                $_SESSION['kullanici_ad']     = $row['Ad'] . ' ' . $row['Soyad'];
                $_SESSION['kullanici_adi']    = $row['Ad'];
                $_SESSION['kullanici_soyadi'] = $row['Soyad'];
                $_SESSION['kullanici_eposta'] = $row['Eposta'];
                $hedef = $_GET['hedef'] ?? 'hesabim.php';
                header("Location: $hedef");
                exit();
            } else {
                $hata_giris = "E-posta veya şifre hatalı.";
            }
        }
    }
}

// ── KAYIT İŞLEMİ ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kayit_ol'])) {
    $aktif_tab = 'kayit';
    $ad     = trim($_POST['ad']      ?? '');
    $soyad  = trim($_POST['soyad']   ?? '');
    $eposta = trim($_POST['eposta2'] ?? '');
    $sifre  = trim($_POST['sifre2']  ?? '');
    $sifre2 = trim($_POST['sifre2k'] ?? '');
    $tel    = trim($_POST['telefon'] ?? '') ?: null;

    if (empty($ad) || empty($soyad) || empty($eposta) || empty($sifre)) {
        $hata_kayit = "Lütfen zorunlu alanları doldurun.";
    } elseif ($sifre !== $sifre2) {
        $hata_kayit = "Şifreler eşleşmiyor.";
    } elseif (strlen($sifre) < 6) {
        $hata_kayit = "Şifre en az 6 karakter olmalı.";
    } else {
        // E-posta tekrar kontrolü
        $kontrol = sqlsrv_query($baglanti,
            "SELECT COUNT(*) FROM KULLANICILAR WHERE Eposta = ?",
            [[$eposta, SQLSRV_PARAM_IN]]
        );

        if ($kontrol === false) {
            $hata_kayit = "Veritabanı hatası: " . print_r(sqlsrv_errors(), true);
        } else {
            $sayi_row = sqlsrv_fetch_array($kontrol, SQLSRV_FETCH_NUMERIC);
            $sayi     = $sayi_row ? (int)$sayi_row[0] : 0;

            if ($sayi > 0) {
                $hata_kayit = "Bu e-posta adresiyle zaten kayıt var.";
            } else {
                $r = sqlsrv_query($baglanti,
                    "INSERT INTO KULLANICILAR (Ad, Soyad, Eposta, Sifre, Telefon)
                     VALUES (?, ?, ?, ?, ?)",
                    [
                        [$ad,     SQLSRV_PARAM_IN],
                        [$soyad,  SQLSRV_PARAM_IN],
                        [$eposta, SQLSRV_PARAM_IN],
                        [$sifre,  SQLSRV_PARAM_IN],
                        [$tel,    SQLSRV_PARAM_IN],
                    ]
                );

                if ($r) {
                    // Yeni kullanıcı ID'sini al
                    $yeni_stmt = sqlsrv_query($baglanti,
                        "SELECT KullaniciId FROM KULLANICILAR WHERE Eposta = ?",
                        [[$eposta, SQLSRV_PARAM_IN]]
                    );
                    $yeni = $yeni_stmt ? sqlsrv_fetch_array($yeni_stmt, SQLSRV_FETCH_ASSOC) : null;

                    $_SESSION['kullanici_id']     = $yeni['KullaniciId'] ?? null;
                    $_SESSION['kullanici_ad']     = "$ad $soyad";
                    $_SESSION['kullanici_adi']    = $ad;
                    $_SESSION['kullanici_soyadi'] = $soyad;
                    $_SESSION['kullanici_eposta'] = $eposta;
                    header("Location: hesabim.php?yeni=1");
                    exit();
                } else {
                    $hata_kayit = "Kayıt hatası: " . print_r(sqlsrv_errors(), true);
                }
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
    <title>Giriş / Kayıt — İş Başvuru Sistemi</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="giris-kapsayici">
    <div class="giris-kart" style="max-width:440px">
        <div class="giris-logo">İş<span>Başvuru</span></div>
        <div class="giris-alt">Hesabınıza giriş yapın veya kayıt olun</div>

        <!-- Sekmeler -->
        <div class="tab-grup">
            <button class="tab-btn <?= $aktif_tab === 'giris' ? 'aktif' : '' ?>" onclick="sekmeAc('giris', event)">
                Giriş Yap
            </button>
            <button class="tab-btn <?= $aktif_tab === 'kayit' ? 'aktif' : '' ?>" onclick="sekmeAc('kayit', event)">
                Kayıt Ol
            </button>
        </div>

        <!-- GİRİŞ FORMU -->
        <div class="tab-panel <?= $aktif_tab === 'giris' ? 'aktif' : '' ?>" id="panel-giris">
            <?php if ($hata_giris): ?>
                <div class="mesaj mesaj-hata">⚠️ <?= htmlspecialchars($hata_giris) ?></div>
            <?php endif; ?>
            <form method="POST" action="giris.php<?= isset($_GET['hedef']) ? '?hedef=' . urlencode($_GET['hedef']) : '' ?>">
                <div class="form-grup">
                    <label>E-posta Adresi</label>
                    <input type="email" name="eposta" placeholder="ornek@eposta.com" required autofocus>
                </div>
                <div class="form-grup">
                    <label>Şifre</label>
                    <input type="password" name="sifre" placeholder="••••••••" required>
                </div>
                <button type="submit" name="giris_yap" class="btn btn-ana"
                    style="width:100%;justify-content:center;padding:12px">
                    Giriş Yap
                </button>
            </form>
        </div>

        <!-- KAYIT FORMU -->
        <div class="tab-panel <?= $aktif_tab === 'kayit' ? 'aktif' : '' ?>" id="panel-kayit">
            <?php if ($hata_kayit): ?>
                <div class="mesaj mesaj-hata">⚠️ <?= htmlspecialchars($hata_kayit) ?></div>
            <?php endif; ?>
            <form method="POST" action="giris.php?tab=kayit">
                <div class="form-grid">
                    <div class="form-grup">
                        <label>Ad *</label>
                        <input type="text" name="ad" placeholder="Adınız" required>
                    </div>
                    <div class="form-grup">
                        <label>Soyad *</label>
                        <input type="text" name="soyad" placeholder="Soyadınız" required>
                    </div>
                </div>
                <div class="form-grup">
                    <label>E-posta *</label>
                    <input type="email" name="eposta2" placeholder="ornek@eposta.com" required>
                </div>
                <div class="form-grup">
                    <label>Telefon</label>
                    <input type="text" name="telefon" placeholder="05xx xxx xx xx">
                </div>
                <div class="form-grid">
                    <div class="form-grup">
                        <label>Şifre *</label>
                        <input type="password" name="sifre2" placeholder="Min. 6 karakter" required>
                    </div>
                    <div class="form-grup">
                        <label>Şifre Tekrar *</label>
                        <input type="password" name="sifre2k" placeholder="••••••••" required>
                    </div>
                </div>
                <button type="submit" name="kayit_ol" class="btn btn-ana"
                    style="width:100%;justify-content:center;padding:12px">
                    Kayıt Ol & Giriş Yap
                </button>
            </form>
        </div>

        <hr class="ayirici">
        <div style="text-align:center;font-size:0.82rem;color:var(--gri-4)">
            <a href="index.php">← Ana sayfaya dön</a> &nbsp;·&nbsp;
            <a href="basvuru.php">Giriş yapmadan başvur</a>
        </div>
    </div>
</div>

<script>
function sekmeAc(tab, event) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('aktif'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('aktif'));
    document.querySelector('#panel-' + tab).classList.add('aktif');
    event.target.classList.add('aktif');
}
</script>
</body>
</html>