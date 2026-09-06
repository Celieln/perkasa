<?php
include 'config.php';

$tracking_result = null;
$kode_tracking = '';
$no_pengurusan = '';
$tracking_logs = [];

if(isset($_POST['track']) && isset($_POST['kode_tracking'])){
    verify_csrf();
    $kode_tracking = mysqli_real_escape_string($conn, trim($_POST['kode_tracking']));

    if(strlen($kode_tracking) > 0){
        $q_track = mysqli_query($conn, "SELECT tp.*, p.nama_lengkap, p.nik, p.tipe_program
                                        FROM tracking_pengurusan tp
                                        LEFT JOIN peserta p ON tp.peserta_id = p.id
                                        WHERE tp.kode_tracking='$kode_tracking'
                                        LIMIT 1");
        $tracking_result = mysqli_fetch_assoc($q_track);
        if($tracking_result){
            $no_pengurusan = $tracking_result['no_pengurusan'] ?? '-';

            $peserta_id = $tracking_result['peserta_id'] ?? 0;
            if($peserta_id){
                $q_logs = mysqli_query($conn, "SELECT * FROM tracking_log WHERE peserta_id=$peserta_id ORDER BY created_at DESC LIMIT 10");
                if($q_logs){
                    while($log = mysqli_fetch_assoc($q_logs)){
                        $tracking_logs[] = $log;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PERKASA — Lacak Pengurusan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    background:#0a0f1a;
    min-height:100vh;color:white;overflow-x:hidden;
    position:relative;
}
#particles-canvas{position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none;}
.animated-bg{
    position:fixed;top:0;left:0;width:100%;height:100%;
    background:linear-gradient(-45deg,#0a0f1a,#1a1a2e,#16213e,#0f3460);
    background-size:400% 400%;animation:gradientBG 15s ease infinite;z-index:-1;
}
@keyframes gradientBG{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
.glass-card{
    background:rgba(255,255,255,0.03);backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,0.06);border-radius:24px;
    box-shadow:0 25px 50px rgba(0,0,0,0.3);position:relative;overflow:hidden;
}
.glass-card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.1),transparent);}
.feature-card{
    background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);
    border-radius:20px;padding:20px 18px;text-decoration:none;color:white;
    transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);display:block;height:100%;
    position:relative;overflow:hidden;
}
.feature-card::before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(135deg,rgba(59,130,246,0.1),transparent);opacity:0;transition:0.4s;}
.feature-card:hover{transform:translateY(-8px) scale(1.02);border-color:rgba(59,130,246,0.4);box-shadow:0 20px 40px rgba(59,130,246,0.2);}
.feature-card:hover::before{opacity:1;}
.feature-card .icon-wrapper{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;transition:0.4s;}
.feature-card:hover .icon-wrapper{transform:scale(1.1) rotate(5deg);}
.feature-card .title{font-size:14px;font-weight:700;}
.feature-card .desc{font-size:12px;opacity:0.5;line-height:1.5;}
.feature-card .arrow{position:absolute;right:16px;top:50%;transform:translateY(-50%);opacity:0;transition:0.4s;}
.feature-card:hover .arrow{opacity:1;transform:translateY(-50%) translateX(5px);}
.input-group-custom{position:relative;}
.custom-input{
    background:rgba(255,255,255,0.04);border:2px solid rgba(255,255,255,0.08);
    color:white;border-radius:14px;height:56px;padding:0 20px;transition:all 0.3s;font-size:15px;
}
.custom-input::placeholder{color:rgba(255,255,255,0.25);}
.custom-input:focus{background:rgba(255,255,255,0.06)!important;border-color:#3b82f6!important;box-shadow:0 0 0 4px rgba(59,130,246,0.15),0 0 30px rgba(59,130,246,0.1)!important;color:white!important;}
.input-icon{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.3);font-size:18px;transition:0.3s;}
.custom-input:focus ~ .input-icon{color:#3b82f6;}
.custom-btn{
    height:56px;border-radius:14px;font-weight:700;font-size:16px;
    background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;transition:all 0.3s;
    box-shadow:0 4px 20px rgba(59,130,246,0.4);display:flex;align-items:center;justify-content:center;gap:10px;
    position:relative;overflow:hidden;
}
.custom-btn::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);transition:0.5s;}
.custom-btn:hover::before{left:100%;}
.custom-btn:hover{transform:translateY(-3px);box-shadow:0 8px 30px rgba(59,130,246,0.5);}

/* Timeline Styles */
.timeline-header{display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid rgba(255,255,255,0.06);}
.timeline-icon{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,rgba(59,130,246,0.2),rgba(147,197,253,0.1));border:2px solid rgba(59,130,246,0.3);display:flex;align-items:center;justify-content:center;color:#60a5fa;flex-shrink:0;font-size:24px;}
.timeline-info-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-bottom:24px;}
.timeline-info-box{background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05);border-radius:14px;padding:16px;}
.timeline-info-box .label{font-size:11px;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;}
.timeline-info-box .value{font-size:15px;font-weight:600;color:rgba(255,255,255,0.9);}
.timeline-status-badge{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:12px;font-weight:700;font-size:14px;}
.status-selesai{background:rgba(52,211,153,0.12);color:#34d399;border:1px solid rgba(52,211,153,0.25);}
.status-diproses{background:rgba(251,191,36,0.12);color:#fbbf24;border:1px solid rgba(251,191,36,0.25);}
.status-ditolak{background:rgba(239,68,68,0.12);color:#f87171;border:1px solid rgba(239,68,68,0.25);}
.status-pending{background:rgba(148,163,184,0.12);color:#94a3b8;border:1px solid rgba(148,163,184,0.25);}

/* Progress Steps */
.progress-steps{display:flex;align-items:center;justify-content:space-between;margin:28px 0;position:relative;}
.progress-steps::before{content:'';position:absolute;top:20px;left:40px;right:40px;height:3px;background:rgba(255,255,255,0.08);border-radius:2px;}
.progress-step{display:flex;flex-direction:column;align-items:center;gap:10px;z-index:1;}
.step-circle{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;transition:0.3s;}
.step-done{background:linear-gradient(135deg,#10b981,#059669);color:white;box-shadow:0 4px 16px rgba(16,185,129,0.4);}
.step-current{background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;box-shadow:0 4px 16px rgba(59,130,246,0.5);animation:pulseGlow 2s infinite;}
.step-pending{background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.3);border:2px solid rgba(255,255,255,0.08);}
@keyframes pulseGlow{0%,100%{transform:scale(1);box-shadow:0 4px 16px rgba(59,130,246,0.5);}50%{transform:scale(1.08);box-shadow:0 6px 24px rgba(59,130,246,0.7);}}
.step-label{font-size:11px;color:rgba(255,255,255,0.4);text-align:center;max-width:80px;}
.step-label.active{color:rgba(255,255,255,0.8);font-weight:600;}

/* Timeline Log */
.timeline-log{margin-top:28px;}
.timeline-log-header{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
.timeline-log-header h6{font-size:15px;font-weight:700;margin:0;}
.log-entry{display:flex;gap:14px;padding:14px 0;border-bottom:1px solid rgba(255,255,255,0.04);}
.log-entry:last-child{border-bottom:none;}
.log-dot{width:10px;height:10px;border-radius:50%;background:#3b82f6;margin-top:6px;flex-shrink:0;box-shadow:0 0 10px rgba(59,130,246,0.5);}
.log-content{flex:1;}
.log-status{font-size:13px;font-weight:600;color:rgba(255,255,255,0.9);}
.log-desc{font-size:12px;color:rgba(255,255,255,0.4);margin-top:3px;}
.log-time{font-size:11px;color:rgba(255,255,255,0.25);margin-top:4px;}
.result-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:20px;padding:28px;margin-top:20px;}
.not-found-box{text-align:center;padding:40px 20px;}
.not-found-icon{width:72px;height:72px;border-radius:50%;background:rgba(239,68,68,0.08);border:2px dashed rgba(239,68,68,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;color:#f87171;font-size:28px;}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.08),transparent);margin:20px 0;}
::-webkit-scrollbar{width:8px;}
::-webkit-scrollbar-track{background:rgba(255,255,255,0.02);}
::-webkit-scrollbar-thumb{background:linear-gradient(180deg,#3b82f6,#2563eb);border-radius:10px;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.5;}}
@media(max-width:768px){
    .glass-card{padding:28px 18px;}
    .timeline-info-grid{grid-template-columns:1fr;}
    .progress-steps{flex-wrap:wrap;gap:16px;justify-content:center;}
    .progress-steps::before{display:none;}
    .feature-card{padding:16px 14px;}
}
</style>
</head>
<body>

<div class="animated-bg"></div>
<canvas id="particles-canvas"></canvas>

<div class="container position-relative" style="z-index:1;">
<div class="row justify-content-center">
<div class="col-lg-6 col-md-8">

    <!-- Header -->
    <div class="text-center mb-4 pt-4" data-aos="fade-down">
        <div class="d-inline-flex align-items-center gap-3 mb-3">
            <div style="width:52px;height:52px;border-radius:16px;background:linear-gradient(135deg,#3b82f6,#60a5fa);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 30px rgba(59,130,246,0.4);">
                <i class="fas fa-route text-white" style="font-size:24px;"></i>
            </div>
            <div class="text-start">
                <h1 class="fw-bold mb-0" style="font-size:28px;letter-spacing:3px;background:linear-gradient(135deg,#fff,#94a3b8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">PERKASA</h1>
                <p style="color:rgba(255,255,255,0.4);font-size:13px;letter-spacing:1px;">Lacak Pengurusan Klaim JKK/JKM</p>
            </div>
        </div>
    </div>

    <!-- Feature Links -->
    <div class="row g-3 mb-4" data-aos="fade-up" data-aos-delay="50">
        <div class="col-6">
            <a href="cek_peserta.php" class="feature-card">
                <div class="icon-wrapper" style="background:linear-gradient(135deg,rgba(16,185,129,0.2),rgba(52,211,153,0.1));">
                    <i class="fas fa-clipboard-check" style="color:#34d399;font-size:22px;"></i>
                </div>
                <div class="title">Cek Kepersertaan</div>
                <div class="desc">Periksa status data Anda</div>
                <div class="arrow"><i class="fas fa-arrow-right" style="color:#34d399;"></i></div>
            </a>
        </div>
        <div class="col-6">
            <a href="index.php" class="feature-card">
                <div class="icon-wrapper" style="background:linear-gradient(135deg,rgba(139,92,246,0.2),rgba(168,85,247,0.1));">
                    <i class="fas fa-sign-in-alt" style="color:#a855f7;font-size:22px;"></i>
                </div>
                <div class="title">Login</div>
                <div class="desc">Masuk ke akun Anda</div>
                <div class="arrow"><i class="fas fa-arrow-right" style="color:#a855f7;"></i></div>
            </a>
        </div>
    </div>

    <!-- Search Form -->
    <div class="glass-card p-4 p-md-5 mb-4" data-aos="zoom-in" data-aos-delay="100">
        <div class="text-center mb-4">
            <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,rgba(59,130,246,0.2),rgba(147,197,253,0.1));border:2px solid rgba(59,130,246,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-search" style="color:#60a5fa;font-size:28px;"></i>
            </div>
            <h4 class="fw-bold mb-1" style="font-size:22px;">Lacak Pengurusan</h4>
            <p style="color:rgba(255,255,255,0.4);font-size:13px;">Masukkan kode tracking untuk melihat progress klaim</p>
        </div>

        <form method="POST" id="trackForm">
            <?= csrf_field() ?>
            <div class="mb-4">
                <label class="form-label" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.7);display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-key" style="color:#60a5fa;"></i> Kode Tracking
                </label>
                <div class="input-group-custom">
                    <input type="text" name="kode_tracking" class="form-control custom-input" placeholder="Contoh: PRK-ABCD-EFGH" value="<?= htmlspecialchars($kode_tracking) ?>" required style="padding-left:48px;text-transform:uppercase;letter-spacing:2px;">
                    <i class="fas fa-key input-icon"></i>
                </div>
            </div>
            <button type="submit" name="track" class="btn custom-btn w-100 text-white mb-2" id="trackBtn">
                <i class="fas fa-search"></i> LACAK SEKARANG
            </button>
        </form>

        <?php if(isset($_POST['track'])): ?>
        <?php if($tracking_result): ?>
        <div class="result-card" data-aos="fade-up">
            <!-- Header -->
            <div class="timeline-header">
                <div class="timeline-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="font-size:18px;">Pengurusan Ditemukan</h5>
                    <small style="color:rgba(255,255,255,0.5);font-size:12px;">Kode: <?= htmlspecialchars($kode_tracking) ?></small>
                </div>
            </div>

            <!-- Info Grid -->
            <div class="timeline-info-grid">
                <div class="timeline-info-box">
                    <div class="label"><i class="fas fa-hashtag" style="margin-right:6px;"></i> No. Pengurusan</div>
                    <div class="value"><?= htmlspecialchars($no_pengurusan) ?></div>
                </div>
                <div class="timeline-info-box">
                    <div class="label"><i class="fas fa-tag" style="margin-right:6px;"></i> Program</div>
                    <div class="value" style="color:#60a5fa;"><?= htmlspecialchars($tracking_result['tipe_program'] ?? '-') ?></div>
                </div>
                <div class="timeline-info-box">
                    <div class="label"><i class="fas fa-user" style="margin-right:6px;"></i> Nama Peserta</div>
                    <div class="value"><?= htmlspecialchars($tracking_result['nama_lengkap'] ?? '-') ?></div>
                </div>
                <div class="timeline-info-box">
                    <div class="label"><i class="fas fa-flag" style="margin-right:6px;"></i> Status Saat Ini</div>
                    <div class="value">
                        <?php
                        $status = $tracking_result['status'] ?? 'pending';
                        $statusClass = match($status){
                            'selesai' => 'status-selesai',
                            'diproses' => 'status-diproses',
                            'ditolak' => 'status-ditolak',
                            default => 'status-pending'
                        };
                        ?>
                        <span class="timeline-status-badge <?= $statusClass ?>">
                            <?php if($status === 'selesai'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php elseif($status === 'diproses'): ?>
                                <i class="fas fa-clock"></i>
                            <?php elseif($status === 'ditolak'): ?>
                                <i class="fas fa-times-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-hourglass-half"></i>
                            <?php endif; ?>
                            <?= strtoupper($status) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="progress-step">
                    <div class="step-circle <?= in_array($status, ['pending','diproses','selesai','ditolak']) ? ($status === 'pending' ? 'step-pending' : 'step-done') : 'step-pending' ?>">
                        <i class="fas fa-paper-plane" style="font-size:16px;"></i>
                    </div>
                    <div class="step-label <?= $status !== 'pending' ? 'active' : '' ?>">Diajukan</div>
                </div>
                <div class="progress-step">
                    <div class="step-circle <?= in_array($status, ['diproses','selesai']) ? ($status === 'diproses' ? 'step-current' : 'step-done') : 'step-pending' ?>">
                        <i class="fas fa-cog" style="font-size:16px;"></i>
                    </div>
                    <div class="step-label <?= in_array($status, ['diproses','selesai']) ? 'active' : '' ?>">Diproses</div>
                </div>
                <div class="progress-step">
                    <div class="step-circle <?= $status === 'selesai' ? 'step-done' : 'step-pending' ?>">
                        <i class="fas fa-check" style="font-size:16px;"></i>
                    </div>
                    <div class="step-label <?= $status === 'selesai' ? 'active' : '' ?>">Selesai</div>
                </div>
            </div>

            <?php if(!empty($tracking_result['keterangan'])): ?>
            <div class="p-3 mb-3" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05);border-radius:14px;">
                <div style="font-size:11px;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;"><i class="fas fa-info-circle" style="margin-right:6px;"></i> Keterangan</div>
                <p style="font-size:14px;margin:0;color:rgba(255,255,255,0.8);"><?= nl2br(htmlspecialchars($tracking_result['keterangan'])) ?></p>
            </div>
            <?php endif; ?>

            <?php if(count($tracking_logs) > 0): ?>
            <!-- Timeline Log -->
            <div class="divider"></div>
            <div class="timeline-log">
                <div class="timeline-log-header">
                    <i class="fas fa-history" style="color:#60a5fa;font-size:16px;"></i>
                    <h6>Riwayat Perubahan</h6>
                </div>
                <?php foreach($tracking_logs as $log): ?>
                <div class="log-entry">
                    <div class="log-dot"></div>
                    <div class="log-content">
                        <div class="log-status"><?= htmlspecialchars(strtoupper($log['status'])) ?></div>
                        <?php if(!empty($log['keterangan'])): ?>
                        <div class="log-desc"><?= htmlspecialchars($log['keterangan']) ?></div>
                        <?php endif; ?>
                        <div class="log-time"><i class="fas fa-clock" style="margin-right:4px;"></i> <?= date('d M Y, H:i', strtotime($log['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="result-card" data-aos="fade-up">
            <div class="not-found-box">
                <div class="not-found-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h6 style="color:#f87171;font-size:18px;margin-bottom:8px;">Kode Tidak Ditemukan</h6>
                <p style="color:rgba(255,255,255,0.45);font-size:13px;margin-bottom:0;">Kode tracking <strong style="color:rgba(255,255,255,0.7);"><?= htmlspecialchars($kode_tracking) ?></strong> tidak terdaftar.</p>
                <p style="color:rgba(255,255,255,0.3);font-size:12px;margin-top:10px;margin-bottom:0;">Pastikan kode yang dimasukkan sudah benar.</p>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="text-center mt-4 pb-4" data-aos="fade-up">
        <div class="divider"></div>
        <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
            <div style="width:8px;height:8px;border-radius:50%;background:#10b981;animation:pulse 2s infinite;"></div>
            <small style="color:rgba(255,255,255,0.4);font-size:12px;">Sistem Aktif</small>
        </div>
        <small style="color:rgba(255,255,255,0.25);font-size:11px;">&copy; <?= date('Y') ?> PERKASA — Dinas Ketenagakerjaan Provinsi Sulawesi Utara</small>
    </div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({once:true, duration:800});

// Particle Background
const canvas = document.getElementById('particles-canvas');
const ctx = canvas.getContext('2d');
let particles = [];
function resizeCanvas(){canvas.width=window.innerWidth;canvas.height=window.innerHeight;}
resizeCanvas();
window.addEventListener('resize', resizeCanvas);
class Particle{
    constructor(){this.x=Math.random()*canvas.width;this.y=Math.random()*canvas.height;this.size=Math.random()*2+0.5;this.speedX=(Math.random()-0.5)*0.5;this.speedY=(Math.random()-0.5)*0.5;this.opacity=Math.random()*0.5+0.1;}
    update(){this.x+=this.speedX;this.y+=this.speedY;if(this.x<0||this.x>canvas.width)this.speedX*=-1;if(this.y<0||this.y>canvas.height)this.speedY*=-1;}
    draw(){ctx.beginPath();ctx.arc(this.x,this.y,this.size,0,Math.PI*2);ctx.fillStyle=`rgba(59, 130, 246, ${this.opacity})`;ctx.fill();}
}
for(let i=0;i<50;i++)particles.push(new Particle());
function connectParticles(){for(let i=0;i<particles.length;i++){for(let j=i+1;j<particles.length;j++){const dx=particles[i].x-particles[j].x;const dy=particles[i].y-particles[j].y;const dist=Math.sqrt(dx*dx+dy*dy);if(dist<150){ctx.beginPath();ctx.strokeStyle=`rgba(59, 130, 246, ${0.1*(1-dist/150)})`;ctx.lineWidth=0.5;ctx.moveTo(particles[i].x,particles[i].y);ctx.lineTo(particles[j].x,particles[j].y);ctx.stroke();}}}}
function animateParticles(){ctx.clearRect(0,0,canvas.width,canvas.height);particles.forEach(p=>{p.update();p.draw();});connectParticles();requestAnimationFrame(animateParticles);}
animateParticles();

// Uppercase tracking code
const trackInput = document.querySelector('input[name="kode_tracking"]');
if(trackInput){
    trackInput.addEventListener('input', function(){
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
    });
}

// Form submit animation
document.getElementById('trackForm')?.addEventListener('submit', function(){
    const btn = document.getElementById('trackBtn');
    if(btn){btn.innerHTML='<span class="spinner-border spinner-border-sm"></span> Mencari...';btn.disabled=true;}
});
</script>
</body>
</html>
