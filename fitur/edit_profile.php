<?php

include '../config.php';



if(!isset($_SESSION['login'])){
    header("Location: ../index.php");
    exit;
}

deny_if_readonly('Anda tidak memiliki izin untuk mengakses halaman ini');

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
ON peserta.id = dokumen.peserta_id

WHERE peserta.id = $id")

);

if(!$data){
    header("Location: profile.php");
    exit;
}

if(isset($_POST['update'])){
    verify_csrf();

    // Only admin / superadmin can update peserta
    if(!is_admin()){
        echo "\n        <script>\n        alert('Anda tidak memiliki izin untuk mengubah data peserta');\n        window.history.back();\n        </script>\n        ";
        exit;
    }

    // Data Almarhum
    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $no_kk = mysqli_real_escape_string($conn, $_POST['no_kk']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $rt = mysqli_real_escape_string($conn, $_POST['rt']);
    $rw = mysqli_real_escape_string($conn, $_POST['rw']);
    $kelurahan = mysqli_real_escape_string($conn, $_POST['kelurahan']);
    $kecamatan = mysqli_real_escape_string($conn, $_POST['kecamatan']);
    $kota = mysqli_real_escape_string($conn, $_POST['kota']);
    $provinsi = mysqli_real_escape_string($conn, $_POST['provinsi']);
    $agama = mysqli_real_escape_string($conn, $_POST['agama']);
    $denominasi = mysqli_real_escape_string($conn, $_POST['denominasi']);
    $profesi = mysqli_real_escape_string($conn, $_POST['profesi']);
    $pekerjaan = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $program = mysqli_real_escape_string($conn, $_POST['tipe_program']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $status_rekom = in_array($_POST['status_rekom'], array_keys(rekom_list())) ? $_POST['status_rekom'] : 'belum';
    $status_klaim = in_array($_POST['status_klaim'], array_keys(klaim_list())) ? $_POST['status_klaim'] : 'belum';

    // ========== PENANGANAN NULL UNTUK TANGGAL MENINGGAL ==========
    $tanggal_meninggal = !empty($_POST['tanggal_meninggal']) ? "'" . mysqli_real_escape_string($conn, $_POST['tanggal_meninggal']) . "'" : "NULL";

    // GIS (opsional)
    $latitude = !empty($_POST['latitude']) ? "'" . mysqli_real_escape_string($conn, $_POST['latitude']) . "'" : "NULL";
    $longitude = !empty($_POST['longitude']) ? "'" . mysqli_real_escape_string($conn, $_POST['longitude']) . "'" : "NULL";
    $alamat_lokasi = !empty($_POST['alamat_lokasi']) ? "'" . mysqli_real_escape_string($conn, $_POST['alamat_lokasi']) . "'" : "NULL";

    // Data Ahli Waris (opsional)
    $nama_ahli_waris = !empty($_POST['nama_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['nama_ahli_waris']) . "'" : "NULL";
    $no_telp_ahli_waris = !empty($_POST['no_telp_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['no_telp_ahli_waris']) . "'" : "NULL";
    $status_ahli_waris = !empty($_POST['status_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['status_ahli_waris']) . "'" : "NULL";
    $pekerjaan_ahli_waris = !empty($_POST['pekerjaan_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['pekerjaan_ahli_waris']) . "'" : "NULL";
    $alamat_ahli_waris = !empty($_POST['alamat_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['alamat_ahli_waris']) . "'" : "NULL";
    $rt_ahli_waris = !empty($_POST['rt_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['rt_ahli_waris']) . "'" : "NULL";
    $rw_ahli_waris = !empty($_POST['rw_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['rw_ahli_waris']) . "'" : "NULL";
    $kelurahan_ahli_waris = !empty($_POST['kelurahan_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['kelurahan_ahli_waris']) . "'" : "NULL";
    $kecamatan_ahli_waris = !empty($_POST['kecamatan_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['kecamatan_ahli_waris']) . "'" : "NULL";
    $kota_ahli_waris = !empty($_POST['kota_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['kota_ahli_waris']) . "'" : "NULL";
    $provinsi_ahli_waris = !empty($_POST['provinsi_ahli_waris']) ? "'" . mysqli_real_escape_string($conn, $_POST['provinsi_ahli_waris']) . "'" : "NULL";

    // Validasi tanggal_lahir tidak kosong
    if(empty($tanggal_lahir)){
        echo "\n        <script>\n        alert('Tanggal Lahir tidak boleh kosong');\n        window.history.back();\n        </script>\n        ";
        exit;
    }

    // Cek NIK duplikat (jika NIK berubah)
    if($nik !== $data['nik']){
        $nikCheck = mysqli_query($conn, "SELECT id FROM peserta WHERE nik='$nik' AND id != '$id'");
        if(mysqli_num_rows($nikCheck) > 0){
            echo "\n        <script>\n        alert('NIK sudah terdaftar di sistem. Silakan gunakan NIK yang berbeda.');\n        window.history.back();\n        </script>\n        ";
            exit;
        }
    }

    // Query UPDATE dengan NULL handling (nilai $tanggal_meninggal, $latitude, dll sudah siap pakai tanpa kutip tambahan)
    $updateQuery = "UPDATE peserta SET

    nik='$nik',
    no_kk='$no_kk',

    nama_lengkap='$nama',

    tempat_lahir='$tempat_lahir',

    tanggal_lahir='$tanggal_lahir',

    jenis_kelamin='$jenis_kelamin',

    alamat='$alamat',

    rt='$rt',
    rw='$rw',

    kelurahan='$kelurahan',

    kecamatan='$kecamatan',

    kota='$kota',

    provinsi='$provinsi',

    agama='$agama',

    denominasi='$denominasi',

    profesi='$profesi',

    pekerjaan='$pekerjaan',

    tipe_program='$program',

    keterangan='$keterangan',

    status_rekom='$status_rekom',
    status_klaim='$status_klaim',

    tanggal_meninggal=$tanggal_meninggal,

    latitude=$latitude,
    longitude=$longitude,
    alamat_lokasi=$alamat_lokasi,

    nama_ahli_waris=$nama_ahli_waris,
    no_telp_ahli_waris=$no_telp_ahli_waris,
    status_ahli_waris=$status_ahli_waris,
    pekerjaan_ahli_waris=$pekerjaan_ahli_waris,
    alamat_ahli_waris=$alamat_ahli_waris,
    rt_ahli_waris=$rt_ahli_waris,
    rw_ahli_waris=$rw_ahli_waris,
    kelurahan_ahli_waris=$kelurahan_ahli_waris,
    kecamatan_ahli_waris=$kecamatan_ahli_waris,
    kota_ahli_waris=$kota_ahli_waris,
    provinsi_ahli_waris=$provinsi_ahli_waris

    WHERE id=$id";

    // Catat perubahan status ke tracking_log
    if($status_rekom != $data['status_rekom']){
        insert_tracking_log($conn, $id, 'REKOM', 'Status rekomendasi berubah menjadi ' . rekom_label($status_rekom) . ' oleh ' . ($_SESSION['nama'] ?? 'Staff'));
    }
    if($status_klaim != $data['status_klaim']){
        insert_tracking_log($conn, $id, 'KLAIM', 'Status klaim berubah menjadi ' . klaim_label($status_klaim) . ' oleh ' . ($_SESSION['nama'] ?? 'Staff'));
    }

    $result = mysqli_query($conn, $updateQuery);

    // Error handling untuk UPDATE peserta
    if(!$result){
        $error = mysqli_error($conn);
        
        if(strpos($error, 'Duplicate entry') !== false){
            echo "\n        <script>\n        alert('NIK sudah terdaftar di sistem. Silakan gunakan NIK yang berbeda.');\n        window.history.back();\n        </script>\n        ";
        } else {
            echo "\n        <script>\n        alert('Terjadi kesalahan saat menyimpan data. Silakan cek kembali form Anda.');\n        window.history.back();\n        </script>\n        ";
        }
        exit;
    }

    /* ======================
       UPLOAD DOKUMEN
    ====================== */

    $ktp = $data['ktp'];
    $kk = $data['kk'];
    $akta = $data['akta'];
    $surat = $data['surat_ahli_waris'];
    $foto = $data['foto_bukti'];
    $dokumen_lain = $data['dokumen_lain'];

    function uploadFileWithFallback($name, $folder, $oldFile){
        if($_FILES[$name]['name']){
            $file = time().'_'.basename($_FILES[$name]['name']);
            if(!is_dir("../uploads/$folder")){
                mkdir("../uploads/$folder", 0777, true);
            }
            move_uploaded_file($_FILES[$name]['tmp_name'], "../uploads/$folder/".$file);
            return $file;
        }
        return $oldFile;
    }

    $ktp = uploadFileWithFallback('ktp', 'ktp', $ktp);
    $kk = uploadFileWithFallback('kk', 'kk', $kk);
    $akta = uploadFileWithFallback('akta', 'akta', $akta);
    $surat = uploadFileWithFallback('surat_ahli_waris', 'ahli_waris', $surat);
    $foto = uploadFileWithFallback('foto_bukti', 'bukti', $foto);
    $dokumen_lain = uploadFileWithFallback('dokumen_lain', 'lainnya', $dokumen_lain);

    $cekDokumen = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM dokumen WHERE peserta_id='$id'"));

    if($cekDokumen > 0){
        mysqli_query($conn, "UPDATE dokumen SET
            ktp='$ktp',
            kk='$kk',
            akta='$akta',
            surat_ahli_waris='$surat',
            foto_bukti='$foto',
            dokumen_lain='$dokumen_lain'
            WHERE peserta_id='$id'");
    } else {
        mysqli_query($conn, "INSERT INTO dokumen(
            peserta_id, ktp, kk, akta, surat_ahli_waris, foto_bukti, dokumen_lain
        ) VALUES(
            '$id', '$ktp', '$kk', '$akta', '$surat', '$foto', '$dokumen_lain'
        )");
    }

    header("Location: detail_profile.php?id=$id");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Edit Peserta</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
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
#0a1830,
#102d63
);

min-height:100vh;

color:white;

overflow-x:hidden;

}

/* GLASS */

.glass-box{

background:
rgba(255,255,255,0.08);

backdrop-filter:blur(18px);

border-radius:25px;

padding:30px;

border:
1px solid rgba(255,255,255,0.08);

box-shadow:
0 10px 40px
rgba(0,0,0,0.25);

}

/* FORM */

.form-control,
.form-select{

background:
rgba(255,255,255,0.08);

border:
1px solid rgba(255,255,255,0.08);

color:#ffffff !important;

height:55px;

border-radius:16px;

padding-left:16px;

transition:0.3s;

}

/* TEXTAREA */

textarea.form-control{

height:130px;

padding-top:15px;

resize:none;

}

/* INPUT FOCUS */

.form-control:focus,
.form-select:focus{

background:
rgba(255,255,255,0.12);

border:
1px solid #3b82f6;

color:#ffffff !important;

box-shadow:
0 0 0 4px
rgba(59,130,246,0.15);

}

/* LABEL */

label{

color:#ffffff;

font-weight:600;

margin-bottom:10px;

display:block;

font-size:14px;

}

/* PLACEHOLDER */

.form-control::placeholder,
textarea::placeholder{

color:
rgba(255,255,255,0.55)
!important;

}

/* SELECT OPTION */

.form-select option{

background:#0f172a;

color:white;

}

/* TITLE */

.section-title{

font-size:24px;

font-weight:700;

margin-bottom:25px;
margin-top:30px;
border-left:4px solid #3b82f6;
padding-left:15px;
color:white;

}

.section-title.first{
margin-top:0;
}

/* BUTTON */

.modern-btn{

border:none;

border-radius:14px;

padding:12px 20px;

font-weight:600;

transition:0.3s;

}

.modern-btn:hover{

transform:
translateY(-2px);

}

/* CHECKBOX */

.checkbox-box{

background:
rgba(255,255,255,0.05);

padding:18px;

border-radius:16px;

border:
1px solid rgba(255,255,255,0.06);

}

.form-check-label{

color:white !important;

font-weight:500;

}

.form-check-input{

width:20px;

height:20px;

background-color:#0f172a;

border:
1px solid rgba(255,255,255,0.2);

cursor:pointer;

}

.form-check-input:checked{

background-color:#2563eb;

border-color:#2563eb;

}

/* FILE INPUT */

input[type="file"]{

padding:10px;

color:white !important;

}

/* FILE BUTTON */

input[type="file"]::file-selector-button{

background:#2563eb;

border:none;

padding:10px 15px;

border-radius:10px;

color:white;

margin-right:15px;

cursor:pointer;

transition:0.3s;

}

input[type="file"]::file-selector-button:hover{

background:#1d4ed8;

}

/* PREVIEW */

.preview-box{

background:
rgba(255,255,255,0.05);

padding:15px;

border-radius:16px;

text-align:center;

border:
1px solid rgba(255,255,255,0.06);

}

.preview-image{

width:100%;

height:180px;

object-fit:cover;

border-radius:14px;

margin-bottom:10px;

border:
2px solid rgba(255,255,255,0.08);

}

/* HEAD TEXT */

h2,
h4,
h5{

color:white;

}

/* PARAGRAPH */

p{

color:
rgba(255,255,255,0.7);

}

hr {
border-color: rgba(255,255,255,0.1);
margin:30px 0;
}

.optional-badge {
font-size:12px;
background:#16a34a;
padding:2px 8px;
border-radius:20px;
margin-left:10px;
vertical-align:middle;
}

/* SCROLLBAR */

::-webkit-scrollbar{
width:8px;
}

::-webkit-scrollbar-track{
background:#081426;
}

::-webkit-scrollbar-thumb{

background:#2563eb;

border-radius:20px;

}

/* MOBILE */

@media(max-width:768px){

.glass-box{

padding:20px;

}

.section-title{

font-size:20px;

}

.form-control,
.form-select{

height:50px;

font-size:14px;

}

.modern-btn{

width:100%;

}

}

</style>

</head>

<body>

<div class="container py-5">

<div class="glass-box">

<div class="d-flex
justify-content-between
align-items-center
flex-wrap
gap-3
mb-5">

<div>

<h2 class="fw-bold">

Edit Peserta

</h2>

<p class="opacity-75 mb-0">

Update data peserta dan ahli waris

</p>

</div>

<a href="profile.php"
class="btn btn-secondary modern-btn">

Kembali

</a>

</div>

<form method="POST"
enctype="multipart/form-data">

<?= csrf_field() ?>

<!-- DATA ALMARHUM/ALMARHUMAH (WAJIB) -->

<h4 class="section-title first">
Data Peserta
</h4>

<div class="row g-4 mb-5">

<div class="col-lg-6">

<label class="mb-2">
NIK
</label>

<input
type="text"
name="nik"
class="form-control"
value="<?= $data['nik'] ?>"
required>

</div>

<div class="col-lg-6">

<label class="mb-2">
No KK
</label>

<input
type="text"
name="no_kk"
class="form-control"
value="<?= $data['no_kk'] ?>">

</div>

<div class="col-lg-6">

<label class="mb-2">
Nama Lengkap
</label>

<input
type="text"
name="nama_lengkap"
class="form-control"
value="<?= $data['nama_lengkap'] ?>">

</div>

<div class="col-lg-6">

<label class="mb-2">
Tempat Lahir
</label>

<input
type="text"
name="tempat_lahir"
class="form-control"
value="<?= $data['tempat_lahir'] ?>">

</div>

<div class="col-lg-6">

<label class="mb-2">
Tanggal Lahir <span style="color:red">*</span>
</label>

<input
type="date"
name="tanggal_lahir"
class="form-control"
value="<?= $data['tanggal_lahir'] ?>"
required>

</div>

<div class="col-lg-6">

<label class="mb-2">
Jenis Kelamin
</label>

<select
name="jenis_kelamin"
class="form-select">

<option value="Laki-laki"
<?= $data['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>

Laki-laki

</option>

<option value="Perempuan"
<?= $data['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>

Perempuan

</option>

</select>

</div>

<!-- TANGGAL MENINGGAL DIPINDAHKAN KE SINI -->
<div class="col-lg-6">

<label class="mb-2">
Tanggal Meninggal
</label>

<input
type="date"
name="tanggal_meninggal"
class="form-control"
value="<?= $data['tanggal_meninggal'] ?>">

</div>

</div>

<!-- ALAMAT ALMARHUM -->

<h4 class="section-title">
Alamat Peserta
</h4>

<div class="row g-4 mb-5">

<div class="col-lg-12">

<textarea
name="alamat"
class="form-control"><?= $data['alamat'] ?></textarea>

</div>

<div class="col-lg-2">

<input
type="text"
name="rt"
class="form-control"
placeholder="RT"
value="<?= $data['rt'] ?>">

</div>

<div class="col-lg-2">

<input
type="text"
name="rw"
class="form-control"
placeholder="RW"
value="<?= $data['rw'] ?>">

</div>

<div class="col-lg-4">

<input
type="text"
name="kelurahan"
class="form-control"
placeholder="Kelurahan"
value="<?= $data['kelurahan'] ?>">

</div>

<div class="col-lg-4">

<input
type="text"
name="kecamatan"
class="form-control"
placeholder="Kecamatan"
value="<?= $data['kecamatan'] ?>">

</div>

<div class="col-lg-6">

<input
type="text"
name="kota"
class="form-control"
placeholder="Kota"
value="<?= $data['kota'] ?>">

</div>

<div class="col-lg-6">

<input
type="text"
name="provinsi"
class="form-control"
placeholder="Provinsi"
value="<?= $data['provinsi'] ?>">

</div>

</div>

<!-- PROGRAM & DATA LAIN -->

<h4 class="section-title">
Program Bantuan & Data Lain
</h4>

<div class="row g-4 mb-5">

<div class="col-lg-3">

<input
type="text"
name="agama"
class="form-control"
placeholder="Agama"
value="<?= $data['agama'] ?>">

</div>

<div class="col-lg-3">

<input
type="text"
name="denominasi"
class="form-control"
placeholder="Denominasi"
value="<?= $data['denominasi'] ?>">

</div>

<div class="col-lg-3">

<input
type="text"
name="profesi"
class="form-control"
placeholder="Profesi"
value="<?= $data['profesi'] ?>">

</div>

<div class="col-lg-3">

<input
type="text"
name="pekerjaan"
class="form-control"
placeholder="Pekerjaan"
value="<?= $data['pekerjaan'] ?>">

</div>

<div class="col-lg-6">

<select
name="tipe_program"
class="form-select">

<option value="JKK"
<?= $data['tipe_program'] == 'JKK' ? 'selected' : '' ?>>

JKK

</option>

<option value="JKM"
<?= $data['tipe_program'] == 'JKM' ? 'selected' : '' ?>>

JKM

</option>

</select>

</div>

<div class="col-lg-12">

<textarea
name="keterangan"
class="form-control"
placeholder="Keterangan tambahan"><?= $data['keterangan'] ?></textarea>

</div>

</div>

<!-- STATUS BANTUAN -->

<h4 class="section-title">
Status Bantuan
</h4>

<div class="row g-4 mb-5">

<div class="col-lg-6">

<label class="mb-2">Status Rekomendasi</label>

<select name="status_rekom" class="form-select">
<?php foreach(rekom_list() as $key => $label): ?>
<option value="<?= $key ?>" <?= ($data['status_rekom'] ?? 'belum') === $key ? 'selected' : '' ?>>
<?= $label ?>
</option>
<?php endforeach; ?>
</select>

</div>

<div class="col-lg-6">

<label class="mb-2">Status Klaim / Cair</label>

<select name="status_klaim" class="form-select">
<?php foreach(klaim_list() as $key => $label): ?>
<option value="<?= $key ?>" <?= ($data['status_klaim'] ?? 'belum') === $key ? 'selected' : '' ?>>
<?= $label ?>
</option>
<?php endforeach; ?>
</select>

</div>

</div>

<!-- LOKASI GIS (OPSIONAL) -->

<h4 class="section-title">
Lokasi GIS (Opsional)
</h4>

<div class="row g-4 mb-5">

<div class="col-lg-4">

<input
type="text"
name="latitude"
class="form-control"
placeholder="Latitude"
value="<?= $data['latitude'] ?>">

</div>

<div class="col-lg-4">

<input
type="text"
name="longitude"
class="form-control"
placeholder="Longitude"
value="<?= $data['longitude'] ?>">

</div>

<div class="col-lg-4">

<input
type="text"
name="alamat_lokasi"
class="form-control"
placeholder="Alamat Lokasi (Peta)"
value="<?= $data['alamat_lokasi'] ?>">

</div>

</div>

<hr>

<!-- DATA AHLI WARIS (OPSIONAL) -->

<h4 class="section-title">
Data Ahli Waris <span class="optional-badge">Opsional</span>
</h4>
<p class="small opacity-75 mb-3">Isi jika ada, jika tidak ada biarkan kosong</p>

<div class="row g-4 mb-5">

<div class="col-lg-6">

<label class="mb-2">
Nama Lengkap Ahli Waris
</label>

<input
type="text"
name="nama_ahli_waris"
class="form-control"
value="<?= $data['nama_ahli_waris'] ?>">

</div>

<div class="col-lg-6">

<label class="mb-2">
Nomor Telepon
</label>

<input
type="tel"
name="no_telp_ahli_waris"
class="form-control"
placeholder="08xx-xxxx-xxxx"
value="<?= $data['no_telp_ahli_waris'] ?>">

</div>

<div class="col-lg-4">

<label class="mb-2">
Status Ahli Waris
</label>

<select
name="status_ahli_waris"
class="form-select">

<option value="">-- Pilih --</option>
<option value="Suami"
<?= $data['status_ahli_waris'] == 'Suami' ? 'selected' : '' ?>>Suami</option>
<option value="Istri"
<?= $data['status_ahli_waris'] == 'Istri' ? 'selected' : '' ?>>Istri</option>
<option value="Anak kandung"
<?= $data['status_ahli_waris'] == 'Anak kandung' ? 'selected' : '' ?>>Anak kandung</option>
<option value="Orang tua"
<?= $data['status_ahli_waris'] == 'Orang tua' ? 'selected' : '' ?>>Orang tua</option>
<option value="Saudara kandung"
<?= $data['status_ahli_waris'] == 'Saudara kandung' ? 'selected' : '' ?>>Saudara kandung</option>
<option value="Lainnya"
<?= $data['status_ahli_waris'] == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>

</select>

</div>

<div class="col-lg-4">

<label class="mb-2">
Pekerjaan Ahli Waris
</label>

<input
type="text"
name="pekerjaan_ahli_waris"
class="form-control"
value="<?= $data['pekerjaan_ahli_waris'] ?>">

</div>

</div>

<h5 class="mt-2 mb-3">Alamat Ahli Waris (Opsional)</h5>

<div class="row g-4 mb-5">

<div class="col-lg-12">

<textarea
name="alamat_ahli_waris"
class="form-control"
placeholder="Alamat lengkap ahli waris"><?= $data['alamat_ahli_waris'] ?></textarea>

</div>

<div class="col-lg-2">

<input
type="text"
name="rt_ahli_waris"
class="form-control"
placeholder="RT"
value="<?= $data['rt_ahli_waris'] ?>">

</div>

<div class="col-lg-2">

<input
type="text"
name="rw_ahli_waris"
class="form-control"
placeholder="RW"
value="<?= $data['rw_ahli_waris'] ?>">

</div>

<div class="col-lg-4">

<input
type="text"
name="kelurahan_ahli_waris"
class="form-control"
placeholder="Kelurahan"
value="<?= $data['kelurahan_ahli_waris'] ?>">

</div>

<div class="col-lg-4">

<input
type="text"
name="kecamatan_ahli_waris"
class="form-control"
placeholder="Kecamatan"
value="<?= $data['kecamatan_ahli_waris'] ?>">

</div>

<div class="col-lg-6">

<input
type="text"
name="kota_ahli_waris"
class="form-control"
placeholder="Kota"
value="<?= $data['kota_ahli_waris'] ?>">

</div>

<div class="col-lg-6">

<input
type="text"
name="provinsi_ahli_waris"
class="form-control"
placeholder="Provinsi"
value="<?= $data['provinsi_ahli_waris'] ?>">

</div>

</div>

<hr>

<!-- DOKUMEN -->

<h4 class="section-title">
Dokumen Peserta
</h4>

<div class="row g-4 mb-5">

<div class="col-lg-6">

<label class="mb-2">
Upload KTP
</label>

<input
type="file"
name="ktp"
class="form-control">

</div>

<div class="col-lg-6">

<label class="mb-2">
Upload KK
</label>

<input
type="file"
name="kk"
class="form-control">

</div>

<div class="col-lg-6">

<label class="mb-2">
Upload Akta
</label>

<input
type="file"
name="akta"
class="form-control">

</div>

<div class="col-lg-6">

<label class="mb-2">
Surat Ahli Waris
</label>

<input
type="file"
name="surat_ahli_waris"
class="form-control">

</div>

<div class="col-lg-6">

<label class="mb-2">
Foto Bukti
</label>

<input
type="file"
name="foto_bukti"
class="form-control">

</div>

<div class="col-lg-6">

<label class="mb-2">
Dokumen Lain
</label>

<input
type="file"
name="dokumen_lain"
class="form-control">

</div>

</div>

<button
type="submit"
name="update"
class="btn btn-primary modern-btn">

Simpan Perubahan

</button>

</form>

</div>

</div>

<script>

// Auto-extract date of birth from NIK - real-time extraction
document.addEventListener('DOMContentLoaded', function(){

    const nikInput = document.querySelector('input[name="nik"]');
    const tanggalLahirInput = document.querySelector('input[name="tanggal_lahir"]');

    function extractDateFromNIK(){
        const nik = nikInput.value.replace(/\D/g, ''); // Remove non-digits
        
        console.log('NIK entered:', nik, 'Length:', nik.length);

        if(nik.length >= 12){
            // NIK format: PPPPPPDDMMYYGGGGGGG (16 digits total)
            // Positions (0-indexed):
            // 0-5: Province/District code (6 digits)
            // 6-7: Day (DD)
            // 8-9: Month (MM)
            // 10-11: Year (YY)
            // 12-15: Serial number

            const dayStr = nik.substring(6, 8);
            const monthStr = nik.substring(8, 10);
            const yearStr = nik.substring(10, 12);

            console.log('Extracted - Day:', dayStr, 'Month:', monthStr, 'Year:', yearStr);

            // Validate day and month are numeric
            const day = parseInt(dayStr);
            const month = parseInt(monthStr);
            const yearNum = parseInt(yearStr);

            // Validate ranges
            if(day >= 1 && day <= 31 && month >= 1 && month <= 12){
                // Convert 2-digit year
                let fullYear = yearNum;
                if(fullYear <= 30){
                    fullYear = 2000 + fullYear;
                } else {
                    fullYear = 1900 + fullYear;
                }

                // Format as YYYY-MM-DD with leading zeros
                const dateStr = fullYear + '-' + 
                    (month < 10 ? '0' : '') + month + '-' + 
                    (day < 10 ? '0' : '') + day;

                console.log('Formatted date:', dateStr);
                tanggalLahirInput.value = dateStr;
            } else {
                console.log('Invalid day/month values');
            }
        }
    }

    if(nikInput && tanggalLahirInput){
        // Trigger on every keystroke
        nikInput.addEventListener('input', extractDateFromNIK);
        nikInput.addEventListener('keyup', extractDateFromNIK);
        nikInput.addEventListener('change', extractDateFromNIK);
    }

});

</script>

</body>
</html>