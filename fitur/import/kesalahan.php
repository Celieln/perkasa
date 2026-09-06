<?php
include '../../config.php';
// functions.php sudah di-include oleh config.php

if (!isset($_SESSION['login'])) {
    header("Location: ../../index.php");
    exit;
}

// Filters
$f_date    = isset($_GET['date'])       ? $_GET['date']       : '';
$f_op      = isset($_GET['operator'])   ? $_GET['operator']   : '';
$f_type    = isset($_GET['error_type']) ? $_GET['error_type'] : '';
$f_file    = isset($_GET['filename'])   ? $_GET['filename']   : '';
$f_nik     = isset($_GET['nik'])        ? $_GET['nik']        : '';
$f_nama    = isset($_GET['nama'])       ? $_GET['nama']       : '';
$f_status  = isset($_GET['status'])     ? $_GET['status']     : '';
$f_kind    = isset($_GET['kind'])       ? $_GET['kind']       : 'all'; // all | error | warning
$f_log_id  = isset($_GET['log_id'])     ? (int)$_GET['log_id'] : 0;

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset= ($page - 1) * $limit;

// Build query for errors
function buildErrorQuery($conn, $f_date, $f_op, $f_type, $f_file, $f_nik, $f_nama, $f_status, $f_log_id) {
    $w = "WHERE 1=1";
    if ($f_date)   $w .= " AND DATE(l.created_at)='" . mysqli_real_escape_string($conn, $f_date) . "'";
    if ($f_op)     $w .= " AND l.operator LIKE '%" . mysqli_real_escape_string($conn, $f_op) . "%'";
    if ($f_type)   $w .= " AND e.error_type='" . mysqli_real_escape_string($conn, $f_type) . "'";
    if ($f_file)   $w .= " AND l.filename LIKE '%" . mysqli_real_escape_string($conn, $f_file) . "%'";
    if ($f_nik)    $w .= " AND e.nik LIKE '%" . mysqli_real_escape_string($conn, $f_nik) . "%'";
    if ($f_nama)   $w .= " AND e.nama LIKE '%" . mysqli_real_escape_string($conn, $f_nama) . "%'";
    if ($f_status) $w .= " AND e.status='" . mysqli_real_escape_string($conn, $f_status) . "'";
    if ($f_log_id) $w .= " AND e.log_id=$f_log_id";
    return $w;
}

$errWhere  = buildErrorQuery($conn, $f_date, $f_op, $f_type, $f_file, $f_nik, $f_nama, $f_status, $f_log_id);
$warnWhere = str_replace('e.', 'w.', $errWhere);

// Count
$count_error = 0; $count_warn = 0;
if ($f_kind !== 'warning') {
    $cr = mysqli_query($conn, "SELECT COUNT(*) as c FROM import_errors e LEFT JOIN import_logs l ON e.log_id=l.id $errWhere");
    $count_error = mysqli_fetch_assoc($cr)['c'];
}
if ($f_kind !== 'error') {
    $cw = mysqli_query($conn, "SELECT COUNT(*) as c FROM import_warnings w LEFT JOIN import_logs l ON w.log_id=l.id $warnWhere");
    $count_warn = mysqli_fetch_assoc($cw)['c'];
}
$total_rows  = ($f_kind === 'error' ? $count_error : ($f_kind === 'warning' ? $count_warn : $count_error + $count_warn));
$total_pages = ceil($total_rows / $limit);

// Fetch errors
$errors   = [];
$warnings = [];
if ($f_kind !== 'warning') {
    $res = mysqli_query($conn, "SELECT e.*, l.filename, l.operator, l.created_at as log_date FROM import_errors e LEFT JOIN import_logs l ON e.log_id=l.id $errWhere ORDER BY e.log_id DESC, e.row_number ASC LIMIT $limit OFFSET $offset");
    while ($r = mysqli_fetch_assoc($res)) $errors[] = $r;
}
if ($f_kind !== 'error') {
    $res = mysqli_query($conn, "SELECT w.*, l.filename, l.operator, l.created_at as log_date FROM import_warnings w LEFT JOIN import_logs l ON w.log_id=l.id $warnWhere ORDER BY w.log_id DESC, w.row_number ASC LIMIT $limit OFFSET $offset");
    while ($r = mysqli_fetch_assoc($res)) $warnings[] = $r;
}

// Unique error types for filter
$errTypes = [];
$etRes = mysqli_query($conn, "SELECT DISTINCT error_type FROM import_errors WHERE error_type IS NOT NULL ORDER BY error_type");
while ($et = mysqli_fetch_assoc($etRes)) $errTypes[] = $et['error_type'];
$wtRes = mysqli_query($conn, "SELECT DISTINCT warning_type FROM import_warnings WHERE warning_type IS NOT NULL ORDER BY warning_type");
while ($wt = mysqli_fetch_assoc($wtRes)) $errTypes[] = $wt['warning_type'];
$errTypes = array_unique($errTypes);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kesalahan Import — PERKASA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--glass:rgba(255,255,255,0.07);--glass-border:rgba(255,255,255,0.10);--blue:#2563eb;--green:#16a34a;--red:#dc2626;--cyan:#0ea5e9;--radius:18px;}
*{box-sizing:border-box;}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#061220,#081426,#102d63);min-height:100vh;color:#fff;overflow-x:hidden;}
.custom-navbar{background:rgba(255,255,255,0.04);backdrop-filter:blur(20px);border-bottom:1px solid var(--glass-border);padding:14px 0;position:sticky;top:0;z-index:9999;}
.navbar-brand{font-size:22px;font-weight:800;color:#fff!important;display:flex;align-items:center;gap:10px;}
.brand-dot{width:10px;height:10px;background:var(--cyan);border-radius:50%;box-shadow:0 0 12px var(--cyan);}
.nav-link{color:#fff!important;font-weight:500;padding:10px 16px!important;border-radius:12px;transition:.25s;font-size:.92rem;}
.nav-link:hover{background:var(--glass);color:var(--cyan)!important;}
.nav-link.active-nav{background:rgba(59,130,246,.18);color:#60a5fa!important;}
.logout-btn{background:linear-gradient(135deg,#dc2626,#ef4444);border:none;border-radius:12px;padding:9px 16px;font-weight:600;color:#fff!important;text-decoration:none;transition:.25s;font-size:.9rem;}
.logout-btn:hover{transform:translateY(-2px);}
.glass-box{background:var(--glass);backdrop-filter:blur(18px);border:1px solid var(--glass-border);border-radius:var(--radius);padding:28px;box-shadow:0 20px 50px rgba(0,0,0,.2);}
.page-container{max-width:1300px;margin:0 auto;padding:32px 16px 60px;}
.filter-input{background:rgba(255,255,255,.08);border:1px solid var(--glass-border);color:#fff;border-radius:10px;padding:8px 14px;font-size:13px;width:100%;}
.filter-input:focus{outline:none;border-color:var(--blue);}
.filter-input option{background:#1e2a3a;}
.btn-glass{background:var(--glass);border:1px solid var(--glass-border);color:#fff;border-radius:12px;padding:9px 18px;font-weight:600;transition:.25s;font-size:.88rem;text-decoration:none;display:inline-block;}
.btn-glass:hover{background:rgba(255,255,255,.11);color:#fff;transform:translateY(-2px);}
.err-table th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;padding:12px 14px;background:rgba(255,255,255,.06);white-space:nowrap;color:rgba(255,255,255,.8);}
.err-table td{padding:12px 14px;font-size:12px;border-top:1px solid rgba(255,255,255,.06);vertical-align:middle;}
.err-table tr.is-error td{background:rgba(220,38,38,.04);}
.err-table tr.is-warning td{background:rgba(202,138,4,.04);}
.badge-error{background:rgba(220,38,38,.2);color:#f87171;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600;}
.badge-warning{background:rgba(202,138,4,.2);color:#fbbf24;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600;}
.tab-btn{background:transparent;border:1px solid var(--glass-border);color:rgba(255,255,255,.6);border-radius:10px;padding:8px 18px;font-size:13px;font-weight:600;transition:.2s;cursor:pointer;}
.tab-btn.active{background:rgba(37,99,235,.2);border-color:var(--blue);color:#60a5fa;}
.tab-btn:hover{background:var(--glass);}
.stat-pill{border-radius:999px;padding:4px 14px;font-size:12px;font-weight:700;}
.err-pill{background:rgba(220,38,38,.2);color:#f87171;}
.warn-pill{background:rgba(202,138,4,.2);color:#fbbf24;}
.pagination .page-link{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);color:#fff;border-radius:8px!important;margin:0 2px;transition:.2s;}
.pagination .page-link:hover{background:var(--blue);}
.pagination .active .page-link{background:var(--blue);border-color:var(--blue);}
#toastContainer{position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;}
.toast-msg{background:rgba(15,25,45,.95);backdrop-filter:blur(12px);border:1px solid var(--glass-border);border-radius:12px;padding:14px 20px;min-width:280px;display:flex;align-items:center;gap:12px;animation:toastIn .3s ease;font-size:13px;}
.toast-msg.success{border-left:3px solid var(--green);}
.toast-msg.error{border-left:3px solid var(--red);}
@keyframes toastIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:none}}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark custom-navbar">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="../../dashboard.php"><div class="brand-dot"></div>PERKASA</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item"><a class="nav-link" href="../../dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="../profile.php">Peserta</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php">Import Excel</a></li>
        <li class="nav-item"><a class="nav-link" href="riwayat.php">Riwayat</a></li>
        <li class="nav-item"><a class="nav-link active-nav" href="kesalahan.php">Kesalahan</a></li>
        <li class="nav-item ms-lg-2"><a class="logout-btn" href="../../auth/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="page-container">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
      <h1 class="fw-bold fs-3 mb-1">⚠ Data Kesalahan Import</h1>
      <p style="opacity:.6;font-size:.92rem">Data yang gagal atau mencurigakan saat proses import Excel</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php
      $exportUrl = 'api/export_errors.php?type=' . ($f_kind === 'all' ? 'all' : $f_kind);
      if ($f_log_id) $exportUrl .= '&log_id=' . $f_log_id;
      ?>
      <a href="<?= $exportUrl ?>" class="btn-glass">📥 Export Excel</a>
      <a href="index.php" class="btn-glass">+ Import Baru</a>
    </div>
  </div>

  <!-- Summary pills -->
  <div class="d-flex gap-3 mb-4 flex-wrap align-items-center">
    <span class="stat-pill err-pill">❌ <?= number_format($count_error) ?> Error</span>
    <span class="stat-pill warn-pill">⚠ <?= number_format($count_warn) ?> Warning</span>
    <!-- Tab filter -->
    <div class="ms-auto d-flex gap-2">
      <a href="?<?= http_build_query(array_merge($_GET, ['kind' => 'all',     'page' => 1])) ?>" class="tab-btn <?= $f_kind==='all'?'active':'' ?>">Semua</a>
      <a href="?<?= http_build_query(array_merge($_GET, ['kind' => 'error',   'page' => 1])) ?>" class="tab-btn <?= $f_kind==='error'?'active':'' ?>">Error</a>
      <a href="?<?= http_build_query(array_merge($_GET, ['kind' => 'warning', 'page' => 1])) ?>" class="tab-btn <?= $f_kind==='warning'?'active':'' ?>">Warning</a>
    </div>
  </div>

  <!-- Filter -->
  <div class="glass-box mb-4">
    <form method="GET">
      <input type="hidden" name="kind" value="<?= htmlspecialchars($f_kind) ?>">
      <div class="row g-3">
        <div class="col-md-2">
          <label style="font-size:11px;opacity:.5;margin-bottom:3px">Tanggal</label>
          <input type="date" name="date" class="filter-input" value="<?= htmlspecialchars($f_date) ?>">
        </div>
        <div class="col-md-2">
          <label style="font-size:11px;opacity:.5;margin-bottom:3px">Operator</label>
          <input type="text" name="operator" class="filter-input" placeholder="Operator..." value="<?= htmlspecialchars($f_op) ?>">
        </div>
        <div class="col-md-2">
          <label style="font-size:11px;opacity:.5;margin-bottom:3px">Jenis Error</label>
          <select name="error_type" class="filter-input">
            <option value="">Semua Jenis</option>
            <?php foreach ($errTypes as $et): ?>
            <option value="<?= $et ?>" <?= $f_type===$et?'selected':'' ?>><?= $et ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label style="font-size:11px;opacity:.5;margin-bottom:3px">NIK</label>
          <input type="text" name="nik" class="filter-input" placeholder="NIK..." value="<?= htmlspecialchars($f_nik) ?>">
        </div>
        <div class="col-md-2">
          <label style="font-size:11px;opacity:.5;margin-bottom:3px">Nama</label>
          <input type="text" name="nama" class="filter-input" placeholder="Nama..." value="<?= htmlspecialchars($f_nama) ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary" style="border-radius:10px;flex:1;font-size:13px">Filter</button>
          <a href="kesalahan.php" class="btn-glass" style="font-size:13px;padding:8px 14px">Reset</a>
        </div>
      </div>
    </form>
  </div>

  <!-- Table -->
  <div class="glass-box">
    <?php
    $allRows = [];
    foreach ($errors   as $e) { $e['__kind__'] = 'error';   $allRows[] = $e; }
    foreach ($warnings as $w) { $w['__kind__'] = 'warning'; $allRows[] = $w; }

    if (empty($allRows)):
    ?>
    <div class="text-center py-5" style="opacity:.4">
      <div style="font-size:48px">✨</div>
      <div class="mt-2">Tidak ada data kesalahan yang ditemukan</div>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-dark err-table" style="--bs-table-bg:transparent">
        <thead>
          <tr>
            <th>No</th>
            <th>Tipe</th>
            <th>Log ID</th>
            <th>Nama File</th>
            <th>Operator</th>
            <th>Tanggal Import</th>
            <th>Baris Excel</th>
            <th>NIK</th>
            <th>Nama</th>
            <th>Jenis Error</th>
            <th>Alasan</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = $offset + 1; foreach ($allRows as $row): ?>
          <tr class="is-<?= $row['__kind__'] ?>">
            <td style="opacity:.5"><?= $no++ ?></td>
            <td>
              <?php if ($row['__kind__'] === 'error'): ?>
              <span class="badge-error">❌ Error</span>
              <?php else: ?>
              <span class="badge-warning">⚠ Warning</span>
              <?php endif; ?>
            </td>
            <td style="opacity:.5">#<?= $row['log_id'] ?></td>
            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($row['filename']) ?>">
              <?= htmlspecialchars($row['filename']) ?>
            </td>
            <td><?= htmlspecialchars($row['operator'] ?? '—') ?></td>
            <td style="white-space:nowrap"><?= $row['log_date'] ? date('d/m/Y H:i', strtotime($row['log_date'])) : '—' ?></td>
            <td><strong>Baris <?= $row['row_number'] ?></strong></td>
            <td style="font-family:monospace"><?= htmlspecialchars($row['nik'] ?? '—') ?></td>
            <td><?= htmlspecialchars($row['nama'] ?? '—') ?></td>
            <td>
              <span style="background:rgba(255,255,255,.08);border-radius:6px;padding:3px 8px;font-size:10px;font-family:monospace">
                <?= htmlspecialchars($row['error_type'] ?? $row['warning_type'] ?? '—') ?>
              </span>
            </td>
            <td style="color:<?= $row['__kind__']==='error'?'#fca5a5':'#fde68a' ?>;max-width:250px">
              <?= htmlspecialchars($row['reason'] ?? '—') ?>
            </td>
            <td>
              <?php
              $st = $row['status'] ?? 'pending';
              $stMap = ['pending' => ['Pending','rgba(202,138,4,.2)','#fbbf24'],
                        'imported' => ['Diimport','rgba(22,163,74,.2)','#4ade80'],
                        'skipped'  => ['Dilewati','rgba(107,114,128,.2)','#9ca3af']];
              [$stLabel, $stBg, $stColor] = $stMap[$st] ?? ['—','transparent','#fff'];
              ?>
              <span style="background:<?= $stBg ?>;color:<?= $stColor ?>;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600"><?= $stLabel ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-3 d-flex justify-content-center">
      <ul class="pagination pagination-sm">
        <?php
        $qp = $_GET;
        for ($p = 1; $p <= $total_pages; $p++):
          $qp['page'] = $p;
        ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query($qp) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>

    <?php endif; ?>
  </div>
</div>

<div id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
