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

$templates = mysqli_query($conn, "
    SELECT t.*, u.nama_lengkap as creator_name,
           COUNT(d.id) as mapping_count,
           COUNT(r.id) as rule_count
    FROM mapping_templates t
    LEFT JOIN users u ON t.created_by = u.id
    LEFT JOIN mapping_details d ON d.template_id = t.id
    LEFT JOIN mapping_rules r ON r.template_id = t.id
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Template Mapping — PERKASA</title>
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
.page-container{max-width:1100px;margin:0 auto;padding:32px 16px 60px;}
.template-card{background:rgba(255,255,255,.05);border:1px solid var(--glass-border);border-radius:16px;padding:22px;transition:.3s;cursor:pointer;}
.template-card:hover{background:rgba(255,255,255,.09);transform:translateY(-4px);border-color:rgba(37,99,235,.4);}
.template-card.selected{border-color:var(--blue);background:rgba(37,99,235,.1);}
.btn-glass{background:var(--glass);border:1px solid var(--glass-border);color:#fff;border-radius:12px;padding:9px 18px;font-weight:600;transition:.25s;font-size:.88rem;text-decoration:none;display:inline-block;}
.btn-glass:hover{background:rgba(255,255,255,.11);color:#fff;transform:translateY(-2px);}
.btn-primary-custom{background:linear-gradient(135deg,var(--blue),#1d4ed8);border:none;border-radius:12px;padding:9px 18px;font-weight:700;color:#fff;transition:.25s;font-size:.88rem;}
.btn-primary-custom:hover{transform:translateY(-2px);}
.btn-danger-sm{background:rgba(220,38,38,.2);border:1px solid rgba(220,38,38,.3);color:#f87171;border-radius:8px;padding:6px 12px;font-size:11px;font-weight:600;transition:.2s;}
.btn-danger-sm:hover{background:rgba(220,38,38,.4);}
.form-control-glass{background:rgba(255,255,255,.08);border:1px solid var(--glass-border);color:#fff;border-radius:10px;padding:9px 14px;font-size:13px;width:100%;}
.form-control-glass:focus{outline:none;border-color:var(--blue);background:rgba(37,99,235,.1);}
.form-control-glass option{background:#1e2a3a;}
.detail-table th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;padding:10px 14px;background:rgba(255,255,255,.06);color:rgba(255,255,255,.7);}
.detail-table td{padding:10px 14px;font-size:12px;border-top:1px solid rgba(255,255,255,.06);}
.mode-badge{border-radius:999px;padding:4px 12px;font-size:11px;font-weight:600;}
.m-valid{background:rgba(22,163,74,.2);color:#4ade80;}
.m-upsert{background:rgba(37,99,235,.2);color:#60a5fa;}
.m-all{background:rgba(220,38,38,.2);color:#f87171;}
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
        <li class="nav-item"><a class="nav-link active-nav" href="template.php">Template</a></li>
        <li class="nav-item ms-lg-2"><a class="logout-btn" href="../../auth/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="page-container">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
      <h1 class="fw-bold fs-3 mb-1">🗂 Template Mapping</h1>
      <p style="opacity:.6;font-size:.92rem">Simpan dan kelola konfigurasi pemetaan kolom untuk digunakan kembali</p>
    </div>
    <a href="index.php" class="btn-glass">← Kembali ke Import</a>
  </div>

  <div class="row g-4">
    <!-- Template List -->
    <div class="col-md-5">
      <div class="glass-box" style="height:fit-content">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
          📋 Daftar Template
          <span style="background:rgba(37,99,235,.2);color:#60a5fa;border-radius:999px;padding:2px 10px;font-size:11px">
            <?= mysqli_num_rows(mysqli_query($conn, "SELECT id FROM mapping_templates")) ?>
          </span>
        </h6>

        <?php
        mysqli_data_seek($templates, 0);
        if (mysqli_num_rows($templates) === 0):
        ?>
        <div class="text-center py-4" style="opacity:.4">
          <div style="font-size:36px">📭</div>
          <div class="mt-2" style="font-size:13px">Belum ada template tersimpan</div>
          <div style="font-size:12px;margin-top:4px">Buat template saat import pertama kali</div>
        </div>
        <?php else: ?>
        <div class="d-flex flex-column gap-2" id="templateList">
          <?php while ($t = mysqli_fetch_assoc($templates)): ?>
          <div class="template-card" onclick="loadTemplateDetail(<?= $t['id'] ?>)" id="tcard-<?= $t['id'] ?>">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="fw-bold" style="font-size:14px"><?= htmlspecialchars($t['name']) ?></div>
                <div style="font-size:11px;opacity:.5;margin-top:3px"><?= htmlspecialchars($t['description'] ?? '') ?></div>
              </div>
              <div class="d-flex gap-1">
                <button class="btn btn-sm" style="background:rgba(255,165,0,.15);color:#fbbf24;border:none;border-radius:8px;font-size:11px;padding:4px 10px" onclick="event.stopPropagation(); duplicateTemplate(<?= $t['id'] ?>, '<?= htmlspecialchars($t['name']) ?>')">📋</button>
                <button class="btn-danger-sm btn btn-sm" onclick="event.stopPropagation(); deleteTemplate(<?= $t['id'] ?>, '<?= htmlspecialchars($t['name']) ?>')">🗑</button>
              </div>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-2">
              <span style="font-size:10px;background:rgba(255,255,255,.08);border-radius:6px;padding:2px 8px"><?= $t['mapping_count'] ?> kolom</span>
              <span style="font-size:10px;background:rgba(255,255,255,.08);border-radius:6px;padding:2px 8px"><?= $t['rule_count'] ?> rules</span>
              <span style="font-size:10px;background:rgba(37,99,235,.15);border-radius:6px;padding:2px 8px;color:#7dd3fc"><?= $t['target_table'] ?></span>
            </div>
            <div style="font-size:10px;opacity:.35;margin-top:6px">
              Dibuat: <?= date('d/m/Y', strtotime($t['created_at'])) ?>
              <?= $t['creator_name'] ? ' oleh ' . htmlspecialchars($t['creator_name']) : '' ?>
            </div>
          </div>
          <?php endwhile; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Template Detail -->
    <div class="col-md-7">
      <div class="glass-box" id="templateDetail" style="display:none">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0" id="detailTitle">Detail Template</h6>
          <a href="#" id="detailUseBtn" class="btn-primary-custom btn btn-sm">Gunakan Template Ini</a>
        </div>

        <!-- Mode info -->
        <div class="d-flex gap-2 mb-4 flex-wrap" id="detailMeta"></div>

        <!-- Mapping table -->
        <div class="mb-4">
          <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:.5;margin-bottom:10px">Mapping Kolom</div>
          <div class="table-responsive" style="border-radius:12px;overflow:hidden;border:1px solid var(--glass-border)">
            <table class="table table-dark detail-table mb-0" id="detailMappingTable">
              <thead><tr><th>Header Excel</th><th>Kolom Database</th><th>Default Value</th><th>Wajib</th></tr></thead>
              <tbody id="detailMappingBody"></tbody>
            </table>
          </div>
        </div>

        <!-- Rules table -->
        <div id="detailRulesSection" style="display:none">
          <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:.5;margin-bottom:10px">Aturan Transformasi</div>
          <div class="table-responsive" style="border-radius:12px;overflow:hidden;border:1px solid var(--glass-border)">
            <table class="table table-dark detail-table mb-0">
              <thead><tr><th>Kolom DB</th><th>Nilai Excel</th><th>→</th><th>Nilai DB</th></tr></thead>
              <tbody id="detailRulesBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="glass-box" id="templateEmpty" style="text-align:center;padding:60px 20px">
        <div style="font-size:48px;opacity:.3">👈</div>
        <div style="opacity:.4;margin-top:12px">Pilih template di sebelah kiri untuk melihat detail</div>
      </div>
    </div>
  </div>
</div>

<div id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showToast(msg, type='info') {
  const el = document.createElement('div');
  el.className = `toast-msg ${type}`;
  el.innerHTML = `<span>${type==='success'?'✅':'❌'}</span><span>${msg}</span>`;
  document.getElementById('toastContainer').appendChild(el);
  setTimeout(() => el.remove(), 5000);
}

function loadTemplateDetail(id) {
  document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
  document.getElementById('tcard-' + id)?.classList.add('selected');

  fetch(`api/load_template.php?id=${id}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast(data.message, 'error'); return; }

      document.getElementById('templateEmpty').style.display  = 'none';
      document.getElementById('templateDetail').style.display = 'block';

      const t = data.template;
      document.getElementById('detailTitle').textContent = '📋 ' + t.name;
      document.getElementById('detailUseBtn').href = `index.php`;

      // Meta
      const modeMap = {
        valid_only: ['Valid Only','m-valid'],
        all_or_nothing: ['All or Nothing','m-all'],
        valid_skip_error: ['Skip Error','m-valid'],
        upsert: ['Upsert','m-upsert'],
        update: ['Update','m-upsert'],
        insert_only: ['Insert Only','m-valid'],
      };
      const [mLabel, mClass] = modeMap[t.import_mode] || [t.import_mode, 'm-valid'];
      document.getElementById('detailMeta').innerHTML = `
        <span class="mode-badge ${mClass}">${mLabel}</span>
        <span class="mode-badge m-upsert">Tabel: ${t.target_table}</span>
        <span class="mode-badge" style="background:rgba(107,114,128,.2);color:#9ca3af">Duplikat: ${t.duplicate_mode}</span>
      `;

      // Mapping rows
      const tbody = document.getElementById('detailMappingBody');
      tbody.innerHTML = data.details.map(d => `
        <tr>
          <td><span style="background:rgba(37,99,235,.18);border-radius:6px;padding:3px 10px;font-size:11px">📊 ${d.excel_header}</span></td>
          <td><code style="background:rgba(255,255,255,.08);border-radius:4px;padding:2px 8px;font-size:11px">${d.db_column || '<span style="opacity:.4">— abaikan —</span>'}</code></td>
          <td style="font-size:11px;opacity:.6">${d.default_value || '—'}</td>
          <td>${d.is_required == 1 ? '<span style="color:#f87171;font-size:11px">✓ Wajib</span>' : '<span style="opacity:.3;font-size:11px">—</span>'}</td>
        </tr>
      `).join('') || '<tr><td colspan="4" class="text-center" style="opacity:.4">Tidak ada detail</td></tr>';

      // Rules
      if (data.rules && data.rules.length) {
        document.getElementById('detailRulesSection').style.display = 'block';
        document.getElementById('detailRulesBody').innerHTML = data.rules.map(r => `
          <tr>
            <td><code style="font-size:11px">${r.db_column}</code></td>
            <td style="font-size:11px">${r.excel_value}</td>
            <td style="opacity:.4">→</td>
            <td style="font-size:11px;color:#4ade80">${r.db_value}</td>
          </tr>
        `).join('');
      } else {
        document.getElementById('detailRulesSection').style.display = 'none';
      }
    });
}

function deleteTemplate(id, name) {
  if (!confirm(`Hapus template "${name}"? Aksi ini tidak dapat dibatalkan.`)) return;
  fetch(`api/load_template.php?action=delete&id=${id}`)
    .then(r => r.json())
    .then(data => {
      showToast(data.message, data.success ? 'success' : 'error');
      if (data.success) setTimeout(() => location.reload(), 1500);
    });
}

function duplicateTemplate(id, name) {
  fetch(`api/load_template.php?action=duplicate&id=${id}`)
    .then(r => r.json())
    .then(data => {
      showToast(data.message || (data.success ? 'Template berhasil disalin' : 'Gagal'), data.success ? 'success' : 'error');
      if (data.success) setTimeout(() => location.reload(), 1500);
    });
}
</script>
</body>
</html>
