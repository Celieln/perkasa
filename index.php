<?php
include 'config.php';

// Auto-migration: tambah kolom tanggal_meninggal jika belum ada
$check = @mysqli_query($conn, "SHOW COLUMNS FROM peserta LIKE 'tanggal_meninggal'");
if($check && mysqli_num_rows($check) == 0){
    @mysqli_query($conn, "ALTER TABLE peserta ADD COLUMN `tanggal_meninggal` DATE DEFAULT NULL AFTER `keterangan`");
}

if(isset($_SESSION['login'])){
    header("Location: dashboard.php");
    exit;
}

// Statistik mini untuk halaman login
$stat_total = 0;
$stat_denominasi = [];
$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peserta");
if($q){
    $stat_total = (int)mysqli_fetch_assoc($q)['total'];
}
$q = mysqli_query($conn, "SELECT denominasi, COUNT(*) AS jumlah FROM peserta WHERE denominasi IS NOT NULL AND denominasi != '' GROUP BY denominasi ORDER BY jumlah DESC");
if($q){
    while($row = mysqli_fetch_assoc($q)){
        $stat_denominasi[] = $row;
    }
}

// ===== Statistik Kematian (dari tanggal_meninggal) =====
$stat_meninggal_total = 0;
$stat_meninggal_bulan = 0;
$stat_meninggal_tahun = 0;
$data_meninggal = [];

$q = mysqli_query($conn, "SELECT COUNT(*) AS total,
    SUM(YEAR(tanggal_meninggal) = YEAR(CURDATE()) AND MONTH(tanggal_meninggal) = MONTH(CURDATE())) AS bulan,
    SUM(YEAR(tanggal_meninggal) = YEAR(CURDATE())) AS tahun
    FROM peserta WHERE tanggal_meninggal IS NOT NULL");
if($q){
    $row = mysqli_fetch_assoc($q);
    $stat_meninggal_total = (int)$row['total'];
    $stat_meninggal_bulan = (int)($row['bulan'] ?? 0);
    $stat_meninggal_tahun = (int)($row['tahun'] ?? 0);
}

function ambil_meninggal($conn, $where, $limit = 5){
    $out = [];
    $q = mysqli_query($conn, "SELECT nama_lengkap, jenis_kelamin, kota, tanggal_meninggal, tipe_program, status_klaim
        FROM peserta WHERE tanggal_meninggal IS NOT NULL $where
        ORDER BY tanggal_meninggal DESC, id DESC LIMIT $limit");
    if($q){
        while($row = mysqli_fetch_assoc($q)){ $out[] = $row; }
    }
    return $out;
}
$data_meninggal_jkm  = ambil_meninggal($conn, "AND tipe_program = 'JKM'");
$data_meninggal_jkk  = ambil_meninggal($conn, "AND tipe_program = 'JKK'");
$data_meninggal_null = ambil_meninggal($conn, "AND tipe_program IS NULL");

// Total peserta program JKK (kartu Status Peserta)
$stat_jkk_total = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peserta WHERE tipe_program = 'JKK'");
if($q){ $stat_jkk_total = (int)mysqli_fetch_assoc($q)['total']; }

// Total peserta program JKM (kartu Status Peserta)
$stat_jkm_total = 0;
$q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM peserta WHERE tipe_program = 'JKM'");
if($q){ $stat_jkm_total = (int)mysqli_fetch_assoc($q)['total']; }

// Sensor nama parsial (nama tengah diganti •••) demi perlindungan data
function sensor_nama_ringkas($nama){
    $nama = trim((string)$nama);
    $len  = mb_strlen($nama, 'UTF-8');
    if($len <= 3) return $nama;
    $first = mb_substr($nama, 0, 1, 'UTF-8');
    $last  = mb_substr($nama, -1, 1, 'UTF-8');
    $sen   = str_repeat('•', min(4, $len - 2));
    return $first . $sen . $last;
}

// ===== Keamanan halaman login =====
$ip = client_ip();
$lock = login_lock_state($conn, $ip);
$lock_active    = $lock['locked'];
$lock_remaining = $lock['remaining'];
$need_captcha   = $lock['attempts'] >= 3;
captcha_generate();

$alert_msg = '';
$alert_type = 'danger';
if(isset($_GET['error'])){ $alert_msg = 'Username atau password salah! Periksa kembali data Anda.'; }
if(isset($_GET['captcha'])){ $alert_msg = 'Jawaban keamanan salah. Silakan coba lagi.'; }
if(isset($_GET['suspended'])){ $alert_msg = 'Akun Anda sedang disuspend. Hubungi administrator.'; }
if(isset($_GET['maintenance'])){ $alert_msg = 'Sistem sedang dalam pemeliharaan. Silakan coba lagi nanti.'; $alert_type = 'warning'; }
if($lock_active){
    $menit = (int)ceil($lock_remaining / 60);
    $sisa  = $lock_remaining % 60;
    $alert_msg = 'Terlalu banyak percobaan login. Silakan coba lagi dalam ' . $menit . ' menit ' . $sisa . ' detik.';
    $alert_type = 'warning';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PERKASA — Sistem Manajemen Klaim</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    background:#0a0f1a;
    min-height:100vh;color:white;overflow-x:hidden;
    position:relative;
}

/* ===== PARTICLE BACKGROUND ===== */
#particles-canvas{
    position:fixed;top:0;left:0;width:100%;height:100%;
    z-index:0;pointer-events:none;
}

/* ===== GOVERNOR BACKGROUND ===== */
.governor-bg{
    position:fixed;top:0;left:0;width:100%;height:100%;
    background:url('assets/foto-gubernur.jpg') center center/cover no-repeat;
    z-index:-2;
}
.governor-overlay{
    position:fixed;top:0;left:0;width:100%;height:100%;
    background:linear-gradient(135deg,rgba(10,15,26,0.78),rgba(26,26,46,0.72),rgba(22,33,62,0.75),rgba(15,52,96,0.7));
    z-index:-1;
}

/* ===== ANIMATED GRADIENT BG ===== */
.animated-bg{
    position:fixed;top:0;left:0;width:100%;height:100%;
    background:linear-gradient(-45deg,#0a0f1a,#1a1a2e,#16213e,#0f3460);
    background-size:400% 400%;
    animation:gradientBG 15s ease infinite;
    z-index:-1;
    opacity:0.5;
}
@keyframes gradientBG{
    0%{background-position:0% 50%;}
    50%{background-position:100% 50%;}
    100%{background-position:0% 50%;}
}

/* ===== GLOW EFFECTS ===== */
.glow-blue{box-shadow:0 0 60px rgba(59,130,246,0.15);}
.glow-green{box-shadow:0 0 60px rgba(16,185,129,0.15);}
.glow-purple{box-shadow:0 0 60px rgba(139,92,246,0.15);}

/* ===== GLASS CARD ===== */
.glass-card{
    background:rgba(255,255,255,0.03);
    backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,0.06);
    border-radius:24px;
    box-shadow:0 25px 50px rgba(0,0,0,0.3);
    position:relative;
    overflow:hidden;
}
.glass-card::before{
    content:'';position:absolute;top:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent);
}

/* ===== FEATURE CARDS ===== */
.feature-card{
    background:rgba(255,255,255,0.02);
    border:1px solid rgba(255,255,255,0.05);
    border-radius:20px;
    padding:28px 24px;
    text-decoration:none;
    color:white;
    transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
    display:block;
    height:100%;
    position:relative;
    overflow:hidden;
}
.feature-card::before{
    content:'';position:absolute;top:0;left:0;width:100%;height:100%;
    background:linear-gradient(135deg,rgba(59,130,246,0.1),transparent);
    opacity:0;transition:0.4s;
}
.feature-card:hover{
    transform:translateY(-8px) scale(1.02);
    border-color:rgba(59,130,246,0.4);
    box-shadow:0 20px 40px rgba(59,130,246,0.2);
}
.feature-card:hover::before{opacity:1;}
.feature-card .icon-wrapper{
    width:60px;height:60px;
    border-radius:16px;
    display:flex;align-items:center;justify-content:center;
    margin-bottom:18px;
    position:relative;
    transition:0.4s;
}
.feature-card:hover .icon-wrapper{
    transform:scale(1.1) rotate(5deg);
}
.feature-card .title{font-size:18px;font-weight:700;margin-bottom:6px;}
.feature-card .desc{font-size:13px;opacity:0.5;line-height:1.5;}
.feature-card .arrow{
    position:absolute;right:20px;top:50%;transform:translateY(-50%);
    opacity:0;transition:0.4s;
}
.feature-card:hover .arrow{
    opacity:1;transform:translateY(-50%) translateX(5px);
}

/* ===== INPUT STYLES ===== */
.input-group-custom{
    position:relative;
}
.custom-input{
    background:rgba(255,255,255,0.04);
    border:2px solid rgba(255,255,255,0.08);
    color:white;border-radius:14px;
    height:56px;padding:0 20px;transition:all 0.3s;
    font-size:15px;
}
.custom-input::placeholder{color:rgba(255,255,255,0.25);}
.custom-input:focus{
    background:rgba(255,255,255,0.06)!important;
    border-color:#3b82f6!important;
    box-shadow:0 0 0 4px rgba(59,130,246,0.15),0 0 30px rgba(59,130,246,0.1)!important;
    color:white!important;
}
.input-icon{
    position:absolute;left:18px;top:50%;transform:translateY(-50%);
    color:rgba(255,255,255,0.3);font-size:18px;transition:0.3s;
}
.custom-input:focus + .input-icon,
.input-group-custom:focus-within .input-icon{
    color:#3b82f6;
}
.input-counter{
    position:absolute;right:16px;top:50%;transform:translateY(-50%);
    font-size:12px;color:rgba(255,255,255,0.3);
    background:rgba(255,255,255,0.06);padding:4px 10px;border-radius:8px;
}

/* ===== BUTTON STYLES ===== */
.btn-primary-custom{
    height:56px;border-radius:14px;font-weight:700;font-size:16px;
    background:linear-gradient(135deg,#3b82f6,#2563eb);
    border:none;transition:all 0.3s;
    box-shadow:0 4px 20px rgba(59,130,246,0.4);
    display:flex;align-items:center;justify-content:center;gap:10px;
    position:relative;overflow:hidden;
}
.btn-primary-custom::before{
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);
    transition:0.5s;
}
.btn-primary-custom:hover::before{left:100%;}
.btn-primary-custom:hover{
    transform:translateY(-3px);
    box-shadow:0 8px 30px rgba(59,130,246,0.5);
}
.btn-primary-custom:active{transform:translateY(0);}

/* ===== STAT CARDS ===== */
.stat-card{
    background:rgba(255,255,255,0.02);
    border:1px solid rgba(255,255,255,0.05);
    border-radius:18px;padding:20px;
    transition:all 0.3s;
    position:relative;
    overflow:hidden;
}
.stat-card::after{
    content:'';position:absolute;top:0;right:0;width:80px;height:80px;
    border-radius:50%;filter:blur(40px);opacity:0.3;
}
.stat-card:hover{
    transform:translateY(-5px);
    border-color:rgba(255,255,255,0.1);
}
.stat-card .stat-icon{
    width:48px;height:48px;border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    margin-bottom:14px;font-size:22px;
}
.stat-card .stat-value{
    font-size:28px;font-weight:800;line-height:1;
    margin-bottom:4px;
}
.stat-card .stat-label{
    font-size:12px;color:rgba(255,255,255,0.4);
    text-transform:uppercase;letter-spacing:0.5px;
}

/* ===== DATA LIST ===== */
.data-section{margin-top:20px;}
.section-header{
    display:flex;align-items:center;gap:10px;margin-bottom:14px;
}
.section-dot{width:10px;height:10px;border-radius:50%;}
.section-title{
    font-size:13px;font-weight:700;color:rgba(255,255,255,0.7);
    text-transform:uppercase;letter-spacing:1px;
}
.section-count{
    background:rgba(255,255,255,0.08);padding:2px 10px;
    border-radius:10px;font-size:11px;color:rgba(255,255,255,0.5);
    margin-left:auto;
}
.data-list-row{
    display:flex;align-items:center;gap:14px;
    background:rgba(255,255,255,0.02);
    border:1px solid rgba(255,255,255,0.04);
    border-radius:14px;padding:16px;
    transition:all 0.3s;
    margin-bottom:10px;
}
.data-list-row:hover{
    background:rgba(255,255,255,0.05);
    border-color:rgba(255,255,255,0.1);
    transform:translateX(5px);
}
.row-indicator{width:4px;height:40px;border-radius:2px;flex-shrink:0;}
.row-content{flex:1;min-width:0;}
.row-main{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.row-name{font-size:14px;font-weight:600;letter-spacing:1.5px;}
.row-badge{
    font-size:10px;padding:3px 10px;border-radius:8px;
    font-weight:700;text-transform:uppercase;letter-spacing:0.5px;
}
.badge-jkm{background:rgba(239,68,68,0.15);color:#fca5a5;border:1px solid rgba(239,68,68,0.25);}
.badge-jkk{background:rgba(16,185,129,0.15);color:#6ee7b7;border:1px solid rgba(16,185,129,0.25);}
.row-meta{
    font-size:12px;color:rgba(255,255,255,0.35);margin-top:4px;
    display:flex;align-items:center;gap:6px;
}
.meta-dot{opacity:0.4;}
.status-badge{
    font-size:11px;padding:6px 12px;border-radius:10px;
    font-weight:700;white-space:nowrap;flex-shrink:0;
    display:flex;align-items:center;gap:6px;
}
.status-belum{background:rgba(148,163,184,0.1);color:#94a3b8;border:1px solid rgba(148,163,184,0.2);}
.status-proses_claim,.status-proses_cair{background:rgba(251,191,36,0.1);color:#fbbf24;border:1px solid rgba(251,191,36,0.2);}
.status-sudah_claim,.status-sudah_cair{background:rgba(52,211,153,0.1);color:#34d399;border:1px solid rgba(52,211,153,0.2);}

/* ===== EMPTY STATE ===== */
.empty-state{
    text-align:center;padding:40px 20px;
}
.empty-state-icon{
    width:80px;height:80px;border-radius:50%;
    background:rgba(255,255,255,0.03);
    border:2px dashed rgba(255,255,255,0.08);
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 16px;
}

/* ===== DIVIDER ===== */
.divider{
    height:1px;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.08),transparent);
    margin:20px 0;
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar{width:8px;}
::-webkit-scrollbar-track{background:rgba(255,255,255,0.02);}
::-webkit-scrollbar-thumb{
    background:linear-gradient(180deg,#3b82f6,#2563eb);
    border-radius:10px;
}

/* ===== ANIMATIONS ===== */
@keyframes float{
    0%,100%{transform:translateY(0);}
    50%{transform:translateY(-20px);}
}
@keyframes pulse{
    0%,100%{opacity:1;}
    50%{opacity:0.5;}
}
@keyframes shimmer{
    0%{background-position:-200% 0;}
    100%{background-position:200% 0;}
}

/* ===== RESPONSIVE ===== */
@media(max-width:768px){
    .feature-card{padding:20px 18px;}
    .feature-card .icon-wrapper{width:50px;height:50px;margin-bottom:14px;}
    .feature-card .title{font-size:16px;}
    .stat-card .stat-value{font-size:24px;}
    .data-list-row{padding:12px 14px;}
}
</style>
</head>
<body>

<!-- Governor Background Photo -->
<div class="governor-bg"></div>
<div class="governor-overlay"></div>

<!-- Animated Background -->
<div class="animated-bg"></div>

<!-- Particle Canvas -->
<canvas id="particles-canvas"></canvas>

<div class="container position-relative" style="z-index:1;">
<div class="row justify-content-center">
<div class="col-lg-8 col-xl-7">

    <!-- HEADER -->
    <div class="text-center mb-5 pt-4" data-aos="fade-down">
        <div class="d-inline-flex align-items-center gap-3 mb-4">
            <div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 30px rgba(59,130,246,0.4);">
                <i class="fas fa-shield-halved text-white" style="font-size:28px;"></i>
            </div>
            <div class="text-start">
                <h1 class="fw-bold mb-0" style="font-size:36px;letter-spacing:3px;background:linear-gradient(135deg,#fff,#94a3b8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">PERKASA</h1>
                <p style="color:rgba(255,255,255,0.4);font-size:13px;letter-spacing:1px;">Sistem Manajemen Klaim JKK & JKM</p>
            </div>
        </div>
    </div>

    <!-- FEATURE CARDS -->
    <div class="row g-4 mb-5" data-aos="fade-up" data-aos-delay="100">
        <div class="col-md-6">
            <a href="cek_peserta.php" class="feature-card">
                <div class="icon-wrapper" style="background:linear-gradient(135deg,rgba(16,185,129,0.2),rgba(52,211,153,0.1));">
                    <i class="fas fa-clipboard-check" style="color:#34d399;font-size:28px;"></i>
                </div>
                <div class="title">Cek Kepersertaan</div>
                <div class="desc">Periksa status data kepesertaan JKK/JKM Anda secara instan</div>
                <div class="arrow"><i class="fas fa-arrow-right" style="color:#34d399;"></i></div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="tracking.php" class="feature-card">
                <div class="icon-wrapper" style="background:linear-gradient(135deg,rgba(96,165,250,0.2),rgba(59,130,246,0.1));">
                    <i class="fas fa-route" style="color:#60a5fa;font-size:28px;"></i>
                </div>
                <div class="title">Lacak Pengurusan</div>
                <div class="desc">Pantau progress pengurusan klaim dengan kode tracking</div>
                <div class="arrow"><i class="fas fa-arrow-right" style="color:#60a5fa;"></i></div>
            </a>
        </div>
    </div>

    <!-- STATISTIK SECTION -->
    <div class="glass-card p-4 p-md-5 mb-5 glow-blue" data-aos="fade-up" data-aos-delay="150">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(59,130,246,0.2),rgba(139,92,246,0.1));display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-chart-pie" style="color:#8b5cf6;font-size:20px;"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="font-size:16px;">Statistik Data</h5>
                    <small style="color:rgba(255,255,255,0.35);font-size:12px;">Ringkasan kepesertaan</small>
                </div>
            </div>
            <div class="text-end">
                <div style="font-size:32px;font-weight:800;line-height:1;background:linear-gradient(135deg,#60a5fa,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;" class="counter" data-target="<?= $stat_total ?>"><?= number_format($stat_total, 0, ',', '.') ?></div>
                <small style="color:rgba(255,255,255,0.35);font-size:12px;">Total Terdaftar</small>
            </div>
        </div>
        <div class="divider"></div>
        <div class="d-flex align-items-center gap-2 mb-3">
            <small style="color:rgba(255,255,255,0.4);font-size:12px;font-weight:600;">DENOMINASI</small>
            <span style="background:rgba(139,92,246,0.15);color:#a78bfa;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:600;"><?= count($stat_denominasi) ?></span>
        </div>
        <div class="d-flex flex-wrap gap-2" style="max-height:100px;overflow-y:auto;padding-right:4px;">
            <?php foreach(array_slice($stat_denominasi, 0, 15) as $d): ?>
                <span style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:6px 14px;font-size:11px;color:rgba(255,255,255,0.6);white-space:nowrap;display:flex;align-items:center;gap:6px;transition:0.2s;cursor:default;" onmouseover="this.style.background='rgba(59,130,246,0.15)';this.style.borderColor='rgba(59,130,246,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.borderColor='rgba(255,255,255,0.06)'">
                    <span style="width:6px;height:6px;border-radius:50%;background:#3b82f6;"></span>
                    <?= h($d['denominasi']) ?>
                    <span style="color:#60a5fa;font-weight:700;"><?= number_format((int)$d['jumlah'], 0, ',', '.') ?></span>
                </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- LOGIN CARD -->
    <div class="glass-card p-4 p-md-5 mb-5" data-aos="zoom-in" data-aos-delay="200">
        <div class="text-center mb-4">
            <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,rgba(59,130,246,0.2),rgba(139,92,246,0.1));border:2px solid rgba(59,130,246,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-user-shield" style="color:#60a5fa;font-size:28px;"></i>
            </div>
            <h4 class="fw-bold mb-1" style="font-size:22px;">Masuk ke Sistem</h4>
            <p style="color:rgba(255,255,255,0.4);font-size:13px;">Login untuk staff dan peserta terdaftar</p>
        </div>

        <form action="auth/auth.php" method="POST" id="loginForm">
            <?= csrf_field() ?>
            <?= captcha_html() ?>
            <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;height:0;width:0;">

            <?php if($alert_msg): ?>
            <div class="alert py-3 d-flex align-items-center gap-3 mb-4" style="font-size:13px;border-radius:14px;background:<?= $alert_type === 'danger' ? 'rgba(239,68,68,0.1)' : 'rgba(251,191,36,0.1)' ?>;border:1px solid <?= $alert_type === 'danger' ? 'rgba(239,68,68,0.2)' : 'rgba(251,191,36,0.2)' ?>;color:<?= $alert_type === 'danger' ? '#fca5a5' : '#fde68a' ?>;">
                <i class="fas <?= $alert_type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle' ?>" style="font-size:18px;"></i>
                <?= h($alert_msg) ?>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.7);display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-id-card" style="color:#60a5fa;"></i> NIK
                    <span style="font-size:11px;font-weight:400;color:rgba(255,255,255,0.3);">(akun pengguna)</span>
                </label>
                <div class="input-group-custom">
                    <input type="text" name="nik" class="form-control custom-input" placeholder="Masukkan 16 digit NIK" maxlength="16" inputmode="numeric" style="padding-left:48px;" <?= $lock_active ? 'disabled' : '' ?>>
                    <i class="fas fa-id-card input-icon"></i>
                    <span class="input-counter">16 digit</span>
                </div>
            </div>

            <div class="divider"></div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.7);display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-user" style="color:#8b5cf6;"></i> Username
                </label>
                <div class="input-group-custom">
                    <input type="text" name="username" class="form-control custom-input" placeholder="Masukkan username" autocomplete="username" style="padding-left:48px;" <?= $lock_active ? 'disabled' : '' ?> required>
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.7);display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-lock" style="color:#f59e0b;"></i> Password
                </label>
                <div class="input-group-custom">
                    <input type="password" name="password" id="loginPassword" class="form-control custom-input" placeholder="Masukkan password" autocomplete="current-password" style="padding-left:48px;padding-right:48px;" <?= $lock_active ? 'disabled' : '' ?> required>
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" onclick="togglePassword('loginPassword', this)" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,0.3);cursor:pointer;font-size:18px;transition:0.2s;" onmouseover="this.style.color='rgba(255,255,255,0.7)'" onmouseout="this.style.color='rgba(255,255,255,0.3)'">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <?php if($need_captcha && !$lock_active): ?>
            <div class="mb-4 p-3" style="background:rgba(251,191,36,0.05);border:1px solid rgba(251,191,36,0.15);border-radius:12px;">
                <label class="form-label" style="font-size:13px;font-weight:600;color:#fbbf24;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-puzzle-piece"></i> Verifikasi Keamanan
                </label>
                <div class="d-flex gap-3 align-items-center">
                    <?= captcha_question_html() ?>
                    <input type="text" name="captcha_answer" class="form-control custom-input" placeholder="Jawaban" maxlength="2" inputmode="numeric" style="width:100px;height:44px;" required>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" name="login" class="btn btn-primary-custom w-100 text-white mb-3" <?= $lock_active ? 'disabled' : '' ?>>
                <?php if($lock_active): ?>
                    <i class="fas fa-lock"></i> TERKUNCI
                <?php else: ?>
                    <i class="fas fa-sign-in-alt"></i> MASUK
                <?php endif; ?>
            </button>
        </form>

        <div class="text-center" style="font-size:13px;color:rgba(255,255,255,0.4);">
            Belum punya akun?
            <a href="register.php" style="color:#60a5fa;font-weight:600;text-decoration:none;transition:0.2s;" onmouseover="this.style.color='#93c5fd'" onmouseout="this.style.color='#60a5fa'">Daftar di sini <i class="fas fa-arrow-right" style="font-size:11px;"></i></a>
        </div>
    </div>

    <!-- DASHBOARD MINI -->
    <div class="glass-card p-4 p-md-5 mb-5 glow-purple" data-aos="fade-up" data-aos-delay="250">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(239,68,68,0.2),rgba(168,85,247,0.1));display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-users" style="color:#f87171;font-size:20px;"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="font-size:16px;">Status Peserta</h5>
                    <small style="color:rgba(255,255,255,0.35);font-size:12px;">Rekap kepesertaan & klaim</small>
                </div>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(239,68,68,0.12);color:#f87171;"><i class="fas fa-heart-broken"></i></div>
                    <div class="stat-value" style="color:#f87171;"><?= $stat_meninggal_total ?></div>
                    <div class="stat-label">Total Meninggal</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(251,191,36,0.12);color:#fbbf24;"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-value" style="color:#fbbf24;"><?= $stat_meninggal_tahun ?></div>
                    <div class="stat-label">Tahun Ini</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(96,165,250,0.12);color:#60a5fa;"><i class="fas fa-clock"></i></div>
                    <div class="stat-value" style="color:#60a5fa;"><?= $stat_meninggal_bulan ?></div>
                    <div class="stat-label">Bulan Ini</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(52,211,153,0.12);color:#34d399;"><i class="fas fa-shield-alt"></i></div>
                    <div class="stat-value" style="color:#34d399;"><?= $stat_jkk_total ?></div>
                    <div class="stat-label">Total JKK</div>
                </div>
            </div>
            <div class="col">
                <div class="stat-card">
                    <div class="stat-icon" style="background:rgba(168,85,247,0.12);color:#a855f7;"><i class="fas fa-heart"></i></div>
                    <div class="stat-value" style="color:#a855f7;"><?= $stat_jkm_total ?></div>
                    <div class="stat-label">Total JKM</div>
                </div>
            </div>
        </div>

        <?php if(count($data_meninggal_jkm) > 0 || count($data_meninggal_jkk) > 0): ?>
            <!-- JKM Section -->
            <div class="data-section">
                <div class="section-header">
                    <span class="section-dot" style="background:#f87171;"></span>
                    <span class="section-title">JKM (Jaminan Kematian)</span>
                    <span class="section-count"><?= count($data_meninggal_jkm) ?></span>
                </div>
                <?php foreach($data_meninggal_jkm as $m): ?>
                <div class="data-list-row">
                    <div class="row-indicator" style="background:linear-gradient(180deg,#f87171,#ef4444);"></div>
                    <div class="row-content">
                        <div class="row-main">
                            <span class="row-name"><?= h(sensor_nama_ringkas($m['nama_lengkap'])) ?></span>
                            <span class="row-badge badge-jkm"><?= h($m['tipe_program']) ?></span>
                        </div>
                        <div class="row-meta">
                            <span><?= h($m['jenis_kelamin']) ?></span>
                            <span class="meta-dot">·</span>
                            <span><?= h($m['kota']) ?: '-' ?></span>
                            <span class="meta-dot">·</span>
                            <span><?= !empty($m['tanggal_meninggal']) ? date('d M Y', strtotime($m['tanggal_meninggal'])) : '-' ?></span>
                        </div>
                    </div>
                    <span class="status-badge status-<?= $m['status_klaim'] ?? 'belum' ?>">
                        <i class="fas fa-circle" style="font-size:6px;"></i>
                        <?= h(klaim_label($m['status_klaim'] ?? 'belum')) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- JKK Section -->
            <div class="data-section">
                <div class="section-header">
                    <span class="section-dot" style="background:#34d399;"></span>
                    <span class="section-title">JKK (Jaminan Kecelakaan Kerja)</span>
                    <span class="section-count"><?= count($data_meninggal_jkk) ?></span>
                </div>
                <?php if(count($data_meninggal_jkk) > 0): ?>
                <?php foreach($data_meninggal_jkk as $m): ?>
                <div class="data-list-row">
                    <div class="row-indicator" style="background:linear-gradient(180deg,#34d399,#10b981);"></div>
                    <div class="row-content">
                        <div class="row-main">
                            <span class="row-name"><?= h(sensor_nama_ringkas($m['nama_lengkap'])) ?></span>
                            <span class="row-badge badge-jkk"><?= h($m['tipe_program']) ?></span>
                        </div>
                        <div class="row-meta">
                            <span><?= h($m['jenis_kelamin']) ?></span>
                            <span class="meta-dot">·</span>
                            <span><?= h($m['kota']) ?: '-' ?></span>
                            <span class="meta-dot">·</span>
                            <span><?= !empty($m['tanggal_meninggal']) ? date('d M Y', strtotime($m['tanggal_meninggal'])) : '-' ?></span>
                        </div>
                    </div>
                    <span class="status-badge status-<?= $m['status_klaim'] ?? 'belum' ?>">
                        <i class="fas fa-circle" style="font-size:6px;"></i>
                        <?= h(klaim_label($m['status_klaim'] ?? 'belum')) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-shield-alt" style="font-size:28px;color:rgba(255,255,255,0.2);"></i>
                    </div>
                    <p style="color:rgba(255,255,255,0.3);font-size:13px;margin:0;">Belum ada data JKK</p>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-inbox" style="font-size:28px;color:rgba(255,255,255,0.2);"></i>
            </div>
            <h6 style="color:rgba(255,255,255,0.5);font-size:15px;margin-top:12px;">Belum ada data kematian</h6>
            <p style="color:rgba(255,255,255,0.3);font-size:12px;margin-top:4px;">Statistik otomatis tampil saat data diinput sistem</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <div class="text-center mt-4 pb-4" data-aos="fade-up">
        <div class="divider"></div>
        <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
            <div style="width:8px;height:8px;border-radius:50%;background:#10b981;animation:pulse 2s infinite;"></div>
            <small style="color:rgba(255,255,255,0.4);font-size:12px;">Sistem Aktif</small>
        </div>
        <small style="color:rgba(255,255,255,0.25);font-size:11px;">
            &copy; <?= date('Y') ?> PERKASA — Dinas Ketenagakerjaan Provinsi Sulawesi Utara
        </small>
    </div>

</div>
</div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({once:true, duration:800});

// Particle Background
const canvas = document.getElementById('particles-canvas');
const ctx = canvas.getContext('2d');
let particles = [];
const particleCount = 50;

function resizeCanvas(){
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
resizeCanvas();
window.addEventListener('resize', resizeCanvas);

class Particle {
    constructor(){
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.size = Math.random() * 2 + 0.5;
        this.speedX = (Math.random() - 0.5) * 0.5;
        this.speedY = (Math.random() - 0.5) * 0.5;
        this.opacity = Math.random() * 0.5 + 0.1;
    }
    update(){
        this.x += this.speedX;
        this.y += this.speedY;
        if(this.x < 0 || this.x > canvas.width) this.speedX *= -1;
        if(this.y < 0 || this.y > canvas.height) this.speedY *= -1;
    }
    draw(){
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(59, 130, 246, ${this.opacity})`;
        ctx.fill();
    }
}

for(let i = 0; i < particleCount; i++){
    particles.push(new Particle());
}

function connectParticles(){
    for(let i = 0; i < particles.length; i++){
        for(let j = i + 1; j < particles.length; j++){
            const dx = particles[i].x - particles[j].x;
            const dy = particles[i].y - particles[j].y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if(dist < 150){
                ctx.beginPath();
                ctx.strokeStyle = `rgba(59, 130, 246, ${0.1 * (1 - dist/150)})`;
                ctx.lineWidth = 0.5;
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.stroke();
            }
        }
    }
}

function animateParticles(){
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => { p.update(); p.draw(); });
    connectParticles();
    requestAnimationFrame(animateParticles);
}
animateParticles();

// Password Toggle
function togglePassword(inputId, btn){
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if(input.type === 'password'){
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// NIK Input
document.querySelector('input[name="nik"]')?.addEventListener('input', function(){
    this.value = this.value.replace(/\D/g, '').slice(0, 16);
    const counter = document.querySelector('.input-counter');
    if(counter) counter.textContent = this.value.length + '/16';
});

// Form Loading
document.getElementById('loginForm')?.addEventListener('submit', function(){
    const btn = this.querySelector('.btn-primary-custom');
    if(btn){
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
        btn.disabled = true;
    }
});

// Animate counters on scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if(entry.isIntersecting){
            entry.target.querySelectorAll('.counter').forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target')) || 0;
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if(current >= target){
                        counter.textContent = target.toLocaleString('id-ID');
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current).toLocaleString('id-ID');
                    }
                }, 30);
            });
        }
    });
}, {threshold: 0.5});

document.querySelectorAll('.glass-card').forEach(card => observer.observe(card));
</script>
</body>
</html>
