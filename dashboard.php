<?php

include 'config.php';


// Login check bypassed for UI preview

// Admin check bypassed for UI preview

$counts = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) as total,
        SUM(IFNULL(tipe_program='JKK',0)) as jkk,
        SUM(IFNULL(tipe_program='JKM',0)) as jkm,
        SUM(IFNULL(status_klaim IN ('proses_claim','sudah_claim','sudah_cair'),0)) as claim,
        SUM(IFNULL(status_klaim IN ('proses_cair','sudah_cair','sudah_claim'),0)) as cair
    FROM peserta"
));
$total = $counts['total'];
$jkk   = $counts['jkk'];
$jkm   = $counts['jkm'];
$claim = $counts['claim'];
$cair  = $counts['cair'];

$recent = mysqli_query($conn,
"SELECT * FROM peserta
ORDER BY id DESC
LIMIT 5");

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>PERKASA Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link href="https://unpkg.com/aos@2.3.4/dist/aos.css"
rel="stylesheet">

<style>

body{

background:
linear-gradient(
135deg,
#061220,
#081426,
#102d63
);

min-height:100vh;

overflow-x:hidden;

color:white;

position:relative;

}

/* FLOATING BACKGROUND */

.floating-circle{

position:fixed;

width:450px;
height:450px;

background:
rgba(37,99,235,0.08);

border-radius:50%;

filter:blur(80px);

z-index:-1;

animation:
floatAnim 10s infinite alternate;

}

.circle1{

top:-150px;
left:-120px;

}

.circle2{

bottom:-150px;
right:-100px;

}

@keyframes floatAnim{

from{
transform:translateY(0px);
}

to{
transform:translateY(40px);
}

}

/* NAVBAR */

.custom-navbar{

background:
rgba(255,255,255,0.05);

backdrop-filter:blur(18px);

border-bottom:
1px solid rgba(255,255,255,0.08);

padding:14px 0;

position:sticky;

top:0;

z-index:9999;

}

.navbar-brand{

font-size:26px;

font-weight:700;

display:flex;

align-items:center;

gap:12px;

color:white !important;

}

.brand-dot{

width:12px;
height:12px;

background:#0ea5e9;

border-radius:50%;

box-shadow:
0 0 15px #0ea5e9;

}

.navbar-nav{

gap:10px;

}

.nav-link{

color:white !important;

font-weight:500;

padding:12px 18px !important;

border-radius:14px;

transition:0.3s;

position:relative;

overflow:hidden;

}

.nav-link::before{

content:'';

position:absolute;

left:0;
bottom:0;

width:0%;

height:2px;

background:#38bdf8;

transition:0.3s;

}

.nav-link:hover{

background:
rgba(255,255,255,0.08);

color:#38bdf8 !important;

transform:translateY(-2px);

}

.nav-link:hover::before{

width:100%;

}

.nav-link.active{

background:
rgba(59,130,246,0.18);

color:#60a5fa !important;

}

.nav-link.active::before{

width:100%;

}

.logout-btn{

background:
linear-gradient(
135deg,
#dc2626,
#ef4444
);

border:none;

border-radius:14px;

padding:10px 18px;

font-weight:600;

color:white !important;

transition:0.3s;

text-decoration:none;

}

.logout-btn:hover{

transform:translateY(-2px);

box-shadow:
0 8px 25px rgba(239,68,68,0.35);

}

/* HERO */

.hero-box{

background:
linear-gradient(
135deg,
rgba(37,99,235,0.25),
rgba(14,165,233,0.15)
);

border:
1px solid rgba(255,255,255,0.08);

backdrop-filter:blur(20px);

border-radius:28px;

padding:35px;

position:relative;

overflow:hidden;

}

.hero-box::before{

content:'';

position:absolute;

width:300px;
height:300px;

background:
rgba(59,130,246,0.15);

border-radius:50%;

top:-120px;
right:-100px;

filter:blur(40px);

}

.live-clock{

font-size:15px;

opacity:0.8;

}

/* CARD */

.dashboard-card{

background:
rgba(255,255,255,0.08);

backdrop-filter:blur(15px);

border:
1px solid rgba(255,255,255,0.08);

border-radius:24px;

padding:25px;

height:100%;

transition:0.3s;

position:relative;

overflow:hidden;

}

.dashboard-card:hover{

transform:
translateY(-6px);

}

.card-title{

font-size:14px;

opacity:0.75;

margin-bottom:10px;

}

.card-value{

font-size:38px;

font-weight:bold;

}

/* QUICK */

.quick-btn{

height:100%;

display:flex;

align-items:center;

justify-content:center;

flex-direction:column;

gap:10px;

padding:25px;

text-decoration:none;

color:white;

background:
rgba(255,255,255,0.05);

border-radius:20px;

transition:0.3s;

border:
1px solid rgba(255,255,255,0.08);

}

.quick-btn:hover{

transform:
translateY(-5px);

background:
rgba(255,255,255,0.08);

}

/* GLASS */

.glass-box{

background:
rgba(255,255,255,0.08);

backdrop-filter:blur(15px);

border-radius:24px;

border:
1px solid rgba(255,255,255,0.08);

padding:25px;

height:100%;

}

/* ACTIVITY */

.activity-item{

padding:16px;

border-radius:16px;

background:
rgba(255,255,255,0.04);

margin-bottom:12px;

transition:0.3s;

}

.activity-item:hover{

background:
rgba(255,255,255,0.08);

transform:
translateX(5px);

}

.progress-modern{

height:12px;

border-radius:999px;

overflow:hidden;

background:
rgba(255,255,255,0.08);

}

.navbar-nav{gap:2px;}

.navbar .dropdown-menu{
background:rgba(15,23,42,0.95);
backdrop-filter:blur(16px);
border:1px solid rgba(255,255,255,0.08);
border-radius:16px;
padding:8px;
margin-top:10px;
box-shadow:0 20px 50px rgba(0,0,0,0.4);
}
.navbar .dropdown-menu .dropdown-item{
color:rgba(255,255,255,0.85);
border-radius:10px;
padding:10px 16px;
font-size:14px;
transition:0.2s;
}
.navbar .dropdown-menu .dropdown-item:hover{
background:rgba(59,130,246,0.2);
color:white;
transform:translateX(4px);
}

.mobile-divider{
display:none;
}

@media(max-width:991px){
.navbar-collapse{
max-height:70vh;
overflow-y:auto;
margin-top:20px;

padding:20px;

background:
rgba(255,255,255,0.05);

border-radius:20px;

border:
1px solid rgba(255,255,255,0.08);

}

.mobile-divider{

display:block;

height:1px;

background:
rgba(255,255,255,0.08);

margin:15px 0;

}

}

@media(max-width:768px){

.card-value{
font-size:28px;
}

.hero-box{
padding:25px;
}

}

@media(max-width:576px){
    .container{ padding-left:12px; padding-right:12px; }
    .dashboard-card{ padding:18px; }
    .dashboard-card .card-value{ font-size:24px; }
    .quick-btn{ padding:18px 12px; }
    .quick-btn h5{ font-size:14px; }
    .glass-box{ padding:18px; }
    .hero-box{ padding:20px; border-radius:20px; }
    .hero-box h1{ font-size:20px; }
    .navbar-brand{ font-size:20px; }
    .navbar .container{ padding-left:12px; padding-right:12px; }
    .floating-circle{ width:250px; height:250px; }
    .live-clock{ font-size:13px; }
    .activity-item{ padding:12px; }
    .activity-item .fw-bold{ font-size:14px; }
}
@media(max-width:991px){
}

/* User dashboard */
.info-box{
background:rgba(255,255,255,0.05);
padding:20px;
border-radius:16px;
height:100%;
transition:0.3s;
}
.info-box:hover{
background:rgba(255,255,255,0.07);
}
.info-box .label{
font-size:13px;
opacity:0.7;
margin-bottom:6px;
}
.info-box .value{
font-size:17px;
font-weight:600;
word-break:break-word;
}

</style>

</head>

<body>

<div class="floating-circle circle1"></div>
<div class="floating-circle circle2"></div>

<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar">

<div class="container">

<a class="navbar-brand"
href="dashboard.php">

<div class="brand-dot"></div>

PERKASA

</a>

<button class="navbar-toggler"
type="button"
data-bs-toggle="collapse"
data-bs-target="#navbarNav">

<span class="navbar-toggler-icon"></span>

</button>

<div class="collapse navbar-collapse"
id="navbarNav">

<ul class="navbar-nav ms-auto align-items-lg-center gap-1">

<li class="nav-item">
<a class="nav-link" href="dashboard.php">📊 Dashboard</a>
</li>

<?php if(is_admin()) : ?>

<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
📋 Data
</a>
<ul class="dropdown-menu dropdown-menu-dark">
<li><a class="dropdown-item" href="fitur/profile.php">Peserta</a></li>
<li><a class="dropdown-item" href="fitur/claims.php">Claims</a></li>
<li><a class="dropdown-item" href="fitur/pencairan.php">Pencairan</a></li>
</ul>
</li>

<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
🔧 Tools
</a>
<ul class="dropdown-menu dropdown-menu-dark">
<li><a class="dropdown-item" href="fitur/lokasi.php">Lokasi</a></li>
<li><a class="dropdown-item" href="fitur/laporan.php">Laporan</a></li>
<li><a class="dropdown-item" href="fitur/import/index.php">Import Excel</a></li>
<li><a class="dropdown-item" href="fitur/import/riwayat.php">Riwayat Import</a></li>
<li><a class="dropdown-item" href="fitur/sync_status.php">🔄 Sync Status JKM</a></li>
</ul>
</li>

<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
⚙️ Pengaturan
</a>
<ul class="dropdown-menu dropdown-menu-dark">
<li><a class="dropdown-item" href="fitur/users.php">Users</a></li>
<li><a class="dropdown-item position-relative" href="fitur/feedback_manage.php">
Feedback
<?php
$fb_pending = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM feedback WHERE status='pending'"));
if($fb_pending > 0): ?>
<span class="badge rounded-pill bg-danger ms-1" style="font-size:10px;"><?= $fb_pending ?></span>
<?php endif; ?>
</a></li>
</ul>
</li>

<?php else : ?>

<li class="nav-item">
<a class="nav-link" href="tracking.php">🔍 Tracking</a>
</li>
<li class="nav-item">
<a class="nav-link" href="feedback.php">💬 Feedback</a>
</li>

<?php endif; ?>

<li class="nav-item ms-lg-3">
<a class="logout-btn" href="auth/logout.php">Logout</a>
</li>

</ul>

</div>

</div>

</nav>

<!-- CONTENT -->

<div class="container py-5">

<!-- === ADMIN DASHBOARD === -->

<!-- HERO -->

<div class="hero-box mb-5">

<div class="d-flex
justify-content-between
align-items-center
flex-wrap
gap-4">

<div>

<h1 class="fw-bold mb-3">

Selamat Datang,
<?= h($_SESSION['nama']) ?>

</h1>

<p class="opacity-75 mb-0">

Monitoring Sistem PERKASA Sulawesi Utara

</p>

</div>

<div class="text-end">

<div class="live-clock"
id="liveClock">

Loading...

</div>

<h3 class="fw-bold mt-2">

<?= date('d M Y') ?>

</h3>

</div>

</div>

</div>

<!-- STATS -->

<div class="row g-4 mb-5">

<div class="col-lg-3 col-md-6">

<div class="dashboard-card"
data-aos="fade-up">

<div class="card-title">
Total JKK
</div>

<div class="card-value text-primary">
<?= $jkk ?>
</div>

</div>

</div>

<div class="col-lg-3 col-md-6">

<div class="dashboard-card"
data-aos="fade-up"
data-aos-delay="100">

<div class="card-title">
Total JKM
</div>

<div class="card-value text-success">
<?= $jkm ?>
</div>

</div>

</div>

<div class="col-lg-3 col-md-6">

<div class="dashboard-card"
data-aos="fade-up"
data-aos-delay="200">

<div class="card-title">
Sudah Claim
</div>

<div class="card-value text-warning">
<?= $claim ?>
</div>

</div>

</div>

<div class="col-lg-3 col-md-6">

<div class="dashboard-card"
data-aos="fade-up"
data-aos-delay="300">

<div class="card-title">
Sudah Cair
</div>

<div class="card-value text-info">
<?= $cair ?>
</div>

</div>

</div>

</div>

<!-- QUICK ACTION -->

<div class="row g-4 mb-5">

<?php if(is_admin()) : ?>

<div class="col-lg-3 col-6">

<a href="fitur/create_profile.php"
class="quick-btn">

<h5 class="mb-0">
Tambah Peserta
</h5>

<small class="opacity-75">
Create data baru
</small>

</a>

</div>

<?php endif; ?>

<?php if(is_superadmin()) :
$maintenance_on = is_maintenance($conn);
?>

<div class="col-lg-3 col-6">

<form method="POST" action="auth/toggle_maintenance.php" onsubmit="return confirm('<?= $maintenance_on ? 'Matikan mode pemeliharaan?' : 'Nyalakan mode pemeliharaan? User selain admin TIDAK AKAN BISA login!' ?>')">
<?= csrf_field() ?>
<button type="submit"
class="quick-btn"
style="border:2px solid <?= $maintenance_on ? '#ef4444' : 'rgba(255,255,255,0.15)' ?>; <?= $maintenance_on ? 'background:rgba(239,68,68,0.15);' : '' ?>">
<h5 class="mb-0" style="color:<?= $maintenance_on ? '#ef4444' : 'inherit' ?>">
<?= $maintenance_on ? '🔴 MAINTENANCE NYALA' : '⚙️ Maintenance' ?>
</h5>
<small class="opacity-75">
<?= $maintenance_on ? 'Klik untuk matikan' : 'Mode pemeliharaan' ?>
</small>
</button>
</form>

</div>

<?php endif; ?>

<div class="col-lg-3 col-6">

<a href="fitur/lokasi.php"
class="quick-btn">

<h5 class="mb-0">
Lokasi
</h5>

<small class="opacity-75">
Update GIS
</small>

</a>

</div>

<div class="col-lg-3 col-6">

<a href="fitur/profile.php"
class="quick-btn">

<h5 class="mb-0">
Management
</h5>

<small class="opacity-75">
Kelola peserta
</small>

</a>

</div>

<div class="col-lg-3 col-6">

<a href="fitur/laporan.php"
            class="quick-btn">

            <h5 class="mb-0">
            Export
            </h5>

            <small class="opacity-75">
            Download laporan
            </small>

            </a>

</div>

</div>

<!-- CHART & ACTIVITY -->

<div class="row g-4">

<div class="col-lg-6">

<div class="glass-box">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

<h4 class="mb-0">Statistik Program</h4>

<div class="btn-group">

<button type="button" id="btnBarChart" class="btn btn-sm btn-outline-primary active">Grafik Batang</button>

<button type="button" id="btnLineChart" class="btn btn-sm btn-outline-secondary">Grafik Garis</button>

</div>

</div>

<canvas id="programChart" height="250"></canvas>

<div class="mt-5">

<h5 class="mb-4">
Progress Program
</h5>

<div class="mb-4">

<div class="d-flex
justify-content-between
mb-2">

<span>
Sudah Claim
</span>

<span class="text-warning">
<?= $claim ?>
</span>

</div>

<div class="progress-modern">

<div class="bg-warning h-100"
style="width:
<?= $total
? ($claim/$total)*100
: 0 ?>%">
</div>

</div>

</div>

<div>

<div class="d-flex
justify-content-between
mb-2">

<span>
Sudah Cair
</span>

<span class="text-info">
<?= $cair ?>
</span>

</div>

<div class="progress-modern">

<div class="bg-info h-100"
style="width:
<?= $total
? ($cair/$total)*100
: 0 ?>%">
</div>

</div>

</div>

</div>

</div>

</div>

<!-- ACTIVITY -->

<div class="col-lg-6">

<div class="glass-box">

<h4 class="mb-4">
Aktivitas Peserta
</h4>

<?php while($row = mysqli_fetch_assoc($recent)) : ?>

<div class="activity-item">

<div class="d-flex
justify-content-between
align-items-center">

<div>

<div class="fw-bold">

<?= h($row['nama_lengkap']) ?>

</div>

<div class="small opacity-75">

<?= h($row['tipe_program']) ?>

</div>

</div>

<div>

<?php
$s_rekom = $row['status_rekom'] ?? 'belum';
$s_klaim = $row['status_klaim'] ?? 'belum';
echo rekom_badge($s_rekom) . ' ' . klaim_badge($s_klaim);
?>

</div>

</div>

</div>

<?php endwhile; ?>

</div>

</div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>

AOS.init();

function updateClock(){

const now = new Date();

document.getElementById('liveClock').innerHTML = now.toLocaleTimeString('id-ID', { timeZone:'Asia/Makassar' });

}

setInterval(updateClock,1000);

updateClock();

// Data dari PHP

const chartData = {

labels: ['JKK', 'JKM', 'Sudah Claim', 'Sudah Cair'],

datasets: [{

label: 'Jumlah Peserta',

data: [<?= $jkk ?>, <?= $jkm ?>, <?= $claim ?>, <?= $cair ?>],

backgroundColor: ['#2563eb', '#16a34a', '#f59e0b', '#06b6d4'],

borderColor: ['#3b82f6', '#22c55e', '#fbbf24', '#22d3ee'],

borderWidth: 2,

tension: 0.3,   // untuk line chart membuat garis sedikit melengkung

fill: false

}]

};

const chartConfig = {

type: 'bar',   // awal bar chart

data: chartData,

options: {

responsive: true,

maintainAspectRatio: true,

plugins: {

legend: { labels: { color: 'white' } },

tooltip: { backgroundColor: 'rgba(0,0,0,0.7)' }

},

scales: {

y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: 'white' } },

x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: 'white' } }

}

}

};

let myChart = new Chart(document.getElementById('programChart'), chartConfig);

// Toggle ke Bar Chart

document.getElementById('btnBarChart').addEventListener('click', function() {

if (myChart.config.type === 'line') {

myChart.destroy();

chartConfig.type = 'bar';

myChart = new Chart(document.getElementById('programChart'), chartConfig);

}

// Update tampilan tombol

document.getElementById('btnBarChart').classList.add('active', 'btn-outline-primary');

document.getElementById('btnBarChart').classList.remove('btn-outline-secondary');

document.getElementById('btnLineChart').classList.remove('active', 'btn-outline-primary');

document.getElementById('btnLineChart').classList.add('btn-outline-secondary');

});

// Toggle ke Line Chart

document.getElementById('btnLineChart').addEventListener('click', function() {

if (myChart.config.type === 'bar') {

myChart.destroy();

chartConfig.type = 'line';

myChart = new Chart(document.getElementById('programChart'), chartConfig);

}

// Update tampilan tombol

document.getElementById('btnLineChart').classList.add('active', 'btn-outline-primary');

document.getElementById('btnLineChart').classList.remove('btn-outline-secondary');

document.getElementById('btnBarChart').classList.remove('active', 'btn-outline-primary');

document.getElementById('btnBarChart').classList.add('btn-outline-secondary');

});

</script>
</body>


