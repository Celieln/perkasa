<?php

include '../config.php';


if(!isset($_SESSION['login'])){
    header("Location: ../index.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: profile.php");
    exit;
}

$id = (int)$_GET['id'];

$data = mysqli_fetch_assoc(

mysqli_query($conn,

"SELECT

peserta.*,

dokumen.ktp,
dokumen.kk,
dokumen.akta,
dokumen.surat_ahli_waris,
dokumen.foto_bukti,
dokumen.dokumen_lain

FROM peserta

LEFT JOIN dokumen
ON peserta.id = $id")

);

if(!$data){
    header("Location: profile.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Detail Peserta</title>

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
#0a1830,
#102d63
);

min-height:100vh;

color:white;

overflow-x:hidden;

}

.glass-box{

background:
rgba(255,255,255,0.08);

backdrop-filter:blur(18px);

border-radius:25px;

padding:30px;

border:
1px solid rgba(255,255,255,0.08);

}

.info-box{

background:
rgba(255,255,255,0.05);

padding:22px;

border-radius:18px;

height:100%;

transition:0.3s;

}

.info-box:hover{

transform:
translateY(-3px);

background:
rgba(255,255,255,0.07);

}

.label{

font-size:13px;

opacity:0.7;

margin-bottom:8px;

}

.value{

font-size:17px;

font-weight:600;

word-break:break-word;

}

.modern-btn{

border:none;

border-radius:14px;

padding:12px 18px;

transition:0.3s;

}

.modern-btn:hover{

transform:
translateY(-2px);

}

.status-badge{

padding:10px 18px;

border-radius:999px;

font-size:13px;

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
background:#dc2626;
}

.doc-card{

background:
rgba(255,255,255,0.05);

border-radius:18px;

padding:20px;

text-align:center;

height:100%;

transition:0.3s;

border:
1px solid rgba(255,255,255,0.05);

}

.doc-card:hover{

transform:
translateY(-4px);

background:
rgba(255,255,255,0.08);

}

.preview-image{

width:100%;

height:220px;

object-fit:cover;

border-radius:16px;

border:
2px solid rgba(255,255,255,0.08);

}

.section-title{

font-size:22px;

font-weight:bold;

margin-bottom:25px;
margin-top:30px;
border-left:4px solid #3b82f6;
padding-left:15px;

}

.section-title.first{
margin-top:0;
}

.map-box{

border-radius:20px;

overflow:hidden;

height:350px;

}

iframe{

width:100%;
height:100%;
border:none;

}

.optional-badge{
font-size:12px;
background:#16a34a;
padding:2px 8px;
border-radius:20px;
margin-left:10px;
vertical-align:middle;
}

@media(max-width:768px){

.glass-box{
padding:20px;
}

}

</style>

</head>

<body>

<div class="container py-5">

<div class="glass-box">

<!-- HEADER -->

<div class="d-flex
justify-content-between
align-items-center
flex-wrap
gap-3
mb-5">

<div>

<h2 class="fw-bold">

Detail Peserta

</h2>

<p class="opacity-75 mb-0">

Informasi lengkap peserta PERKASA

</p>

<?php if(!empty($data['tracking_code'])): ?>
<span class="badge bg-primary mt-2" style="font-size:13px;padding:8px 18px;border-radius:12px;">
    Kode Tracking: <strong style="letter-spacing:2px;"><?= htmlspecialchars($data['tracking_code']) ?></strong>
    <a href="../tracking.php?code=<?= urlencode($data['tracking_code']) ?>" target="_blank" class="text-white ms-2" style="text-decoration:none;">🔍</a>
</span>
<?php endif; ?>

</div>

<div class="d-flex gap-2 flex-wrap">

<a href="profile.php"
class="btn btn-secondary modern-btn">

Kembali

</a>

<?php if(is_admin()) : ?>

<a href="edit_profile.php?id=<?= h($data['id']) ?>"
class="btn btn-warning modern-btn">

Edit

</a>

<a href="update_location.php?id=<?= h($data['id']) ?>"
class="btn btn-success modern-btn">

Update Lokasi

</a>

<?php endif; ?>

</div>

</div>

<!-- IDENTITAS ALMARHUM/ALMARHUMAH -->

<h4 class="section-title first">
Data Peserta
</h4>

<div class="row g-4 mb-5">

<div class="col-lg-4">

<div class="info-box">

<div class="label">
NIK
</div>

<div class="value">
<?= h($data['nik']) ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
No KK
</div>

<div class="value">
<?= h($data['no_kk']) ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Nama Lengkap
</div>

<div class="value">
<?= h($data['nama_lengkap']) ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Tempat / Tanggal Lahir
</div>

<div class="value">

<?= h($data['tempat_lahir']) ?>,
<?= h($data['tanggal_lahir']) ?>

</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Jenis Kelamin
</div>

<div class="value">
<?= h($data['jenis_kelamin']) ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Agama
</div>

<div class="value">
<?= h($data['agama']) ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Denominasi
</div>

<div class="value">
<?= h($data['denominasi']) ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Profesi
</div>

<div class="value">
<?= h($data['profesi']) ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Pekerjaan
</div>

<div class="value">
<?= h($data['pekerjaan'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Tanggal Meninggal
</div>

<div class="value">
<?= h($data['tanggal_meninggal'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Program
</div>

<div class="value">

<?php if(
$data['tipe_program']
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

</div>

</div>

</div>

</div>

<!-- ALAMAT ALMARHUM -->

<h4 class="section-title">

Alamat Peserta

</h4>

<div class="row g-4 mb-5">

<div class="col-lg-12">

<div class="info-box">

<div class="label">
Alamat Lengkap
</div>

<div class="value">

<?= h($data['alamat']) ?>

</div>

</div>

</div>

<div class="col-lg-2">

<div class="info-box">

<div class="label">
RT
</div>

<div class="value">
<?= h($data['rt']) ?>
</div>

</div>

</div>

<div class="col-lg-2">

<div class="info-box">

<div class="label">
RW
</div>

<div class="value">
<?= h($data['rw']) ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Kelurahan
</div>

<div class="value">
<?= h($data['kelurahan']) ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Kecamatan
</div>

<div class="value">
<?= h($data['kecamatan']) ?>
</div>

</div>

</div>

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Kota
</div>

<div class="value">
<?= h($data['kota']) ?>
</div>

</div>

</div>

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Provinsi
</div>

<div class="value">
<?= h($data['provinsi']) ?>
</div>

</div>

</div>

</div>

<!-- STATUS BANTUAN -->

<h4 class="section-title">

Status Bantuan

</h4>

<div class="row g-4 mb-5">

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Status Rekomendasi
</div>

<div class="value">

<?= rekom_badge($data['status_rekom'] ?? 'belum') ?>

</div>

</div>

</div>

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Status Klaim / Cair
</div>

<div class="value">

<?= klaim_badge($data['status_klaim'] ?? 'belum') ?>

</div>

</div>

</div>

<!-- DATA AHLI WARIS (OPSIONAL) -->

<h4 class="section-title">
Data Ahli Waris <span class="optional-badge">Opsional</span>
</h4>

<?php if(
!empty($data['nama_ahli_waris']) ||
!empty($data['no_telp_ahli_waris']) ||
!empty($data['status_ahli_waris']) ||
!empty($data['pekerjaan_ahli_waris']) ||
!empty($data['alamat_ahli_waris'])
) : ?>

<div class="row g-4 mb-5">

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Nama Lengkap Ahli Waris
</div>

<div class="value">
<?= h($data['nama_ahli_waris']) ?>
</div>

</div>

</div>

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Nomor Telepon
</div>

<div class="value">
<?= h($data['no_telp_ahli_waris'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Status Ahli Waris
</div>

<div class="value">
<?= h($data['status_ahli_waris'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Pekerjaan Ahli Waris
</div>

<div class="value">
<?= h($data['pekerjaan_ahli_waris'] ?: '-') ?>
</div>

</div>

</div>

</div>

<h5 class="mt-2 mb-3">Alamat Ahli Waris</h5>

<div class="row g-4 mb-5">

<div class="col-lg-12">

<div class="info-box">

<div class="label">
Alamat Lengkap
</div>

<div class="value">
<?= h($data['alamat_ahli_waris'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-2">

<div class="info-box">

<div class="label">
RT
</div>

<div class="value">
<?= h($data['rt_ahli_waris'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-2">

<div class="info-box">

<div class="label">
RW
</div>

<div class="value">
<?= h($data['rw_ahli_waris'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Kelurahan
</div>

<div class="value">
<?= h($data['kelurahan_ahli_waris'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-4">

<div class="info-box">

<div class="label">
Kecamatan
</div>

<div class="value">
<?= h($data['kecamatan_ahli_waris'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Kota
</div>

<div class="value">
<?= h($data['kota_ahli_waris'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Provinsi
</div>

<div class="value">
<?= h($data['provinsi_ahli_waris'] ?: '-') ?>
</div>

</div>

</div>

</div>

<?php else : ?>

<div class="alert alert-secondary mb-4">

Tidak ada data ahli waris yang diinput.

</div>

<?php endif; ?>

<!-- LOKASI GIS -->

<h4 class="section-title">

Lokasi GIS

</h4>

<div class="glass-box mb-5">

<?php if(
$data['latitude']
&&
$data['longitude']
) : ?>

<div class="map-box mb-4">

<iframe
src="https://maps.google.com/maps?q=<?= h($data['latitude']) ?>,<?= h($data['longitude']) ?>&z=15&output=embed">
</iframe>

</div>

<?php endif; ?>

<div class="row g-4">

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Latitude
</div>

<div class="value">
<?= h($data['latitude'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-6">

<div class="info-box">

<div class="label">
Longitude
</div>

<div class="value">
<?= h($data['longitude'] ?: '-') ?>
</div>

</div>

</div>

<div class="col-lg-12">

<div class="info-box">

<div class="label">
Alamat Lokasi (GIS)
</div>

<div class="value">
<?= h($data['alamat_lokasi'] ?: '-') ?>
</div>

</div>

</div>

</div>

</div>

<!-- DOKUMEN -->

<h4 class="section-title">

Dokumen Peserta

</h4>

<div class="row g-4">

<div class="col-lg-4">

<div class="doc-card">

<h5 class="mb-4">
KTP
</h5>

<?php if($data['ktp']) : ?>

<a
target="_blank"
href="../uploads/ktp/<?= h($data['ktp']) ?>"
class="btn btn-primary modern-btn w-100">

Lihat KTP

</a>

<?php else : ?>

<div class="alert alert-secondary mb-0">

Belum upload

</div>

<?php endif; ?>

</div>

</div>

<div class="col-lg-4">

<div class="doc-card">

<h5 class="mb-4">
KK
</h5>

<?php if($data['kk']) : ?>

<a
target="_blank"
href="../uploads/kk/<?= h($data['kk']) ?>"
class="btn btn-success modern-btn w-100">

Lihat KK

</a>

<?php else : ?>

<div class="alert alert-secondary mb-0">

Belum upload

</div>

<?php endif; ?>

</div>

</div>

<div class="col-lg-4">

<div class="doc-card">

<h5 class="mb-4">
Akta
</h5>

<?php if($data['akta']) : ?>

<a
target="_blank"
href="../uploads/akta/<?= h($data['akta']) ?>"
class="btn btn-warning modern-btn w-100">

Lihat Akta

</a>

<?php else : ?>

<div class="alert alert-secondary mb-0">

Belum upload

</div>

<?php endif; ?>

</div>

</div>

<div class="col-lg-6">

<div class="doc-card">

<h5 class="mb-4">
Surat Ahli Waris
</h5>

<?php if($data['surat_ahli_waris']) : ?>

<a
target="_blank"
href="../uploads/ahli_waris/<?= h($data['surat_ahli_waris']) ?>"
class="btn btn-info modern-btn w-100">

Lihat Surat

</a>

<?php else : ?>

<div class="alert alert-secondary mb-0">

Belum upload

</div>

<?php endif; ?>

</div>

</div>

<div class="col-lg-6">

<div class="doc-card">

<h5 class="mb-4">
Foto Bukti
</h5>

<?php if($data['foto_bukti']) : ?>

<img
src="../uploads/bukti/<?= h($data['foto_bukti']) ?>"
class="preview-image mb-4">

<a
target="_blank"
href="../uploads/bukti/<?= h($data['foto_bukti']) ?>"
class="btn btn-danger modern-btn w-100">

Lihat Foto

</a>

<?php else : ?>

<div class="alert alert-secondary mb-0">

Belum upload

</div>

<?php endif; ?>

</div>

</div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>

AOS.init();

</script>

</body>

</html>