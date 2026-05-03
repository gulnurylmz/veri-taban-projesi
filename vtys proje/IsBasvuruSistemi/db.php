<?php
// ============================================================
// DOSYA YOLU: IsBasvuruSistemi/db.php
// TÜM SAYFALAR BU DOSYAYI INCLUDE EDER
// ============================================================

$sunucu = "LAPTOP-EFQR8UEG";

$baglantiBilgisi = array(
    "Database"               => "IsBasvuruSistemi",
    "CharacterSet"           => "UTF-8",
    "TrustServerCertificate" => true
);

$baglanti = sqlsrv_connect($sunucu, $baglantiBilgisi);

if ($baglanti === false) {
    die("
    <div style='font-family:sans-serif;color:red;padding:20px;border:2px solid red;margin:20px;border-radius:8px'>
        <h3>⚠️ Veritabanı Bağlantı Hatası</h3>
        <pre>" . print_r(sqlsrv_errors(), true) . "</pre>
        <p><b>Kontrol edin:</b></p>
        <ul>
            <li>SQL Server servisi çalışıyor mu?</li>
            <li>\$sunucu değişkenindeki sunucu adı doğru mu? (SSMS'de sol üstte görünür)</li>
            <li>Kullanıcı adı ve şifre doğru mu?</li>
            <li>php_sqlsrv_82_ts_x64.dll kurulu ve php.ini'ye eklendi mi?</li>
            <li>Apache yeniden başlatıldı mı?</li>
        </ul>
    </div>");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
