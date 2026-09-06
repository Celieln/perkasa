<?php
include '../../config.php';
// functions.php sudah di-include oleh config.php

if (!isset($_SESSION['login'])) {
    header("Location: ../../index.php");
    exit;
}
if (!is_admin()) {
    echo "<script>alert('Hanya admin yang dapat mengakses modul ini'); window.location='../../dashboard.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Import Excel Dinamis — PERKASA</title>
<meta name="description" content="Modul import data peserta dari file Excel dengan mapping kolom dinamis">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
:root {
  --bg-start: #061220;
  --bg-mid: #081426;
  --bg-end: #102d63;
  --glass: rgba(255,255,255,0.07);
  --glass-border: rgba(255,255,255,0.10);
  --glass-hover: rgba(255,255,255,0.11);
  --blue: #2563eb;
  --blue-light: #3b82f6;
  --cyan: #0ea5e9;
  --green: #16a34a;
  --yellow: #ca8a04;
  --red: #dc2626;
  --text-muted: rgba(255,255,255,0.6);
  --radius: 18px;
  --radius-sm: 12px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Inter',sans-serif;
  background:linear-gradient(135deg,var(--bg-start),var(--bg-mid),var(--bg-end));
  min-height:100vh;color:#fff;
  overflow-x:hidden;
}
/* ─── NAVBAR ─────────────────────────── */
.custom-navbar{
  background:rgba(255,255,255,0.04);
  backdrop-filter:blur(20px);
  border-bottom:1px solid var(--glass-border);
  padding:14px 0;position:sticky;top:0;z-index:9999;
}
.navbar-brand{font-size:22px;font-weight:800;color:#fff!important;display:flex;align-items:center;gap:10px;}
.brand-dot{width:10px;height:10px;background:var(--cyan);border-radius:50%;box-shadow:0 0 12px var(--cyan);}
.nav-link{color:#fff!important;font-weight:500;padding:10px 16px!important;border-radius:12px;transition:.25s;font-size:.92rem;}
.nav-link:hover{background:var(--glass);color:var(--cyan)!important;}
.nav-link.active-nav{background:rgba(59,130,246,.18);color:#60a5fa!important;}
.logout-btn{background:linear-gradient(135deg,#dc2626,#ef4444);border:none;border-radius:12px;padding:9px 16px;font-weight:600;color:#fff!important;text-decoration:none;transition:.25s;font-size:.9rem;}
.logout-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(239,68,68,.35);}
/* ─── LAYOUT ─────────────────────────── */
.page-container{max-width:1100px;margin:0 auto;padding:32px 16px 60px;}
/* ─── WIZARD STEPS ───────────────────── */
.wizard-steps{display:flex;gap:0;margin-bottom:36px;position:relative;}
.wizard-steps::before{
  content:'';position:absolute;top:22px;left:10%;right:10%;
  height:2px;background:var(--glass-border);z-index:0;
}
.step-item{flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;position:relative;z-index:1;}
.step-bubble{
  width:44px;height:44px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:15px;transition:.3s;
  background:var(--glass);border:2px solid var(--glass-border);
  position:relative;
}
.step-bubble.active{background:var(--blue);border-color:var(--blue);box-shadow:0 0 20px rgba(37,99,235,.5);}
.step-bubble.done{background:var(--green);border-color:var(--green);}
.step-bubble.done::after{content:'✓';font-size:18px;}
.step-bubble.active .step-num{display:block;}
.step-bubble.done .step-num{display:none;}
.step-label{font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;opacity:.6;text-align:center;}
.step-label.active{opacity:1;color:var(--blue-light);}
/* ─── GLASS BOX ──────────────────────── */
.glass-box{
  background:var(--glass);
  backdrop-filter:blur(18px);
  border:1px solid var(--glass-border);
  border-radius:var(--radius);padding:28px;
  box-shadow:0 20px 50px rgba(0,0,0,.2);
}
.glass-box-sm{
  background:rgba(255,255,255,0.05);
  border:1px solid var(--glass-border);
  border-radius:var(--radius-sm);padding:18px;
}
/* ─── DROP ZONE ──────────────────────── */
#dropZone{
  border:2px dashed rgba(255,255,255,.18);
  border-radius:var(--radius);
  min-height:200px;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:14px;cursor:pointer;transition:.3s;padding:32px;text-align:center;
}
#dropZone:hover,#dropZone.drag-over{
  border-color:var(--blue);
  background:rgba(37,99,235,.08);
}
#dropZone .dz-icon{font-size:48px;opacity:.5;}
#dropZone.has-file{border-color:var(--green);background:rgba(22,163,74,.07);}
/* ─── MAPPING TABLE ──────────────────── */
.mapping-row{
  display:grid;grid-template-columns:1fr 40px 1fr;
  gap:12px;align-items:center;
  padding:10px 0;border-bottom:1px solid var(--glass-border);
}
.mapping-row:last-child{border-bottom:none;}
.excel-header-badge{
  background:rgba(37,99,235,.2);border:1px solid rgba(37,99,235,.3);
  border-radius:8px;padding:8px 14px;font-size:13px;font-weight:500;
  display:flex;align-items:center;gap:8px;
}
.mapping-arrow{color:var(--cyan);font-size:20px;text-align:center;}
.db-select{
  background:rgba(255,255,255,.08);border:1px solid var(--glass-border);
  color:#fff;border-radius:10px;padding:8px 12px;width:100%;font-size:13px;
  transition:.2s;
}
.db-select:focus{outline:none;border-color:var(--blue);background:rgba(37,99,235,.1);}
.db-select option{background:#1e2a3a;color:#fff;}
/* ─── CONFIDENCE BADGE ───────────────── */
.conf-high{color:#4ade80;font-size:11px;}
.conf-medium{color:#fbbf24;font-size:11px;}
.conf-low{color:#f87171;font-size:11px;}
/* ─── STATS CARDS ────────────────────── */
.stat-card{
  border-radius:var(--radius-sm);padding:20px;text-align:center;
  border:1px solid var(--glass-border);transition:.3s;
}
.stat-card:hover{transform:translateY(-4px);}
.stat-card.valid{background:rgba(22,163,74,.12);border-color:rgba(22,163,74,.3);}
.stat-card.warning{background:rgba(202,138,4,.12);border-color:rgba(202,138,4,.3);}
.stat-card.error{background:rgba(220,38,38,.12);border-color:rgba(220,38,38,.3);}
.stat-card.total{background:rgba(37,99,235,.12);border-color:rgba(37,99,235,.3);}
.stat-num{font-size:38px;font-weight:800;line-height:1;}
.stat-label{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;opacity:.7;margin-top:6px;}
/* ─── PREVIEW TABLE ──────────────────── */
.preview-table th{background:rgba(255,255,255,.06);font-size:11px;text-transform:uppercase;letter-spacing:.05em;padding:10px 12px;white-space:nowrap;}
.preview-table td{font-size:12px;padding:10px 12px;border-top:1px solid var(--glass-border);}
.row-valid td{background:rgba(22,163,74,.05);}
.row-warning td{background:rgba(202,138,4,.05);}
.row-error td{background:rgba(220,38,38,.05);}
.badge-valid{background:rgba(22,163,74,.2);color:#4ade80;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600;}
.badge-warning{background:rgba(202,138,4,.2);color:#fbbf24;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600;}
.badge-error{background:rgba(220,38,38,.2);color:#f87171;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600;}
/* ─── PROGRESS BAR ───────────────────── */
.progress-modern{height:10px;border-radius:999px;background:var(--glass);overflow:hidden;}
.progress-modern .bar{height:100%;background:linear-gradient(90deg,var(--blue),var(--cyan));transition:width .4s ease;border-radius:999px;}
/* ─── BUTTONS ────────────────────────── */
.btn-glass{background:var(--glass);border:1px solid var(--glass-border);color:#fff;border-radius:var(--radius-sm);padding:10px 20px;font-weight:600;transition:.25s;font-size:.9rem;}
.btn-glass:hover{background:var(--glass-hover);color:#fff;transform:translateY(-2px);}
.btn-primary-custom{background:linear-gradient(135deg,var(--blue),#1d4ed8);border:none;border-radius:var(--radius-sm);padding:11px 24px;font-weight:700;color:#fff;transition:.25s;font-size:.9rem;}
.btn-primary-custom:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(37,99,235,.4);}
.btn-success-custom{background:linear-gradient(135deg,#16a34a,#15803d);border:none;border-radius:var(--radius-sm);padding:11px 24px;font-weight:700;color:#fff;transition:.25s;}
.btn-success-custom:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(22,163,74,.4);}
.btn-danger-custom{background:linear-gradient(135deg,#dc2626,#b91c1c);border:none;border-radius:var(--radius-sm);padding:11px 24px;font-weight:700;color:#fff;transition:.25s;}
/* ─── TOAST ──────────────────────────── */
#toastContainer{position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;}
.toast-msg{
  background:rgba(15,25,45,.95);backdrop-filter:blur(12px);
  border:1px solid var(--glass-border);border-radius:12px;
  padding:14px 20px;min-width:280px;max-width:380px;
  display:flex;align-items:center;gap:12px;
  animation:toastIn .3s ease;font-size:13px;
}
.toast-msg.success{border-left:3px solid var(--green);}
.toast-msg.error{border-left:3px solid var(--red);}
.toast-msg.warning{border-left:3px solid #f59e0b;}
.toast-msg.info{border-left:3px solid var(--blue);}
@keyframes toastIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:none}}
/* ─── RULE BUILDER ───────────────────── */
.rule-row{display:grid;grid-template-columns:1fr 30px 1fr 30px;gap:8px;align-items:center;margin-bottom:8px;}
.rule-input{background:var(--glass);border:1px solid var(--glass-border);color:#fff;border-radius:8px;padding:7px 12px;font-size:12px;width:100%;}
/* ─── DEFAULT VALUE ──────────────────── */
.default-row{display:flex;gap:10px;align-items:center;margin-bottom:8px;}
.default-label{font-size:12px;width:160px;opacity:.8;background:rgba(37,99,235,.15);padding:6px 10px;border-radius:8px;}
/* ─── SECTION HEADER ─────────────────── */
.section-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;opacity:.5;margin-bottom:14px;}
/* ─── STEP PANELS ────────────────────── */
.step-panel{display:none;}
.step-panel.active{display:block;}
/* ─── INFO BOX ───────────────────────── */
.info-box{background:rgba(14,165,233,.12);border:1px solid rgba(14,165,233,.25);border-radius:12px;padding:14px 18px;font-size:13px;color:#7dd3fc;}
.warn-box{background:rgba(202,138,4,.1);border:1px solid rgba(202,138,4,.25);border-radius:12px;padding:14px 18px;font-size:13px;color:#fcd34d;}
/* ─── RESPONSIVE ─────────────────────── */
@media(max-width:768px){
  .wizard-steps::before{display:none;}
  .step-label{display:none;}
  .mapping-row{grid-template-columns:1fr 32px 1fr;}
  .stat-num{font-size:28px;}
}
/* ─── SCROLLBAR ──────────────────────── */
::-webkit-scrollbar{width:6px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:99px;}
</style>
</head>
<body>

<!-- ─── NAVBAR ─────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark custom-navbar">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="../../dashboard.php">
      <div class="brand-dot"></div>PERKASA
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item"><a class="nav-link" href="../../dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="../profile.php">Peserta</a></li>
        <li class="nav-item"><a class="nav-link active-nav" href="index.php">Import Excel</a></li>
        <li class="nav-item"><a class="nav-link" href="riwayat.php">Riwayat</a></li>
        <li class="nav-item"><a class="nav-link" href="kesalahan.php">Kesalahan</a></li>
        <li class="nav-item ms-lg-2"><a class="logout-btn" href="../../auth/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- ─── CONTENT ────────────────────────────────────── -->
<div class="page-container">

  <!-- HEADER -->
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4" data-aos="fade-down">
    <div>
      <h1 class="fw-bold fs-3 mb-1">📥 Import Excel Dinamis</h1>
      <p style="opacity:.6;font-size:.92rem">Impor data peserta dari berbagai format Excel tanpa mengubah kode program</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="template.php" class="btn-glass btn btn-sm">🗂 Kelola Template</a>
      <a href="riwayat.php"  class="btn-glass btn btn-sm">📋 Riwayat Import</a>
    </div>
  </div>

  <!-- WIZARD STEPS -->
  <div class="wizard-steps mb-4" data-aos="fade-up">
    <div class="step-item">
      <div class="step-bubble active" id="bubble-1"><span class="step-num">1</span></div>
      <div class="step-label active" id="label-1">Upload</div>
    </div>
    <div class="step-item">
      <div class="step-bubble" id="bubble-2"><span class="step-num">2</span></div>
      <div class="step-label" id="label-2">Mapping</div>
    </div>
    <div class="step-item">
      <div class="step-bubble" id="bubble-3"><span class="step-num">3</span></div>
      <div class="step-label" id="label-3">Preview</div>
    </div>
    <div class="step-item">
      <div class="step-bubble" id="bubble-4"><span class="step-num">4</span></div>
      <div class="step-label" id="label-4">Import</div>
    </div>
    <div class="step-item">
      <div class="step-bubble" id="bubble-5"><span class="step-num">5</span></div>
      <div class="step-label" id="label-5">Hasil</div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════ -->
  <!-- STEP 1: UPLOAD                                 -->
  <!-- ══════════════════════════════════════════════ -->
  <div class="step-panel active" id="panel-1">
    <div class="glass-box" data-aos="fade-up">
      <h5 class="fw-bold mb-4">📁 Upload File Excel</h5>

      <!-- Drop Zone -->
      <div id="dropZone" onclick="document.getElementById('fileInput').click()">
        <div class="dz-icon">📊</div>
        <div>
          <div class="fw-bold fs-5 mb-1">Seret file ke sini atau klik untuk memilih</div>
          <div style="font-size:13px;opacity:.5">Mendukung .xlsx, .xls, .csv — maks. 50 MB</div>
        </div>
        <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none">
      </div>

      <!-- File info (hidden by default) -->
      <div id="fileInfo" class="mt-3" style="display:none">
        <div class="glass-box-sm d-flex align-items-center gap-16 justify-content-between flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <span style="font-size:32px">📊</span>
            <div>
              <div class="fw-bold" id="fi-name">-</div>
              <div style="font-size:12px;opacity:.5" id="fi-size">-</div>
            </div>
          </div>
          <div class="d-flex gap-4 text-center">
            <div><div class="fw-bold text-info fs-4" id="fi-rows">-</div><div style="font-size:11px;opacity:.5">Baris Data</div></div>
            <div><div class="fw-bold text-primary fs-4" id="fi-headers">-</div><div style="font-size:11px;opacity:.5">Kolom</div></div>
          </div>
        </div>
      </div>

      <!-- Header preview -->
      <div id="headerPreview" class="mt-3" style="display:none">
        <div class="section-title">Kolom yang Terdeteksi</div>
        <div id="headerBadges" class="d-flex flex-wrap gap-2"></div>
      </div>

      <!-- Data preview table -->
      <div id="dataPreview" class="mt-4" style="display:none">
        <div class="section-title">Preview Data (5 baris pertama)</div>
        <div class="table-responsive" style="border-radius:12px;overflow:hidden;border:1px solid var(--glass-border)">
          <table class="table table-dark preview-table mb-0" id="previewTable"></table>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-4">
        <div id="uploadStatus" class="info-box" style="display:none"></div>
        <div class="ms-auto">
          <button class="btn btn-primary-custom" id="btnToStep2" disabled onclick="goToStep(2)">
            Lanjut ke Mapping →
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════ -->
  <!-- STEP 2: MAPPING                                -->
  <!-- ══════════════════════════════════════════════ -->
  <div class="step-panel" id="panel-2">
    <div class="glass-box" data-aos="fade-up">

      <!-- Template bar -->
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h5 class="fw-bold mb-0">🗺 Konfigurasi Mapping Kolom</h5>
        <div class="d-flex gap-2 flex-wrap">
          <select id="templateSelect" class="db-select" style="width:auto;min-width:180px" onchange="loadTemplate()">
            <option value="">— Load Template —</option>
          </select>
          <button class="btn-glass btn btn-sm" onclick="openSaveTemplate()">💾 Simpan Template</button>
        </div>
      </div>

      <!-- Import Mode -->
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="section-title">Mode Import</div>
          <select id="importMode" class="db-select">
            <option value="valid_only">Import hanya data valid</option>
            <option value="all_or_nothing">Batalkan semua jika ada 1 error</option>
            <option value="valid_skip_error">Import valid, abaikan error</option>
            <option value="upsert">Upsert (Insert atau Update jika NIK ada)</option>
            <option value="update">Update jika NIK sudah ada</option>
            <option value="insert_only">Insert saja (skip jika NIK duplikat)</option>
          </select>
        </div>
        <div class="col-md-6">
          <div class="section-title">Mode Deteksi Duplikat</div>
          <select id="dupMode" class="db-select">
            <option value="nik">Berdasarkan NIK</option>
            <option value="nama_tgl_lahir">Nama + Tanggal Lahir</option>
            <option value="custom">Kombinasi field kustom</option>
          </select>
        </div>
      </div>

      <!-- Mapping rows -->
      <div class="section-title">Pemetaan Kolom Excel → Database</div>
      <div class="info-box mb-3">
        🤖 Sistem telah mencoba mencocokkan kolom secara otomatis. Periksa dan sesuaikan bila perlu.
        <span class="conf-high ms-2">● Tinggi</span>
        <span class="conf-medium ms-1">● Sedang</span>
        <span class="conf-low ms-1">● Rendah</span>
      </div>
      <div id="mappingRows"></div>

      <!-- Default Values -->
      <div class="mt-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="section-title mb-0">Nilai Default (kolom tidak ada di Excel)</div>
          <button class="btn-glass btn btn-sm" onclick="addDefaultRow()">+ Tambah Default</button>
        </div>
        <div id="defaultRows"></div>
      </div>

      <!-- Value Transformation Rules -->
      <div class="mt-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="section-title mb-0">Aturan Transformasi Nilai</div>
          <button class="btn-glass btn btn-sm" onclick="addRuleRow()">+ Tambah Rule</button>
        </div>
        <div class="info-box mb-3 py-2" style="font-size:12px">
          Contoh: L → Laki-laki &nbsp;|&nbsp; P → Perempuan &nbsp;|&nbsp; JKK ACTIVE → JKK
        </div>
        <div id="ruleRows"></div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button class="btn btn-glass" onclick="goToStep(1)">← Kembali</button>
        <button class="btn btn-primary-custom" onclick="runPreview()">Preview & Validasi →</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════ -->
  <!-- STEP 3: PREVIEW                                -->
  <!-- ══════════════════════════════════════════════ -->
  <div class="step-panel" id="panel-3">
    <div class="glass-box" data-aos="fade-up">
      <h5 class="fw-bold mb-4">🔍 Preview & Validasi Data</h5>

      <!-- Stats -->
      <div class="row g-3 mb-4" id="previewStats">
        <div class="col-6 col-md-3"><div class="stat-card total"><div class="stat-num text-primary" id="st-total">-</div><div class="stat-label">Total</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card valid"><div class="stat-num" style="color:#4ade80" id="st-valid">-</div><div class="stat-label">✅ Valid</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card warning"><div class="stat-num" style="color:#fbbf24" id="st-warning">-</div><div class="stat-label">⚠ Warning</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card error"><div class="stat-num" style="color:#f87171" id="st-error">-</div><div class="stat-label">❌ Error</div></div></div>
      </div>

      <!-- Loading -->
      <div id="previewLoading" class="text-center py-5" style="display:none">
        <div class="spinner-border text-primary mb-3"></div>
        <div style="opacity:.6">Memvalidasi data...</div>
      </div>

      <!-- Error list -->
      <div id="errorSection" style="display:none">
        <div class="section-title">❌ Data Error</div>
        <div class="table-responsive" style="border-radius:12px;overflow:hidden;border:1px solid rgba(220,38,38,.2);max-height:300px;overflow-y:auto">
          <table class="table table-dark preview-table mb-0">
            <thead><tr><th>Baris</th><th>NIK</th><th>Nama</th><th>Jenis Error</th><th>Alasan</th></tr></thead>
            <tbody id="errorTableBody"></tbody>
          </table>
        </div>
      </div>

      <!-- Warning list -->
      <div id="warningSection" class="mt-3" style="display:none">
        <div class="section-title">⚠ Data Warning</div>
        <div class="table-responsive" style="border-radius:12px;overflow:hidden;border:1px solid rgba(202,138,4,.2);max-height:300px;overflow-y:auto">
          <table class="table table-dark preview-table mb-0">
            <thead><tr><th>Baris</th><th>NIK</th><th>Nama</th><th>Jenis</th><th>Alasan</th></tr></thead>
            <tbody id="warningTableBody"></tbody>
          </table>
        </div>
      </div>

      <!-- Preview rows -->
      <div id="previewRowsSection" class="mt-4" style="display:none">
        <div class="section-title">Preview Data (10 baris pertama)</div>
        <div class="table-responsive" style="border-radius:12px;overflow:hidden;border:1px solid var(--glass-border)">
          <table class="table table-dark preview-table mb-0" id="previewDataTable"></table>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button class="btn btn-glass" onclick="goToStep(2)">← Ubah Mapping</button>
        <button class="btn btn-primary-custom" id="btnToStep4" onclick="goToStep(4)" disabled>
          Konfirmasi Import →
        </button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════ -->
  <!-- STEP 4: IMPORT                                 -->
  <!-- ══════════════════════════════════════════════ -->
  <div class="step-panel" id="panel-4">
    <div class="glass-box" data-aos="fade-up">
      <h5 class="fw-bold mb-4">🚀 Eksekusi Import</h5>

      <!-- Summary -->
      <div class="glass-box-sm mb-4" id="importSummary">
        <div class="row g-3 text-center">
          <div class="col-6 col-md-3"><div class="fw-bold fs-3 text-primary" id="conf-total">-</div><div style="font-size:12px;opacity:.6">Total Baris</div></div>
          <div class="col-6 col-md-3"><div class="fw-bold fs-3" style="color:#4ade80" id="conf-valid">-</div><div style="font-size:12px;opacity:.6">Siap Import</div></div>
          <div class="col-6 col-md-3"><div class="fw-bold fs-3 text-warning" id="conf-warning">-</div><div style="font-size:12px;opacity:.6">Warning</div></div>
          <div class="col-6 col-md-3"><div class="fw-bold fs-3 text-danger" id="conf-error">-</div><div style="font-size:12px;opacity:.6">Error</div></div>
        </div>
        <hr style="border-color:var(--glass-border)">
        <div class="row g-2" style="font-size:13px">
          <div class="col-md-6"><span style="opacity:.6">File:</span> <span class="fw-bold" id="conf-file">-</span></div>
          <div class="col-md-6"><span style="opacity:.6">Mode:</span> <span class="fw-bold" id="conf-mode">-</span></div>
          <div class="col-md-6"><span style="opacity:.6">Tabel:</span> <span class="fw-bold">peserta</span></div>
          <div class="col-md-6"><span style="opacity:.6">Operator:</span> <span class="fw-bold"><?= htmlspecialchars($_SESSION['nama'] ?? 'Unknown') ?></span></div>
        </div>
      </div>

      <div class="warn-box mb-4">
        ⚠ Pastikan mapping sudah benar sebelum memulai import. Data yang sudah diimport dapat di-rollback melalui halaman Riwayat Import.
      </div>

      <!-- Progress area (hidden before import) -->
      <div id="progressArea" style="display:none">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span style="font-size:13px;opacity:.7">Progress Import</span>
          <span class="fw-bold" id="progressPct">0%</span>
        </div>
        <div class="progress-modern mb-2"><div class="bar" id="progressBar" style="width:0%"></div></div>
        <div style="font-size:12px;opacity:.5" id="progressDetail">Memulai...</div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button class="btn btn-glass" id="btnBackFromImport" onclick="goToStep(3)">← Kembali</button>
        <button class="btn btn-success-custom" id="btnStartImport" onclick="startImport()">
          ✅ Mulai Import Sekarang
        </button>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════ -->
  <!-- STEP 5: HASIL                                  -->
  <!-- ══════════════════════════════════════════════ -->
  <div class="step-panel" id="panel-5">
    <div class="glass-box text-center" data-aos="fade-up">
      <div style="font-size:72px;margin-bottom:16px" id="resultIcon">✅</div>
      <h3 class="fw-bold mb-2" id="resultTitle">Import Selesai!</h3>
      <p style="opacity:.6;font-size:.95rem" id="resultMsg" class="mb-4"></p>

      <div class="row g-3 justify-content-center mb-4" id="resultStats">
        <div class="col-6 col-md-3"><div class="stat-card valid"><div class="stat-num" style="color:#4ade80" id="res-imported">-</div><div class="stat-label">Diimport</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card warning"><div class="stat-num" style="color:#fbbf24" id="res-warning">-</div><div class="stat-label">Warning</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card error"><div class="stat-num" style="color:#f87171" id="res-error">-</div><div class="stat-label">Error</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card total"><div class="stat-num text-info" id="res-duration">-</div><div class="stat-label">Detik</div></div></div>
      </div>

      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="../profile.php" class="btn btn-primary-custom">👥 Lihat Data Peserta</a>
        <a href="riwayat.php"    class="btn btn-glass">📋 Riwayat Import</a>
        <a href="kesalahan.php"  class="btn btn-glass">❌ Lihat Errors</a>
        <button class="btn btn-glass" onclick="resetWizard()">📥 Import Lagi</button>
      </div>
    </div>
  </div>

</div><!-- /.page-container -->

<!-- ─── SAVE TEMPLATE MODAL ─────────────────────── -->
<div class="modal fade" id="saveTemplateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:#0e1929;border:1px solid var(--glass-border)">
      <div class="modal-header" style="border-color:var(--glass-border)">
        <h5 class="modal-title text-white">💾 Simpan Template Mapping</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label" style="font-size:13px;opacity:.7">Nama Template</label>
          <input type="text" id="tmplName" class="form-control" style="background:var(--glass);border-color:var(--glass-border);color:#fff" placeholder="e.g. BPJS Sulut, Dinsos, PERKASA">
        </div>
        <div class="mb-3">
          <label class="form-label" style="font-size:13px;opacity:.7">Keterangan (opsional)</label>
          <textarea id="tmplDesc" class="form-control" rows="2" style="background:var(--glass);border-color:var(--glass-border);color:#fff" placeholder="Deskripsi singkat template ini"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="border-color:var(--glass-border)">
        <button class="btn btn-glass" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary-custom" onclick="saveTemplate()">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- ─── TOAST CONTAINER ──────────────────────────── -->
<div id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({duration:500,once:true});

// ═══════════════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════════════
let currentStep   = 1;
let uploadedFile  = null;
let excelHeaders  = [];
let dbColumns     = [];
let autoMappings  = [];
let previewResult = null;
let importLogId   = null;

// ═══════════════════════════════════════════════════════
// TOAST
// ═══════════════════════════════════════════════════════
function showToast(msg, type='info', duration=4000) {
  const icons = {success:'✅',error:'❌',warning:'⚠️',info:'ℹ️'};
  const el = document.createElement('div');
  el.className = `toast-msg ${type}`;
  el.innerHTML = `<span>${icons[type]||'ℹ️'}</span><span>${msg}</span>`;
  document.getElementById('toastContainer').appendChild(el);
  setTimeout(() => el.remove(), duration);
}

// ═══════════════════════════════════════════════════════
// WIZARD NAVIGATION
// ═══════════════════════════════════════════════════════
function goToStep(n) {
  document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + n).classList.add('active');

  for (let i = 1; i <= 5; i++) {
    const bubble = document.getElementById('bubble-' + i);
    const label  = document.getElementById('label-' + i);
    bubble.classList.remove('active','done');
    label.classList.remove('active');
    if (i < n)  { bubble.classList.add('done'); }
    if (i === n){ bubble.classList.add('active'); label.classList.add('active'); }
  }
  currentStep = n;
  window.scrollTo({top:0,behavior:'smooth'});
}

// ═══════════════════════════════════════════════════════
// STEP 1: UPLOAD
// ═══════════════════════════════════════════════════════
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave',()=> dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
  e.preventDefault(); dropZone.classList.remove('drag-over');
  if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', e => { if(e.target.files[0]) handleFile(e.target.files[0]); });

function handleFile(file) {
  const ext = file.name.split('.').pop().toLowerCase();
  if (!['xlsx','xls','csv'].includes(ext)) {
    showToast('Format tidak didukung. Gunakan .xlsx, .xls, atau .csv', 'error');
    return;
  }
  uploadedFile = file;
  dropZone.classList.add('has-file');

  const formData = new FormData();
  formData.append('excel_file', file);

  document.getElementById('uploadStatus').style.display = 'block';
  document.getElementById('uploadStatus').innerHTML = '⏳ Membaca file...';
  document.getElementById('btnToStep2').disabled = true;

  fetch('api/upload.php', { method:'POST', body: formData })
    .then(async r => {
      const text = await r.text();
      try {
        return JSON.parse(text);
      } catch (err) {
        console.error("Raw server response:", text);
        throw new Error("Respon server bukan JSON valid: " + text.substring(0, 300));
      }
    })
    .then(data => {
      if (!data.success) { showToast(data.message, 'error'); return; }

      excelHeaders = data.headers;

      // File info
      document.getElementById('fi-name').textContent = data.filename;
      document.getElementById('fi-size').textContent = data.file_size + ' — ' + data.total_rows + ' baris data';
      document.getElementById('fi-rows').textContent = data.total_rows.toLocaleString();
      document.getElementById('fi-headers').textContent = data.headers.length;
      document.getElementById('fileInfo').style.display = 'block';

      // Header badges
      const hBadges = document.getElementById('headerBadges');
      hBadges.innerHTML = data.headers.map(h =>
        `<span style="background:rgba(37,99,235,.18);border:1px solid rgba(37,99,235,.3);border-radius:8px;padding:5px 12px;font-size:12px;font-weight:500">${h}</span>`
      ).join('');
      document.getElementById('headerPreview').style.display = 'block';

      // Data preview table
      if (data.preview && data.preview.length) {
        const tbl = document.getElementById('previewTable');
        const thead = `<thead><tr>${data.headers.map(h=>`<th>${h}</th>`).join('')}</tr></thead>`;
        const rows  = data.preview.map(r =>
          `<tr>${r.map(c => `<td style="font-size:12px">${c||'<span style="opacity:.3">—</span>'}</td>`).join('')}</tr>`
        ).join('');
        tbl.innerHTML = thead + `<tbody>${rows}</tbody>`;
        document.getElementById('dataPreview').style.display = 'block';
      }

      document.getElementById('uploadStatus').innerHTML = '✅ File berhasil dibaca: ' + data.total_rows.toLocaleString() + ' baris ditemukan';
      document.getElementById('btnToStep2').disabled = false;
      showToast('File berhasil diupload!', 'success');

      // Auto-load DB columns & mapping suggestions
      loadDbColumns();
    })
    .catch(err => { showToast('Gagal mengupload: ' + err.message, 'error'); });
}

// ═══════════════════════════════════════════════════════
// LOAD DB COLUMNS
// ═══════════════════════════════════════════════════════
function loadDbColumns() {
  fetch('api/get_db_columns.php?table=peserta')
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      dbColumns = data.columns;

      // Auto-suggest mapping
      fetch('api/auto_map.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ headers: excelHeaders, db_columns: dbColumns })
      })
      .then(r => r.json())
      .then(amData => {
        if (amData.success) autoMappings = amData.mappings;
      });

      // Load template list
      loadTemplateList();
    });
}

// ═══════════════════════════════════════════════════════
// STEP 2: MAPPING UI
// ═══════════════════════════════════════════════════════
function buildMappingUI(customMappings) {
  if (!dbColumns.length) {
    showToast('Kolom database belum dimuat. Ulangi upload.', 'error');
    return;
  }

  const container = document.getElementById('mappingRows');
  container.innerHTML = '';

  const dbOptions = `<option value="__skip__">— Abaikan kolom ini —</option>` +
    dbColumns.map(c => `<option value="${c.field}">${c.label} (${c.field})</option>`).join('');

  excelHeaders.forEach((header, idx) => {
    let suggested = '';
    let confidence = 'low';

    if (customMappings) {
      suggested = customMappings[header] || '__skip__';
    } else if (autoMappings[idx]) {
      suggested = autoMappings[idx].suggested_col || '__skip__';
      confidence= autoMappings[idx].confidence || 'low';
    }

    const confClass = `conf-${confidence}`;
    const confLabel = confidence === 'high' ? '● Tinggi' : (confidence === 'medium' ? '● Sedang' : '● Rendah');

    const row = document.createElement('div');
    row.className = 'mapping-row';
    row.innerHTML = `
      <div>
        <div class="excel-header-badge">
          <span>📊</span> ${header}
        </div>
        <div class="${confClass} mt-1 ps-2">${confClass !== 'conf-low' ? confLabel : ''}</div>
      </div>
      <div class="mapping-arrow">→</div>
      <div>
        <select class="db-select mapping-db-select" data-header="${header}" id="map-${idx}">
          ${dbOptions}
        </select>
      </div>
    `;
    container.appendChild(row);

    const sel = row.querySelector(`#map-${idx}`);
    if (suggested) sel.value = suggested;
  });
}

// Override goToStep to build mapping when entering step 2
const _origGoToStep = goToStep;
window.goToStep = function(n) {
  if (n === 2 && excelHeaders.length) {
    buildMappingUI(null);
  }
  if (n === 4 && previewResult) {
    fillConfirmation();
  }
  _origGoToStep(n);
};

// ═══════════════════════════════════════════════════════
// DEFAULT VALUES
// ═══════════════════════════════════════════════════════
function addDefaultRow(dbCol, defVal) {
  const container = document.getElementById('defaultRows');
  const row = document.createElement('div');
  row.className = 'default-row';

  const dbOptions = dbColumns.map(c =>
    `<option value="${c.field}" ${c.field===(dbCol||'')?'selected':''}>${c.label} (${c.field})</option>`
  ).join('');

  row.innerHTML = `
    <select class="db-select" style="flex:1" name="def_col[]">${dbOptions}</select>
    <span style="opacity:.5;font-size:13px">=</span>
    <input type="text" class="rule-input" style="flex:1" name="def_val[]" value="${defVal||''}" placeholder="Nilai default atau NOW()">
    <button class="btn btn-sm" style="color:#f87171;background:none;border:none;font-size:16px" onclick="this.parentElement.remove()">🗑</button>
  `;
  container.appendChild(row);
}

// ═══════════════════════════════════════════════════════
// TRANSFORMATION RULES
// ═══════════════════════════════════════════════════════
function addRuleRow(dbCol, excelVal, dbVal) {
  const container = document.getElementById('ruleRows');
  const row = document.createElement('div');
  row.className = 'rule-row';

  const dbOptions = dbColumns.map(c =>
    `<option value="${c.field}" ${c.field===(dbCol||'')?'selected':''}>${c.label}</option>`
  ).join('');

  row.innerHTML = `
    <select class="db-select" name="rule_col[]">${dbOptions}</select>
    <span style="opacity:.5;text-align:center">:</span>
    <div style="display:flex;gap:6px;align-items:center">
      <input type="text" class="rule-input" name="rule_from[]" value="${excelVal||''}" placeholder="Nilai Excel">
      <span style="opacity:.4">→</span>
      <input type="text" class="rule-input" name="rule_to[]"   value="${dbVal||''}"   placeholder="Nilai DB">
    </div>
    <button class="btn btn-sm" style="color:#f87171;background:none;border:none" onclick="this.parentElement.remove()">🗑</button>
  `;
  container.appendChild(row);
}

// Default rules preset
function addDefaultRules() {
  addRuleRow('jenis_kelamin', 'L', 'Laki-laki');
  addRuleRow('jenis_kelamin', 'P', 'Perempuan');
  addRuleRow('jenis_kelamin', 'Laki laki', 'Laki-laki');
  addRuleRow('tipe_program',  'JKK ACTIVE', 'JKK');
}

// ═══════════════════════════════════════════════════════
// COLLECT MAPPING DATA
// ═══════════════════════════════════════════════════════
function collectMapping() {
  const mapping  = {};
  const rules    = {};
  const defaults = {};

  document.querySelectorAll('.mapping-db-select').forEach(sel => {
    const header = sel.dataset.header;
    const dbCol  = sel.value;
    if (dbCol && dbCol !== '__skip__') mapping[header] = dbCol;
  });

  // Rules
  const ruleCols = document.querySelectorAll('[name="rule_col[]"]');
  const ruleFrom = document.querySelectorAll('[name="rule_from[]"]');
  const ruleTo   = document.querySelectorAll('[name="rule_to[]"]');
  ruleCols.forEach((col, i) => {
    const c = col.value, f = ruleFrom[i]?.value, t = ruleTo[i]?.value;
    if (c && f && t) {
      if (!rules[c]) rules[c] = {};
      rules[c][f] = t;
    }
  });

  // Defaults
  const defCols = document.querySelectorAll('[name="def_col[]"]');
  const defVals = document.querySelectorAll('[name="def_val[]"]');
  defCols.forEach((col, i) => {
    const c = col.value, v = defVals[i]?.value;
    if (c && v) defaults[c] = v;
  });

  return { mapping, rules, defaults };
}

// ═══════════════════════════════════════════════════════
// STEP 3: PREVIEW & VALIDATE
// ═══════════════════════════════════════════════════════
function runPreview() {
  const { mapping, rules, defaults } = collectMapping();

  if (!Object.keys(mapping).length) {
    showToast('Harap mapping minimal 1 kolom sebelum melanjutkan', 'warning');
    return;
  }

  goToStep(3);
  document.getElementById('previewLoading').style.display = 'block';
  document.getElementById('previewRowsSection').style.display = 'none';
  document.getElementById('errorSection').style.display = 'none';
  document.getElementById('warningSection').style.display = 'none';
  document.getElementById('btnToStep4').disabled = true;

  fetch('api/preview.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      mapping,
      rules,
      defaults,
      dup_mode:     document.getElementById('dupMode').value,
      target_table: 'peserta'
    })
  })
  .then(r => r.json())
  .then(data => {
    document.getElementById('previewLoading').style.display = 'none';

    if (!data.success) { showToast(data.message, 'error'); return; }

    previewResult = data;

    // Stats
    document.getElementById('st-total').textContent   = data.stats.total.toLocaleString();
    document.getElementById('st-valid').textContent   = data.stats.valid.toLocaleString();
    document.getElementById('st-warning').textContent = data.stats.warning.toLocaleString();
    document.getElementById('st-error').textContent   = data.stats.error.toLocaleString();

    // Error rows
    if (data.error_rows && data.error_rows.length) {
      const tbody = document.getElementById('errorTableBody');
      tbody.innerHTML = data.error_rows.flatMap(row =>
        row.errors.map(err => `
          <tr class="row-error">
            <td><span class="badge-error">Baris ${row.row}</span></td>
            <td style="font-size:12px">${row.data?.nik || row.raw?.[Object.keys(row.raw)[0]] || '—'}</td>
            <td style="font-size:12px">${row.data?.nama_lengkap || '—'}</td>
            <td><span class="badge-error" style="font-size:10px">${err.type}</span></td>
            <td style="font-size:12px;color:#fca5a5">${err.reason}</td>
          </tr>`)
      ).join('');
      document.getElementById('errorSection').style.display = 'block';
    }

    // Warning rows
    if (data.warning_rows && data.warning_rows.length) {
      const tbody = document.getElementById('warningTableBody');
      tbody.innerHTML = data.warning_rows.flatMap(row =>
        row.warnings.map(w => `
          <tr class="row-warning">
            <td><span class="badge-warning">Baris ${row.row}</span></td>
            <td style="font-size:12px">${row.data?.nik || '—'}</td>
            <td style="font-size:12px">${row.data?.nama_lengkap || '—'}</td>
            <td><span class="badge-warning" style="font-size:10px">${w.type}</span></td>
            <td style="font-size:12px;color:#fde68a">${w.reason}</td>
          </tr>`)
      ).join('');
      document.getElementById('warningSection').style.display = 'block';
    }

    // Preview data rows
    if (data.preview_rows && data.preview_rows.length) {
      const cols   = Object.keys(data.preview_rows[0].data);
      const tbl    = document.getElementById('previewDataTable');
      const thead  = `<thead><tr><th>Baris</th><th>Status</th>${cols.map(c=>`<th>${c}</th>`).join('')}</tr></thead>`;
      const rows   = data.preview_rows.map(row => {
        const cls  = row.status === 'valid' ? 'row-valid' : (row.status === 'warning' ? 'row-warning' : 'row-error');
        const badge= row.status === 'valid' ? '<span class="badge-valid">Valid</span>' :
                     (row.status === 'warning' ? '<span class="badge-warning">Warning</span>' :
                      '<span class="badge-error">Error</span>');
        const cells= cols.map(c => `<td>${row.data[c] || '<span style="opacity:.3">—</span>'}</td>`).join('');
        return `<tr class="${cls}"><td>${row.row}</td><td>${badge}</td>${cells}</tr>`;
      }).join('');
      tbl.innerHTML = thead + `<tbody>${rows}</tbody>`;
      document.getElementById('previewRowsSection').style.display = 'block';
    }

    // Enable next button - always allow proceeding
    document.getElementById('btnToStep4').disabled = false;

    let msg;
    let toastType = 'success';
    if (data.stats.error > 0 && data.stats.valid === 0) {
      msg = `⚠ PERHATIAN: Semua ${data.stats.total} baris error! Mode import akan menentukan tindakan selanjutnya (skip error atau batalkan).`;
      toastType = 'error';
    } else if (data.stats.error > 0) {
      msg = `⚠ Ditemukan ${data.stats.error} baris error. Akan di-skip berdasarkan mode import.`;
      toastType = 'warning';
    } else if (data.stats.warning > 0) {
      msg = `✅ Data valid dengan ${data.stats.warning} warning(s). Siap diimport.`;
      toastType = 'success';
    } else {
      msg = '✅ Semua data valid, siap diimport.';
      toastType = 'success';
    }
    showToast(msg, toastType);
  })
  .catch(err => {
    document.getElementById('previewLoading').style.display = 'none';
    showToast('Error validasi: ' + err.message, 'error');
  });
}

// ═══════════════════════════════════════════════════════
// STEP 4: CONFIRMATION
// ═══════════════════════════════════════════════════════
function fillConfirmation() {
  if (!previewResult) return;
  const modeLabels = {
    valid_only: 'Import hanya data valid',
    all_or_nothing: 'Batalkan semua jika ada error',
    valid_skip_error: 'Import valid, abaikan error',
    upsert: 'Upsert (Insert/Update)',
    update: 'Update jika NIK ada',
    insert_only: 'Insert saja'
  };
  document.getElementById('conf-total').textContent   = previewResult.stats.total.toLocaleString();
  document.getElementById('conf-valid').textContent   = previewResult.stats.valid.toLocaleString();
  document.getElementById('conf-warning').textContent = previewResult.stats.warning.toLocaleString();
  document.getElementById('conf-error').textContent   = previewResult.stats.error.toLocaleString();
  document.getElementById('conf-file').textContent    = uploadedFile?.name || '—';
  document.getElementById('conf-mode').textContent    = modeLabels[document.getElementById('importMode').value] || '';
}

// ═══════════════════════════════════════════════════════
// STEP 4: START IMPORT (batch processing)
// ═══════════════════════════════════════════════════════
async function startImport() {
  const { mapping, rules, defaults } = collectMapping();
  const importMode = document.getElementById('importMode').value;

  document.getElementById('btnStartImport').disabled    = true;
  document.getElementById('btnBackFromImport').disabled = true;
  document.getElementById('progressArea').style.display = 'block';

  let batchStart = 0;
  let totalImported = 0;
  let totalError    = 0;
  let totalWarning  = 0;
  let logId         = null;
  let duration      = 0;

  while (true) {
    try {
      const res = await fetch('api/do_import.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ mapping, rules, defaults, import_mode: importMode, batch_start: batchStart })
      });
      const data = await res.json();

      if (!data.success) {
        showToast('Import gagal: ' + data.message, 'error');
        document.getElementById('btnStartImport').disabled = false;
        document.getElementById('btnBackFromImport').disabled = false;
        return;
      }

      totalImported += data.batch_valid  || 0;
      totalError    += data.batch_error  || 0;
      totalWarning  += data.batch_warning|| 0;
      logId          = data.log_id;
      duration       = data.duration || 0;

      const pct = data.progress_pct || 100;
      document.getElementById('progressBar').style.width   = pct + '%';
      document.getElementById('progressPct').textContent   = pct + '%';
      document.getElementById('progressDetail').textContent= `Batch: ${batchStart} — ${totalImported} baris berhasil`;

      if (data.is_last) break;
      batchStart = data.next_start;

    } catch (err) {
      showToast('Error jaringan: ' + err.message, 'error');
      break;
    }
  }

  importLogId = logId;

  // Go to result
  document.getElementById('resultTitle').textContent      = 'Import Selesai! 🎉';
  document.getElementById('resultMsg').textContent        = `${totalImported.toLocaleString()} data berhasil diimport ke database.`;
  document.getElementById('res-imported').textContent     = totalImported.toLocaleString();
  document.getElementById('res-warning').textContent      = totalWarning.toLocaleString();
  document.getElementById('res-error').textContent        = totalError.toLocaleString();
  document.getElementById('res-duration').textContent     = duration;
  goToStep(5);
  showToast(`Import selesai! ${totalImported} baris berhasil.`, 'success');
}

// ═══════════════════════════════════════════════════════
// TEMPLATE
// ═══════════════════════════════════════════════════════
function loadTemplateList() {
  fetch('api/load_template.php?action=list')
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      const sel = document.getElementById('templateSelect');
      sel.innerHTML = '<option value="">— Load Template —</option>' +
        data.templates.map(t => `<option value="${t.id}">${t.name} (${t.mapping_count} kolom)</option>`).join('');
    });
}

function loadTemplate() {
  const id = document.getElementById('templateSelect').value;
  if (!id) return;

  fetch(`api/load_template.php?id=${id}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast(data.message, 'error'); return; }

      // Set import mode & dup mode from template
      document.getElementById('importMode').value = data.template.import_mode;
      document.getElementById('dupMode').value     = data.template.duplicate_mode;

      // Build custom mappings from template details
      const customMap = {};
      data.details.forEach(d => { if (d.db_column) customMap[d.excel_header] = d.db_column; });

      buildMappingUI(customMap);

      // Load defaults
      document.getElementById('defaultRows').innerHTML = '';
      data.details.filter(d => d.default_value).forEach(d => {
        addDefaultRow(d.db_column, d.default_value);
      });

      // Load rules
      document.getElementById('ruleRows').innerHTML = '';
      data.rules.forEach(r => addRuleRow(r.db_column, r.excel_value, r.db_value));

      showToast(`Template "${data.template.name}" berhasil dimuat`, 'success');
    });
}

function openSaveTemplate() {
  const modal = new bootstrap.Modal(document.getElementById('saveTemplateModal'));
  modal.show();
}

function saveTemplate() {
  const name = document.getElementById('tmplName').value.trim();
  if (!name) { showToast('Nama template wajib diisi', 'warning'); return; }

  const { mapping, rules, defaults } = collectMapping();

  const mappings = Object.entries(mapping).map(([h, c]) => ({
    excel_header: h, db_column: c, default_value: defaults[c] || '', is_required: false
  }));
  const rulesList = [];
  Object.entries(rules).forEach(([col, rmap]) => {
    Object.entries(rmap).forEach(([from, to]) => {
      rulesList.push({ db_column: col, excel_value: from, db_value: to });
    });
  });

  fetch('api/save_template.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      name, description: document.getElementById('tmplDesc').value,
      target_table: 'peserta',
      import_mode: document.getElementById('importMode').value,
      dup_mode:    document.getElementById('dupMode').value,
      mappings, rules: rulesList
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('saveTemplateModal')).hide();
      showToast(data.message, 'success');
      loadTemplateList();
    } else {
      showToast(data.message, 'error');
    }
  });
}

// ═══════════════════════════════════════════════════════
// RESET
// ═══════════════════════════════════════════════════════
function resetWizard() {
  uploadedFile  = null;
  excelHeaders  = [];
  autoMappings  = [];
  previewResult = null;
  importLogId   = null;

  document.getElementById('fileInput').value      = '';
  document.getElementById('fileInfo').style.display    = 'none';
  document.getElementById('headerPreview').style.display = 'none';
  document.getElementById('dataPreview').style.display   = 'none';
  document.getElementById('uploadStatus').style.display  = 'none';
  document.getElementById('btnToStep2').disabled = true;
  dropZone.classList.remove('has-file');
  document.getElementById('mappingRows').innerHTML = '';
  document.getElementById('defaultRows').innerHTML = '';
  document.getElementById('ruleRows').innerHTML    = '';
  document.getElementById('progressArea').style.display = 'none';
  document.getElementById('progressBar').style.width   = '0%';

  goToStep(1);
}

// ─── Init ────────────────────────────────────────────
loadDbColumns();
addDefaultRules();
</script>
</body>
</html>
