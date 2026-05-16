<?php
// basvuru.php — Başvuru Formu (giriş şart değil)
session_start();
include("db.php");

$pozisyon_id = isset($_GET['pozisyon']) ? (int)$_GET['pozisyon'] : 0;
$mesaj = $hata = "";

// ── Pozisyonu çek (Aktif kolonu OLMADAN — güvenli) ──────────
$pRow = null;
if ($pozisyon_id > 0) {
    $stmt = sqlsrv_query($baglanti,
        "SELECT PozisyonId, PozisyonAdi, CalismaSekli, Aciklama FROM POZISYONLAR WHERE PozisyonId = ?",
        [[$pozisyon_id, SQLSRV_PARAM_IN]]
    );
    if ($stmt !== false) {
        $pRow = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
}

// ── Tüm pozisyonlar (dropdown) ───────────────────────────────
$tum_poz = [];
$stmt_poz = sqlsrv_query($baglanti,
    "SELECT PozisyonId, PozisyonAdi FROM POZISYONLAR ORDER BY PozisyonAdi"
);
if ($stmt_poz !== false) {
    while ($row = sqlsrv_fetch_array($stmt_poz, SQLSRV_FETCH_ASSOC)) {
        $tum_poz[] = $row;
    }
}

// ── Badge rengi ──────────────────────────────────────────────
function calismaRenk($sekil) {
    return match($sekil) {
        'Uzaktan'      => 'badge-uzaktan',
        'Hibrit'       => 'badge-hibrit',
        'Tam Zamanlı'  => 'badge-tam',
        'Yarı Zamanlı' => 'badge-yari',
        'Staj'         => 'badge-staj',
        default        => 'badge-inceleniyor'
    };
}

// ── FORM GÖNDERİMİ ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gonder'])) {
    $ad        = trim($_POST['ad']        ?? '');
    $soyad     = trim($_POST['soyad']     ?? '');
    $eposta    = trim($_POST['eposta']    ?? '');
    $telefon   = trim($_POST['telefon']   ?? '') ?: null;
    $dogum     = trim($_POST['dogum']     ?? '') ?: null;
    $cinsiyet  = $_POST['cinsiyet']       ?? null;
    $poz_id    = (int)($_POST['pozisyon_id'] ?? 0);
    $maas      = trim($_POST['maas']      ?? '') ?: null;
    $hakkinda  = trim($_POST['hakkinda']  ?? '') ?: null;
    $referans  = trim($_POST['referans']  ?? '') ?: null;
    $baslangic = trim($_POST['baslangic'] ?? '') ?: null;
    $kvkk      = isset($_POST['kvkk']) ? 1 : 0;

    if (!$kvkk) {
        $hata = "Devam etmek için KVKK metnini onaylamanız gerekmektedir.";
    } elseif (empty($ad) || empty($soyad) || empty($eposta) || $poz_id < 1) {
        $hata = "Lütfen zorunlu alanları doldurun.";
    } else {
        $aday_id = null;

        // Aday var mı? (e-postaya göre)
        $aday_sorgu = sqlsrv_query($baglanti,
            "SELECT AdayId FROM ADAYLAR WHERE Eposta = ?",
            [[$eposta, SQLSRV_PARAM_IN]]
        );
        if ($aday_sorgu !== false) {
            $aday_row = sqlsrv_fetch_array($aday_sorgu, SQLSRV_FETCH_ASSOC);
            if ($aday_row) {
                $aday_id = $aday_row['AdayId'];
            }
        }

        // Yeni aday oluştur
        if ($aday_id === null) {
            $kullanici_id = $_SESSION['kullanici_id'] ?? null;
            $ins = sqlsrv_query($baglanti,
                "INSERT INTO ADAYLAR (Ad, Soyad, Eposta, Telefon, DogumTarihi, Cinsiyet)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    [$ad,               SQLSRV_PARAM_IN],
                    [$soyad,            SQLSRV_PARAM_IN],
                    [$eposta,           SQLSRV_PARAM_IN],
                    [$telefon,          SQLSRV_PARAM_IN],
                    [$dogum,            SQLSRV_PARAM_IN],
                    [$cinsiyet ?: null, SQLSRV_PARAM_IN],
                ]
            );
            if (!$ins) {
                $hata = "Aday kaydı oluşturulamadı: " . print_r(sqlsrv_errors(), true);
            } else {
                $id_sorgu = sqlsrv_query($baglanti, "SELECT @@IDENTITY AS id");
                if ($id_sorgu !== false) {
                    $id_row  = sqlsrv_fetch_array($id_sorgu, SQLSRV_FETCH_ASSOC);
                    $aday_id = (int)$id_row['id'];
                } else {
                    $hata = "Aday ID alınamadı.";
                }
            }
        }

        // CV yükleme
        if (empty($hata) && !empty($_FILES['cv']['name'])) {
            $izinli = ['application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $dosya_tipi = mime_content_type($_FILES['cv']['tmp_name']);

            if (!in_array($dosya_tipi, $izinli)) {
                $hata = "Sadece PDF veya Word belgesi yükleyebilirsiniz.";
            } elseif ($_FILES['cv']['size'] > 5 * 1024 * 1024) {
                $hata = "Dosya boyutu 5 MB'yi geçemez.";
            } else {
                $uzanti  = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
                $yeni_ad = uniqid('cv_') . '.' . $uzanti;
                if (!is_dir("uploads")) mkdir("uploads", 0755, true);
                move_uploaded_file($_FILES['cv']['tmp_name'], "uploads/$yeni_ad");

                sqlsrv_query($baglanti,
                    "INSERT INTO DOSYALAR (AdayId, DosyaAdi, DosyaTipi, DosyaBoyutu)
                     VALUES (?, ?, ?, ?)",
                    [
                        [$aday_id,              SQLSRV_PARAM_IN],
                        [$yeni_ad,              SQLSRV_PARAM_IN],
                        [$uzanti,               SQLSRV_PARAM_IN],
                        [$_FILES['cv']['size'], SQLSRV_PARAM_IN],
                    ]
                );
            }
        }

        // Başvuruyu ekle
        if (empty($hata) && $aday_id !== null) {
            $ins_b = sqlsrv_query($baglanti,
                "INSERT INTO BASVURULAR
                    (AdayId, PozisyonId, Durum, MaasBeklenti, BaslangicTarihi, ReferansKaynagi, Hakkinda, KvkkOnay, Ad, Soyad)
                 VALUES (?, ?, N'Beklemede', ?, ?, ?, ?, ?, ?, ?)",
                [
                    [$aday_id,   SQLSRV_PARAM_IN],
                    [$poz_id,    SQLSRV_PARAM_IN],
                    [$maas,      SQLSRV_PARAM_IN],
                    [$baslangic, SQLSRV_PARAM_IN],
                    [$referans,  SQLSRV_PARAM_IN],
                    [$hakkinda,  SQLSRV_PARAM_IN],
                    [$kvkk,      SQLSRV_PARAM_IN],
                    [$ad,        SQLSRV_PARAM_IN],
                    [$soyad,     SQLSRV_PARAM_IN],
                ]
            );

            if ($ins_b) {
                $mesaj       = "Başvurunuz başarıyla alındı! En kısa sürede sizinle iletişime geçeceğiz.";
                $pozisyon_id = 0;
                $pRow        = null;
            } else {
                $hata = "Başvuru kaydedilemedi: " . print_r(sqlsrv_errors(), true);
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
    <title>Başvuru Yap — İş Başvuru Sistemi</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .basvuru-kapsayici {
            max-width: 720px;
            margin: 0 auto;
            padding: 88px 24px 60px;
        }
        .bolum-baslik-kucuk {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--gri-4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 24px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--sinir);
        }
        .giris-davet {
            background: rgba(79,142,247,0.08);
            border: 1px solid rgba(79,142,247,0.2);
            border-radius: var(--r);
            padding: 14px 18px;
            font-size: 0.85rem;
            color: var(--vurgu-acik);
            margin-bottom: 24px;
        }
        .pozisyon-bilgi-kutu {
            background: var(--lacivert-3);
            border: 1px solid rgba(79,142,247,0.25);
            border-radius: var(--r);
            padding: 16px 20px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="navbar-logo">İş<span>Başvuru</span></a>
    <div class="navbar-menu">
        <a href="index.php" class="gizle-mobil">Ana Sayfa</a>
        <?php if (isset($_SESSION['kullanici_id'])): ?>
            <a href="hesabim.php" class="gizle-mobil">Başvurularım</a>
            <a href="cikis.php" class="cikis">Çıkış</a>
        <?php else: ?>
            <a href="giris.php">Giriş Yap</a>
            <a href="giris.php?tab=kayit" class="btn-nav">Kayıt Ol</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['admin_id'])): ?>
            <a href="admin/panel.php" class="btn-nav-admin">⚙️ Panel</a>
        <?php else: ?>
            <a href="admin/login.php" class="btn-nav-admin">🔐 Admin</a>
        <?php endif; ?>
    </div>
</nav>

<div class="basvuru-kapsayici">
    <h1 style="font-size:1.8rem;margin-bottom:6px">Başvuru Formu</h1>
    <p style="color:var(--gri-3);margin-bottom:28px;font-size:0.9rem">
        Giriş yapmadan başvurabilirsiniz. Başvurunuzu takip etmek için hesap oluşturmanızı öneririz.
    </p>

    <?php if (!isset($_SESSION['kullanici_id'])): ?>
    <div class="giris-davet">
        💡 <a href="giris.php">Giriş yaparak</a> veya <a href="giris.php?tab=kayit">kayıt olarak</a>
        başvurularınızı takip edebilirsiniz.
    </div>
    <?php endif; ?>

    <?php if ($mesaj): ?>
        <div class="mesaj mesaj-basari" style="padding:20px 24px;font-size:1rem">
            ✅ <?= htmlspecialchars($mesaj) ?>
            <br><br>
            <a href="index.php" class="btn btn-ikincil btn-kucuk">← İlanlar sayfasına dön</a>
            <?php if (isset($_SESSION['kullanici_id'])): ?>
            <a href="hesabim.php" class="btn btn-ana btn-kucuk">Başvurularımı Gör</a>
            <?php endif; ?>
        </div>
    <?php else: ?>

    <?php if ($hata): ?>
        <div class="mesaj mesaj-hata">⚠️ <?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>

    <?php if ($pRow): ?>
    <div class="pozisyon-bilgi-kutu">
        <div>
            <div style="font-size:0.75rem;color:var(--gri-3);margin-bottom:3px">Başvurduğunuz Pozisyon</div>
            <div style="font-weight:700;font-family:var(--font-baslik)"><?= htmlspecialchars($pRow['PozisyonAdi']) ?></div>
        </div>
        <span class="badge <?= calismaRenk($pRow['CalismaSekli']) ?>"><?= htmlspecialchars($pRow['CalismaSekli']) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="kart">
            <div class="bolum-baslik-kucuk">01 — Pozisyon Seçimi</div>
            <div class="form-grup">
                <label>Başvurmak İstediğiniz Pozisyon *</label>
                <select name="pozisyon_id" required>
                    <option value="">— Pozisyon seçin —</option>
                    <?php foreach ($tum_poz as $pz): ?>
                    <option value="<?= $pz['PozisyonId'] ?>"
                        <?= ($pz['PozisyonId'] == $pozisyon_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pz['PozisyonAdi']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="kart">
            <div class="bolum-baslik-kucuk">02 — Kişisel Bilgiler</div>
            <?php if (isset($_SESSION['kullanici_id'])): ?>
                <input type="hidden" name="ad"     value="<?= htmlspecialchars($_SESSION['kullanici_adi']    ?? '') ?>">
                <input type="hidden" name="soyad"  value="<?= htmlspecialchars($_SESSION['kullanici_soyadi'] ?? '') ?>">
                <input type="hidden" name="eposta" value="<?= htmlspecialchars($_SESSION['kullanici_eposta'] ?? '') ?>">
                <div class="mesaj mesaj-bilgi" style="margin-bottom:16px">
                    ℹ️ Kişisel bilgileriniz hesabınızdan otomatik alınacak:
                    <strong><?= htmlspecialchars($_SESSION['kullanici_ad']) ?></strong>
                    (<?= htmlspecialchars($_SESSION['kullanici_eposta']) ?>)
                </div>
            <?php else: ?>
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
            <div class="form-grid">
                <div class="form-grup">
                    <label>E-posta *</label>
                    <input type="email" name="eposta" placeholder="ornek@eposta.com" required>
                </div>
                <div class="form-grup">
                    <label>Telefon</label>
                    <input type="text" name="telefon" placeholder="05xx xxx xx xx">
                </div>
            </div>
            <div class="form-grid">
                <div class="form-grup">
                    <label>Doğum Tarihi</label>
                    <input type="date" name="dogum">
                </div>
                <div class="form-grup">
                    <label>Cinsiyet</label>
                    <select name="cinsiyet">
                        <option value="">Belirtmek istemiyorum</option>
                        <option value="Erkek">Erkek</option>
                        <option value="Kadın">Kadın</option>
                        <option value="Diğer">Diğer</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="kart">
            <div class="bolum-baslik-kucuk">03 — Başvuru Detayları</div>
            <div class="form-grid">
                <div class="form-grup">
                    <label>Maaş Beklentisi (₺)</label>
                    <input type="number" name="maas" placeholder="Örn: 50000" min="0">
                </div>
                <div class="form-grup">
                    <label>Başlangıç Tarihi</label>
                    <input type="date" name="baslangic">
                </div>
            </div>
            <div class="form-grup">
                <label>Nasıl Öğrendiniz?</label>
                <select name="referans">
                    <option value="">Seçiniz</option>
                    <option>LinkedIn</option>
                    <option>Kariyer.net</option>
                    <option>Indeed</option>
                    <option>Referans</option>
                    <option>Şirket Web Sitesi</option>
                    <option>Diğer</option>
                </select>
            </div>
            <div class="form-grup">
                <label>Kendinizden Bahsedin</label>
                <textarea name="hakkinda" rows="5"
                    placeholder="Deneyimleriniz, güçlü yönleriniz, neden bu pozisyona uygun olduğunuzu kısaca anlatın…"></textarea>
            </div>
        </div>

        <div class="kart">
            <div class="bolum-baslik-kucuk">04 — CV Yükleme</div>
            <div class="form-grup">
                <label>CV Dosyası (PDF veya Word, maks 5 MB)</label>
                <input type="file" name="cv" accept=".pdf,.doc,.docx" style="cursor:pointer">
            </div>
        </div>

        <div class="kart">
            <label class="checkbox-grup">
                <input type="checkbox" name="kvkk" required>
                <span>
                    Kişisel verilerimin <a href="#">KVKK kapsamında</a> işlenmesini onaylıyorum. *
                </span>
            </label>
            <button type="submit" name="gonder" class="btn btn-ana btn-buyuk"
                style="width:100%;justify-content:center;margin-top:16px">
                Başvuruyu Gönder →
            </button>
        </div>

    </form>
    <?php endif; ?>
</div>

<footer class="footer">
    <p>© <?= date('Y') ?> İş Başvuru Sistemi</p>
</footer>
</body>
</html>