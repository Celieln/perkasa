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

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = "";
if(!empty($search)){
    $search_condition = " WHERE (nik LIKE '%$search%' OR nama_lengkap LIKE '%$search%')";
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$total_rows = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM peserta" . ($search_condition ?: "")
))['c'];
$total_pages = ceil(max($total_rows, 1) / $limit);

$query = mysqli_query($conn,
"SELECT * FROM peserta" . ($search_condition ?: " WHERE 1=1 ") . "
ORDER BY id DESC
LIMIT $limit OFFSET $offset");

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Lokasi Peserta</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>

body{

    background:
    linear-gradient(
    135deg,
    #061220,
    #0a1830,
    #102d63
    );

    min-height:100vh;

    color:white;

}

.custom-navbar{

    background:rgba(255,255,255,0.05);

    backdrop-filter:blur(15px);

    border-bottom:
    1px solid rgba(255,255,255,0.08);

}

.glass-box{

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(18px);

    border-radius:25px;

    border:
    1px solid rgba(255,255,255,0.08);

    padding:25px;

    box-shadow:
    0 10px 40px rgba(0,0,0,0.3);

}

.table-dark{
    --bs-table-bg: transparent;
}

.table tbody tr:hover{

    background:
    rgba(255,255,255,0.04);

    transform:scale(1.005);

}

.table th, .table td {
    vertical-align: middle !important;
}

.table th:nth-child(2), .table td:nth-child(2),
.table th:nth-child(3), .table td:nth-child(3),
.table th:nth-child(4), .table td:nth-child(4),
.table th:nth-child(5), .table td:nth-child(5),
.table th:nth-child(6), .table td:nth-child(6) {
    text-align: center;
}

.location-badge{

    padding:8px 14px;

    border-radius:999px;

    font-size:12px;

    font-weight:bold;

}

.badge-active{

    background:#16a34a;

    color:white;

}

.badge-empty{

    background:#dc2626;

    color:white;

}

.modern-btn{

    border:none;

    border-radius:14px;

    padding:10px 18px;

    transition:0.3s;

}

.modern-btn:hover{

    transform:translateY(-2px);

}

.preview-map{

    width: 100%;

    height: 100%;

    border: none;

    display: block;

}

.map-preview-wrapper{

    display: inline-block;

    width: 220px;

    height: 140px;

    border-radius: 16px;

    overflow: hidden;

    border: 2px solid rgba(255, 255, 255, 0.08);

    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.45);

    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

    position: relative;

}

.map-preview-wrapper:hover {

    transform: translateY(-4px) scale(1.03);

    border-color: #3b82f6;

    box-shadow: 0 12px 30px rgba(59, 130, 246, 0.35);

}

.search-box{

    background:rgba(255,255,255,0.08);

    border:none;

    color:white;

    height:50px;

    border-radius:14px;

    padding:0 18px;

}

.search-box:focus{

    outline:none;

    box-shadow:none;

    background:rgba(255,255,255,0.1);

    color:white;

}

.search-box::placeholder{
    color:rgba(255,255,255,0.5);
}

.no-preview{

    width: 220px;

    height: 140px;

    border-radius: 16px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    background: rgba(255, 255, 255, 0.03);

    border: 2px dashed rgba(255, 255, 255, 0.1);

    color: rgba(255, 255, 255, 0.4);

    font-size: 13px;

    transition: all 0.3s;

}

.no-preview:hover {

    background: rgba(255, 255, 255, 0.05);

    border-color: rgba(255, 255, 255, 0.2);

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
        min-width:1100px;
    }

}

</style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar">

<div class="container">

<a class="navbar-brand fw-bold text-white"
href="../dashboard.php">

PERKASA

</a>

<div class="d-flex gap-2">

<a href="../dashboard.php"
class="btn btn-primary modern-btn">

Dashboard

</a>

<a href="profile.php"
class="btn btn-success modern-btn">

Profile

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

<div class="glass-box">

<div class="d-flex
justify-content-between
align-items-center
flex-wrap
gap-3
mb-4">

<div>

<h2 class="fw-bold">
Lokasi Peserta
</h2>

<p class="opacity-75 mb-0">

Monitoring lokasi peserta PERKASA

</p>

</div>

                    <form method="GET" class="d-flex gap-2">
<input
type="text"
name="search"
class="search-box"
placeholder="Cari NIK/Nama..."
value="<?= h($search) ?>">
<button type="submit" class="btn btn-primary modern-btn">Cari</button>
</form>

</div>

<div class="table-responsive">

<table
class="table table-dark table-hover align-middle"
id="pesertaTable">

<thead>

<tr>

<th>Peserta</th>
<th>Status</th>
<th>Latitude</th>
<th>Longitude</th>
<th>Preview Lokasi</th>
<th>Aksi</th>

</tr>

</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($query)) : ?>

<tr>

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
$row['latitude']
&&
$row['longitude']
) : ?>

<span class="location-badge badge-active">

Lokasi Aktif

</span>

<?php else : ?>

<span class="location-badge badge-empty">

Belum Diatur

</span>

<?php endif; ?>

</td>

<td>

<?= h($row['latitude'] ?: '-') ?>

</td>

<td>

<?= h($row['longitude'] ?: '-') ?>

</td>

<td>

<?php if(
$row['latitude']
&&
$row['longitude']
) : ?>

<div class="map-preview-wrapper">

<iframe
class="preview-map"
loading="lazy"
src="https://www.openstreetmap.org/export/embed.html?bbox=
<?= $row['longitude'] - 0.001 ?>,
<?= $row['latitude'] - 0.001 ?>,
<?= $row['longitude'] + 0.001 ?>,
<?= $row['latitude'] + 0.001 ?>
&layer=mapnik&marker=
<?= h($row['latitude']) ?>,
<?= h($row['longitude']) ?>">

</iframe>

</div>

<?php else : ?>

<div class="no-preview">

Belum Ada Lokasi

</div>

<?php endif; ?>

</td>

<td>

<div class="d-flex gap-2 flex-wrap justify-content-center">

<?php if(is_admin()) : ?>

<a href="update_location.php?id=<?= h($row['id']) ?>"
class="btn btn-primary btn-sm modern-btn">

Update

</a>

<?php endif; ?>

<?php if(
$row['latitude']
&&
$row['longitude']
) : ?>

<a
target="_blank"
href="https://www.google.com/maps?q=<?= h($row['latitude']) ?>,<?= h($row['longitude']) ?>"
class="btn btn-info btn-sm modern-btn">

Maps

</a>

<?php endif; ?>

</div>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

<?= pagination_links($page, $total_pages, ['search' => $search]) ?>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>