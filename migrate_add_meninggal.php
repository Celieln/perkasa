<?php
include 'config.php';

$sql = "ALTER TABLE peserta ADD COLUMN `tanggal_meninggal` DATE DEFAULT NULL AFTER `keterangan`";

if(mysqli_query($conn, $sql)){
    echo "<h2>Migration Berhasil!</h2>";
    echo "<p>Kolom <code>tanggal_meninggal</code> berhasil ditambahkan ke tabel <code>peserta</code>.</p>";
    echo "<p><a href='index.php'>Kembali ke Beranda</a></p>";
} else {
    echo "<h2>Migration Gagal</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    echo "<p>Kemungkinan kolom sudah ada.</p>";
    echo "<p><a href='index.php'>Kembali ke Beranda</a></p>";
}

mysqli_close($conn);
?>
