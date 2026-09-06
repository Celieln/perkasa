<?php
/**
 * One-time script to generate tracking codes for existing peserta records.
 * Akses: hanya untuk superadmin/admin
 * Usage: Kunjungi halaman ini di browser, atau jalankan via CLI
 */

include '../config.php';

if(!isset($_SESSION['login'])){
    header("Location: ../index.php");
    exit;
}

if(!is_admin()){
    echo "<script>alert('Akses ditolak');window.location='../dashboard.php';</script>";
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action === 'generate' && isset($_GET['confirm']) && $_GET['confirm'] === 'yes'){
    // Cari semua peserta yang belum punya tracking_code
    $query = mysqli_query($conn, "SELECT id, nama_lengkap FROM peserta WHERE tracking_code IS NULL OR tracking_code = ''");
    $total = mysqli_num_rows($query);
    $updated = 0;

    while($row = mysqli_fetch_assoc($query)){
        $code = generate_tracking_code($conn);
        mysqli_query($conn, "UPDATE peserta SET tracking_code='$code' WHERE id='{$row['id']}'");

        // Buat tracking log awal
        $status = 'TERDAFTAR';
        insert_tracking_log($conn, $row['id'], $status, 'Data peserta sudah ada sebelum fitur tracking diterapkan');

        $updated++;
    }

    $msg = "Berhasil: $updated dari $total peserta mendapatkan tracking code.";
}

// Hitung peserta yang belum punya tracking code
$belum = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM peserta WHERE tracking_code IS NULL OR tracking_code = ''"));
$total = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM peserta"));

?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generate Tracking Codes — PERKASA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{
    background:linear-gradient(135deg,#061220,#081426,#102d63);
    min-height:100vh;color:white;
}
.glass-box{
    background:rgba(255,255,255,0.07);
    backdrop-filter:blur(16px);
    border-radius:24px;
    border:1px solid rgba(255,255,255,0.08);
    padding:28px;
    box-shadow:0 10px 40px rgba(0,0,0,0.25);
}
.modern-btn{
    border:none;border-radius:14px;padding:12px 20px;
    font-weight:600;transition:0.3s;
}
.modern-btn:hover{transform:translateY(-2px);}
</style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="glass-box text-center">
                <h2 class="fw-bold mb-3">🔄 Generate Tracking Code</h2>

                <?php if(isset($msg)): ?>
                    <div class="alert alert-success"><?= $msg ?></div>
                    <a href="profile.php" class="btn btn-primary modern-btn mt-2">← Kembali ke Peserta</a>
                <?php else: ?>
                    <p class="opacity-75 mb-4">
                        Total peserta: <strong><?= $total ?></strong><br>
                        Belum punya tracking code: <strong><?= $belum ?></strong>
                    </p>

                    <?php if($belum > 0): ?>
                        <p class="mb-4">Klik tombol di bawah untuk generate tracking code untuk <?= $belum ?> peserta yang belum memilikinya.</p>
                        <a href="?action=generate&confirm=yes"
                           onclick="return confirm('Generate tracking code untuk <?= $belum ?> peserta? Proses ini tidak bisa dibatalkan.')"
                           class="btn btn-primary modern-btn">
                            Generate Tracking Code
                        </a>
                    <?php else: ?>
                        <div class="alert alert-success">Semua peserta sudah memiliki tracking code ✅</div>
                    <?php endif; ?>

                    <a href="profile.php" class="btn btn-secondary modern-btn mt-3 d-block">← Kembali</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
