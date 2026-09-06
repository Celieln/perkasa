<?php
include 'config.php';

$result = null;
$nik_cek = '';
$user_exists = false;

if(isset($_POST['cek'])){
    verify_csrf();
    $nik_cek = preg_replace('/\D/', '', trim($_POST['nik'] ?? ''));
    if(strlen($nik_cek) === 16){
        $q = mysqli_query($conn, "SELECT * FROM peserta WHERE nik='$nik_cek' LIMIT 1");
        $result = mysqli_fetch_assoc($q);
        if($result){
            $check_user = mysqli_query($conn, "SELECT id FROM users WHERE nik='$nik_cek' LIMIT 1");
            $user_exists = mysqli_num_rows($check_user) > 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PERKASA — Cek Kepersertaan</title>
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
.input-counter{position:absolute;right:16px;top:50%;transform:translateY(-50%);font-size:12px;color:rgba(255,255,255,0.3);background:rgba(255,255,255,0.06);padding:4px 10px;border-radius:8px;}
.custom-btn{
    height:56px;border-radius:14px;font-weight:700;font-size:16px;
    background:linear-gradient(135deg,#3b82f6,#2563eb);border:none;transition:all 0.3s;
    box-shadow:0 4px 20px rgba(59,130,246,0.4);display:flex;align-items:center;justify-content:center;gap:10px;
    position:relative;overflow:hidden;
}
.custom-btn::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);transition:0.5s;}
.custom-btn:hover::before{left:100%;}
.custom-btn:hover{transform:translateY(-3px);box-shadow:0 8px 30px rgba(59,130,246,0.5);}
.result-card{
    background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);
    border-radius:20px;padding:28px;margin-top:20px;
}
.result-success-header{display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid rgba(255,255,255,0.06);}
.result-success-icon{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,rgba(52,211,153,0.2),rgba(16,185,129,0.1));border:2px solid rgba(52,211,153,0.4);display:flex;align-items:center;justify-content:center;color:#34d399;flex-shrink:0;font-size:24px;}
.result-info-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;}
.result-info-box{background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05);border-radius:14px;padding:16px;}
.result-info-box .label{font-size:11px;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;}
.result-info-box .value{font-size:15px;font-weight:600;color:rgba(255,255,255,0.9);}
.result-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.08),transparent);margin:20px 0;}
.btn-success-custom{
    height:52px;border-radius:14px;font-weight:700;font-size:15px;
    background:linear-gradient(135deg,#10b981,#059669);border:none;transition:all 0.3s;
    box-shadow:0 4px 20px rgba(16,185,129,0.4);display:flex;align-items:center;justify-content:center;gap:10px;
    position:relative;overflow:hidden;
}
.btn-success-custom::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);transition:0.5s;}
.btn-success-custom:hover::before{left:100%;}
.btn-success-custom:hover{transform:translateY(-3px);box-shadow:0 8px 30px rgba(16,185,129,0.5);}
.not-found-box{text-align:center;padding:40px 20px;}
.not-found-icon{width:72px;height:72px;border-radius:50%;background:rgba(239,68,68,0.08);border:2px dashed rgba(239,68,68,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;color:#f87171;font-size:28px;}
.divider{height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.08),transparent);margin:20px 0;}
::-webkit-scrollbar{width:8px;}
::-webkit-scrollbar-track{background:rgba(255,255,255,0.02);}
::-webkit-scrollbar-thumb{background:linear-gradient(180deg,#3b82f6,#2563eb);border-radius:10px;}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-20px);}}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.5;}}
@media(max-width:768px){
    .glass-card{padding:28px 18px;}
    .result-info-grid{grid-template-columns:1fr;}
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
            <div style="width:52px;height:52px;border-radius:16px;background:linear-gradient(135deg,#10b981,#34d399);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 30px rgba(16,185,129,0.4);">
                <i class="fas fa-clipboard-check text-white" style="font-size:24px;"></i>
            </div>
            <div class="text-start">
                <h1 class="fw-bold mb-0" style="font-size:28px;letter-spacing:3px;background:linear-gradient(135deg,#fff,#94a3b8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">PERKASA</h1>
                <p style="color:rgba(255,255,255,0.4);font-size:13px;letter-spacing:1px;">Cek Data Kepersertaan JKK/JKM</p>
            </div>
        </div>
    </div>

    <!-- Feature Links -->
    <div class="row g-3 mb-4" data-aos="fade-up" data-aos-delay="50">
        <div class="col-6">
            <a href="tracking.php" class="feature-card">
                <div class="icon-wrapper" style="background:linear-gradient(135deg,rgba(96,165,250,0.2),rgba(59,130,246,0.1));">
                    <i class="fas fa-route" style="color:#60a5fa;font-size:22px;"></i>
                </div>
                <div class="title">Lacak Pengurusan</div>
                <div class="desc">Pantau progress klaim</div>
                <div class="arrow"><i class="fas fa-arrow-right" style="color:#60a5fa;"></i></div>
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
            <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,rgba(16,185,129,0.2),rgba(52,211,153,0.1));border:2px solid rgba(16,185,129,0.25);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-search" style="color:#34d399;font-size:28px;"></i>
            </div>
            <h4 class="fw-bold mb-1" style="font-size:22px;">Cek Kepersertaan</h4>
            <p style="color:rgba(255,255,255,0.4);font-size:13px;">Masukkan NIK untuk mengecek status kepesertaan</p>
        </div>

        <form method="POST" id="cekForm">
            <?= csrf_field() ?>
            <div class="mb-4">
                <label class="form-label" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.7);display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-id-card" style="color:#34d399;"></i> NIK (Nomor Induk Kependudukan)
                </label>
                <div class="input-group-custom">
                    <input type="text" name="nik" id="nikInput" class="form-control custom-input" placeholder="Masukkan 16 digit NIK" maxlength="16" inputmode="numeric" value="<?= htmlspecialchars($nik_cek) ?>" required style="padding-left:48px;padding-right:70px;">
                    <i class="fas fa-id-card input-icon"></i>
                    <span class="input-counter" id="nikCounter">0/16</span>
                </div>
            </div>
            <button type="submit" name="cek" class="btn custom-btn w-100 text-white mb-2" id="cekBtn">
                <i class="fas fa-search"></i> CEK DATA
            </button>
        </form>

        <?php if(isset($_POST['cek'])): ?>
        <?php if($result): ?>
        <div class="result-card" data-aos="fade-up">
            <div class="result-success-header">
                <div class="result-success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="font-size:18px;">Data Ditemukan</h5>
                    <small style="color:rgba(255,255,255,0.5);font-size:12px;">Status kepesertaan Anda aktif</small>
                </div>
            </div>

            <div class="result-info-grid">
                <div class="result-info-box">
                    <div class="label"><i class="fas fa-user" style="margin-right:6px;"></i> Nama Lengkap</div>
                    <div class="value"><?= htmlspecialchars($result['nama_lengkap']) ?></div>
                </div>
                <div class="result-info-box">
                    <div class="label"><i class="fas fa-tag" style="margin-right:6px;"></i> Program</div>
                    <div class="value" style="color:#60a5fa;"><?= htmlspecialchars($result['tipe_program'] ?? '-') ?></div>
                </div>
                <div class="result-info-box">
                    <div class="label"><i class="fas fa-clipboard-list" style="margin-right:6px;"></i> Status Rekomendasi</div>
                    <div class="value"><?= rekom_badge($result['status_rekom'] ?? 'belum') ?></div>
                </div>
                <div class="result-info-box">
                    <div class="label"><i class="fas fa-check-circle" style="margin-right:6px;"></i> Status Klaim</div>
                    <div class="value"><?= klaim_badge($result['status_klaim'] ?? 'belum') ?></div>
                </div>
            </div>

            <div class="result-divider"></div>

            <?php if($user_exists): ?>
            <div class="d-flex align-items-center gap-3 p-3" style="background:rgba(52,211,153,0.08);border:1px solid rgba(52,211,153,0.2);border-radius:14px;">
                <div style="width:44px;height:44px;border-radius:50%;background:rgba(52,211,153,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#34d399;font-size:20px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:600;color:#34d399;">Akun Tersedia</div>
                    <div style="font-size:12px;color:rgba(255,255,255,0.5);">Anda sudah memiliki akun. Silakan <a href="index.php" style="color:#60a5fa;text-decoration:none;font-weight:600;">login</a> untuk memantau data ini.</div>
                </div>
            </div>
            <?php else: ?>
            <div class="p-3" style="background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.15);border-radius:14px;">
                <div style="font-size:13px;color:rgba(255,255,255,0.7);margin-bottom:14px;">
                    <i class="fas fa-info-circle" style="color:#60a5fa;margin-right:6px;"></i>
                    Data ini belum memiliki akun pemantauan. Buat akun untuk memantau progress klaim.
                </div>
                <a href="register.php?nik=<?= urlencode($nik_cek) ?>" class="btn btn-success-custom w-100 text-white">
                    <i class="fas fa-user-plus"></i> Buat Akun Pemantauan
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="result-card" data-aos="fade-up">
            <div class="not-found-box">
                <div class="not-found-icon">
                    <i class="fas fa-times"></i>
                </div>
                <h6 style="color:#f87171;font-size:18px;margin-bottom:8px;">Data Tidak Ditemukan</h6>
                <p style="color:rgba(255,255,255,0.45);font-size:13px;margin-bottom:0;">NIK <strong style="color:rgba(255,255,255,0.7);"><?= htmlspecialchars($nik_cek) ?></strong> tidak terdaftar dalam sistem.</p>
                <p style="color:rgba(255,255,255,0.3);font-size:12px;margin-top:10px;margin-bottom:0;">Pastikan NIK yang dimasukkan sudah benar (16 digit).</p>
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
    draw(){ctx.beginPath();ctx.arc(this.x,this.y,this.size,0,Math.PI*2);ctx.fillStyle=`rgba(16, 185, 129, ${this.opacity})`;ctx.fill();}
}
for(let i=0;i<50;i++)particles.push(new Particle());
function connectParticles(){for(let i=0;i<particles.length;i++){for(let j=i+1;j<particles.length;j++){const dx=particles[i].x-particles[j].x;const dy=particles[i].y-particles[j].y;const dist=Math.sqrt(dx*dx+dy*dy);if(dist<150){ctx.beginPath();ctx.strokeStyle=`rgba(16, 185, 129, ${0.1*(1-dist/150)})`;ctx.lineWidth=0.5;ctx.moveTo(particles[i].x,particles[i].y);ctx.lineTo(particles[j].x,particles[j].y);ctx.stroke();}}}}
function animateParticles(){ctx.clearRect(0,0,canvas.width,canvas.height);particles.forEach(p=>{p.update();p.draw();});connectParticles();requestAnimationFrame(animateParticles);}
animateParticles();

// NIK Input with counter
const nikInput = document.getElementById('nikInput');
const nikCounter = document.getElementById('nikCounter');
if(nikInput && nikCounter){
    nikInput.addEventListener('input', function(){
        this.value = this.value.replace(/\D/g, '').slice(0, 16);
        nikCounter.textContent = this.value.length + '/16';
    });
    nikCounter.textContent = nikInput.value.length + '/16';
}

// Form submit animation
document.getElementById('cekForm')?.addEventListener('submit', function(){
    const btn = document.getElementById('cekBtn');
    if(btn){btn.innerHTML='<span class="spinner-border spinner-border-sm"></span> Mencari...';btn.disabled=true;}
});
</script>
</body>
</html>
