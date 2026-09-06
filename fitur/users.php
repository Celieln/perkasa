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

/* =========================
   CREATE USER
========================= */

if(isset($_POST['create'])){
   verify_csrf();
   // Server-side permission: only admin or superadmin may create users
   if(!is_admin()){
      echo "\n            <script>\n            alert('Anda tidak memiliki izin untuk membuat user');\n            window.location='users.php';\n            </script>\n            ";
      exit;
   }

    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama     = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $nik      = mysqli_real_escape_string($conn, trim($_POST['nik'] ?? ''));
    $role     = $_POST['role'];

    $currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    // Prevent non-superadmin from creating a superadmin
    if($role === 'superadmin' && $currentRole !== 'superadmin'){
        echo "<script>alert('Hanya Superadmin yang dapat membuat Superadmin');window.location='users.php';</script>";
        exit;
    }

    // Cek username duplikat
    $checkUser = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
    if(mysqli_num_rows($checkUser) > 0){
        echo "<script>alert('Username sudah terdaftar!');window.history.back();</script>";
        exit;
    }

    // Cek NIK duplikat (jika NIK diisi)
    if(!empty($nik)){
        $checkNik = mysqli_query($conn, "SELECT id FROM users WHERE nik='$nik'");
        if(mysqli_num_rows($checkNik) > 0){
            echo "<script>alert('NIK sudah terdaftar!');window.history.back();</script>";
            exit;
        }
    }

    mysqli_query($conn,
    "INSERT INTO users(username, password, nama_lengkap, nik, role)
     VALUES('$username', '$password', '$nama', '$nik', '$role')");

    header("Location: users.php");
    exit;

}

/* =========================
   DELETE USER
========================= */

if(isset($_GET['delete'])){

   $id = (int)$_GET['delete'];

   // Check target role and current user's permissions
   $targetRes = mysqli_query($conn, "SELECT role FROM users WHERE id=$id");
   $target = mysqli_fetch_assoc($targetRes);
   $targetRole = $target ? $target['role'] : '';
   $currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

   $allowed = false;

   if($currentRole === 'superadmin'){
      $allowed = true;
   }elseif($currentRole === 'admin'){
      if($targetRole === 'users' || $targetRole === 'petugas'){
         $allowed = true;
      }
   }

   if(!$allowed){
      echo "\n        <script>\n        alert('Anda tidak memiliki izin untuk menghapus user ini');\n        window.location='users.php';\n        </script>\n        ";
      exit;
   }

   mysqli_query($conn,
   "DELETE FROM users
   WHERE id=$id");

   header("Location: users.php");
   exit;

}

/* =========================
   SET ROLE
========================= */

if(isset($_GET['setrole']) && isset($_GET['role'])){

   $id = (int)$_GET['setrole'];
   $newRole = $_GET['role'];
   $allowedRoles = ['superadmin','admin','users','petugas'];
   if(!in_array($newRole, $allowedRoles)){
       echo "<script>alert('Role tidak valid');window.location='users.php';</script>";
       exit;
   }
   $currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

   // Prevent changing own role
   if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id){
      echo "<script>alert('Tidak dapat mengubah role sendiri');window.location='users.php';</script>";
      exit;
   }

   // Only superadmin can create or assign superadmin
   if($newRole === 'superadmin' && $currentRole !== 'superadmin'){
      echo "<script>alert('Hanya Superadmin yang dapat menetapkan Superadmin');window.location='users.php';</script>";
      exit;
   }

   // Admins cannot change roles of admins/superadmins
   $targetRes = mysqli_query($conn, "SELECT role FROM users WHERE id=$id");
   $target = mysqli_fetch_assoc($targetRes);
   $targetRole = $target ? $target['role'] : '';

   if($currentRole === 'admin'){
      if($targetRole === 'admin' || $targetRole === 'superadmin'){
         echo "<script>alert('Admin tidak berwenang mengubah role ini');window.location='users.php';</script>";
         exit;
      }
      if($newRole !== 'users'){
         echo "<script>alert('Admin hanya dapat mengatur role menjadi Users');window.location='users.php';</script>";
         exit;
      }
   }

   $newRole = mysqli_real_escape_string($conn, $newRole);
   mysqli_query($conn, "UPDATE users SET role='$newRole' WHERE id=$id");
   header('Location: users.php');
   exit;

}

/* =========================
   TOGGLE SUSPEND
========================= */

if(isset($_GET['toggle_suspend'])){
   $id = (int)$_GET['toggle_suspend'];
   $currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

   if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id){
      echo "<script>alert('Tidak dapat menggantungkan akun sendiri');window.location='users.php';</script>";
      exit;
   }

   $targetRes = mysqli_query($conn, "SELECT status, role FROM users WHERE id=$id");
   $target = mysqli_fetch_assoc($targetRes);
   $targetStatus = $target ? $target['status'] : 'active';
   $targetRole = $target ? $target['role'] : '';

   // Admins can only suspend regular users
   if($currentRole === 'admin'){
      if($targetRole !== 'users' && $targetRole !== 'petugas'){
         echo "<script>alert('Admin hanya dapat suspend Users');window.location='users.php';</script>";
         exit;
      }
   }

   $newStatus = ($targetStatus === 'suspended') ? 'active' : 'suspended';
   mysqli_query($conn, "UPDATE users SET status='$newStatus' WHERE id=$id");
   header('Location: users.php');
   exit;

}

/* =========================
   CHANGE PASSWORD (POST)
========================= */

if(isset($_POST['change_password']) && isset($_POST['user_id']) && isset($_POST['new_password'])){
    verify_csrf();
   $id = (int)$_POST['user_id'];
   $new = $_POST['new_password'];
   $currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

   // permission checks: admin can only change users' passwords
   $targetRes = mysqli_query($conn, "SELECT role FROM users WHERE id='$id'");
   $target = mysqli_fetch_assoc($targetRes);
   $targetRole = $target ? $target['role'] : '';

   if($currentRole === 'admin'){
      if($targetRole !== 'users' && $targetRole !== 'petugas'){
         echo "<script>alert('Admin tidak berwenang mengubah password user ini');window.location='users.php';</script>";
         exit;
      }
   }

   // Hash and update
   $hashed = password_hash($new, PASSWORD_DEFAULT);
   mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$id");
   echo "<script>alert('Password diperbarui');window.location='users.php';</script>";
   exit;

}

/* =========================
   QUERY
========================= */

$query = mysqli_query($conn,
"SELECT * FROM users
ORDER BY id DESC");

/* =========================
   STATS
========================= */

$totalAdmin =
mysqli_num_rows(
mysqli_query($conn,
"SELECT * FROM users
WHERE role='admin'"
)
);

$totalUsers =
mysqli_num_rows(
mysqli_query($conn,
"SELECT * FROM users
WHERE role='users' OR role='petugas'"
)
);

$totalSuperadmin =
mysqli_num_rows(
mysqli_query($conn,
"SELECT * FROM users
WHERE role='superadmin'"
)
);

?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Management Users</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link href="https://unpkg.com/aos@2.3.4/dist/aos.css"
rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:
'Segoe UI',
sans-serif;
}

body{

background:
linear-gradient(
135deg,
#061220,
#081426,
#102d63
);

min-height:100vh;

overflow-x:hidden;

color:white;

}

/* NAVBAR */

.custom-navbar{

background:
rgba(255,255,255,0.05);

backdrop-filter:blur(20px);

border-bottom:
1px solid rgba(255,255,255,0.08);

padding:15px 0;

position:sticky;

top:0;

z-index:9999;

}

.navbar-brand{

font-size:26px;

font-weight:700;

color:white !important;

}

/* GLASS */

.glass-box{

background:
rgba(255,255,255,0.07);

backdrop-filter:blur(16px);

border-radius:24px;

border:
1px solid rgba(255,255,255,0.08);

padding:28px;

box-shadow:
0 10px 40px
rgba(0,0,0,0.25);

}

/* STATS */

.stats-card{

background:
rgba(255,255,255,0.05);

border:
1px solid rgba(255,255,255,0.08);

border-radius:22px;

padding:25px;

transition:0.3s;

height:100%;

position:relative;

overflow:hidden;

}

.stats-card::before{

content:'';

position:absolute;

top:0;
left:0;

width:100%;
height:4px;

background:
linear-gradient(
90deg,
#2563eb,
#0ea5e9
);

}

.stats-card:hover{

transform:
translateY(-5px);

background:
rgba(255,255,255,0.08);

}

.stats-title{

font-size:14px;

opacity:0.7;

margin-bottom:12px;

}

.stats-value{

font-size:38px;

font-weight:bold;

}

/* SEARCH */

.search-box{

background:
rgba(255,255,255,0.08);

border:
1px solid rgba(255,255,255,0.08);

color:white !important;

height:52px;

border-radius:14px;

padding:0 18px;

width:260px;

}

.search-box::placeholder{

color:
rgba(255,255,255,0.5);

}

.search-box:focus{

outline:none;

box-shadow:
0 0 0 4px
rgba(59,130,246,0.15);

background:
rgba(255,255,255,0.12);

border:
1px solid #3b82f6;

}

/* TABLE */

.table-dark{
--bs-table-bg: transparent;
}

.table{

color:white;

margin-bottom:0;

}

.table thead th{

border-bottom:
1px solid rgba(255,255,255,0.08);

padding:18px;

font-size:13px;

text-transform:uppercase;

letter-spacing:1px;

opacity:0.8;

}

.table tbody td{

padding:18px;

vertical-align:middle;

border-color:
rgba(255,255,255,0.05);

}

.table tbody tr{

transition:0.3s;

}

.table tbody tr:hover{

background:
rgba(255,255,255,0.04);

transform:scale(1.002);

}

/* BADGE */

.role-badge{

padding:8px 15px;

border-radius:999px;

font-size:12px;

font-weight:700;

display:inline-block;

}

.admin{

background:
rgba(37,99,235,0.15);

color:#60a5fa;

border:
1px solid rgba(37,99,235,0.3);

}

.petugas{

background:
rgba(34,197,94,0.15);

color:#4ade80;

border:
1px solid rgba(34,197,94,0.3);

}

.superadmin{

background: rgba(124,58,237,0.12);

color:#c4b5fd;

border:1px solid rgba(124,58,237,0.28);

}

.users{

background: rgba(100,116,139,0.08);

color:#cbd5e1;

border:1px solid rgba(203,213,225,0.06);

}

/* BUTTON */

.modern-btn{

border:none;

border-radius:14px;

padding:11px 18px;

font-weight:600;

transition:0.3s;

}

.modern-btn:hover{

transform:
translateY(-2px);

}

/* MODAL */

.modal-content{

background:
rgba(8,20,38,0.95);

backdrop-filter:blur(18px);

border:
1px solid rgba(255,255,255,0.08);

border-radius:24px;

}

.form-control,
.form-select{

background:
rgba(255,255,255,0.08);

border:
1px solid rgba(255,255,255,0.08);

color:white !important;

height:55px;

border-radius:16px;

padding-left:16px;

}

.form-control:focus,
.form-select:focus{

background:
rgba(255,255,255,0.12);

border:
1px solid #3b82f6;

box-shadow:
0 0 0 4px
rgba(59,130,246,0.15);

}

.form-select option{

background:#0f172a;
color:white;

}

/* MOBILE */

@media(max-width:768px){

.table{
min-width:900px;
}

.stats-value{
font-size:30px;
}

.glass-box{
padding:20px;
}

.search-box{
width:100%;
}

}

</style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar">

<div class="container">

<a class="navbar-brand"
href="../dashboard.php">

PERKASA

</a>

<div class="d-flex gap-2 flex-wrap">

<a href="../dashboard.php"
class="btn btn-primary modern-btn">

Dashboard

</a>

<a href="profile.php"
class="btn btn-success modern-btn">

Peserta

</a>

<a href="laporan.php"
class="btn btn-info modern-btn">

Laporan

</a>

<a href="../auth/logout.php"
class="btn btn-danger modern-btn">

Logout

</a>

</div>

</div>

</nav>

<!-- CONTENT -->

<div class="container py-5">

<!-- HEADER -->

<div class="glass-box mb-5">

<div class="d-flex
justify-content-between
align-items-center
flex-wrap
gap-4">

<div>

<h2 class="fw-bold mb-2">

Management Users

</h2>

<p class="opacity-75 mb-0">

Kelola akun admin dan petugas PERKASA

</p>

</div>

<div class="d-flex gap-2 flex-wrap">

<input
type="text"
id="searchInput"
class="search-box"
placeholder="Cari user...">

<?php if(is_admin()) : ?>

<button
class="btn btn-primary modern-btn"
data-bs-toggle="modal"
data-bs-target="#createUser">

Tambah User

</button>

<?php endif; ?>

</div>

</div>

</div>

<!-- STATS -->

<div class="row g-4 mb-5">

<div class="col-lg-4">

<div class="stats-card">

<div class="stats-title">
Total Users
</div>

<div class="stats-value text-primary">

<?= $totalUsers ?>

</div>

</div>

</div>

<div class="col-lg-4">

<div class="stats-card">

<div class="stats-title">
Admin
</div>

<div class="stats-value text-info">

<?= $totalAdmin ?>

</div>

</div>

</div>

<div class="col-lg-4">

<div class="stats-card">

<div class="stats-title">
Users
</div>

<div class="stats-value text-success">

<?= $totalUsers ?>

</div>

</div>

</div>

</div>

<!-- TABLE -->

<div class="glass-box">

<div class="table-responsive">

<table
class="table table-dark table-hover align-middle"
id="usersTable">

<thead>

<tr>

<th>Username</th>
<th>Nama Lengkap</th>
<th>NIK</th>
<th>Role</th>
<th>Status</th>
<th>Dibuat</th>
<th>Aksi</th>

</tr>

</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($query)) : ?>

<tr>

<td>

<div class="fw-bold">

<?= h($row['username']) ?>

</div>

</td>

<td><?= h($row['nama_lengkap']) ?></td>

<td>
<span style="font-family:monospace; font-size:13px; letter-spacing:1px; color:rgba(255,255,255,0.75);">
<?= !empty($row['nik']) ? $row['nik'] : '<span style="opacity:0.35;">—</span>' ?>
</span>
</td>

<td>

<?php if($row['role'] == 'superadmin') : ?>

<span class="role-badge superadmin">

Superadmin

</span>

<?php elseif($row['role'] == 'admin') : ?>

<span class="role-badge admin">

Admin

</span>

<?php else : ?>

<span class="role-badge users">

Users

</span>

<?php endif; ?>

</td>


<td>

<?= isset($row['status']) ? $row['status'] : 'active' ?>

</td>

<td>

<?= h($row['created_at']) ?>

</td>

<td>

<?php $currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>

<?php if($currentRole === 'superadmin') : ?>

<div class="btn-group" role="group">

<a class="btn btn-sm btn-outline-primary" href="?setrole=<?= h($row['id']) ?>&role=superadmin" onclick="return confirm('Set role jadi Superadmin?')">Superadmin</a>

<a class="btn btn-sm btn-outline-info" href="?setrole=<?= h($row['id']) ?>&role=admin" onclick="return confirm('Set role jadi Admin?')">Admin</a>

<a class="btn btn-sm btn-outline-secondary" href="?setrole=<?= h($row['id']) ?>&role=users" onclick="return confirm('Set role jadi Users?')">Users</a>

</div>

<?php elseif($currentRole === 'admin') : ?>

<!-- Admin may only manage users -->
<?php if($row['role'] === 'users' || $row['role'] === 'petugas') : ?>
<a class="btn btn-sm btn-outline-secondary" href="?setrole=<?= h($row['id']) ?>&role=users" onclick="return confirm('Set role jadi Users?')">Set Users</a>
<?php endif; ?>

<?php endif; ?>

<!-- Suspend/Unsuspend -->
<a class="btn btn-sm btn-warning" href="?toggle_suspend=<?= h($row['id']) ?>">
<?php if(isset($row['status']) && $row['status'] === 'suspended') : ?>Unsuspend<?php else: ?>Suspend<?php endif; ?>
</a>

<!-- Change password form -->
<form method="POST" style="display:inline-block;margin-left:6px">
   <?= csrf_field() ?>
   <input type="hidden" name="user_id" value="<?= h($row['id']) ?>">
   <input type="password" name="new_password" placeholder="New password" required class="form-control form-control-sm" style="display:inline-block;width:150px">
   <button type="submit" name="change_password" class="btn btn-sm btn-success">Ubah PW</button>
</form>

<!-- Delete -->
<a href="?delete=<?= h($row['id']) ?>" onclick="return confirm('Delete user ini?')" class="btn btn-danger btn-sm modern-btn" style="margin-left:6px">Delete</a>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

</div>

</div>

<!-- MODAL -->

<div class="modal fade"
id="createUser">

<div class="modal-dialog">

<div class="modal-content">

<div class="modal-header border-0">

<h5 class="fw-bold">

Tambah User

</h5>

<button
type="button"
class="btn-close btn-close-white"
data-bs-dismiss="modal">
</button>

</div>

<form method="POST">

<?= csrf_field() ?>

<div class="modal-body">

<div class="mb-3">
<label class="form-label small fw-semibold opacity-75 mb-1">Nama Lengkap <span class="text-danger">*</span></label>
<input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap" required>
</div>

<div class="mb-3">
<label class="form-label small fw-semibold opacity-75 mb-1">NIK
<span class="ms-1" style="font-size:11px;font-weight:400;opacity:0.5;">(opsional untuk Admin/Superadmin)</span>
</label>
<input type="text" name="nik" class="form-control" placeholder="16 digit NIK" maxlength="16" inputmode="numeric" id="modalNikInput">
<div class="form-text opacity-50" style="font-size:11px;">⚠ Wajib diisi jika role adalah Users</div>
</div>

<div class="mb-3">
<label class="form-label small fw-semibold opacity-75 mb-1">Username <span class="text-danger">*</span></label>
<input type="text" name="username" class="form-control" placeholder="Masukkan username unik" required>
</div>

<div class="mb-3">
<label class="form-label small fw-semibold opacity-75 mb-1">Password <span class="text-danger">*</span></label>
<input type="password" name="password" class="form-control" placeholder="Buat password" required>
</div>

<div class="mb-3">
<label class="form-label small fw-semibold opacity-75 mb-1">Role <span class="text-danger">*</span></label>
<select name="role" class="form-select" id="modalRoleSelect">
<?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') : ?>
<option value="superadmin">Superadmin</option>
<?php endif; ?>
<option value="admin">Admin</option>
<option value="users" selected>Users</option>
</select>
</div>

</div>

<div class="modal-footer border-0">

<button
type="submit"
name="create"
class="btn btn-primary modern-btn w-100">

Simpan User

</button>

</div>

</form>

</div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>

AOS.init();

/* SEARCH */
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('keyup', function(){
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
});

/* NIK — auto remove non-digit & limit 16 */
const modalNikInput = document.getElementById('modalNikInput');
if(modalNikInput){
    modalNikInput.addEventListener('input', function(){
        this.value = this.value.replace(/\D/g, '').slice(0, 16);
    });
}

/* NIK required jika role = users */
const modalRoleSelect = document.getElementById('modalRoleSelect');
if(modalRoleSelect && modalNikInput){
    function toggleNikRequired(){
        const isUsers = modalRoleSelect.value === 'users';
        modalNikInput.required = isUsers;
        modalNikInput.closest('.mb-3').querySelector('.form-text').style.color =
            isUsers ? '#fbbf24' : 'rgba(255,255,255,0.4)';
    }
    modalRoleSelect.addEventListener('change', toggleNikRequired);
    toggleNikRequired(); // init
}

</script>

</body>
</html>