<?php
// admin/auth.php — Tüm admin sayfalarına include et
// Kullanım: include("auth.php"); — sayfanın en üstüne

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . dirname($_SERVER['PHP_SELF']) . "/login.php");
    exit;
}
