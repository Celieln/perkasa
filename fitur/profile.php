<?php

include '../config.php';


if(!isset($_SESSION['login'])){
    header("Location: ../index.php");
    exit;
}

// Users (masyarakat) redirect ke dashboard — mereka lihat data sendiri via dashboard
if(!is_admin()){
    header("Location: ../dashboard.php");
    exit;
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// ========== PAGINATION ==========
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search_condition = "";
$search_params = [];
if(!empty($search)){
    $search_condition = " AND (nik LIKE '%$search%' OR nama_lengkap LIKE '%$search%' OR kecamatan LIKE '%$search%' OR kelurahan LIKE '%$search%' OR pekerjaan LIKE '%$search%' OR nama_ahli_waris LIKE '%$search%' OR status_ahli_waris LIKE '%$search%')";
}

$count_query = "SELECT COUNT(*) as total FROM peserta WHERE 1=1 $search_condition";
$total_rows = mysqli_fetch_assoc(mysqli_query($conn, $count_query))['total'];
$total_pages = ceil(max($total_rows, 1) / $limit);

$query = "
SELECT peserta.*,
    (SELECT COUNT(*) FROM dokumen WHERE dokumen.peserta_id = peserta.id AND dokumen.ktp != '') as has_ktp,
    (SELECT COUNT(*) FROM dokumen WHERE dokumen.peserta_id = peserta.id AND dokumen.kk != '') as has_kk,
    (SELECT COUNT(*) FROM dokumen WHERE dokumen.peserta_id = peserta.id AND dokumen.akta != '') as has_akta,
    (SELECT COUNT(*) FROM dokumen WHERE dokumen.peserta_id = peserta.id AND dokumen.surat_ahli_waris != '') as has_waris,
    (SELECT COUNT(*) FROM dokumen WHERE dokumen.peserta_id = peserta.id AND dokumen.foto_bukti != '') as has_foto
FROM peserta
WHERE 1=1 $search_condition
ORDER BY peserta.id DESC
LIMIT $limit OFFSET $offset
";

$data = mysqli_query($conn, $query);

$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) as total,
        SUM(tipe_program='JKK') as jkk,
        SUM(tipe_program='JKM') as jkm,
        SUM(nama_ahli_waris IS NOT NULL AND nama_ahli_waris != '') as dengan_ahli_waris
    FROM peserta"
));
$total = $stats['total'];
$jkk   = $stats['jkk'];
$jkm   = $stats['jkm'];
$dengan_ahli_waris = $stats['dengan_ahli_waris'];

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0, user-scalable=yes">

<title>Profile Peserta</title>

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

/* SEARCH */

.search-box{

background:
rgba(255,255,255,0.08);

border:none;

color:white;

height:50px;

border-radius:14px;

padding:0 18px;

width:260px;

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

/* STATUS */

.status-badge{

padding:8px 14px;

border-radius:999px;

font-size:12px;

font-weight:bold;

display:inline-block;

}

.status-claim{
background:#16a34a;
}

.status-cair{
background:#2563eb;
}

.status-pending{
background:#dc2626;
}

.status-badge.small {
font-size: 11px;
padding: 6px 12px;
}

/* DOC BADGE */

.doc-badge{

padding:6px 12px;

border-radius:999px;

font-size:11px;

font-weight:bold;

display:inline-block;

margin-bottom:6px;

}

/* INFO TOOLTIP */

.info-icon{

cursor:help;

border-bottom:1px dotted rgba(255,255,255,0.5);

margin-left:5px;

}

/* RESPONSIVE */

@media(max-width:768px){

.table{
min-width:1400px; /* menyesuaikan karena kolom bertambah */
}

.search-box{
width:100%;
}

.stats-value{
font-size:28px;
}

.stats-card{
padding:18px;
}

}

/* Tambahan untuk kolom agar lebih rapi */

.table td, .table th {
vertical-align: middle;
white-space: nowrap;
}

.table td .wrap-text {
white-space: normal;
word-break: break-word;
min-width: 150px;
}

.badge-gis {
background: #6f42c1;
font-size: 10px;
padding: 4px 8px;
border-radius: 12px;
}

/* Pagination style */
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

.table-responsive {
overflow-x: auto;
}

.table thead th {
color: rgba(255,255,255,0.92);
font-weight: 600;
letter-spacing: 0.03em;
text-transform: uppercase;
padding: 16px 12px;
border-bottom: 1px solid rgba(255,255,255,0.12);
}

.table td,
.table th {
vertical-align: middle;
white-space: nowrap;
padding: 16px 12px;
}

.table td {
border-top: 1px solid rgba(255,255,255,0.08);
}

.table-hover tbody tr:hover {
background: rgba(255,255,255,0.08);
}

.table .badge {
font-size: 11px;
}

.glass-box {
background: rgba(255,255,255,0.08);
backdrop-filter: blur(18px);
box-shadow: 0 30px 60px rgba(0,0,0,0.18);
}

.search-box {
min-width: 260px;
max-width: 100%;
}

.header-actions {
justify-content: space-between;
align-items: center;
}

.header-meta {
color: rgba(255,255,255,0.7);
font-size: 0.95rem;
}

</style>

</head>

<body>

<div class="container py-5">

<!-- HEADER -->

<div class="d-flex
justify-content-between
align-items-center
flex-wrap
gap-3
mb-5 header-actions">

<div>

<h2 class="fw-bold">

Management Peserta

</h2>

<p class="opacity-75 mb-1">

Data peserta PERKASA Sulawesi Utara

</p>

<p class="header-meta mb-0">
Lihat ringkasan cepat dan kelola peserta dengan tabel yang lebih bersih dan responsif.
</p>

</div>

<div class="d-flex gap-2 flex-wrap">

<a href="../dashboard.php"
class="btn btn-secondary modern-btn">

Dashboard

</a>

<?php if(is_admin()) : ?>

<a href="import/index.php"
class="btn btn-info modern-btn text-white">

📥 Import Excel

</a>

<a href="create_profile.php"
class="btn btn-primary modern-btn">

Tambah Peserta

</a>

<a href="generate_tracking_codes.php"
class="btn btn-warning modern-btn">

🔄 Tracking Code

</a>

<?php endif; ?>

</div>

</div>

<!-- STATS (4 card) -->

<div class="row g-4 mb-5">

<div class="col-lg-3 col-md-6">

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

<div class="col-lg-3 col-md-6">

<div class="stats-card"
data-aos="fade-up"
data-aos-delay="100">

<div class="stats-title">

Program JKK

</div>

<div class="stats-value text-info">

<?= $jkk ?>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6">

<div class="stats-card"
data-aos="fade-up"
data-aos-delay="200">

<div class="stats-title">

Program JKM

</div>

<div class="stats-value text-success">

<?= $jkm ?>

</div>

</div>

</div>

<div class="col-lg-3 col-md-6">

<div class="stats-card"
data-aos="fade-up"
data-aos-delay="300">

<div class="stats-title">

Dengan Ahli Waris

</div>

<div class="stats-value text-warning">

<?= $dengan_ahli_waris ?>

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

<h4 class="fw-bold mb-0">

List Peserta

</h4>

<form method="GET" class="d-flex gap-2">

<input
type="text"
name="search"
class="search-box"
placeholder="Cari peserta, ahli waris, pekerjaan..."
value="<?= htmlspecialchars($search) ?>">
<button type="submit" class="btn btn-primary modern-btn">Cari</button>

</form>

</div>

<div class="table-responsive">

<table class="table table-dark table-hover align-middle">

<thead>
<tr>
<th>Tracking</th>
<th>NIK</th>
<th>No KK</th>
<th>Peserta</th>
<th>Alamat</th>
<th>Pekerjaan</th>
<th>Program</th>
<th>Ahli Waris</th>
<th>Dokumen</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>

<?php while($row = mysqli_fetch_assoc($data)) : ?>

<tr>

<td>
<?php if(!empty($row['tracking_code'])): ?>
    <a href="../tracking.php?code=<?= urlencode($row['tracking_code']) ?>" target="_blank" class="text-info" style="text-decoration:none;letter-spacing:1px;font-size:13px;">
        <?= htmlspecialchars($row['tracking_code']) ?>
    </a>
<?php else: ?>
    <span class="opacity-50">-</span>
<?php endif; ?>
</td>
<td><?= h($row['nik']) ?></td>
<td><?= h($row['no_kk']) ?></td>

<td>

<div class="fw-bold">

<?= h($row['nama_lengkap']) ?>

</div>

<div class="small opacity-75">

<?= h($row['tempat_lahir']) ?>,
<?= h($row['tanggal_lahir']) ?>

</div>

<div class="small text-info">

<?= h($row['jenis_kelamin']) ?>

</div>

<?php if(!empty($row['tanggal_meninggal'])): ?>

<div class="small text-danger">

Meninggal: <?= h($row['tanggal_meninggal']) ?>

</div>

<?php endif; ?>

</td>

<td>

<div class="fw-bold">

<?= h($row['kecamatan']) ?>

</div>

<div class="small opacity-75">

<?= h($row['kelurahan']) ?>,
<?= h($row['kota']) ?>

</div>

<div class="small opacity-50">

RT <?= h($row['rt']) ?> / RW <?= h($row['rw']) ?>

</div>

<?php if(!empty($row['latitude']) && !empty($row['longitude'])): ?>

<span class="badge-gis" title="Memiliki koordinat GIS">📍 GIS</span>

<?php endif; ?>

</td>

<td>

<?= !empty($row['pekerjaan']) ? $row['pekerjaan'] : '<span class="opacity-50">-</span>' ?>

</td>

<td>

<?php if($row['tipe_program'] == 'JKK') : ?>

<span class="badge bg-primary">JKK</span>

<?php else : ?>

<span class="badge bg-success">JKM</span>

<?php endif; ?>

</td>

<td>

<?php if(!empty($row['nama_ahli_waris'])): ?>

<div class="fw-bold small"><?= h($row['nama_ahli_waris']) ?></div>

<div class="small opacity-75">

<?= h($row['status_ahli_waris']) ?>

<?php if(!empty($row['no_telp_ahli_waris'])): ?>

<br>📞 <?= h($row['no_telp_ahli_waris']) ?>

<?php endif; ?>

</div>

<?php if(!empty($row['pekerjaan_ahli_waris'])): ?>

<div class="small text-info"><?= h($row['pekerjaan_ahli_waris']) ?></div>

<?php endif; ?>

<?php else: ?>

<span class="opacity-50">-</span>

<?php endif; ?>

</td>

<td>

<div class="d-flex flex-column">

                <?php if($row['has_ktp']) : ?>

<span class="doc-badge bg-info">KTP</span>

<?php endif; ?>

<?php if($row['has_kk']) : ?>

<span class="doc-badge bg-success">KK</span>

<?php endif; ?>

<?php if($row['has_akta']) : ?>

<span class="doc-badge bg-warning text-dark">AKTA</span>

<?php endif; ?>

<?php if($row['has_waris']) : ?>

<span class="doc-badge bg-primary">WARIS</span>

<?php endif; ?>

<?php if($row['has_foto']) : ?>

<span class="doc-badge bg-danger">FOTO</span>

<?php endif; ?>

</div>

</td>

<td>

<div class="d-flex gap-2 flex-wrap">

<div class="mb-1"><?= rekom_badge($row['status_rekom'] ?? 'belum') ?></div>
<?= klaim_badge($row['status_klaim'] ?? 'belum') ?>

</div>

</td>

<td>

<div class="d-flex gap-2 flex-wrap">

<a href="detail_profile.php?id=<?= h($row['id']) ?>"
class="btn btn-info btn-sm modern-btn">Detail</a>

<?php if(is_admin()) : ?>

<a href="edit_profile.php?id=<?= h($row['id']) ?>"
class="btn btn-warning btn-sm modern-btn">Edit</a>

<a href="update_location.php?id=<?= h($row['id']) ?>"
class="btn btn-success btn-sm modern-btn">Lokasi</a>

<a href="delete_profile.php?id=<?= h($row['id']) ?>"
onclick="return confirm('Hapus peserta ini?')"
class="btn btn-danger btn-sm modern-btn">Delete</a>

<?php endif; ?>

</div>

</td>

</tr>

<?php endwhile; ?>

<?php if(mysqli_num_rows($data) == 0): ?>

<tr>

<td colspan="11" class="text-center py-5">Tidak ada data peserta</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

<!-- PAGINATION -->
<?= pagination_links($page, $total_pages, ['search' => $search]) ?>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>

AOS.init();

</script>

</body>

</html>