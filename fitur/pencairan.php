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
   FILTER SYSTEM
========================= */

$where = "";
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

if($filter == 'pending'){
    $where = "WHERE status_klaim NOT IN ('proses_cair','sudah_cair','proses_claim','sudah_claim')";
} elseif($filter == 'claim'){
    $where = "WHERE status_klaim IN ('proses_claim','sudah_claim')";
} elseif($filter == 'selesai'){
    $where = "WHERE status_klaim IN ('proses_claim','sudah_claim')";
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = "";
if(!empty($search)){
    $search_condition = " (nik LIKE '%$search%' OR nama_lengkap LIKE '%$search%')";
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$count_base = "FROM peserta $where";
if (!empty($search_condition)) {
    $count_base = "FROM peserta " . ($where ? "$where AND $search_condition" : "WHERE $search_condition");
}
$total_rows = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c $count_base"))['c'];
$total_pages = ceil(max($total_rows, 1) / $limit);

$full_where = $where;
if (!empty($search_condition)) {
    $full_where = $where ? "$where AND $search_condition" : "WHERE $search_condition";
}

/* =========================
   QUERY
========================= */

$query = mysqli_query($conn,
"SELECT * FROM peserta
$full_where
ORDER BY id DESC
LIMIT $limit OFFSET $offset");

/* =========================
   STATS
========================= */

$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) as totalPencairan,
        SUM(status_klaim IN ('proses_claim','sudah_claim')) as totalSelesai,
        SUM(status_klaim NOT IN ('proses_claim','sudah_claim')) as totalPending
    FROM peserta"
));
$totalPencairan = $stats['totalPencairan'];
$totalSelesai   = $stats['totalSelesai'];
$totalPending   = $stats['totalPending'];

?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Pencairan</title>

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

letter-spacing:1px;

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

position:relative;

overflow:hidden;

}

.stats-card::before{

content:'';

position:absolute;

top:0;
left:0;

width:100%;
height:4px;

background:
linear-gradient(
90deg,
#2563eb,
#0ea5e9
);

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

/* SEARCH */

.search-box{

background:
rgba(255,255,255,0.08);

border:
1px solid rgba(255,255,255,0.08);

color:white !important;

height:52px;

border-radius:14px;

padding:0 18px;

width:260px;

}

.search-box::placeholder{

color:
rgba(255,255,255,0.5);

}

.search-box:focus{

outline:none;

box-shadow:
0 0 0 4px
rgba(59,130,246,0.15);

background:
rgba(255,255,255,0.12);

border:
1px solid #3b82f6;

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

transform:scale(1.002);

}

/* STATUS */

.status-badge{

padding:9px 16px;

border-radius:999px;

font-size:12px;

font-weight:700;

display:inline-flex;

align-items:center;

gap:8px;

}

.pending{

background:
rgba(245,158,11,0.15);

color:#fbbf24;

border:
1px solid rgba(245,158,11,0.3);

}

.claim{

background:
rgba(34,197,94,0.15);

color:#4ade80;

border:
1px solid rgba(34,197,94,0.3);

}

.selesai{

background:
rgba(14,165,233,0.15);

color:#38bdf8;

border:
1px solid rgba(14,165,233,0.3);

}

/* PROGRESS */

.progress-modern{

height:14px;

border-radius:999px;

overflow:hidden;

background:
rgba(255,255,255,0.08);

}

.progress-fill{

height:100%;

border-radius:999px;

transition:1s;

}

.progress-pending{

background:
linear-gradient(
90deg,
#f59e0b,
#fbbf24
);

}

.progress-done{

background:
linear-gradient(
90deg,
#0ea5e9,
#38bdf8
);

}

/* WORKFLOW */

.workflow-step{

display:flex;

align-items:center;

gap:18px;

padding:18px;

border-radius:20px;

background:
rgba(255,255,255,0.04);

margin-bottom:15px;

border:
1px solid rgba(255,255,255,0.05);

transition:0.3s;

}

.workflow-step:hover{

background:
rgba(255,255,255,0.07);

transform:translateX(5px);

}

.workflow-dot{

width:18px;
height:18px;

border-radius:50%;

background:#0ea5e9;

box-shadow:
0 0 20px #0ea5e9;

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

/* MOBILE */

@media(max-width:768px){

.table{
min-width:1200px;
}

.search-box{
width:100%;
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

<a href="claims.php"
class="btn btn-success modern-btn">

Claims

</a>

<a href="profile.php"
class="btn btn-info modern-btn">

Peserta

</a>

<a href="../auth/logout.php"
class="btn btn-danger modern-btn">

Logout

</a>

</div>

</div>

</nav>

<!-- CONTENT -->

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

Monitoring Pencairan

</h2>

<p class="opacity-75 mb-0">

Monitoring proses pencairan dana peserta

</p>

</div>

                    <form method="GET"
class="d-flex gap-2 flex-wrap align-items-center">

<select
name="filter"
class="filter-box"
onchange="this.form.submit()">

<option value="">
Semua
</option>

<option value="pending" <?= $filter == 'pending' ? 'selected' : '' ?>>
Pending
</option>

<option value="claim" <?= $filter == 'claim' ? 'selected' : '' ?>>
Sudah Claim
</option>

<option value="selesai" <?= $filter == 'selesai' ? 'selected' : '' ?>>
Selesai
</option>

</select>

<input type="text" name="search" class="search-box" placeholder="Cari NIK/Nama..." value="<?= h($search) ?>">
<button class="btn btn-primary modern-btn">Cari</button>

</form>

</div>

</div>

<!-- STATS -->

<div class="row g-4 mb-5">

<div class="col-lg-4">

<div class="stats-card">

<div class="stats-title">
Total Peserta
</div>

<div class="stats-value text-primary">

<?= $totalPencairan ?>

</div>

</div>

</div>

<div class="col-lg-4">

<div class="stats-card">

<div class="stats-title">
Pending
</div>

<div class="stats-value text-warning">

<?= $totalPending ?>

</div>

</div>

</div>

<div class="col-lg-4">

<div class="stats-card">

<div class="stats-title">
Selesai
</div>

<div class="stats-value text-info">

<?= $totalSelesai ?>

</div>

</div>

</div>

</div>

<!-- TABLE -->

<div class="glass-box">

<div class="d-flex
justify-content-between
align-items-center
flex-wrap
gap-3
mb-4">

                    <h4 class="mb-0">

Data Pencairan

</h4>

</div>

<div class="table-responsive">

<table
class="table table-dark table-hover align-middle"
id="pencairanTable">

<thead>

<tr>

<th>NIK</th>
<th>Nama</th>
<th>Program</th>
<th>Rekom</th>
<th>Klaim</th>
<th>Pencairan</th>

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

<?= rekom_badge($row['status_rekom'] ?? 'belum') ?>

</td>

<td>

<?= klaim_badge($row['status_klaim'] ?? 'belum') ?>

</td>

<td>

<?= klaim_badge($row['status_klaim'] ?? 'belum') ?>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

<?= pagination_links($page, $total_pages, ['filter' => $filter, 'search' => $search]) ?>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>

AOS.init();



</script>

</body>
</html>