<?php
// ============================================================
// DOSYA YOLU: IsBasvuruSistemi/login.php
// AÇIKLAMA  : ADMIN tablosuna göre giriş doğrulaması yapar.
// ============================================================
include("db.php");

$hata = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $kullanici = trim($_POST["kullanici_adi"]);
    $sifre     = trim($_POST["sifre"]);

    // ADMIN tablosundan kullanıcı sorgula
    $sql    = "SELECT AdminId, Ad, Soyad, KullaniciAdi, Sifre FROM ADMIN WHERE KullaniciAdi = ?";
    $params = array($kullanici);
    $sonuc  = sqlsrv_query($baglanti, $sql, $params);

    if ($sonuc && $row = sqlsrv_fetch_array($sonuc, SQLSRV_FETCH_ASSOC)) {
        // Şifreyi doğrula (düz metin karşılaştırması — isterseniz password_hash kullanabilirsiniz)
        if ($row["Sifre"] === $sifre) {
            $_SESSION["admin_id"]  = $row["AdminId"];
            $_SESSION["admin_ad"]  = $row["Ad"] . " " . $row["Soyad"];
            $_SESSION["kullanici"] = $row["KullaniciAdi"];
            header("Location: admin/panel.php");
            exit();
        } else {
            $hata = "Kullanıcı adı veya şifre hatalı.";
        }
    } else {
        $hata = "Kullanıcı bulunamadı.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi — İş Başvuru Sistemi</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="giris-kapsayici">
    <div class="giris-kart">
        <div class="giris-logo">İş<span>Başvuru</span></div>
        <div class="giris-alt">Yönetim Paneline Giriş</div>

        <?php if ($hata): ?>
            <div class="mesaj mesaj-hata"><?= htmlspecialchars($hata) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-grup">
                <label>Kullanıcı Adı</label>
                <input type="text" name="kullanici_adi" placeholder="admin" required autofocus>
            </div>
            <div class="form-grup">
                <label>Şifre</label>
                <input type="password" name="sifre" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-ana" style="width:100%;margin-top:8px">
                Giriş Yap
            </button>
        </form>

        <div style="text-align:center;margin-top:24px">
            <a href="index.php" style="color:var(--metin-acik);font-size:0.85rem;text-decoration:none">
                ← Başvuru formuna dön
            </a>
        </div>
    </div>
</div>
</body>
</html>
