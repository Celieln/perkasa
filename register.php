<?php
include 'config.php';

if(isset($_SESSION['login'])){
    header("Location: dashboard.php");
    exit;
}

// Block registrasi saat maintenance
if(is_maintenance($conn)){
    echo "<script>alert('Sistem sedang dalam pemeliharaan. Registrasi ditutup sementara.');window.location='index.php';</script>";
    exit;
}

$prefill_nik = '';
$prefill_username = '';
$prefill_nama = '';
$from_cek = false;

$ip = client_ip();
$reg_state = register_state($conn, $ip);
$reg_blocked = $reg_state['count'] >= 5;

if(isset($_GET['nik'])){
    $nik_get = preg_replace('/\D/', '', trim($_GET['nik']));
    if(preg_match('/^\d{16}$/', $nik_get) && !$reg_blocked){
        $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap FROM peserta WHERE nik = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $nik_get);
        mysqli_stmt_execute($stmt);
        $arg = mysqli_stmt_get_result($stmt);
        $peserta_data = mysqli_fetch_assoc($arg);
        if($peserta_data){
            $stmt2 = mysqli_prepare($conn, "SELECT id FROM users WHERE nik = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt2, 's', $nik_get);
            mysqli_stmt_execute($stmt2);
            if(mysqli_num_rows(mysqli_stmt_get_result($stmt2)) === 0){
                $prefill_nik = $nik_get;
                $prefill_username = strtolower(preg_replace('/\s+/', '', $peserta_data['nama_lengkap']));
                $prefill_nama = $peserta_data['nama_lengkap'];
                $from_cek = true;
            }
        }
    }
}

if(isset($_POST['register'])){
    verify_csrf();

    // Rate limit: maks 5x registrasi per jam per IP
    if($reg_state['count'] >= 5){
        echo "<script>alert('Terlalu banyak percobaan pendaftaran dari IP ini. Silakan coba lagi nanti.');window.history.back();</script>";
        exit;
    }

    // CAPTCHA wajib
    if(!captcha_verify($_POST['captcha_token'] ?? '', $_POST['captcha_answer'] ?? '')){
        echo "<script>alert('Jawaban keamanan salah!');window.location.href='register.php';</script>";
        exit;
    }

    if(strlen($_POST['password']) < 8){
        echo "<script>alert('Password minimal 8 karakter');window.history.back();</script>";
        exit;
    }
    if(!preg_match('/[A-Z]/', $_POST['password']) || !preg_match('/[a-z]/', $_POST['password']) || !preg_match('/[0-9]/', $_POST['password'])){
        echo "<script>alert('Password harus mengandung huruf besar, huruf kecil, dan angka');window.history.back();</script>";
        exit;
    }

    $username       = trim($_POST['username']);
    $nama_lengkap   = trim($_POST['nama_lengkap']);
    $nik            = trim($_POST['nik']);
    $password       = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if(empty($username) || empty($nama_lengkap) || empty($nik) || empty($password) || empty($confirm_password)){
        echo "<script>alert('Semua kolom wajib diisi!');window.history.back();</script>";
        exit;
    }

    if(!preg_match('/^[a-zA-Z0-9_.]{3,20}$/', $username)){
        echo "<script>alert('Username 3-20 karakter, hanya huruf, angka, titik, atau underscore!');window.history.back();</script>";
        exit;
    }

    if(!preg_match('/^\d{16}$/', $nik)){
        echo "<script>alert('NIK harus berupa 16 digit angka!');window.history.back();</script>";
        exit;
    }

    if($password !== $confirm_password){
        echo "<script>alert('Konfirmasi password tidak cocok!');window.history.back();</script>";
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap FROM peserta WHERE nik = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $nik);
    mysqli_stmt_execute($stmt);
    $check_peserta = mysqli_stmt_get_result($stmt);
    if(mysqli_num_rows($check_peserta) === 0){
        echo "<script>alert('NIK tidak terdaftar sebagai peserta PERKASA! Silakan hubungi petugas.');window.history.back();</script>";
        exit;
    }
    $peserta_data = mysqli_fetch_assoc($check_peserta);

    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    if(mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0){
        echo "<script>alert('Username sudah terdaftar! Gunakan username lain.');window.history.back();</script>";
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE nik = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $nik);
    mysqli_stmt_execute($stmt);
    if(mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0){
        echo "<script>alert('NIK sudah terdaftar! Silakan login dengan akun yang sudah ada.');window.history.back();</script>";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, nama_lengkap, nik, role, status)
               VALUES (?, ?, ?, ?, 'users', 'active')");
    mysqli_stmt_bind_param($stmt, 'ssss', $username, $hashed_password, $nama_lengkap, $nik);

    if(mysqli_stmt_execute($stmt)){
        record_register_attempt($conn, $ip);
        echo "<script>alert('Registrasi berhasil! Silakan login dengan username dan NIK Anda.');window.location='index.php';</script>";
        exit;
    } else {
        error_log("Registrasi gagal: " . mysqli_error($conn));
        echo "<script>alert('Gagal registrasi. Silakan coba lagi.');window.history.back();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERKASA — Daftar Akun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{
            background:linear-gradient(135deg,#061220,#081426,#102d63);
            min-height:100vh;color:white;overflow-x:hidden;
            display:flex;align-items:center;padding:40px 0;
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
            padding:44px 40px;
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
        .custom-label{font-size:13px;font-weight:600;color:rgba(255,255,255,0.85);margin-bottom:8px;display:block;}
        .custom-btn{
            height:50px;border-radius:12px;font-weight:700;font-size:15px;letter-spacing:0.5px;
            background:linear-gradient(135deg,#2563eb,#1d4ed8);
            border:none;transition:all 0.3s;
            box-shadow:0 4px 15px rgba(37,99,235,0.4);
        }
        .custom-btn:hover{
            background:linear-gradient(135deg,#1d4ed8,#1e40af);
            transform:translateY(-2px);
            box-shadow:0 8px 20px rgba(37,99,235,0.5);
        }
        .divider{height:1px;background:rgba(255,255,255,0.08);margin:18px 0;}
        .nik-hint{font-size:11px;color:rgba(255,255,255,0.4);margin-top:5px;}
        @media(max-width:768px){
            body{padding:20px 0;}
            .glass-card{padding:28px 20px;}
        }
    </style>
</head>
<body>
<div class="floating-circle circle1"></div>
<div class="floating-circle circle2"></div>

<div class="container">
<div class="row justify-content-center">

<div class="col-lg-7 col-xl-6">

    <div class="text-center mb-4" data-aos="fade-down">
        <h1 class="fw-bold mb-1" style="font-size:28px;letter-spacing:2px;">PERKASA</h1>
        <p style="color:rgba(255,255,255,0.5);font-size:13px;">Buat akun pemantauan kepesertaan JKK/JKM</p>
    </div>

    <!-- FEATURE LINKS -->
    <div class="row g-2 mb-4" data-aos="fade-up" data-aos-delay="50">
        <div class="col-6">
            <a href="cek_peserta.php" class="feature-card">
                <div class="feature-icon" style="background:rgba(52,211,153,0.15);">📋</div>
                <div class="title">Cek Kepesertaan</div>
                <div class="desc">Periksa status data Anda</div>
            </a>
        </div>
        <div class="col-6">
            <a href="tracking.php" class="feature-card">
                <div class="feature-icon" style="background:rgba(96,165,250,0.15);">🔍</div>
                <div class="title">Lacak Pengurusan</div>
                <div class="desc">Pantau progress klaim</div>
            </a>
        </div>
    </div>

    <!-- FORM -->
    <div class="glass-card" data-aos="zoom-in" data-aos-delay="100">
        <div class="text-center mb-4">
            <h4 class="fw-bold mb-1">Daftar Akun Baru</h4>
            <p style="color:rgba(255,255,255,0.45);font-size:13px;">Isi data berikut untuk mulai memantau kepesertaan</p>
        </div>

        <form method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="custom-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control custom-input" placeholder="Masukkan nama lengkap" required>
            </div>

            <div class="mb-3">
                <label class="custom-label">NIK (Nomor Induk Kependudukan)</label>
                <input type="text" name="nik" class="form-control custom-input" placeholder="Masukkan 16 digit NIK" maxlength="16" inputmode="numeric" pattern="\d{16}" value="<?= htmlspecialchars($prefill_nik) ?>" <?= $from_cek ? 'readonly style="opacity:0.7;"' : '' ?> required>
                <?php if($from_cek): ?>
                <div class="nik-hint">✔ NIK terverifikasi atas nama <strong><?= htmlspecialchars($prefill_nama) ?></strong></div>
                <?php else: ?>
                <div class="nik-hint">⚠ NIK harus 16 digit sesuai KTP</div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="custom-label">Username</label>
                <input type="text" name="username" class="form-control custom-input" placeholder="Buat username unik" value="<?= htmlspecialchars($prefill_username) ?>" required>
                <?php if($from_cek): ?>
                <div class="nik-hint">✔ Username terisi otomatis dari nama peserta</div>
                <?php endif; ?>
            </div>

            <div class="divider"></div>

            <div class="mb-3">
                <label class="custom-label">Password</label>
                <input type="password" name="password" class="form-control custom-input" placeholder="Buat password" required>
            </div>

            <div class="mb-4">
                <label class="custom-label">Konfirmasi Password</label>
                <input type="password" name="confirm_password" class="form-control custom-input" placeholder="Ulangi password" required>
            </div>

            <?php if($reg_blocked): ?>
            <div class="alert alert-warning py-2" style="font-size:13px;border-radius:10px;">
                Terlalu banyak percobaan pendaftaran dari IP ini. Silakan coba lagi dalam 1 jam.
            </div>
            <?php else: ?>
            <div class="mb-4">
                <label class="custom-label">Verifikasi Keamanan</label>
                <?= captcha_html() ?>
                <div class="d-flex gap-2 align-items-center mt-2">
                    <?= captcha_question_html() ?>
                    <input type="text"
                           name="captcha_answer"
                           class="form-control custom-input"
                           placeholder="Jawaban"
                           maxlength="2"
                           inputmode="numeric"
                           style="width:120px;"
                           required>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" name="register" class="btn custom-btn w-100 text-white mb-3" <?= $reg_blocked ? 'disabled' : '' ?>>
                DAFTAR SEKARANG
            </button>

            <div class="text-center" style="font-size:13px;color:rgba(255,255,255,0.5);">
                Sudah punya akun?
                <a href="index.php" style="color:#60a5fa;font-weight:600;text-decoration:none;" onmouseover="this.style.color='#93c5fd'" onmouseout="this.style.color='#60a5fa'">
                    Login di sini
                </a>
            </div>
        </form>
    </div>

    <div class="text-center mt-4" style="border-top:1px solid rgba(255,255,255,0.06);padding-top:16px;">
        <small style="color:rgba(255,255,255,0.3);font-size:12px;">&copy; <?= date('Y') ?> PERKASA</small>
    </div>

</div>
</div>
</div>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({once:true});
document.querySelector('input[name="nik"]').addEventListener('input', function(){
    this.value = this.value.replace(/\D/g, '').slice(0, 16);
});
</script>
</body>
</html>
