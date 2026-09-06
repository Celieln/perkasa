<?php
header('Content-Type: text/plain; charset=utf-8');

$host = 'sql310.infinityfree.com';
$user = 'if0_42618711';
$pass = 'ZxQ3OB8G5St';
$db   = 'if0_42618711_perkasa';

$conn = @mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
echo "Koneksi database OK\n";

// Cek apakah kolom sudah ada
$check = mysqli_query($conn, "SHOW COLUMNS FROM peserta LIKE 'tanggal_meninggal'");
if(mysqli_num_rows($check) > 0){
    echo "Kolom 'tanggal_meninggal' sudah ada. Tidak perlu ditambahkan.\n";
} else {
    $sql = "ALTER TABLE peserta ADD COLUMN `tanggal_meninggal` DATE DEFAULT NULL AFTER `keterangan`";
    if(mysqli_query($conn, $sql)){
        echo "Berhasil! Kolom 'tanggal_meninggal' ditambahkan ke tabel peserta.\n";
    } else {
        echo "Gagal: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
echo "Selesai.\n";
?>
