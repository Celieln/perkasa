<?php
include 'config.php';

if(!isset($_SESSION['login'])){
    header("Location: index.php");
    exit;
}

if(is_admin()){
    header("Location: dashboard.php");
    exit;
}

$user_nik = mysqli_real_escape_string($conn, $_SESSION['nik'] ?? '');
$my_peserta = mysqli_query($conn, "SELECT * FROM peserta WHERE nik='$user_nik' ORDER BY id DESC");
$my_total = $my_peserta ? mysqli_num_rows($my_peserta) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PERKASA Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
body{
background:linear-gradient(135deg,#061220,#081426,#102d63);
min-height:100vh;
overflow-x:hidden;
color:white;
position:relative;
}
.floating-circle{
position:fixed;
width:450px;height:450px;
background:rgba(37,99,235,0.08);
border-radius:50%;
filter:blur(80px);
z-index:-1;
animation:floatAnim 10s infinite alternate;
}
.circle1{top:-150px;left:-120px;}
.circle2{bottom:-150px;right:-100px;}
@keyframes floatAnim{
from{transform:translateY(0px);}
to{transform:translateY(40px);}
}
.custom-navbar{
background:rgba(255,255,255,0.05);
backdrop-filter:blur(18px);
border-bottom:1px solid rgba(255,255,255,0.08);
padding:14px 0;
position:sticky;
top:0;
z-index:9999;
}
.navbar-brand{
font-size:26px;font-weight:700;
display:flex;align-items:center;gap:12px;
color:white !important;
}
.brand-dot{
width:12px;height:12px;
background:#0ea5e9;
border-radius:50%;
box-shadow:0 0 15px #0ea5e9;
}
.navbar-nav{gap:10px;}
.nav-link{
color:white !important;font-weight:500;
padding:12px 18px !important;border-radius:14px;
transition:0.3s;position:relative;overflow:hidden;
}
.nav-link::before{
content:'';position:absolute;left:0;bottom:0;
width:0%;height:2px;background:#38bdf8;transition:0.3s;
}
.nav-link:hover{
background:rgba(255,255,255,0.08);
color:#38bdf8 !important;transform:translateY(-2px);
}
.nav-link:hover::before{width:100%;}
.nav-link.active{
background:rgba(59,130,246,0.18);color:#60a5fa !important;
}
.nav-link.active::before{width:100%;}
.logout-btn{
background:linear-gradient(135deg,#dc2626,#ef4444);
border:none;border-radius:14px;
padding:10px 18px;font-weight:600;
color:white !important;transition:0.3s;text-decoration:none;
}
.logout-btn:hover{
transform:translateY(-2px);
box-shadow:0 8px 25px rgba(239,68,68,0.35);
}
.hero-box{
background:linear-gradient(135deg,rgba(37,99,235,0.25),rgba(14,165,233,0.15));
border:1px solid rgba(255,255,255,0.08);
backdrop-filter:blur(20px);
border-radius:28px;padding:35px;position:relative;overflow:hidden;
}
.hero-box::before{
content:'';position:absolute;
width:300px;height:300px;
background:rgba(59,130,246,0.15);
border-radius:50%;top:-120px;right:-100px;
filter:blur(40px);
}
.glass-box{
background:rgba(255,255,255,0.08);
backdrop-filter:blur(15px);
border-radius:24px;
border:1px solid rgba(255,255,255,0.08);
padding:25px;
}
.info-box{
background:rgba(255,255,255,0.05);
padding:20px;border-radius:16px;
height:100%;transition:0.3s;
}
.info-box:hover{background:rgba(255,255,255,0.07);}
.info-box .label{font-size:13px;opacity:0.7;margin-bottom:6px;}
.info-box .value{font-size:17px;font-weight:600;word-break:break-word;}
.activity-item{
padding:16px;border-radius:16px;
background:rgba(255,255,255,0.04);
margin-bottom:12px;transition:0.3s;
}
.activity-item:hover{
background:rgba(255,255,255,0.08);
transform:translateX(5px);
}
.quick-btn{
height:100%;display:flex;align-items:center;
justify-content:center;flex-direction:column;gap:10px;
padding:25px;text-decoration:none;color:white;
background:rgba(255,255,255,0.05);border-radius:20px;
transition:0.3s;border:1px solid rgba(255,255,255,0.08);
}
.quick-btn:hover{
transform:translateY(-5px);
background:rgba(255,255,255,0.08);
}
.navbar-nav{flex-wrap:wrap;}
.mobile-divider{display:none;}
@media(max-width:991px){
.navbar-collapse{
max-height:70vh;overflow-y:auto;
margin-top:20px;padding:20px;
background:rgba(255,255,255,0.05);
border-radius:20px;border:1px solid rgba(255,255,255,0.08);
}
.mobile-divider{
display:block;height:1px;
background:rgba(255,255,255,0.08);margin:15px 0;
}
}
@media(max-width:768px){
.hero-box{padding:25px;}
}
@media(max-width:576px){
.container{padding-left:12px;padding-right:12px;}
.glass-box{padding:18px;}
.hero-box{padding:20px;border-radius:20px;}
.hero-box h1{font-size:20px;}
.navbar-brand{font-size:20px;}
.info-box{padding:15px;}
.info-box .value{font-size:15px;}
.quick-btn{padding:18px 12px;}
.quick-btn h5{font-size:14px;}
.floating-circle{width:250px;height:250px;}
.activity-item{padding:12px;}
}
}
</style>
</head>
<body>

<div class="floating-circle circle1"></div>
<div class="floating-circle circle2"></div>

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar">
<div class="container">
<a class="navbar-brand" href="user_dashboard.php">
<div class="brand-dot"></div>
PERKASA
</a>
<button class="navbar-toggler" type="button"
data-bs-toggle="collapse" data-bs-target="#navbarNav">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navbarNav">
<ul class="navbar-nav ms-auto align-items-lg-center">
<li class="nav-item">
<a class="nav-link active" href="user_dashboard.php">Dashboard</a>
</li>
<li class="nav-item">
<a class="nav-link" href="tracking.php">🔍 Tracking</a>
</li>
<li class="nav-item">
<a class="nav-link" href="feedback.php">💬 Feedback</a>
</li>
</ul>
</div>
<a class="logout-btn ms-2" href="auth/logout.php" style="flex-shrink:0;">Logout</a>
</div>
</nav>

<div class="container py-5">

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="hero-box mb-5" data-aos="fade-down">
<div class="text-center">
<h1 class="fw-bold mb-3">Selamat Datang, <?= htmlspecialchars($_SESSION['username'] ?? '') ?></h1>
<p class="opacity-75 mb-0">Dashboard Pemantauan Klaim PERKASA Sulawesi Utara</p>
</div>
</div>

<div class="glass-box mb-5" data-aos="fade-up">
<h4 class="fw-bold mb-4">👤 Akun Saya</h4>
<div class="row g-4">
<div class="col-md-6">
<div class="info-box">
<div class="label">Username (Peserta)</div>
<div class="value"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></div>
</div>
</div>
<div class="col-md-6">
<div class="info-box">
<div class="label">NIK Peserta</div>
<div class="value"><?= htmlspecialchars($user_nik ?: '-') ?></div>
</div>
</div>
<div class="col-md-6">
<div class="info-box">
<div class="label">Nama Lengkap (Ahli Waris)</div>
<div class="value"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></div>
</div>
</div>
<div class="col-md-6">
<div class="info-box">
<div class="label">Total Data Peserta</div>
<div class="value text-primary"><?= $my_total ?></div>
</div>
</div>
</div>
</div>

<div class="glass-box mb-5" data-aos="fade-up" data-aos-delay="100">
<h4 class="fw-bold mb-4">🔍 Data Pengurusan Saya</h4>

<?php if($my_total > 0): ?>
<?php while($row = mysqli_fetch_assoc($my_peserta)): ?>
<div class="activity-item">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
<div>
<div class="fw-bold"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
<div class="small opacity-75">
Program: <?= h($row['tipe_program']) ?> —
<?= rekom_badge($row['status_rekom'] ?? 'belum') ?>
<?= klaim_badge($row['status_klaim'] ?? 'belum') ?>
</div>
<?php if(!empty($row['tracking_code'])): ?>
<div class="small mt-1">
<span class="badge bg-primary" style="letter-spacing:1px;font-size:12px;">
Kode: <?= htmlspecialchars($row['tracking_code']) ?>
</span>
</div>
<?php endif; ?>
</div>
<div>
<?php if(!empty($row['tracking_code'])): ?>
<a href="tracking.php?code=<?= urlencode($row['tracking_code']) ?>" target="_blank" class="btn btn-outline-info btn-sm" style="height:auto;padding:8px 16px;">
Lihat Progress →
</a>
<?php endif; ?>
</div>
</div>
</div>
<?php endwhile; ?>
<?php else: ?>
<div class="text-center py-4 opacity-75">
<p class="mb-2">Belum ada data pengurusan terdaftar atas NIK Anda.</p>
<p class="small">Hubungi petugas PERKASA untuk informasi lebih lanjut.</p>
</div>
<?php endif; ?>
</div>

<div class="row g-4" data-aos="fade-up" data-aos-delay="200">
<div class="col-md-6">
<a href="tracking.php" class="quick-btn" style="text-decoration:none;color:white;">
<h5 class="mb-2">🔍 Lacak Pengurusan</h5>
<small class="opacity-75">Cek progress klaim dengan kode tracking</small>
</a>
</div>
<div class="col-md-6">
<a href="feedback.php" class="quick-btn" style="text-decoration:none;color:white;">
<h5 class="mb-2">💬 Kirim Feedback</h5>
<small class="opacity-75">Beri masukan untuk layanan PERKASA</small>
</a>
</div>
</div>

</div>
</div>

</div>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ once: true });</script>
</body>
</html>
