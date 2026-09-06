<?php

include '../config.php';



if(!isset($_SESSION['login'])){
    header("Location: ../index.php");
    exit;
}

deny_if_readonly('Anda tidak memiliki izin untuk mengakses halaman ini');

if(isset($_POST['create'])){
    verify_csrf();

    // Only admin / superadmin can create peserta
    if(!is_admin()){
        echo "\n        <script>\n        alert('Anda tidak memiliki izin untuk menambah data peserta');\n        window.history.back();\n        </script>\n        ";
        exit;
    }

    $nik = mysqli_real_escape_string($conn, $_POST['nik']);
    $no_kk = mysqli_real_escape_string($conn, $_POST['no_kk']);

    // Validasi NIK tidak kosong
    if(empty($nik)){
        echo "\n        <script>\n        alert('NIK tidak boleh kosong');\n        window.location='create_profile.php';\n        </script>\n        ";
        exit;
    }

    // Cek NIK sudah ada
    $nikCheck = mysqli_query($conn, "SELECT id FROM peserta WHERE nik='$nik'");
    if(mysqli_num_rows($nikCheck) > 0){
        echo "\n        <script>\n        alert('NIK sudah terdaftar di sistem. Silakan gunakan NIK yang berbeda.');\n        window.history.back();\n        </script>\n        ";
        exit;
    }

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

    // ========== KOLOM BARU UNTUK ALMARHUM ==========
    $pekerjaan = mysqli_real_escape_string($conn, $_POST['pekerjaan']);                     // pekerjaan almarhum
    
    // PERBAIKAN: handling tanggal_meninggal agar NULL jika kosong (tidak error)
    $tanggal_meninggal = !empty($_POST['tanggal_meninggal']) ? "'" . mysqli_real_escape_string($conn, $_POST['tanggal_meninggal']) . "'" : "NULL";

    // ========== KOLOM GIS (OPSIONAL) ==========
    $latitude = !empty($_POST['latitude']) ? "'" . mysqli_real_escape_string($conn, $_POST['latitude']) . "'" : "NULL";
    $longitude = !empty($_POST['longitude']) ? "'" . mysqli_real_escape_string($conn, $_POST['longitude']) . "'" : "NULL";
    $alamat_lokasi = !empty($_POST['alamat_lokasi']) ? "'" . mysqli_real_escape_string($conn, $_POST['alamat_lokasi']) . "'" : "NULL";

    // ========== KOLOM UNTUK AHLI WARIS (OPSIONAL) ==========
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

    $program = mysqli_real_escape_string($conn, $_POST['tipe_program']);

    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    $status_rekom = in_array($_POST['status_rekom'], array_keys(rekom_list())) ? $_POST['status_rekom'] : 'belum';
    $status_klaim = in_array($_POST['status_klaim'], array_keys(klaim_list())) ? $_POST['status_klaim'] : 'belum';

    // Generate tracking code
    $tracking_code = generate_tracking_code($conn);

    // PERHATIAN: Gunakan variabel $tanggal_meninggal, $latitude dll yang sudah berisi 'value' atau NULL tanpa kutip tambahan
    $insertQuery = "INSERT INTO peserta(

    nik,
    no_kk,

    nama_lengkap,

    tempat_lahir,
    tanggal_lahir,

    jenis_kelamin,

    alamat,

    rt,
    rw,

    kelurahan,
    kecamatan,

    kota,
    provinsi,

    agama,
    denominasi,

    profesi,

    pekerjaan,

    tipe_program,

    status_rekom,
    status_klaim,

    keterangan,

    tanggal_meninggal,

    latitude,
    longitude,
    alamat_lokasi,

    nama_ahli_waris,
    no_telp_ahli_waris,
    status_ahli_waris,
    pekerjaan_ahli_waris,
    alamat_ahli_waris,
    rt_ahli_waris,
    rw_ahli_waris,
    kelurahan_ahli_waris,
    kecamatan_ahli_waris,
    kota_ahli_waris,
    provinsi_ahli_waris,

    tracking_code

    ) VALUES(

    '$nik',
    '$no_kk',

    '$nama',

    '$tempat_lahir',
    '$tanggal_lahir',

    '$jenis_kelamin',

    '$alamat',

    '$rt',
    '$rw',

    '$kelurahan',
    '$kecamatan',

    '$kota',
    '$provinsi',

    '$agama',
    '$denominasi',

    '$profesi',

    '$pekerjaan',

    '$program',

    '$status_rekom',
    '$status_klaim',

    '$keterangan',

    $tanggal_meninggal,

    $latitude,
    $longitude,
    $alamat_lokasi,

    $nama_ahli_waris,
    $no_telp_ahli_waris,
    $status_ahli_waris,
    $pekerjaan_ahli_waris,
    $alamat_ahli_waris,
    $rt_ahli_waris,
    $rw_ahli_waris,
    $kelurahan_ahli_waris,
    $kecamatan_ahli_waris,
    $kota_ahli_waris,
    $provinsi_ahli_waris,

    '$tracking_code'

    )";

    $result = mysqli_query($conn, $insertQuery);

    // Error handling untuk INSERT peserta
    if(!$result){
        $error = mysqli_error($conn);
        
        if(strpos($error, 'Duplicate entry') !== false){
            echo "\n        <script>\n        alert('NIK sudah terdaftar di sistem. Silakan gunakan NIK yang berbeda.');\n        window.history.back();\n        </script>\n        ";
        } else {
            echo "\n        <script>\n        alert('Terjadi kesalahan saat menyimpan data. Silakan cek kembali form Anda.');\n        window.history.back();\n        </script>\n        ";
        }
        exit;
    }

    $peserta_id =
    mysqli_insert_id($conn);

    // Catat tracking log awal
    insert_tracking_log($conn, $peserta_id, 'TERDAFTAR', 'Data peserta berhasil didaftarkan oleh ' . ($_SESSION['nama'] ?? 'Staff'));

    // Catat log status awal
    if($status_rekom != 'belum'){
        insert_tracking_log($conn, $peserta_id, 'REKOM', 'Status rekomendasi awal: ' . rekom_label($status_rekom) . ' oleh ' . ($_SESSION['nama'] ?? 'Staff'));
    }
    if($status_klaim != 'belum'){
        insert_tracking_log($conn, $peserta_id, 'KLAIM', 'Status klaim awal: ' . klaim_label($status_klaim) . ' oleh ' . ($_SESSION['nama'] ?? 'Staff'));
    }

    /* =====================
       UPLOAD DOKUMEN
    ===================== */

    function uploadFileByName(
        $name,
        $folder
    ){

        if(
        isset($_FILES[$name])
        &&
        $_FILES[$name]['name']
        ){

            $file =
            time().'_'.
            basename(
            $_FILES[$name]['name']
            );

            // Buat folder jika belum ada
            if(!is_dir("../uploads/$folder")){
                mkdir("../uploads/$folder", 0777, true);
            }

            move_uploaded_file(

            $_FILES[$name]['tmp_name'],

            "../uploads/$folder/".$file

            );

            return $file;

        }

        return '';

    }

    $ktp =
uploadFileByName(
    'ktp',
    'ktp'
    );

    $kk =
uploadFileByName(
    'kk',
    'kk'
    );

    $akta =
uploadFileByName(
    'akta',
    'akta'
    );

    $surat =
uploadFileByName(
    'surat_ahli_waris',
    'ahli_waris'
    );

    $foto =
uploadFileByName(
    'foto_bukti',
    'bukti'
    );

    $dokumen_lain =
uploadFileByName(
    'dokumen_lain',
    'lainnya'
    );

    mysqli_query($conn,

    "INSERT INTO dokumen(

    peserta_id,

    ktp,
    kk,
    akta,

    surat_ahli_waris,

    foto_bukti,

    dokumen_lain

    ) VALUES(

    '$peserta_id',

    '$ktp',
    '$kk',
    '$akta',

    '$surat',

    '$foto',

    '$dokumen_lain'

    )");

    header(
    "Location: profile.php"
    );

    exit;

}

?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Create Peserta</title>

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

.form-control,
.form-select{

background:
rgba(255,255,255,0.08);

border:
1px solid rgba(255,255,255,0.08);

color:white !important;

height:55px;

border-radius:16px;

padding-left:16px;

}

.form-control:focus,
.form-select:focus{

background:
rgba(255,255,255,0.12);

border:
1px solid #3b82f6;

color:white !important;

box-shadow:
0 0 0 4px
rgba(59,130,246,0.15);

}

textarea.form-control{

height:130px;

padding-top:15px;

resize:none;

}

.form-control::placeholder,
textarea::placeholder{

color:
rgba(255,255,255,0.55)
!important;

}

.form-select option{

background:#0f172a;

color:white;

}

label{

font-weight:600;

margin-bottom:10px;

display:block;

font-size:14px;

}

.section-title{

font-size:24px;

font-weight:700;

margin-bottom:25px;
margin-top:30px;
border-left:4px solid #3b82f6;
padding-left:15px;

}

.section-title.first{
margin-top:0;
}

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

cursor:pointer;

}

input[type="file"]{

padding:10px;

color:white !important;

}

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

hr {
border-color: rgba(255,255,255,0.1);
margin: 30px 0;
}

.optional-badge {
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

.form-control,
.form-select{
height:50px;
font-size:14px;
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

Tambah Peserta

</h2>

<p class="opacity-75 mb-0">

Create data peserta PERKASA

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

<!-- ==================== DATA ALMARHUM/ALMARHUMAH (WAJIB) ==================== -->
<h4 class="section-title first">
Data Peserta <span class="optional-badge" style="background:#2563eb;">Wajib</span>
</h4>

<!-- IDENTITAS -->
<div class="row g-4 mb-5">

<div class="col-lg-6">

<label>NIK</label>

<input
type="text"
name="nik"
class="form-control"
required>

</div>

<div class="col-lg-6">

<label>No KK</label>

<input
type="text"
name="no_kk"
class="form-control">

</div>

<div class="col-lg-6">

<label>Nama Lengkap</label>

<input
type="text"
name="nama_lengkap"
class="form-control" required>

</div>

<div class="col-lg-6">

<label>Tempat Lahir</label>

<input
type="text"
name="tempat_lahir"
class="form-control">

</div>

<div class="col-lg-6">

<label>Tanggal Lahir <span style="color:red">*</span></label>

<input
type="date"
name="tanggal_lahir"
class="form-control"
required>

</div>

<div class="col-lg-6">

<label>Jenis Kelamin</label>

<select
name="jenis_kelamin"
class="form-select">

<option value="Laki-laki">
Laki-laki
</option>

<option value="Perempuan">
Perempuan
</option>

</select>

</div>

<!-- TANGGAL MENINGGAL DITAMBAHKAN DI SINI (BAGIAN ALMARHUM) -->
<div class="col-lg-6">

<label>Tanggal Meninggal</label>

<input
type="date"
name="tanggal_meninggal"
class="form-control">

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
class="form-control"
placeholder="Alamat lengkap"></textarea>

</div>

<div class="col-lg-2">

<input
type="text"
name="rt"
class="form-control"
placeholder="RT">

</div>

<div class="col-lg-2">

<input
type="text"
name="rw"
class="form-control"
placeholder="RW">

</div>

<div class="col-lg-4">

<input
type="text"
name="kelurahan"
class="form-control"
placeholder="Kelurahan">

</div>

<div class="col-lg-4">

<input
type="text"
name="kecamatan"
class="form-control"
placeholder="Kecamatan">

</div>

<div class="col-lg-6">

<input
type="text"
name="kota"
class="form-control"
placeholder="Kota">

</div>

<div class="col-lg-6">

<input
type="text"
name="provinsi"
class="form-control"
placeholder="Provinsi">

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
placeholder="Agama">

</div>

<div class="col-lg-3">

<input
type="text"
name="denominasi"
class="form-control"
placeholder="Denominasi">

</div>

<div class="col-lg-3">

<input
type="text"
name="profesi"
class="form-control"
placeholder="Profesi">

</div>

<div class="col-lg-3">

<input
type="text"
name="pekerjaan"
class="form-control"
placeholder="Pekerjaan">

</div>

<div class="col-lg-6">

<select
name="tipe_program"
class="form-select">

<option value="JKK">
JKK
</option>

<option value="JKM">
JKM
</option>

</select>

</div>

<div class="col-lg-12">

<textarea
name="keterangan"
class="form-control"
placeholder="Keterangan tambahan"></textarea>

</div>

</div>

<!-- STATUS BANTUAN (TETAP ADA) -->
<h4 class="section-title">
Status Bantuan
</h4>

<div class="row g-4 mb-5">

<div class="col-lg-6">

<label>Status Rekomendasi</label>

<select name="status_rekom" class="form-select">
<?php foreach(rekom_list() as $key => $label): ?>
<option value="<?= $key ?>" <?= ($_POST['status_rekom'] ?? 'belum') === $key ? 'selected' : '' ?>>
<?= $label ?>
</option>
<?php endforeach; ?>
</select>

</div>

<div class="col-lg-6">

<label>Status Klaim / Cair</label>

<select name="status_klaim" class="form-select">
<?php foreach(klaim_list() as $key => $label): ?>
<option value="<?= $key ?>" <?= ($_POST['status_klaim'] ?? 'belum') === $key ? 'selected' : '' ?>>
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
placeholder="Latitude">

</div>

<div class="col-lg-4">

<input
type="text"
name="longitude"
class="form-control"
placeholder="Longitude">

</div>

<div class="col-lg-4">

<input
type="text"
name="alamat_lokasi"
class="form-control"
placeholder="Alamat Lokasi (Peta)">

</div>

</div>

<hr>

<!-- ==================== DATA AHLI WARIS (OPSIONAL) ==================== -->
<h4 class="section-title">
Data Ahli Waris <span class="optional-badge">Opsional</span>
</h4>
<p class="small opacity-75 mb-3">Isi jika ada, jika tidak ada biarkan kosong</p>

<div class="row g-4 mb-5">

<div class="col-lg-6">

<label>Nama Lengkap Ahli Waris</label>

<input
type="text"
name="nama_ahli_waris"
class="form-control">

</div>

<div class="col-lg-6">

<label>Nomor Telepon</label>

<input
type="tel"
name="no_telp_ahli_waris"
class="form-control"
placeholder="08xx-xxxx-xxxx">

</div>

<div class="col-lg-4">

<label>Status Ahli Waris</label>

<select
name="status_ahli_waris"
class="form-select">

<option value="">-- Pilih --</option>
<option value="Suami">Suami</option>
<option value="Istri">Istri</option>
<option value="Anak kandung">Anak kandung</option>
<option value="Orang tua">Orang tua</option>
<option value="Saudara kandung">Saudara kandung</option>
<option value="Lainnya">Lainnya</option>

</select>

</div>

<div class="col-lg-4">

<label>Pekerjaan Ahli Waris</label>

<input
type="text"
name="pekerjaan_ahli_waris"
class="form-control">

</div>

</div>

<h4 class="section-title">
Alamat Ahli Waris (Opsional)
</h4>

<div class="row g-4 mb-5">

<div class="col-lg-12">

<textarea
name="alamat_ahli_waris"
class="form-control"
placeholder="Alamat lengkap ahli waris"></textarea>

</div>

<div class="col-lg-2">

<input
type="text"
name="rt_ahli_waris"
class="form-control"
placeholder="RT">

</div>

<div class="col-lg-2">

<input
type="text"
name="rw_ahli_waris"
class="form-control"
placeholder="RW">

</div>

<div class="col-lg-4">

<input
type="text"
name="kelurahan_ahli_waris"
class="form-control"
placeholder="Kelurahan">

</div>

<div class="col-lg-4">

<input
type="text"
name="kecamatan_ahli_waris"
class="form-control"
placeholder="Kecamatan">

</div>

<div class="col-lg-6">

<input
type="text"
name="kota_ahli_waris"
class="form-control"
placeholder="Kota">

</div>

<div class="col-lg-6">

<input
type="text"
name="provinsi_ahli_waris"
class="form-control"
placeholder="Provinsi">

</div>

</div>

<hr>

<!-- DOKUMEN -->
<h4 class="section-title">
Dokumen Peserta
</h4>

<div class="row g-4 mb-5">

<div class="col-lg-6">

<label>Upload KTP</label>

<input
type="file"
name="ktp"
class="form-control">

</div>

<div class="col-lg-6">

<label>Upload KK</label>

<input
type="file"
name="kk"
class="form-control">

</div>

<div class="col-lg-6">

<label>Upload Akta</label>

<input
type="file"
name="akta"
class="form-control">

</div>

<div class="col-lg-6">

<label>Surat Ahli Waris</label>

<input
type="file"
name="surat_ahli_waris"
class="form-control">

</div>

<div class="col-lg-6">

<label>Foto Bukti</label>

<input
type="file"
name="foto_bukti"
class="form-control">

</div>

<div class="col-lg-6">

<label>Dokumen Lain</label>

<input
type="file"
name="dokumen_lain"
class="form-control">

</div>

</div>

<button
type="submit"
name="create"
class="btn btn-primary modern-btn">

Simpan Peserta

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