<?php

include '../config.php';

if(!isset($_SESSION['login'])){
    header("Location: ../index.php");
    exit;
}

if(!is_admin()){
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini');window.location='../dashboard.php';</script>";
    exit;
}

// Mark as read
if(isset($_GET['read'])){
    $id = (int)$_GET['read'];
    mysqli_query($conn, "UPDATE feedback SET status='dibaca' WHERE id='$id'");
    header("Location: feedback_manage.php");
    exit;
}

// Mark as done
if(isset($_GET['done'])){
    $id = (int)$_GET['done'];
    mysqli_query($conn, "UPDATE feedback SET status='selesai' WHERE id='$id'");
    header("Location: feedback_manage.php");
    exit;
}

// Delete
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM feedback WHERE id='$id'");
    header("Location: feedback_manage.php");
    exit;
}

// Filter
$where = "";
if(isset($_GET['status']) && $_GET['status'] != ''){
    $filter = mysqli_real_escape_string($conn, $_GET['status']);
    $where = "WHERE status='$filter'";
}

$query = mysqli_query($conn, "SELECT * FROM feedback $where ORDER BY created_at DESC");

// Stats
$total = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM feedback"));
$pending = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM feedback WHERE status='pending'"));
$dibaca = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM feedback WHERE status='dibaca'"));
$selesai = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM feedback WHERE status='selesai'"));

?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Feedback — PERKASA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{
    background:linear-gradient(135deg,#061220,#081426,#102d63);
    min-height:100vh;color:white;overflow-x:hidden;
}
.glass-box{
    background:rgba(255,255,255,0.07);
    backdrop-filter:blur(16px);
    border-radius:24px;
    border:1px solid rgba(255,255,255,0.08);
    padding:28px;
    box-shadow:0 10px 40px rgba(0,0,0,0.25);
}
.stats-card{
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:22px;padding:22px;transition:0.3s;height:100%;
}
.stats-card:hover{transform:translateY(-5px);background:rgba(255,255,255,0.08);}
.stats-value{font-size:36px;font-weight:bold;}
.stats-title{font-size:14px;opacity:0.7;margin-bottom:8px;}
.filter-box{
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.08);
    color:white !important;height:50px;border-radius:14px;padding:0 15px;min-width:170px;
}
.filter-box:focus{
    outline:none;box-shadow:0 0 0 4px rgba(59,130,246,0.15);
    background:rgba(255,255,255,0.12);border:1px solid #3b82f6;
}
.filter-box option{background:#0f172a;color:white;}
.modern-btn{
    border:none;border-radius:14px;padding:10px 18px;
    font-weight:600;transition:0.3s;
}
.modern-btn:hover{transform:translateY(-2px);}
.table-dark{--bs-table-bg: transparent;}
.table{color:white;margin-bottom:0;}
.table thead th{
    border-bottom:1px solid rgba(255,255,255,0.08);
    padding:16px;font-size:13px;text-transform:uppercase;letter-spacing:1px;opacity:0.8;
}
.table tbody td{padding:16px;vertical-align:middle;border-color:rgba(255,255,255,0.05);}
.table tbody tr{transition:0.3s;}
.table tbody tr:hover{background:rgba(255,255,255,0.04);}
.feedback-msg{
    background:rgba(255,255,255,0.04);
    border-radius:14px;padding:14px;margin-top:6px;
    border-left:3px solid #3b82f6;
}
@media(max-width:768px){
    .glass-box{padding:20px;}
    .table{min-width:900px;}
    .stats-value{font-size:28px;}
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background:rgba(255,255,255,0.05);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,0.08);padding:15px 0;position:sticky;top:0;z-index:9999;">
<div class="container">
<a class="navbar-brand fw-bold text-white" href="../dashboard.php">PERKASA</a>
<div class="d-flex gap-2 flex-wrap">
<a href="../dashboard.php" class="btn btn-primary modern-btn">Dashboard</a>
<a href="feedback_manage.php" class="btn btn-info modern-btn text-white">Feedback</a>
<a href="../auth/logout.php" class="btn btn-danger modern-btn">Logout</a>
</div>
</div>
</nav>

<div class="container py-5">

    <!-- HEADER -->
    <div class="glass-box mb-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
            <div>
                <h2 class="fw-bold mb-2">💬 Feedback Masyarakat</h2>
                <p class="opacity-75 mb-0">Kelola masukan dan laporan dari masyarakat</p>
            </div>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <select name="status" class="filter-box">
                    <option value="">Semua</option>
                    <option value="pending" <?= (isset($_GET['status']) && $_GET['status']=='pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="dibaca" <?= (isset($_GET['status']) && $_GET['status']=='dibaca') ? 'selected' : '' ?>>Dibaca</option>
                    <option value="selesai" <?= (isset($_GET['status']) && $_GET['status']=='selesai') ? 'selected' : '' ?>>Selesai</option>
                </select>
                <button type="submit" class="btn btn-primary modern-btn">Filter</button>
            </form>
        </div>
    </div>

    <!-- STATS -->
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="stats-title">Total Feedback</div>
                <div class="stats-value text-primary"><?= $total ?></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="stats-title">Pending</div>
                <div class="stats-value text-warning"><?= $pending ?></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="stats-title">Dibaca</div>
                <div class="stats-value text-info"><?= $dibaca ?></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="stats-title">Selesai</div>
                <div class="stats-value text-success"><?= $selesai ?></div>
            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="glass-box">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kode Tracking</th>
                        <th>Rating</th>
                        <th>Pesan</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($row['nama']) ?></div>
                            <?php if($row['email']): ?>
                                <small class="opacity-75"><?= htmlspecialchars($row['email']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($row['tracking_code']): ?>
                                <a href="../tracking.php?code=<?= urlencode($row['tracking_code']) ?>" class="text-info" style="letter-spacing:1px;text-decoration:none;">
                                    <?= htmlspecialchars($row['tracking_code']) ?>
                                </a>
                            <?php else: ?>
                                <span class="opacity-50">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-size:18px;"><?= str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']) ?></span>
                            <small class="opacity-50 d-block"><?= h($row['rating']) ?>/5</small>
                        </td>
                        <td style="max-width:300px;">
                            <div class="feedback-msg"><?= nl2br(htmlspecialchars($row['pesan'])) ?></div>
                        </td>
                        <td>
                            <?php if($row['status'] == 'pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php elseif($row['status'] == 'dibaca'): ?>
                                <span class="badge bg-info">Dibaca</span>
                            <?php else: ?>
                                <span class="badge bg-success">Selesai</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <?php if($row['status'] == 'pending'): ?>
                                    <a href="?read=<?= h($row['id']) ?>" class="btn btn-sm btn-outline-info">Baca</a>
                                <?php endif; ?>
                                <?php if($row['status'] != 'selesai'): ?>
                                    <a href="?done=<?= h($row['id']) ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Tandai selesai?')">Selesai</a>
                                <?php endif; ?>
                                <a href="?delete=<?= h($row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus feedback ini?')">Hapus</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($query) == 0): ?>
                    <tr><td colspan="7" class="text-center py-5 opacity-50">Belum ada feedback</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init();</script>
</body>
</html>
