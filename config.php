<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "perkasa";
$dbch = 'cb011089a0f995602c6b18753073342256e626939603318bc81b81f80b091b44';

// Cloudflare Turnstile (ganti dengan key Anda sendiri dari https://dash.cloudflare.com/turnstile)
$turnstile_site_key   = '0x4AAAAAAADnPIDROrmt1Wwj';  // contoh test key
$turnstile_secret_key = '0x4AAAAAAADnPIDROrmt1Wwj';  // ganti dgn secret key asli

// CAPTCHA SHA-256 secret (jangan share!)
define('CAPTCHA_SECRET', 'PRK-8e9d6021db4d98171ec38a24f80bb1af91435933');

$conn = mysqli_connect($host, $user, $pass, $db);

if(!$conn){
    die("Koneksi database gagal");
}

mysqli_set_charset($conn, 'utf8mb4');

// Auto-migration: tambah kolom tanggal_meninggal jika belum ada
$check = @mysqli_query($conn, "SHOW COLUMNS FROM peserta LIKE 'tanggal_meninggal'");
if($check && mysqli_num_rows($check) == 0){
    @mysqli_query($conn, "ALTER TABLE peserta ADD COLUMN `tanggal_meninggal` DATE DEFAULT NULL AFTER `keterangan`");
}

if(session_status() === PHP_SESSION_NONE){
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if($is_https){
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

require_once __DIR__ . '/core/functions.php';
