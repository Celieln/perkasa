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

$q = mysqli_query($conn, "SELECT nama_lengkap, jenis_kelamin, kota, tanggal_meninggal, tipe_program, status_klaim
    FROM peserta WHERE tanggal_meninggal IS NOT NULL
    ORDER BY tanggal_meninggal DESC, id DESC LIMIT 5");
if($q){
    while($row = mysqli_fetch_assoc($q)){
        $data_meninggal[] = $row;
    }
}

// Sensor nama parsial (nama tengah diganti •••) demi perlindungan data
function sensor_nama_ringkas($nama){
    $nama = trim((string)$nama);
    $len  = mb_strlen($nama, 'UTF-8');
    if($len <= 3) return $nama;
    // simpan huruf awal & akhir, sensor bagian tengah
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
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{
    background:linear-gradient(135deg,#061220,#081426,#102d63);
    min-height:100vh;color:white;overflow-x:hidden;
    display:flex;align-items:center;padding:40px 0;
}
.floating-circle{
    position:fixed;width:400px;height:400px;
    background:rgba(37,99,235,0.08);border-radius:50%;
    filter:blur(80px);z-index:-1;
    animation:floatAnim 10s infinite alternate;
}
.circle1{top:-120px;left:-100px;}
.circle2{bottom:-120px;right:-80px;}
@keyframes floatAnim{from{transform:translateY(0)}to{transform:translateY(40px)}}
.glass-card{
    background:rgba(255,255,255,0.07);
    backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:24px;
    box-shadow:0 25px 50px rgba(0,0,0,0.4);
}
.feature-card{
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:18px;
    padding:24px 20px;
    text-decoration:none;
    color:white;
    transition:all 0.3s ease;
    display:block;
    height:100%;
    position:relative;
    overflow:hidden;
}
.feature-card:hover{
    transform:translateY(-6px);
    background:rgba(255,255,255,0.09);
    border-color:rgba(59,130,246,0.4);
    color:white;
}
.feature-icon{
    width:52px;height:52px;
    border-radius:16px;
    display:flex;align-items:center;justify-content:center;
    font-size:26px;margin-bottom:14px;
}
.feature-card .title{font-size:16px;font-weight:700;margin-bottom:4px;}
.feature-card .desc{font-size:13px;opacity:0.6;}
.custom-input{
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.1);
    color:white;border-radius:12px;
    height:50px;padding:0 16px;transition:all 0.3s;
}
.custom-input::placeholder{color:rgba(255,255,255,0.35);}
.custom-input:focus{
    background:rgba(255,255,255,0.13)!important;
    border-color:#3b82f6!important;
    box-shadow:0 0 0 3px rgba(59,130,246,0.25)!important;
    color:white!important;
}
.custom-label{font-size:13px;font-weight:600;color:rgba(255,255,255,0.85);margin-bottom:8px;display:block;}
.custom-btn{
    height:50px;border-radius:12px;font-weight:700;font-size:15px;letter-spacing:0.5px;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    border:none;transition:all 0.3s;
    box-shadow:0 4px 15px rgba(37,99,235,0.4);
}
.custom-btn:hover{
    background:linear-gradient(135deg,#1d4ed8,#1e40af);
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(37,99,235,0.5);
}
.divider{height:1px;background:rgba(255,255,255,0.08);margin:18px 0;}
/* ===== Custom scrollbar ===== */
::-webkit-scrollbar{width:8px;height:8px;}
::-webkit-scrollbar-track{background:rgba(255,255,255,0.05);border-radius:10px;}
::-webkit-scrollbar-thumb{
    background:linear-gradient(180deg,#2563eb,#1d4ed8);
    border-radius:10px;
    border:2px solid rgba(255,255,255,0.08);
}
::-webkit-scrollbar-thumb:hover{background:linear-gradient(180deg,#1d4ed8,#1e40af);}
::-webkit-scrollbar-corner{background:transparent;}
.denom-list::-webkit-scrollbar{width:6px;}
.denom-list::-webkit-scrollbar-track{background:rgba(255,255,255,0.06);border-radius:10px;}
.denom-list::-webkit-scrollbar-thumb{background:linear-gradient(180deg,#3b82f6,#2563eb);border-radius:10px;border:none;}
.denom-list::-webkit-scrollbar-thumb:hover{background:#2563eb;}
/* ===== Dashboard Mini Kematian ===== */
.death-stat{
    display:flex;align-items:center;gap:12px;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:14px;padding:14px 16px;height:100%;
}
.death-stat .icon{
    width:40px;height:40px;border-radius:12px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;font-size:18px;
}
.death-stat .val{font-size:22px;font-weight:800;line-height:1.1;color:#f87171;}
.death-stat .lbl{font-size:11px;opacity:0.55;}
.death-list-row{
    display:flex;align-items:center;gap:10px;
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.06);
    border-radius:12px;padding:10px 14px;margin-bottom:8px;
    transition:0.2s;
}
.death-list-row:hover{background:rgba(255,255,255,0.07);}
.death-censor{color:#f87171;font-weight:700;letter-spacing:1px;}
.death-dot{
    width:8px;height:8px;border-radius:50%;background:#f87171;flex-shrink:0;
    box-shadow:0 0 8px rgba(248,113,113,0.7);
}
@media(max-width:768px){
    .death-stat{padding:12px;}
    .death-stat .val{font-size:18px;}
}
@media(max-width:768px){
    body{padding:20px 0;}
    .feature-card{padding:18px 16px;}
    .feature-icon{width:44px;height:44px;font-size:20px;margin-bottom:10px;}
    .feature-card .title{font-size:14px;}
    .feature-card .desc{font-size:12px;}
}
@media(max-width:576px){
    .row.g-3{gap:10px;}
}
</style>
</head>
<body>

<div class="floating-circle circle1"></div>
<div class="floating-circle circle2"></div>

<div class="container">
<div class="row justify-content-center">

<div class="col-lg-8 col-xl-7">

    <!-- HEADER -->
    <div class="text-center mb-4" data-aos="fade-down">
        <h1 class="fw-bold mb-1" style="font-size:30px;letter-spacing:2px;">PERKASA</h1>
        <p style="color:rgba(255,255,255,0.5);font-size:14px;">Sistem Manajemen Klaim JKK & JKM Sulawesi Utara</p>
    </div>

    <!-- FEATURE CARDS -->
    <div class="row g-3 mb-4" data-aos="fade-up" data-aos-delay="50">
        <div class="col-md-6">
            <a href="cek_peserta.php" class="feature-card">
                <div class="feature-icon" style="background:rgba(52,211,153,0.15);">
                    <span>📋</span>
                </div>
                <div class="title">Cek Kepersertaan</div>
                <div class="desc">Periksa status data kepesertaan JKK/JKM Anda</div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="tracking.php" class="feature-card">
                <div class="feature-icon" style="background:rgba(96,165,250,0.15);">
                    <span>🔍</span>
                </div>
                <div class="title">Lacak Pengurusan</div>
                <div class="desc">Pantau progress pengurusan klaim dengan kode tracking</div>
            </a>
        </div>
    </div>

    <!-- MINI STATISTIK -->
    <div class="glass-card p-4 mb-4" data-aos="fade-up" data-aos-delay="75" style="border-radius:18px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="fw-bold mb-0" style="font-size:15px;">📊 Statistik Data</h5>
                <small style="color:rgba(255,255,255,0.45);font-size:12px;">Ringkasan singkat kepesertaan</small>
            </div>
            <div class="text-end">
                <div style="font-size:26px;font-weight:800;line-height:1;color:#60a5fa;"><?= number_format($stat_total, 0, ',', '.') ?></div>
                <small style="color:rgba(255,255,255,0.45);font-size:12px;">Total Terdaftar</small>
            </div>
        </div>
        <div class="divider" style="margin:12px 0;"></div>
        <small style="color:rgba(255,255,255,0.45);font-size:12px;font-weight:600;">
            Denominasi (<?= count($stat_denominasi) ?>) :
        </small>
        <div class="d-flex flex-wrap gap-2 mt-2 denom-list" style="max-height:120px;overflow-y:auto;padding-right:4px;">
            <?php foreach($stat_denominasi as $d): ?>
                <span style="background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.25);border-radius:20px;padding:3px 12px;font-size:11px;color:rgba(255,255,255,0.85);white-space:nowrap;">
                    <?= h($d['denominasi']) ?> · <?= number_format((int)$d['jumlah'], 0, ',', '.') ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- LOGIN CARD -->
    <div class="glass-card p-4 p-md-5" data-aos="zoom-in" data-aos-delay="100">
        <div class="text-center mb-4">
            <h4 class="fw-bold mb-1">Masuk ke Sistem</h4>
            <p style="color:rgba(255,255,255,0.45);font-size:13px;">Login untuk staff dan peserta terdaftar</p>
        </div>

        <form action="auth/auth.php" method="POST">
            <?= csrf_field() ?>
            <?= captcha_html() ?>

            <!-- Honeypot (jangan dihapus — bot akan isi) -->
            <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;height:0;width:0;">

            <?php if($alert_msg): ?>
            <div class="alert alert-<?= $alert_type ?> py-2" style="font-size:13px;border-radius:10px;" role="alert">
                <?= h($alert_msg) ?>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="custom-label">NIK
                    <span style="font-size:11px;font-weight:400;color:rgba(255,255,255,0.4);margin-left:6px;">(hanya untuk akun pengguna biasa)</span>
                </label>
                <input type="text"
                       name="nik"
                       class="form-control custom-input"
                       placeholder="Masukkan NIK — kosongkan jika Admin"
                       maxlength="16"
                       inputmode="numeric"
                       <?= $lock_active ? 'disabled' : '' ?>>
            </div>

            <div class="divider"></div>

            <div class="mb-3">
                <label class="custom-label">Username</label>
                <input type="text"
                       name="username"
                       class="form-control custom-input"
                       placeholder="Masukkan username"
                       autocomplete="username"
                       <?= $lock_active ? 'disabled' : '' ?>
                       required>
            </div>

            <div class="mb-3">
                <label class="custom-label">Password</label>
                <input type="password"
                       name="password"
                       class="form-control custom-input"
                       placeholder="Masukkan password"
                       autocomplete="current-password"
                       <?= $lock_active ? 'disabled' : '' ?>
                       required>
            </div>

            <?php if($need_captcha && !$lock_active): ?>
            <div class="mb-4">
                <label class="custom-label">Verifikasi Keamanan</label>
                <div class="d-flex gap-2 align-items-center">
                    <?= captcha_question_html() ?>
                    <input type="text"
                           name="captcha_answer"
                           class="form-control custom-input"
                           placeholder="Jawaban"
                           maxlength="2"
                           inputmode="numeric"
                           style="width:120px;"
                           required>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit"
                    name="login"
                    class="btn custom-btn w-100 text-white mb-3"
                    <?= $lock_active ? 'disabled' : '' ?>>
                <?= $lock_active ? 'TERKUNCI SEMENTARA' : 'LOGIN' ?>
            </button>
        </form>

        <div class="text-center" style="font-size:13px;color:rgba(255,255,255,0.5);">
            Belum punya akun?
            <a href="register.php"
               style="color:#60a5fa;font-weight:600;text-decoration:none;"
               onmouseover="this.style.color='#93c5fd'"
               onmouseout="this.style.color='#60a5fa'">
                Daftar di sini
            </a>
        </div>
    </div>

    <!-- DASHBOARD MINI: STATUS KEMATIAN -->
    <div class="glass-card p-4" data-aos="zoom-in" data-aos-delay="150" style="border-radius:18px;margin-top:20px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="fw-bold mb-0" style="font-size:15px;">☸️ Status Kematian Peserta</h5>
                <small style="color:rgba(255,255,255,0.45);font-size:12px;">Rekap kepesertaan almarhum dari tanggal meninggal</small>
            </div>
            <span style="background:rgba(248,113,113,0.15);border:1px solid rgba(248,113,113,0.3);border-radius:20px;padding:3px 12px;font-size:11px;color:#f87171;white-space:nowrap;"><?= number_format($stat_meninggal_total,0,',','.') ?> meninggal</span>
        </div>

        <div class="divider" style="margin:12px 0;"></div>

        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="death-stat">
                    <div class="icon" style="background:rgba(248,113,113,0.15);color:#f87171;"><span>☠️</span></div>
                    <div>
                        <div class="val"><?= number_format($stat_meninggal_total,0,',','.') ?></div>
                        <div class="lbl">Total Meninggal</div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="death-stat">
                    <div class="icon" style="background:rgba(251,191,36,0.15);color:#fbbf24;"><span>📅</span></div>
                    <div>
                        <div class="val" style="color:#fbbf24;"><?= number_format($stat_meninggal_tahun,0,',','.') ?></div>
                        <div class="lbl">Tahun Ini</div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="death-stat">
                    <div class="icon" style="background:rgba(96,165,250,0.15);color:#60a5fa;"><span>🗓️</span></div>
                    <div>
                        <div class="val" style="color:#60a5fa;"><?= number_format($stat_meninggal_bulan,0,',','.') ?></div>
                        <div class="lbl">Bulan Ini</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if(count($data_meninggal) > 0): ?>
        <small style="color:rgba(255,255,255,0.45);font-size:12px;font-weight:600;">5 Meninggal Terbaru (nama disensor):</small>
        <div class="mt-2">
            <?php foreach($data_meninggal as $m): ?>
                <div class="death-list-row">
                    <span class="death-dot"></span>
                    <div class="flex-grow-1">
                        <div style="font-size:13px;font-weight:600;"><span class="death-censor"><?= h(sensor_nama_ringkas($m['nama_lengkap'])) ?></span></div>
                        <div style="font-size:11px;opacity:0.55;">
                            <?= h($m['jenis_kelamin']) ?> · <?= h($m['kota']) ?: '-' ?> · <?= !empty($m['tanggal_meninggal']) ? date('d-m-Y', strtotime($m['tanggal_meninggal'])) : '-' ?>
                        </div>
                    </div>
                    <span style="background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.25);border-radius:12px;padding:2px 10px;font-size:10px;color:#34d399;white-space:nowrap;"><?= h($m['tipe_program']) ?> · <?= h(klaim_label($m['status_klaim'] ?? 'belum')) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-4" style="opacity:0.5;">
            <div style="font-size:30px;">🕊️</div>
            <div style="font-size:13px;margin-top:6px;">Belum ada data kematian (tanggal_meninggal kosong).</div>
            <div style="font-size:11px;margin-top:2px;">Statistik otomatis tampil saat data diinput sistem.</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <div class="text-center mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,0.06);">
        <small style="color:rgba(255,255,255,0.3);font-size:12px;">
            &copy; <?= date('Y') ?> PERKASA — Sistem Manajemen Klaim JKK & JKM
        </small>
    </div>

</div>
</div>
</div>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({once:true});
document.querySelector('input[name="nik"]').addEventListener('input', function(){
    this.value = this.value.replace(/\D/g, '').slice(0, 16);
});
</script>
</body>
</html>
