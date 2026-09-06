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

$where = "";
$search_condition = "";

$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
if($filter == 'claim'){
    $where = "WHERE status_klaim IN ('proses_claim','sudah_claim')";
} elseif($filter == 'cair'){
    $where = "WHERE status_klaim IN ('proses_cair','sudah_cair')";
} elseif($filter == 'selesai'){
    $where = "WHERE status_klaim IN ('proses_claim','sudah_claim')";
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$base_where = $where ? "$where AND" : "WHERE";
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

$query = mysqli_query($conn,
"SELECT * FROM peserta $full_where
ORDER BY id DESC
LIMIT $limit OFFSET $offset");

$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) as total,
        SUM(status_klaim IN ('proses_claim','sudah_claim')) as totalClaim,
        SUM(status_klaim IN ('proses_cair','sudah_cair')) as totalCair
    FROM peserta"
));
$total = $stats['total'];
$totalClaim = $stats['totalClaim'];
$totalCair = $stats['totalCair'];

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Claims Monitoring</title>

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

color:white;

overflow-x:hidden;

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

z-index:999;

}

.navbar-brand{

font-size:25px;

font-weight:bold;

color:white !important;

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

}

.stats-title{

font-size:14px;

opacity:0.7;

margin-bottom:10px;

}

.stats-value{

font-size:38px;

font-weight:bold;

}

/* TABLE */

.table-dark{
--bs-table-bg: transparent;
}

.table tbody tr{

transition:0.3s;

}

.table tbody tr:hover{

background:
rgba(255,255,255,0.05);

transform:scale(1.002);

}

/* STATUS */

.status-badge{

padding:8px 14px;

border-radius:999px;

font-size:12px;

font-weight:bold;

display:inline-block;

}

.claim{
background:#16a34a;
}

.cair{
background:#2563eb;
}

.pending{
background:#f59e0b;
color:black;
}

.selesai{
background:#0ea5e9;
}

/* FILTER */

.filter-box{

background:
rgba(255,255,255,0.08);

border:none;

color:white;

height:50px;

border-radius:14px;

padding:0 15px;

}

.filter-box:focus{

outline:none;

box-shadow:none;

background:
rgba(255,255,255,0.1);

color:white;

}

/* SEARCH */

.search-box{

background:
rgba(255,255,255,0.08);

border:none;

color:white;

height:50px;

border-radius:14px;

padding:0 18px;

width:250px;

}

.search-box::placeholder{
color:rgba(255,255,255,0.5);
}

.search-box:focus{

outline:none;

background:
rgba(255,255,255,0.1);

}

/* BUTTON */

.modern-btn{

border:none;

border-radius:14px;

padding:10px 18px;

transition:0.3s;

}

.modern-btn:hover{

transform:translateY(-2px);

}

.progress-modern{

height:12px;

border-radius:999px;

overflow:hidden;

background:
rgba(255,255,255,0.08);

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

.search-box{
width:100%;
}

.stats-value{
font-size:28px;
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

<a href="lokasi.php"
class="btn btn-info modern-btn">

Lokasi

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

Monitoring Claims

</h2>

<p class="opacity-75 mb-0">

Monitoring proses claim dan pencairan peserta

</p>

</div>

                    <form method="GET"
class="d-flex gap-2 flex-wrap">

<select
name="filter"
class="filter-box"
onchange="this.form.submit()">

<option value="">
Semua
</option>

<option value="claim" <?= $filter == 'claim' ? 'selected' : '' ?>>
Sudah Claim
</option>

<option value="cair" <?= $filter == 'cair' ? 'selected' : '' ?>>
Sudah Cair
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

<div class="stats-card"
data-aos="fade-up">

<div class="stats-title">

Total Peserta

</div>

<div class="stats-value text-primary">

<?= $total ?>

</div>

</div>

</div>

<div class="col-lg-4">

<div class="stats-card"
data-aos="fade-up"
data-aos-delay="100">

<div class="stats-title">

Sudah Claim

</div>

<div class="stats-value text-success">

<?= $totalClaim ?>

</div>

</div>

</div>

<div class="col-lg-4">

<div class="stats-card"
data-aos="fade-up"
data-aos-delay="200">

<div class="stats-title">

Sudah Cair

</div>

<div class="stats-value text-info">

<?= $totalCair ?>

</div>

</div>

</div>

</div>

<!-- PROGRESS -->

<div class="glass-box mb-5">

<h5 class="mb-4">

Progress Claims

</h5>

<div class="mb-4">

<div class="d-flex
justify-content-between
mb-2">

<span>
Claim Progress
</span>

<span>
<?= $totalClaim ?>
</span>

</div>

<div class="progress-modern">

<div
class="bg-success h-100"
style="width:
<?= $total
? ($totalClaim/$total)*100
: 0 ?>%">
</div>

</div>

</div>

<div>

<div class="d-flex
justify-content-between
mb-2">

<span>
Pencairan Progress
</span>

<span>
<?= $totalCair ?>
</span>

</div>

<div class="progress-modern">

<div
class="bg-info h-100"
style="width:
<?= $total
? ($totalCair/$total)*100
: 0 ?>%">
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

Data Claims

</h4>

</div>

<div class="table-responsive">

<table
class="table table-dark table-hover align-middle"
id="claimsTable">

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