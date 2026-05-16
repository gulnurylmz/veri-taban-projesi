<?php
// index.php — Ana Sayfa: Hero + Açık İlanlar
include("db.php");

// Pozisyonları çek
$sql = "
SELECT 
    p.PozisyonId,
    p.PozisyonAdi,
    p.CalismaSekli,
    p.Aciklama,
    p.OlusturmaTarihi,
    COUNT(b.BasvuruId) AS BasvuruSayisi
FROM POZISYONLAR p
LEFT JOIN Basvurular b 
    ON p.PozisyonId = b.PozisyonId
GROUP BY 
    p.PozisyonId,
    p.PozisyonAdi,
    p.CalismaSekli,
    p.Aciklama,
    p.OlusturmaTarihi
ORDER BY p.OlusturmaTarihi DESC
";

$Ilanlar = sqlsrv_query($baglanti, $sql);

if ($Ilanlar === false) {
    die(print_r(sqlsrv_errors(), true));
}


$pozisyonlar = [];
while ($r = sqlsrv_fetch_array($Ilanlar, SQLSRV_FETCH_ASSOC)) {
    $pozisyonlar[] = $r;
}

// İstatistikler
$toplamIlan  = count($pozisyonlar);
$toplamBasvuru = dbGetOne($baglanti, "SELECT COUNT(*) FROM BASVURULAR");
$toplamAday    = dbGetOne($baglanti, "SELECT COUNT(*) FROM ADAYLAR");

// Çalışma şekli badge
function calismaRenk($sekil) {
    return match($sekil) {
        'Uzaktan'     => 'badge-uzaktan',
        'Hibrit'      => 'badge-hibrit',
        'Tam Zamanlı' => 'badge-tam',
        'Yarı Zamanlı'=> 'badge-yari',
        'Staj'        => 'badge-staj',
        default       => 'badge-inceleniyor'
    };
}
// Pozisyon emoji
function pozisyonEmoji($ad) {
    $ad = mb_strtolower($ad);
    if (str_contains($ad,'php') || str_contains($ad,'backend') || str_contains($ad,'yazılım')) return '⚙️';
    if (str_contains($ad,'react') || str_contains($ad,'frontend') || str_contains($ad,'ui'))   return '🎨';
    if (str_contains($ad,'veri') || str_contains($ad,'data') || str_contains($ad,'analiz'))    return '📊';
    if (str_contains($ad,'devops') || str_contains($ad,'sistem') || str_contains($ad,'infra')) return '🔧';
    if (str_contains($ad,'test') || str_contains($ad,'qa'))                                    return '🧪';
    if (str_contains($ad,'proje') || str_contains($ad,'manager'))                              return '📋';
    return '💼';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İş Başvuru Sistemi — Kariyerinizi Şekillendirin</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .hero-dekor {
            position: absolute; pointer-events: none;
            width: 600px; height: 600px;
            border-radius: 50%;
            right: -100px; top: -100px;
            background: radial-gradient(circle, rgba(79,142,247,0.08) 0%, transparent 70%);
        }
        .hero-dekor-2 {
            position: absolute; pointer-events: none;
            width: 400px; height: 400px;
            border-radius: 50%;
            left: -80px; bottom: -80px;
            background: radial-gradient(circle, rgba(37,99,235,0.06) 0%, transparent 70%);
        }
        .bolum-alt-bg { background: var(--lacivert-2); }
        .bos-durum {
            text-align: center; padding: 60px 20px;
            color: var(--gri-4); font-size: 0.9rem;
        }
        .bos-durum .ikon { font-size: 3rem; margin-bottom: 12px; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        /* Admin navbar butonu */
        .btn-nav-admin {
            padding: 7px 14px;
            border: 1px solid rgba(245,158,11,0.4);
            border-radius: 8px;
            color: #fbbf24 !important;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, border-color 0.2s;
            white-space: nowrap;
        }
        .btn-nav-admin:hover {
            background: rgba(245,158,11,0.12);
            border-color: rgba(245,158,11,0.7);
        }
        .ilan-kart { animation: fadeUp 0.4s ease both; }
        .ilan-kart:nth-child(2) { animation-delay: 0.05s; }
        .ilan-kart:nth-child(3) { animation-delay: 0.1s; }
        .ilan-kart:nth-child(4) { animation-delay: 0.15s; }
        .ilan-kart:nth-child(5) { animation-delay: 0.2s; }
        .ilan-kart:nth-child(6) { animation-delay: 0.25s; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="navbar-logo">İş<span>Başvuru</span></a>
    <div class="navbar-menu">
        <a href="index.php" class="aktif gizle-mobil">Ana Sayfa</a>
        <a href="#ilanlar" class="gizle-mobil">İlanlar</a>
        <?php if (isset($_SESSION['kullanici_id'])): ?>
            <a href="hesabim.php" class="gizle-mobil">Hesabım</a>
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

<!-- HERO -->
<section class="hero">
    <div class="hero-dekor"></div>
    <div class="hero-dekor-2"></div>
    <div class="hero-icerik">
        <div>
            <div class="hero-etiket">✦ Kariyer Fırsatları</div>
            <h1>
                Hayalindeki İşe<br>
                <span class="vurgu">Adım At</span>
            </h1>
            <p class="hero-aciklama">
                <?= $toplamIlan ?> açık pozisyon arasından sana uygun olanı bul.
                Hızlı başvur, sürecini takip et.
            </p>
            <div class="hero-butonlar">
                <a href="#Ilanlar" class="btn btn-ana btn-buyuk">🔍 İlanları Keşfet</a>
                <?php if (!isset($_SESSION['kullanici_id'])): ?>
                    <a href="giris.php" class="btn btn-outline btn-buyuk">Giriş Yap</a>
                <?php else: ?>
                    <a href="hesabim.php" class="btn btn-outline btn-buyuk">Başvurularım</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-stat-grid">
            <div class="hero-stat-kart vurgulu">
                <span class="sayi"><?= $toplamIlan ?></span>
                <div class="etiket">Açık Pozisyon</div>
            </div>
            <div class="hero-stat-kart">
                <span class="sayi"><?= $toplamBasvuru ?? 0 ?></span>
                <div class="etiket">Toplam Başvuru</div>
            </div>
            <div class="hero-stat-kart">
                <span class="sayi"><?= $toplamAday ?? 0 ?></span>
                <div class="etiket">Kayıtlı Aday</div>
            </div>
            <div class="hero-stat-kart">
                <span class="sayi">7/24</span>
                <div class="etiket">Online Başvuru</div>
            </div>
        </div>
    </div>
</section>

<!-- İLANLAR -->
<section class="bolum" id="ilanlar">
    <div class="bolum-ici">
        <h2 class="bolum-baslik">Açık Pozisyonlar</h2>
        <p class="bolum-alt">Tüm pozisyonlara giriş yapmadan başvurabilirsiniz.</p>

        <!-- Filtre -->
        <div class="ilan-filtre">
            <button class="filtre-btn aktif" onclick="filtrele(this, 'tumu')">Tümü</button>
            <button class="filtre-btn" onclick="filtrele(this, 'Tam Zamanlı')">Tam Zamanlı</button>
            <button class="filtre-btn" onclick="filtrele(this, 'Uzaktan')">Uzaktan</button>
            <button class="filtre-btn" onclick="filtrele(this, 'Hibrit')">Hibrit</button>
            <button class="filtre-btn" onclick="filtrele(this, 'Yarı Zamanlı')">Yarı Zamanlı</button>
            <button class="filtre-btn" onclick="filtrele(this, 'Staj')">Staj</button>
        </div>

        <!-- İlan Kartları -->
        <?php if (empty($pozisyonlar)): ?>
            <div class="bos-durum">
                <div class="ikon">📭</div>
                <p>Şu anda açık pozisyon bulunmamaktadır.</p>
            </div>
        <?php else: ?>
        <div class="ilan-grid" id="ilan-grid">
            <?php foreach ($pozisyonlar as $p):
                $badgeCls = calismaRenk($p['CalismaSekli']);
                $emoji    = pozisyonEmoji($p['PozisyonAdi']);
                $tarih    = tarihFormatla($p['OlusturmaTarihi']);
                $aciklama = mb_substr($p['Aciklama'] ?? '', 0, 100) . (mb_strlen($p['Aciklama'] ?? '') > 100 ? '…' : '');
            ?>
            <div class="ilan-kart" data-calisma="<?= htmlspecialchars($p['CalismaSekli']) ?>">
                <div class="ilan-ust">
                    <div>
                        <div class="ilan-baslik"><?= htmlspecialchars($p['PozisyonAdi']) ?></div>
                        <div class="ilan-sirket">Şirket Adı A.Ş.</div>
                    </div>
                    <div class="ilan-logo"><?= $emoji ?></div>
                </div>
                <?php if ($aciklama): ?>
                <p style="font-size:0.85rem;color:var(--gri-3);line-height:1.6;margin-bottom:0">
                    <?= htmlspecialchars($aciklama) ?>
                </p>
                <?php endif; ?>
                <div class="ilan-bilgiler">
                    <span class="badge <?= $badgeCls ?>"><?= htmlspecialchars($p['CalismaSekli']) ?></span>
                    <?php if ($p['BasvuruSayisi'] > 0): ?>
                    <span class="badge badge-inceleniyor"><?= $p['BasvuruSayisi'] ?> başvuru</span>
                    <?php endif; ?>
                </div>
                <div class="ilan-alt">
                    <span class="ilan-tarih">📅 <?= $tarih ?></span>
                    <a href="basvuru.php?pozisyon=<?= $p['PozisyonId'] ?>" class="btn btn-ana btn-kucuk">
                        Hemen Başvur →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ÖZELLİKLER -->
<section class="bolum bolum-alt-bg">
    <div class="bolum-ici">
        <h2 class="bolum-baslik">Neden Biz?</h2>
        <p class="bolum-alt">Profesyonel işe alım sürecinin her adımında yanınızdayız.</p>
        <div class="ozellikler-grid">
            <div class="ozellik-kart">
                <div class="ozellik-ikon">🚀</div>
                <div class="ozellik-baslik">Hızlı Başvuru</div>
                <p class="ozellik-aciklama">Dakikalar içinde başvurunuzu tamamlayın. Kayıt bile gerektirmez.</p>
            </div>
            <div class="ozellik-kart">
                <div class="ozellik-ikon">📊</div>
                <div class="ozellik-baslik">Süreç Takibi</div>
                <p class="ozellik-aciklama">Hesap açarak başvurularınızın güncel durumunu anlık takip edin.</p>
            </div>
            <div class="ozellik-kart">
                <div class="ozellik-ikon">🔒</div>
                <div class="ozellik-baslik">Güvenli & KVKK</div>
                <p class="ozellik-aciklama">Tüm kişisel verileriniz SQL Server'da güvenle saklanır. KVKK uyumlu.</p>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <p>© <?= date('Y') ?> İş Başvuru Sistemi</p>
</footer>

<script>
function filtrele(btn, deger) {
    document.querySelectorAll('.filtre-btn').forEach(b => b.classList.remove('aktif'));
    btn.classList.add('aktif');
    document.querySelectorAll('.ilan-kart').forEach(kart => {
        const goster = deger === 'tumu' || kart.dataset.calisma === deger;
        kart.style.display = goster ? '' : 'none';
    });
}
</script>
</body>
</html>