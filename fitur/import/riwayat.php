<?php
include '../../config.php';
// functions.php sudah di-include oleh config.php

if (!isset($_SESSION['login'])) {
    header("Location: ../../index.php");
    exit;
}
if (!is_admin()) {
    echo "<script>alert('Hanya admin yang dapat mengakses halaman ini'); window.location='../../dashboard.php';</script>";
    exit;
}

// Filter
$filter_date   = isset($_GET['date'])     ? $_GET['date']     : '';
$filter_status = isset($_GET['status'])   ? $_GET['status']   : '';
$filter_op     = isset($_GET['operator']) ? $_GET['operator'] : '';

$where = "WHERE 1=1";
if ($filter_date)   $where .= " AND DATE(l.created_at)='" . mysqli_real_escape_string($conn, $filter_date) . "'";
if ($filter_status) $where .= " AND l.status='" . mysqli_real_escape_string($conn, $filter_status) . "'";
if ($filter_op)     $where .= " AND l.operator LIKE '%" . mysqli_real_escape_string($conn, $filter_op) . "%'";

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$total_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM import_logs l $where");
$total_rows= mysqli_fetch_assoc($total_res)['c'];
$total_pages = ceil($total_rows / $limit);

$logs = mysqli_query($conn, "
    SELECT l.*, t.name as tpl_name
    FROM import_logs l
    LEFT JOIN mapping_templates t ON l.template_id = t.id
    $where
    ORDER BY l.created_at DESC
    LIMIT $limit OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Import — PERKASA</title>
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
.page-container{max-width:1200px;margin:0 auto;padding:32px 16px 60px;}
.filter-input{background:rgba(255,255,255,.08);border:1px solid var(--glass-border);color:#fff;border-radius:10px;padding:8px 14px;font-size:13px;}
.filter-input:focus{outline:none;border-color:var(--blue);}
.filter-input option{background:#1e2a3a;}
.btn-glass{background:var(--glass);border:1px solid var(--glass-border);color:#fff;border-radius:12px;padding:9px 18px;font-weight:600;transition:.25s;font-size:.88rem;text-decoration:none;}
.btn-glass:hover{background:rgba(255,255,255,.11);color:#fff;transform:translateY(-2px);}
.btn-danger-custom{background:linear-gradient(135deg,#dc2626,#b91c1c);border:none;border-radius:10px;padding:8px 16px;font-weight:700;color:#fff;transition:.25s;font-size:.85rem;}
.btn-danger-custom:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(220,38,38,.4);}
.log-table th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;padding:12px 14px;background:rgba(255,255,255,.06);white-space:nowrap;color:rgba(255,255,255,.8);}
.log-table td{padding:14px;font-size:13px;border-top:1px solid rgba(255,255,255,.06);vertical-align:middle;}
.log-table tr:hover td{background:rgba(255,255,255,.04);}
.status-badge{padding:4px 12px;border-radius:999px;font-size:11px;font-weight:700;}
.s-done{background:rgba(22,163,74,.2);color:#4ade80;}
.s-processing{background:rgba(37,99,235,.2);color:#60a5fa;}
.s-failed{background:rgba(220,38,38,.2);color:#f87171;}
.s-rolled_back{background:rgba(107,114,128,.2);color:#9ca3af;}
.s-pending{background:rgba(202,138,4,.2);color:#fbbf24;}
.num-green{color:#4ade80;font-weight:700;}
.num-yellow{color:#fbbf24;font-weight:700;}
.num-red{color:#f87171;font-weight:700;}
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
        <li class="nav-item"><a class="nav-link active-nav" href="riwayat.php">Riwayat</a></li>
        <li class="nav-item"><a class="nav-link" href="kesalahan.php">Kesalahan</a></li>
        <li class="nav-item ms-lg-2"><a class="logout-btn" href="../../auth/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="page-container">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
      <h1 class="fw-bold fs-3 mb-1">📋 Riwayat Import</h1>
      <p style="opacity:.6;font-size:.92rem">Pantau dan kelola semua sesi import data Excel</p>
    </div>
    <a href="index.php" class="btn-glass">+ Import Baru</a>
  </div>

  <!-- Filter -->
  <div class="glass-box mb-4">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label style="font-size:12px;opacity:.6;margin-bottom:4px">Tanggal</label>
        <input type="date" name="date" class="filter-input w-100" value="<?= htmlspecialchars($filter_date) ?>">
      </div>
      <div class="col-md-3">
        <label style="font-size:12px;opacity:.6;margin-bottom:4px">Status</label>
        <select name="status" class="filter-input w-100">
          <option value="">Semua Status</option>
          <option value="done" <?= $filter_status==='done'?'selected':'' ?>>Selesai</option>
          <option value="processing" <?= $filter_status==='processing'?'selected':'' ?>>Proses</option>
          <option value="failed" <?= $filter_status==='failed'?'selected':'' ?>>Gagal</option>
          <option value="rolled_back" <?= $filter_status==='rolled_back'?'selected':'' ?>>Rolled Back</option>
        </select>
      </div>
      <div class="col-md-3">
        <label style="font-size:12px;opacity:.6;margin-bottom:4px">Operator</label>
        <input type="text" name="operator" class="filter-input w-100" placeholder="Cari operator..." value="<?= htmlspecialchars($filter_op) ?>">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary" style="border-radius:10px;flex:1">🔍 Filter</button>
        <a href="riwayat.php" class="btn-glass">Reset</a>
      </div>
    </form>
  </div>

  <!-- Table -->
  <div class="glass-box">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold mb-0">Log Import (<?= number_format($total_rows) ?> total)</h5>
      <a href="api/export_errors.php?type=all" class="btn-glass" style="font-size:13px">📥 Export Semua Error</a>
    </div>

    <?php if (mysqli_num_rows($logs) === 0): ?>
    <div class="text-center py-5" style="opacity:.4">
      <div style="font-size:48px">📭</div>
      <div class="mt-2">Belum ada riwayat import</div>
    </div>
    <?php else: ?>

    <div class="table-responsive">
      <table class="table table-dark log-table" style="--bs-table-bg:transparent">
        <thead>
          <tr>
            <th>ID</th>
            <th>File</th>
            <th>Template</th>
            <th>Operator</th>
            <th>Tanggal</th>
            <th>Total</th>
            <th>Valid</th>
            <th>Warning</th>
            <th>Error</th>
            <th>Durasi</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($log = mysqli_fetch_assoc($logs)): ?>
          <tr>
            <td style="opacity:.5">#<?= $log['id'] ?></td>
            <td>
              <div class="fw-bold" style="font-size:13px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($log['filename']) ?>">
                📊 <?= htmlspecialchars($log['filename']) ?>
              </div>
              <div style="font-size:11px;opacity:.4">Tabel: <?= $log['target_table'] ?></div>
            </td>
            <td style="font-size:12px"><?= $log['tpl_name'] ? htmlspecialchars($log['tpl_name']) : '<span style="opacity:.4">Manual</span>' ?></td>
            <td style="font-size:13px"><?= htmlspecialchars($log['operator']) ?></td>
            <td style="font-size:12px;white-space:nowrap"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
            <td class="fw-bold"><?= number_format($log['total_rows']) ?></td>
            <td class="num-green"><?= number_format($log['imported_rows']) ?></td>
            <td class="num-yellow"><?= number_format($log['warning_rows']) ?></td>
            <td class="num-red"><?= number_format($log['error_rows']) ?></td>
            <td style="font-size:12px;opacity:.6"><?= number_format($log['duration_seconds'], 1) ?>s</td>
            <td>
              <?php
              $sClass = match($log['status']) {
                'done' => 's-done', 'processing' => 's-processing',
                'failed' => 's-failed', 'rolled_back' => 's-rolled_back', default => 's-pending'
              };
              $sLabel = match($log['status']) {
                'done' => 'Selesai', 'processing' => 'Proses',
                'failed' => 'Gagal', 'rolled_back' => 'Rolled Back', default => 'Pending'
              };
              ?>
              <span class="status-badge <?= $sClass ?>"><?= $sLabel ?></span>
            </td>
            <td>
              <div class="d-flex gap-2 flex-wrap">
                <?php if ($log['error_rows'] > 0 || $log['warning_rows'] > 0): ?>
                <a href="api/export_errors.php?log_id=<?= $log['id'] ?>&type=all" class="btn-glass" style="font-size:11px;padding:5px 10px">📥 Error</a>
                <?php endif; ?>
                <?php if ($log['status'] === 'done' && !empty($log['imported_ids'])): ?>
                <button class="btn-danger-custom" onclick="confirmRollback(<?= $log['id'] ?>, '<?= htmlspecialchars($log['filename']) ?>')" style="font-size:11px;padding:5px 10px">↩ Rollback</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-3 d-flex justify-content-center">
      <ul class="pagination pagination-sm">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $p ?>&date=<?= urlencode($filter_date) ?>&status=<?= urlencode($filter_status) ?>&operator=<?= urlencode($filter_op) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>

    <?php endif; ?>
  </div>
</div>

<!-- Rollback confirm modal -->
<div class="modal fade" id="rollbackModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:#0e1929;border:1px solid rgba(255,255,255,.1)">
      <div class="modal-header" style="border-color:rgba(255,255,255,.08)">
        <h5 class="modal-title text-white">⚠ Konfirmasi Rollback</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p style="font-size:14px;opacity:.8">Apakah Anda yakin ingin me-rollback import dari file:</p>
        <p class="fw-bold text-warning" id="rollbackFileName"></p>
        <p style="font-size:13px;color:#f87171">⚠ Seluruh data yang diimport pada sesi ini akan <strong>DIHAPUS PERMANEN</strong> dari database.</p>
      </div>
      <div class="modal-footer" style="border-color:rgba(255,255,255,.08)">
        <button class="btn btn-glass" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-danger" id="btnConfirmRollback" onclick="doRollback()">Ya, Rollback Sekarang</button>
      </div>
    </div>
  </div>
</div>

<div id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let rollbackLogId = null;

function confirmRollback(logId, filename) {
  rollbackLogId = logId;
  document.getElementById('rollbackFileName').textContent = filename;
  new bootstrap.Modal(document.getElementById('rollbackModal')).show();
}

function doRollback() {
  if (!rollbackLogId) return;
  document.getElementById('btnConfirmRollback').disabled = true;
  document.getElementById('btnConfirmRollback').textContent = 'Memproses...';

  fetch('api/rollback.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ log_id: rollbackLogId })
  })
  .then(r => r.json())
  .then(data => {
    bootstrap.Modal.getInstance(document.getElementById('rollbackModal')).hide();
    showToast(data.message, data.success ? 'success' : 'error');
    if (data.success) setTimeout(() => location.reload(), 1500);
    document.getElementById('btnConfirmRollback').disabled = false;
    document.getElementById('btnConfirmRollback').textContent = 'Ya, Rollback Sekarang';
  })
  .catch(err => showToast('Error: ' + err.message, 'error'));
}

function showToast(msg, type='info') {
  const el = document.createElement('div');
  el.className = `toast-msg ${type}`;
  el.innerHTML = `<span>${type==='success'?'✅':'❌'}</span><span>${msg}</span>`;
  document.getElementById('toastContainer').appendChild(el);
  setTimeout(() => el.remove(), 5000);
}
</script>
</body>
</html>
