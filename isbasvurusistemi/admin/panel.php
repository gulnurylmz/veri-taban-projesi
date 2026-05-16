<?php
// admin/panel.php — Admin Dashboard
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
include("../db.php");

// İstatistikler
$toplamIlan    = dbGetOne($baglanti, "SELECT COUNT(*) FROM POZISYONLAR");
$toplamBasvuru = dbGetOne($baglanti, "SELECT COUNT(*) FROM BASVURULAR");
$toplamAday    = dbGetOne($baglanti, "SELECT COUNT(*) FROM ADAYLAR");
$bekleyenSay   = dbGetOne($baglanti, "SELECT COUNT(*) FROM BASVURULAR WHERE Durum = 'Beklemede'");

// Son 8 başvuru
$sqlSonBasvuru = "
    SELECT TOP 8
        b.BasvuruId,
        b.BasvuruTarihi,
        b.Durum,
        a.Ad + ' ' + a.Soyad AS AdSoyad,
        a.Eposta,
        p.PozisyonAdi
    FROM BASVURULAR b
    JOIN ADAYLAR a ON b.AdayId = a.AdayId
    JOIN POZISYONLAR p ON b.PozisyonId = p.PozisyonId
    ORDER BY b.BasvuruTarihi DESC
";
$stmtSon = sqlsrv_query($baglanti, $sqlSonBasvuru);
$sonBasvurular = [];
if ($stmtSon) {
    while ($r = sqlsrv_fetch_array($stmtSon, SQLSRV_FETCH_ASSOC)) {
        $sonBasvurular[] = $r;
    }
}

// Pozisyon bazlı başvuru sayıları (pasta için)
$sqlPoz = "
    SELECT TOP 5 p.PozisyonAdi, COUNT(b.BasvuruId) AS Sayi
    FROM POZISYONLAR p
    LEFT JOIN BASVURULAR b ON p.PozisyonId = b.PozisyonId
    GROUP BY p.PozisyonAdi
    ORDER BY Sayi DESC
";
$stmtPoz = sqlsrv_query($baglanti, $sqlPoz);
$pozVerisi = [];
if ($stmtPoz) {
    while ($r = sqlsrv_fetch_array($stmtPoz, SQLSRV_FETCH_ASSOC)) {
        $pozVerisi[] = $r;
    }
}

// Durum renkleri
function durumRenk($d) {
    return match($d) {
        'Beklemede'  => '#f59e0b',
        'İnceleniyor'=> '#3b82f6',
        'Kabul'      => '#10b981',
        'Red'        => '#ef4444',
        default      => '#64748b'
    };
}
function durumEmoji($d) {
    return match($d) {
        'Beklemede'   => '⏳',
        'İnceleniyor' => '🔍',
        'Kabul'       => '✅',
        'Red'         => '❌',
        default       => '📋'
    };
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — İş Başvuru Sistemi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --lacivert:   #0a0f1e;
            --lacivert-2: #111827;
            --lacivert-3: #1a2332;
            --kenar:      rgba(255,255,255,0.06);
            --mavi:       #3b82f6;
            --mavi-a:     rgba(59,130,246,0.12);
            --yesil:      #10b981;
            --altin:      #f59e0b;
            --kirmizi:    #ef4444;
            --gri-2:      #e2e8f0;
            --gri-3:      #94a3b8;
            --gri-4:      #64748b;
            --beyaz:      #ffffff;
            --sidebar-w:  240px;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--lacivert);
            color: var(--beyaz);
            min-height: 100vh;
            display: flex;
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--lacivert-2);
            border-right: 1px solid var(--kenar);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0;
            z-index: 100;
        }
        .sidebar-logo {
            padding: 24px 20px 20px;
            border-bottom: 1px solid var(--kenar);
        }
        .sidebar-logo a {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem; font-weight: 800;
            color: var(--beyaz); text-decoration: none;
        }
        .sidebar-logo a span { color: #60a5fa; }
        .sidebar-logo .rozet {
            font-size: 0.65rem; color: var(--gri-3);
            letter-spacing: 0.1em; text-transform: uppercase;
            margin-top: 4px;
        }
        .sidebar-menu {
            flex: 1; padding: 16px 12px;
            display: flex; flex-direction: column; gap: 4px;
        }
        .menu-baslik {
            font-size: 0.65rem; letter-spacing: 0.12em;
            color: var(--gri-4); text-transform: uppercase;
            padding: 8px 8px 4px; margin-top: 8px;
        }
        .sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 8px;
            color: var(--gri-3); text-decoration: none;
            font-size: 0.875rem; font-weight: 500;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-link:hover, .sidebar-link.aktif {
            background: var(--mavi-a);
            color: var(--beyaz);
        }
        .sidebar-link.aktif { color: #93c5fd; }
        .sidebar-link .ikon { font-size: 1rem; width: 20px; text-align: center; }

        .sidebar-alt {
            padding: 16px 12px;
            border-top: 1px solid var(--kenar);
        }
        .admin-kart {
            display: flex; align-items: center; gap: 10px;
            padding: 10px; border-radius: 10px;
            background: rgba(255,255,255,0.03);
        }
        .admin-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--mavi);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.85rem;
        }
        .admin-bilgi { flex: 1; min-width: 0; }
        .admin-ad { font-size: 0.82rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .admin-rol { font-size: 0.7rem; color: var(--gri-4); }
        .cikis-btn {
            background: none; border: none; cursor: pointer;
            color: var(--gri-4); font-size: 0.85rem;
            padding: 4px; border-radius: 6px;
            transition: color 0.15s;
        }
        .cikis-btn:hover { color: var(--kirmizi); }

        /* ANA İÇERİK */
        .ana {
            margin-left: var(--sidebar-w);
            flex: 1; padding: 28px 32px;
            max-width: calc(100vw - var(--sidebar-w));
        }

        /* Üst bar */
        .ust-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 28px;
        }
        .sayfa-baslik {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem; font-weight: 800;
        }
        .tarih-chip {
            font-size: 0.8rem; color: var(--gri-3);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--kenar);
            padding: 6px 14px; border-radius: 100px;
        }

        /* İstatistik kartları */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-kart {
            background: var(--lacivert-2);
            border: 1px solid var(--kenar);
            border-radius: 14px;
            padding: 20px;
            position: relative; overflow: hidden;
            transition: border-color 0.2s, transform 0.2s;
            animation: fadeUp 0.4s ease both;
        }
        .stat-kart:hover { border-color: rgba(59,130,246,0.3); transform: translateY(-2px); }
        .stat-kart:nth-child(2) { animation-delay: 0.05s; }
        .stat-kart:nth-child(3) { animation-delay: 0.1s; }
        .stat-kart:nth-child(4) { animation-delay: 0.15s; }
        .stat-ikon {
            font-size: 1.6rem; margin-bottom: 12px;
        }
        .stat-sayi {
            font-family: 'Syne', sans-serif;
            font-size: 2rem; font-weight: 800;
            line-height: 1;
        }
        .stat-etiket {
            font-size: 0.8rem; color: var(--gri-3); margin-top: 4px;
        }
        .stat-cizgi {
            position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
            border-radius: 0 0 14px 14px;
        }

        /* Alt grid */
        .alt-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
        }

        /* Panel kutusu */
        .panel-kutu {
            background: var(--lacivert-2);
            border: 1px solid var(--kenar);
            border-radius: 14px;
            overflow: hidden;
            animation: fadeUp 0.4s ease 0.2s both;
        }
        .panel-kutu-baslik {
            padding: 16px 20px;
            border-bottom: 1px solid var(--kenar);
            display: flex; align-items: center; justify-content: space-between;
        }
        .panel-kutu-baslik h3 {
            font-family: 'Syne', sans-serif;
            font-size: 0.95rem; font-weight: 700;
        }
        .tumunu-gör {
            font-size: 0.78rem; color: #60a5fa;
            text-decoration: none; font-weight: 500;
        }
        .tumunu-gör:hover { text-decoration: underline; }

        /* Tablo */
        .veri-tablo {
            width: 100%; border-collapse: collapse;
        }
        .veri-tablo th {
            padding: 10px 16px;
            font-size: 0.7rem; font-weight: 600;
            color: var(--gri-4); text-transform: uppercase;
            letter-spacing: 0.08em; text-align: left;
            border-bottom: 1px solid var(--kenar);
        }
        .veri-tablo td {
            padding: 12px 16px;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        .veri-tablo tr:last-child td { border-bottom: none; }
        .veri-tablo tr:hover td { background: rgba(255,255,255,0.02); }

        .ad-hucre { font-weight: 500; }
        .email-hucre { color: var(--gri-3); font-size: 0.8rem; }
        .pozisyon-hucre { color: #93c5fd; }

        .durum-badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 100px;
            font-size: 0.72rem; font-weight: 600;
            white-space: nowrap;
        }

        /* Pozisyon listesi (sağ panel) */
        .poz-liste { padding: 8px 0; }
        .poz-satir {
            display: flex; align-items: center;
            padding: 10px 20px; gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            transition: background 0.15s;
        }
        .poz-satir:last-child { border-bottom: none; }
        .poz-satir:hover { background: rgba(255,255,255,0.02); }
        .poz-bar-sarici { flex: 1; }
        .poz-ad { font-size: 0.82rem; font-weight: 500; margin-bottom: 4px; }
        .poz-bar-bg {
            height: 4px; background: rgba(255,255,255,0.06);
            border-radius: 100px; overflow: hidden;
        }
        .poz-bar-dolu {
            height: 100%; background: var(--mavi);
            border-radius: 100px;
            transition: width 0.8s ease;
        }
        .poz-sayi {
            font-family: 'Syne', sans-serif;
            font-size: 0.9rem; font-weight: 700;
            color: #60a5fa; min-width: 28px; text-align: right;
        }

        /* Boş durum */
        .bos { text-align: center; padding: 40px; color: var(--gri-4); font-size: 0.875rem; }


        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1100px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .alt-grid  { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .ana { margin-left: 0; padding: 16px; max-width: 100vw; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <a href="index.php">İş<span>Başvuru</span></a>
        <div class="rozet">⚙ Yönetim Paneli</div>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-baslik">Genel</div>
        <a href="panel.php" class="sidebar-link aktif">
            <span class="ikon">📊</span> Dashboard
        </a>
        <a href="basvurular.php" class="sidebar-link">
            <span class="ikon">📋</span> Başvurular
            <?php if ($bekleyenSay > 0): ?>
                <span style="margin-left:auto;background:var(--altin);color:#000;font-size:0.65rem;font-weight:700;padding:1px 7px;border-radius:100px;"><?= $bekleyenSay ?></span>
            <?php endif; ?>
        </a>
        <a href="pozisyonlar.php" class="sidebar-link">
            <span class="ikon">💼</span> Pozisyonlar
        </a>
        <a href="adaylar.php" class="sidebar-link">
            <span class="ikon">👤</span> Adaylar
        </a>

        <div class="menu-baslik">Sistem</div>
        <a href="ayarlar.php" class="sidebar-link">
            <span class="ikon">⚙️</span> Ayarlar
        </a>
        <a href="cikis.php" class="sidebar-link" style="color:#fca5a5;">
            <span class="ikon">🚪</span> Çıkış Yap
        </a>
    </nav>

    <div class="sidebar-alt">
        <div class="admin-kart">
            <div class="admin-avatar">
                <?= mb_strtoupper(mb_substr($_SESSION['admin_adi'], 0, 1)) ?>
            </div>
            <div class="admin-bilgi">
                <div class="admin-ad"><?= htmlspecialchars($_SESSION['admin_adi']) ?></div>
                <div class="admin-rol">Süper Admin</div>
            </div>
            <a href="cikis.php" class="cikis-btn" title="Çıkış Yap">🚪</a>
        </div>
    </div>
</aside>

<!-- ANA İÇERİK -->
<main class="ana">
    <div class="ust-bar">
        <div class="sayfa-baslik">Dashboard</div>
        <div class="tarih-chip">📅 <?= date('d M Y, H:i') ?></div>
    </div>

    <!-- İstatistikler -->
    <div class="stat-grid">
        <div class="stat-kart">
            <div class="stat-ikon">💼</div>
            <div class="stat-sayi"><?= $toplamIlan ?? 0 ?></div>
            <div class="stat-etiket">Açık Pozisyon</div>
            <div class="stat-cizgi" style="background:var(--mavi)"></div>
        </div>
        <div class="stat-kart">
            <div class="stat-ikon">📋</div>
            <div class="stat-sayi"><?= $toplamBasvuru ?? 0 ?></div>
            <div class="stat-etiket">Toplam Başvuru</div>
            <div class="stat-cizgi" style="background:var(--yesil)"></div>
        </div>
        <div class="stat-kart">
            <div class="stat-ikon">👥</div>
            <div class="stat-sayi"><?= $toplamAday ?? 0 ?></div>
            <div class="stat-etiket">Kayıtlı Aday</div>
            <div class="stat-cizgi" style="background:#8b5cf6"></div>
        </div>
        <div class="stat-kart">
            <div class="stat-ikon">⏳</div>
            <div class="stat-sayi"><?= $bekleyenSay ?? 0 ?></div>
            <div class="stat-etiket">Bekleyen Başvuru</div>
            <div class="stat-cizgi" style="background:var(--altin)"></div>
        </div>
    </div>

    <!-- Alt grid -->
    <div class="alt-grid">
        <!-- Son Başvurular -->
        <div class="panel-kutu">
            <div class="panel-kutu-baslik">
                <h3>📋 Son Başvurular</h3>
                <a href="basvurular.php" class="tumunu-gör">Tümünü gör →</a>
            </div>
            <?php if (empty($sonBasvurular)): ?>
                <div class="bos">Henüz başvuru yok.</div>
            <?php else: ?>
            <table class="veri-tablo">
                <thead>
                    <tr>
                        <th>Aday</th>
                        <th>Pozisyon</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sonBasvurular as $b):
                    $renk  = durumRenk($b['Durum']);
                    $emoji = durumEmoji($b['Durum']);
                    $tarih = $b['BasvuruTarihi'] instanceof DateTime
                           ? $b['BasvuruTarihi']->format('d.m.Y')
                           : date('d.m.Y', strtotime($b['BasvuruTarihi']));
                ?>
                <tr>
                    <td>
                        <div class="ad-hucre"><?= htmlspecialchars($b['AdSoyad']) ?></div>
                        <div class="email-hucre"><?= htmlspecialchars($b['Eposta']) ?></div>
                    </td>
                    <td class="pozisyon-hucre"><?= htmlspecialchars($b['PozisyonAdi']) ?></td>
                    <td style="color:var(--gri-3);font-size:0.8rem;"><?= $tarih ?></td>
                    <td>
                        <span class="durum-badge" style="background:<?= $renk ?>22;color:<?= $renk ?>;border:1px solid <?= $renk ?>44;">
                            <?= $emoji ?> <?= htmlspecialchars($b['Durum']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Sağ kolon -->
        <div style="display:flex;flex-direction:column;gap:20px;">
            <!-- Pozisyon dağılımı -->
            <div class="panel-kutu">
                <div class="panel-kutu-baslik">
                    <h3>📊 Başvuru Dağılımı</h3>
                </div>
                <?php if (empty($pozVerisi)): ?>
                    <div class="bos">Veri yok.</div>
                <?php else:
                    $maxSayi = max(array_column($pozVerisi, 'Sayi')) ?: 1;
                ?>
                <div class="poz-liste">
                    <?php foreach ($pozVerisi as $pv):
                        $yuzde = round(($pv['Sayi'] / $maxSayi) * 100);
                    ?>
                    <div class="poz-satir">
                        <div class="poz-bar-sarici">
                            <div class="poz-ad"><?= htmlspecialchars(mb_substr($pv['PozisyonAdi'], 0, 22)) ?></div>
                            <div class="poz-bar-bg">
                                <div class="poz-bar-dolu" style="width:<?= $yuzde ?>%"></div>
                            </div>
                        </div>
                        <div class="poz-sayi"><?= $pv['Sayi'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>

</body>
</html>