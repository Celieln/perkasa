<?php
include 'config.php';

if(!isset($_SESSION['login'])){
    header("Location: index.php");
}
?>

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar">

<div class="container-fluid">

<a class="navbar-brand fw-bold" href="#">
PERKASA
</a>

<button class="navbar-toggler"
type="button"
data-bs-toggle="collapse"
data-bs-target="#navbarNav">

<span class="navbar-toggler-icon"></span>

</button>

<div class="collapse navbar-collapse"
id="navbarNav">

<ul class="navbar-nav ms-auto">

<li class="nav-item">
<a class="nav-link" href="dashboard.php">
Dashboard
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="fitur/profile.php">
Profile
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="fitur/claims.php">
Claims
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="fitur/lokasi.php">
Lokasi
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="fitur/users.php">
Users
</a>
</li>

<li class="nav-item">
<a class="nav-link text-danger"
href="auth/logout.php">

Logout

</a>
</li>

</ul>

</div>

</div>

</nav>