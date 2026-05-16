<?php
// admin/login.php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: panel.php");
    exit;
}

include("../db.php");

$hata = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $sifre         = $_POST['sifre'] ?? '';

    if ($kullanici_adi === '' || $sifre === '') {
        $hata = "Kullanıcı adı ve şifre boş bırakılamaz.";
    } else {
        // ADMIN tablosundan giriş sorgusu
        $sql  = "SELECT AdminId, KullaniciAdi, Sifre, Ad, Soyad FROM [ADMIN] WHERE KullaniciAdi = ?";
        $stmt = sqlsrv_query($baglanti, $sql, [[$kullanici_adi, SQLSRV_PARAM_IN]]);

        if ($stmt === false) {
            $hata = "Veritabanı hatası: " . print_r(sqlsrv_errors(), true);
        } else {
            $admin = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if ($admin) {
                $sifreDogruMu = false;

                // 1) PHP hash'i mi? (ileride şifreleri hashlerseniz)
                if (password_verify($sifre, $admin['Sifre'])) {
                    $sifreDogruMu = true;
                }
                // 2) Düz metin karşılaştırma (şu anki verileriniz için)
                elseif ($admin['Sifre'] === $sifre) {
                    $sifreDogruMu = true;
                }

                if ($sifreDogruMu) {
                    $_SESSION['admin_id']        = $admin['AdminId'];
                    $_SESSION['admin_adi']       = $admin['Ad'] . ' ' . $admin['Soyad'];
                    $_SESSION['admin_kullanici']  = $admin['KullaniciAdi'];
                    header("Location: panel.php");
                    exit;
                } else {
                    $hata = "Kullanıcı adı veya şifre hatalı.";
                }
            } else {
                $hata = "Kullanıcı adı veya şifre hatalı.";
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
    <title>Admin Girişi — İş Başvuru Sistemi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --lacivert:    #0a0f1e;
            --lacivert-2:  #111827;
            --mavi:        #3b82f6;
            --mavi-parlak: #60a5fa;
            --gri-3:       #94a3b8;
            --gri-4:       #64748b;
            --beyaz:       #ffffff;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--lacivert);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; position: relative;
        }
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }
        .blob {
            position: fixed; border-radius: 50%;
            filter: blur(80px); pointer-events: none; z-index: 0;
        }
        .blob-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(59,130,246,0.12) 0%, transparent 70%);
            top: -100px; right: -100px;
        }
        .blob-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(245,158,11,0.07) 0%, transparent 70%);
            bottom: -80px; left: -80px;
        }
        .sarici {
            position: relative; z-index: 1;
            width: 100%; max-width: 440px; padding: 16px;
        }
        .logo-alan {
            text-align: center; margin-bottom: 28px;
            animation: fadeDown 0.5s ease both;
        }
        .logo-alan a {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem; font-weight: 800;
            color: var(--beyaz); text-decoration: none;
        }
        .logo-alan a span { color: var(--mavi-parlak); }
        .rozet {
            display: inline-block;
            background: rgba(59,130,246,0.15);
            border: 1px solid rgba(59,130,246,0.3);
            color: var(--mavi-parlak);
            font-size: 0.7rem; font-weight: 500; letter-spacing: 0.12em;
            padding: 4px 12px; border-radius: 100px; margin-top: 8px;
        }
        .kart {
            background: rgba(17,24,39,0.9);
            border: 1px solid rgba(59,130,246,0.15);
            border-radius: 20px; padding: 40px 36px;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            animation: fadeUp 0.5s ease 0.1s both;
        }
        .kart-baslik {
            font-family: 'Syne', sans-serif;
            font-size: 1.6rem; font-weight: 800;
            color: var(--beyaz); margin-bottom: 4px;
        }
        .kart-alt { font-size: 0.875rem; color: var(--gri-3); margin-bottom: 24px; }

        .hata-kutu {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.35);
            border-radius: 10px; padding: 12px 16px;
            color: #fca5a5; font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
            animation: shake 0.4s ease;
        }
        .form-grup { margin-bottom: 18px; }
        label {
            display: block; font-size: 0.78rem; font-weight: 500;
            color: var(--gri-3); margin-bottom: 8px;
            letter-spacing: 0.05em; text-transform: uppercase;
        }
        .input-sarici { position: relative; }
        .input-ikon {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            font-size: 1rem; pointer-events: none; opacity: 0.45;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 12px 14px 12px 42px;
            color: var(--beyaz);
            font-family: 'DM Sans', sans-serif; font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }
        input:focus {
            border-color: rgba(59,130,246,0.5);
            background: rgba(59,130,246,0.05);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        input::placeholder { color: var(--gri-4); }
        .goster-btn {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--gri-4); font-size: 1rem; padding: 0;
            transition: color 0.2s;
        }
        .goster-btn:hover { color: var(--gri-3); }
        .btn-giris {
            width: 100%; margin-top: 8px; padding: 13px;
            background: var(--mavi); color: var(--beyaz);
            font-family: 'Syne', sans-serif;
            font-size: 0.95rem; font-weight: 700;
            border: none; border-radius: 10px; cursor: pointer;
            box-shadow: 0 4px 20px rgba(59,130,246,0.3);
            transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
        }
        .btn-giris:hover { background: #2563eb; box-shadow: 0 6px 28px rgba(59,130,246,0.45); }
        .btn-giris:active { transform: scale(0.98); }
        .alt-not {
            display: flex; align-items: center; gap: 6px;
            margin-top: 20px; font-size: 0.75rem;
            color: var(--gri-4); justify-content: center;
        }
        .geri-don {
            text-align: center; margin-top: 20px;
            font-size: 0.83rem; color: var(--gri-4);
        }
        .geri-don a { color: var(--mavi-parlak); text-decoration: none; font-weight: 500; }
        .geri-don a:hover { text-decoration: underline; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%     { transform: translateX(-6px); }
            40%     { transform: translateX(6px); }
            60%     { transform: translateX(-4px); }
            80%     { transform: translateX(4px); }
        }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="sarici">
        <div class="logo-alan">
            <a href="index.php">İş<span>Başvuru</span></a><br>
            <span class="rozet">🔐 YÖNETİM PANELİ</span>
        </div>

        <div class="kart">
            <div class="kart-baslik">Admin Girişi</div>
            <div class="kart-alt">Devam etmek için kimliğinizi doğrulayın.</div>

            <?php if ($hata): ?>
            <div class="hata-kutu">⚠️ <?= htmlspecialchars($hata) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grup">
                    <label for="kullanici_adi">Kullanıcı Adı</label>
                    <div class="input-sarici">
                        <span class="input-ikon">👤</span>
                        <input
                            type="text"
                            id="kullanici_adi"
                            name="kullanici_adi"
                            placeholder="örn: aliveli"
                            value="<?= htmlspecialchars($_POST['kullanici_adi'] ?? '') ?>"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>

                <div class="form-grup">
                    <label for="sifre">Şifre</label>
                    <div class="input-sarici">
                        <span class="input-ikon">🔑</span>
                        <input
                            type="password"
                            id="sifre"
                            name="sifre"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="goster-btn" onclick="sifreyiGoster()" id="goster-btn">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-giris">Giriş Yap →</button>
            </form>

            <div class="alt-not">🔒 Güvenli bağlantı · Yetkisiz erişim yasaktır</div>
        </div>

        <div class="geri-don"><a href="index.php">← Ana sayfaya dön</a></div>
    </div>

    <script>
        function sifreyiGoster() {
            const inp = document.getElementById('sifre');
            const btn = document.getElementById('goster-btn');
            inp.type = inp.type === 'password' ? 'text' : 'password';
            btn.textContent = inp.type === 'password' ? '👁' : '🙈';
        }
    </script>
</body>
</html>