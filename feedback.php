<?php
include 'config.php';

if(isset($_POST['submit_feedback'])){
    verify_csrf();
    $nama       = mysqli_real_escape_string($conn, trim($_POST['nama'] ?? ''));
    $email      = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $pesan      = mysqli_real_escape_string($conn, trim($_POST['pesan'] ?? ''));
    $rating     = (int)($_POST['rating'] ?? 0);

    if($rating < 1 || $rating > 5){
        echo "<script>alert('Rating harus antara 1-5');history.back();</script>";exit;
    }

    $insert = "INSERT INTO feedback (nama, email, pesan, rating) VALUES ('$nama', '$email', '$pesan', '$rating')";
    if(mysqli_query($conn, $insert)){
        echo "<script>alert('Terima kasih atas feedback Anda!');window.location='feedback.php';</script>";exit;
    } else {
        echo "<script>alert('Gagal mengirim feedback. Silakan coba lagi.');history.back();</script>";exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PERKASA — Feedback</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{
    background:linear-gradient(135deg,#061220,#081426,#102d63);
    min-height:100vh;color:white;overflow-x:hidden;
    padding:40px 0;
}
.floating-circle{
    position:fixed;width:400px;height:400px;
    background:rgba(37,99,235,0.08);border-radius:50%;
    filter:blur(80px);z-index:-1;
    animation:floatAnim 10s infinite alternate;
}
.circle1{top:-120px;left:-100px;}
.circle2{bottom:-120px;right:-80px;}
@keyframes floatAnim{from{transform:translateY(0)}to{transform:translateY(40px)}}
.glass-card{
    background:rgba(255,255,255,0.07);
    backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:24px;
    box-shadow:0 25px 50px rgba(0,0,0,0.4);
    padding:36px 32px;
}
.feature-card{
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:18px;
    padding:20px 18px;
    text-decoration:none;
    color:white;
    transition:all 0.3s ease;
    display:block;
    height:100%;
}
.feature-card:hover{
    transform:translateY(-4px);
    background:rgba(255,255,255,0.09);
    border-color:rgba(59,130,246,0.4);
    color:white;
}
.feature-icon{
    width:44px;height:44px;
    border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    font-size:20px;margin-bottom:10px;
}
.feature-card .title{font-size:14px;font-weight:700;}
.feature-card .desc{font-size:12px;opacity:0.6;}
.custom-input{
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.1);
    color:white;border-radius:12px;
    height:50px;padding:0 16px;transition:all 0.3s;
}
.custom-input::placeholder{color:rgba(255,255,255,0.35);}
.custom-input:focus{
    background:rgba(255,255,255,0.13)!important;
    border-color:#3b82f6!important;
    box-shadow:0 0 0 3px rgba(59,130,246,0.25)!important;
    color:white!important;
}
textarea.custom-input{height:120px;padding:14px 16px;resize:vertical;}
.custom-btn{
    height:50px;border-radius:12px;font-weight:700;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    border:none;transition:0.3s;
    box-shadow:0 4px 15px rgba(37,99,235,0.4);
}
.custom-btn:hover{
    background:linear-gradient(135deg,#1d4ed8,#1e40af);
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(37,99,235,0.5);
}
.star-rating{
    display:flex;gap:8px;flex-direction:row-reverse;justify-content:flex-end;
}
.star-rating input{display:none;}
.star-rating label{
    font-size:32px;cursor:pointer;color:rgba(255,255,255,0.2);
    transition:0.2s;
}
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label{color:#fbbf24;}
@media(max-width:768px){
    body{padding:20px 0;}
    .glass-card{padding:24px 18px;}
}
</style>
</head>
<body>
<div class="floating-circle circle1"></div>
<div class="floating-circle circle2"></div>

<div class="container">
<div class="row justify-content-center">

<div class="col-lg-6 col-md-8">

    <div class="text-center mb-4" data-aos="fade-down">
        <h1 class="fw-bold mb-1" style="font-size:28px;letter-spacing:2px;">PERKASA</h1>
        <p style="color:rgba(255,255,255,0.5);font-size:13px;">Kirim Feedback & Saran</p>
    </div>

    <div class="row g-2 mb-4" data-aos="fade-up" data-aos-delay="50">
        <div class="col-6">
            <a href="index.php" class="feature-card">
                <div class="feature-icon" style="background:rgba(255,255,255,0.08);">🔐</div>
                <div class="title">Login</div>
                <div class="desc">Masuk ke akun Anda</div>
            </a>
        </div>
        <div class="col-6">
            <a href="register.php" class="feature-card">
                <div class="feature-icon" style="background:rgba(168,85,247,0.15);">📝</div>
                <div class="title">Daftar Akun</div>
                <div class="desc">Buat akun baru</div>
            </a>
        </div>
    </div>

    <div class="glass-card" data-aos="zoom-in" data-aos-delay="100">
        <div class="text-center mb-4">
            <h4 class="fw-bold mb-1">Feedback & Saran</h4>
            <p style="color:rgba(255,255,255,0.45);font-size:13px;">Bantu kami meningkatkan kualitas layanan</p>
        </div>

        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.85);">Nama</label>
                <input type="text" name="nama" class="form-control custom-input" placeholder="Nama Anda" required>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.85);">Email</label>
                <input type="email" name="email" class="form-control custom-input" placeholder="Email Anda" required>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.85);">Rating</label>
                <div class="star-rating">
                    <input type="radio" name="rating" value="5" id="s5"><label for="s5">★</label>
                    <input type="radio" name="rating" value="4" id="s4"><label for="s4">★</label>
                    <input type="radio" name="rating" value="3" id="s3"><label for="s3">★</label>
                    <input type="radio" name="rating" value="2" id="s2"><label for="s2">★</label>
                    <input type="radio" name="rating" value="1" id="s1"><label for="s1">★</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.85);">Pesan</label>
                <textarea name="pesan" class="form-control custom-input" placeholder="Tulis pesan, saran, atau kritik Anda..." required></textarea>
            </div>
            <button type="submit" name="submit_feedback" class="btn custom-btn w-100 text-white">
                KIRIM FEEDBACK
            </button>
        </form>
    </div>

    <div class="text-center mt-4" style="border-top:1px solid rgba(255,255,255,0.06);padding-top:16px;">
        <small style="color:rgba(255,255,255,0.3);font-size:12px;">&copy; <?= date('Y') ?> PERKASA</small>
    </div>

</div>
</div>
</div>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({once:true});</script>
</body>
</html>
