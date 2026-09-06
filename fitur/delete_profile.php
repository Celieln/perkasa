<?php
// delete_profile.php
include '../config.php';


if(!isset($_SESSION['login'])){
    header("Location: ../index.php");
    exit;
}

deny_if_readonly('Anda tidak memiliki izin untuk menghapus data peserta');

if(!isset($_GET['id']) || empty($_GET['id'])){
    $_SESSION['error'] = "ID peserta tidak valid.";
    header("Location: profile.php");
    exit;
}

$id = (int)$_GET['id'];

// Ambil data dokumen terlebih dahulu untuk menghapus file fisik
$query_dok = "SELECT ktp, kk, akta, surat_ahli_waris, foto_bukti, dokumen_lain FROM dokumen WHERE peserta_id = $id";
$result_dok = mysqli_query($conn, $query_dok);
$dokumen = mysqli_fetch_assoc($result_dok);

// Hapus data peserta (dokumen otomatis terhapus karena foreign key CASCADE)
$query_delete = "DELETE FROM peserta WHERE id = $id";
if(mysqli_query($conn, $query_delete)){
    // Jika berhasil hapus, hapus file fisik dokumen
    $upload_base = "../uploads/";
    $folders = [
        'ktp' => $dokumen['ktp'] ?? '',
        'kk' => $dokumen['kk'] ?? '',
        'akta' => $dokumen['akta'] ?? '',
        'ahli_waris' => $dokumen['surat_ahli_waris'] ?? '',
        'bukti' => $dokumen['foto_bukti'] ?? '',
        'lainnya' => $dokumen['dokumen_lain'] ?? ''
    ];
    
    foreach($folders as $folder => $file){
        if(!empty($file)){
            $file_path = $upload_base . $folder . '/' . $file;
            if(file_exists($file_path)){
                unlink($file_path); // hapus file
            }
        }
    }
    
    $_SESSION['success'] = "Data peserta berhasil dihapus.";
} else {
    error_log("Hapus peserta gagal: " . mysqli_error($conn));
    $_SESSION['error'] = "Gagal menghapus data.";
}

header("Location: profile.php");
exit;
?>