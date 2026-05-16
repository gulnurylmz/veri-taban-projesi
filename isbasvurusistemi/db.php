<?php
// ============================================================
// db.php — Merkezi veritabanı bağlantısı
// Tüm sayfalar bu dosyayı include eder
// ============================================================

$sunucu = ".";   // veya "." veya "LAPTOP-XXXXX" — SSMS'de sol üstten bakın

$baglantiBilgisi = [
    "Database"               => "IsBasvuruSistemi",
    "CharacterSet"           => "UTF-8",
    "TrustServerCertificate" => true,
    // Windows Auth için aşağıdaki satırlar yorum satırı kalabilir
    // "UID"                 => "sa",
    // "PWD"                 => "sifreniz",
];

$baglanti = sqlsrv_connect($sunucu, $baglantiBilgisi);

if ($baglanti === false) {
    http_response_code(500);
    die('
    <style>body{font-family:sans-serif;background:#0f172a;color:#f87171;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .box{background:#1e293b;border:1px solid #f87171;border-radius:12px;padding:32px;max-width:600px;width:90%}
    pre{font-size:0.75rem;color:#94a3b8;margin-top:12px;overflow:auto}</style>
    <div class="box">
        <h2>⚠️ Veritabanı Bağlantı Hatası</h2>
        <p>db.php içindeki <b>$sunucu</b> değişkenini kontrol edin.</p>
        <pre>' . print_r(sqlsrv_errors(), true) . '</pre>
    </div>');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Yardımcı fonksiyonlar ───────────────────────────────────

/**
 * Tek satır / tek değer döndüren sorgular için
 */
function dbGetOne($baglanti, $sql, $params = []) {
    $s = $params ? sqlsrv_query($baglanti, $sql, $params)
                 : sqlsrv_query($baglanti, $sql);
    if (!$s) return null;
    $r = sqlsrv_fetch_array($s, SQLSRV_FETCH_ASSOC);
    return $r ? array_values($r)[0] : null;
}

/**
 * DateTime nesnelerini Türkçe formata çevirir
 */
function tarihFormatla($deger, $format = 'd.m.Y') {
    if ($deger instanceof DateTime) return $deger->format($format);
    if (is_string($deger))         return date($format, strtotime($deger));
    return '-';
}

/**
 * Durum badge CSS sınıfını döndürür
 */
function durumBadge($durum) {
    return match($durum) {
        'Beklemede'    => 'badge-beklemede',
        'İnceleniyor'  => 'badge-inceleniyor',
        'Kabul Edildi' => 'badge-kabul',
        'Reddedildi'   => 'badge-red',
        default        => 'badge-beklemede'
    };
}
?>
