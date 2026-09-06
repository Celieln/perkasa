<?php

include '../config.php';


if(!isset($_SESSION['login'])){
    header("Location: ../index.php");
    exit;
}

if(!is_admin()){
    header("Location: ../dashboard.php");
    exit;
}

/* =========================
   FILTER
========================= */

$where = "";

$program = isset($_GET['program']) ? mysqli_real_escape_string($conn, $_GET['program']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

if($program != ''){
    $where .= " AND tipe_program='$program'";
}

if($status_filter == 'claim'){
    $where .= " AND status_klaim IN ('proses_claim','sudah_claim')";
} elseif($status_filter == 'cair'){
    $where .= " AND status_klaim IN ('proses_cair','sudah_cair')";
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 100;
$offset = ($page - 1) * $limit;

$total_rows = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM peserta WHERE 1=1 $where"
))['c'];
$total_pages = ceil(max($total_rows, 1) / $limit);

/* =========================
   QUERY
========================= */

$query = mysqli_query($conn,
"SELECT * FROM peserta
WHERE 1=1 $where
ORDER BY id DESC
LIMIT $limit OFFSET $offset");

/* =========================
   STATS
========================= */

$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) as totalPeserta,
        SUM(tipe_program='JKK') as totalJKK,
        SUM(tipe_program='JKM') as totalJKM,
        SUM(status_klaim IN ('proses_claim','sudah_claim')) as totalClaim,
        SUM(status_klaim IN ('proses_cair','sudah_cair')) as totalCair
    FROM peserta"
));
$totalPeserta = $stats['totalPeserta'];
$totalJKK     = $stats['totalJKK'];
$totalJKM     = $stats['totalJKM'];
$totalClaim   = $stats['totalClaim'];
$totalCair    = $stats['totalCair'];

?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Laporan PERKASA</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link href="https://unpkg.com/aos@2.3.4/dist/aos.css"
rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:
'Segoe UI',
sans-serif;
}

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

}

/* NAVBAR */

.custom-navbar{

background:
rgba(255,255,255,0.05);

backdrop-filter:blur(20px);

border-bottom:
1px solid rgba(255,255,255,0.08);

padding:15px 0;

position:sticky;

top:0;

z-index:9999;

}

.navbar-brand{

font-size:26px;

font-weight:700;

color:white !important;

}

/* GLASS */

.glass-box{

background:
rgba(255,255,255,0.07);

backdrop-filter:blur(16px);

border-radius:24px;

border:
1px solid rgba(255,255,255,0.08);

padding:28px;

box-shadow:
0 10px 40px
rgba(0,0,0,0.25);

}

/* STATS */

.stats-card{

background:
rgba(255,255,255,0.05);

border:
1px solid rgba(255,255,255,0.08);

border-radius:22px;

padding:25px;

transition:0.3s;

height:100%;

}

.stats-card:hover{

transform:
translateY(-5px);

background:
rgba(255,255,255,0.08);

}

.stats-title{

font-size:14px;

opacity:0.7;

margin-bottom:12px;

}

.stats-value{

font-size:40px;

font-weight:bold;

}

/* FILTER */

.filter-box{

background:
rgba(255,255,255,0.08);

border:
1px solid rgba(255,255,255,0.08);

color:white !important;

height:52px;

border-radius:14px;

padding:0 15px;

min-width:170px;

}

.filter-box:focus{

outline:none;

box-shadow:
0 0 0 4px
rgba(59,130,246,0.15);

background:
rgba(255,255,255,0.12);

border:
1px solid #3b82f6;

}

.filter-box option{

background:#0f172a;

color:white;

}

/* BUTTON */

.modern-btn{

border:none;

border-radius:14px;

padding:11px 18px;

font-weight:600;

transition:0.3s;

}

.modern-btn:hover{

transform:
translateY(-2px);

}

/* TABLE */

.table-dark{
--bs-table-bg: transparent;
}

.table{

color:white;

margin-bottom:0;

}

.table thead th{

border-bottom:
1px solid rgba(255,255,255,0.08);

padding:18px;

font-size:13px;

text-transform:uppercase;

letter-spacing:1px;

opacity:0.8;

}

.table tbody td{

padding:18px;

vertical-align:middle;

border-color:
rgba(255,255,255,0.05);

}

.table tbody tr{

transition:0.3s;

}

.table tbody tr:hover{

background:
rgba(255,255,255,0.04);

}

/* STATUS */

.status-badge{

padding:8px 15px;

border-radius:999px;

font-size:12px;

font-weight:700;

display:inline-block;

}

.claim{

background:
rgba(34,197,94,0.15);

color:#4ade80;

border:
1px solid rgba(34,197,94,0.3);

}

.cair{

background:
rgba(14,165,233,0.15);

color:#38bdf8;

border:
1px solid rgba(14,165,233,0.3);

}

.pending{

background:
rgba(245,158,11,0.15);

color:#fbbf24;

border:
1px solid rgba(245,158,11,0.3);

}

/* PRINT */

@media print{

body{
background:white;
color:black;
}

.custom-navbar,
.no-print{
display:none !important;
}

.glass-box{

background:white;

box-shadow:none;

border:none;

}

.table{
color:black;
}

}

.pagination .page-link {
background: rgba(255,255,255,0.08);
border: 1px solid rgba(255,255,255,0.1);
color: white;
transition: 0.2s;
}
.pagination .page-link:hover {
background: #2563eb;
transform: translateY(-2px);
}
.pagination .active .page-link {
background: #2563eb;
border-color: #2563eb;
}

@media(max-width:768px){

.table{
min-width:1200px;
}

.stats-value{
font-size:30px;
}

.glass-box{
padding:20px;
}

}

</style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar">

<div class="container">

<a class="navbar-brand"
href="../dashboard.php">

PERKASA

</a>

<div class="d-flex gap-2 flex-wrap">

<a href="../dashboard.php"
class="btn btn-primary modern-btn">

Dashboard

</a>

<a href="profile.php"
class="btn btn-success modern-btn">

Peserta

</a>

<a href="pencairan.php"
class="btn btn-info modern-btn">

Pencairan

</a>

<a href="../auth/logout.php"
class="btn btn-danger modern-btn">

Logout

</a>

</div>

</div>

</nav>

<div class="container py-5">

<!-- HEADER -->

<div class="glass-box mb-5">

<div class="d-flex
justify-content-between
align-items-center
flex-wrap
gap-4">

<div>

<h2 class="fw-bold mb-2">

Laporan PERKASA

</h2>

<p class="opacity-75 mb-0">

Monitoring dan laporan data peserta

</p>

</div>

<div class="d-flex gap-2 flex-wrap no-print">

<?php $exportUrl = "export_laporan_excel.php?program=" . urlencode($program) . "&status=" . urlencode($status_filter); ?>
<a href="<?= $exportUrl ?>"
class="btn btn-success modern-btn">

Export Excel

</a>

<button
onclick="window.print()"
class="btn btn-light modern-btn">

Print Laporan

</button>

</div>

</div>

</div>

<!-- STATS -->

<div class="row g-4 mb-5">

<div class="col-lg-2">

<div class="stats-card">

<div class="stats-title">
Total
</div>

<div class="stats-value text-primary">

<?= $totalPeserta ?>

</div>

</div>

</div>

<div class="col-lg-2">

<div class="stats-card">

<div class="stats-title">
JKK
</div>

<div class="stats-value text-info">

<?= $totalJKK ?>

</div>

</div>

</div>

<div class="col-lg-2">

<div class="stats-card">

<div class="stats-title">
JKM
</div>

<div class="stats-value text-success">

<?= $totalJKM ?>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="stats-card">

<div class="stats-title">
Sudah Claim
</div>

<div class="stats-value text-warning">

<?= $totalClaim ?>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="stats-card">

<div class="stats-title">
Sudah Cair
</div>

<div class="stats-value text-info">

<?= $totalCair ?>

</div>

</div>

</div>

</div>

<!-- FILTER -->

<div class="glass-box mb-5 no-print">

<form method="GET"
class="row g-4">

<div class="col-lg-4">

<select
name="program"
class="filter-box w-100">

<option value="">
Semua Program
</option>

<option value="JKK" <?= $program === "JKK" ? "selected" : "" ?>>
JKK
</option>

<option value="JKM" <?= $program === "JKM" ? "selected" : "" ?>>
JKM
</option>

</select>

</div>

<div class="col-lg-4">

<select
name="status"
class="filter-box w-100">

<option value="">
Semua Status
</option>

<option value="claim" <?= $status_filter === "claim" ? "selected" : "" ?>>
Sudah Claim
</option>

<option value="cair" <?= $status_filter === "cair" ? "selected" : "" ?>>
Sudah Cair
</option>

</select>

</div>

<div class="col-lg-4">

<button
class="btn btn-primary modern-btn w-100">

Filter Laporan

</button>

</div>

</form>

</div>

<!-- TABLE -->

<div class="glass-box">

<div class="table-responsive">

<table
class="table table-dark table-hover align-middle">

<thead>

<tr>

<th>NIK</th>
<th>Nama</th>
<th>Program</th>
<th>Wilayah</th>
<th>Rekom</th>
<th>Klaim</th>

</tr>

</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($query)) : ?>

<tr>

<td>

<?= h($row['nik']) ?>

</td>

<td>

<div class="fw-bold">

<?= h($row['nama_lengkap']) ?>

</div>

<div class="small opacity-75">

<?= h($row['alamat']) ?>

</div>

</td>

<td>

<?php if(
$row['tipe_program']
== 'JKK'
) : ?>

<span class="badge bg-primary">

JKK

</span>

<?php else : ?>

<span class="badge bg-success">

JKM

</span>

<?php endif; ?>

</td>

<td>

<?= h($row['kecamatan']) ?>,
<?= h($row['kota']) ?>

</td>

                                        <td>

<?= rekom_badge($row['status_rekom'] ?? 'belum') ?>

</td>

<td>

<?= klaim_badge($row['status_klaim'] ?? 'belum') ?>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

<?= pagination_links($page, $total_pages, ['program' => $program, 'status' => $status_filter]) ?>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>

AOS.init();

</script>

</body>
</html>