<?php

include '../config.php';



if(!isset($_SESSION['login'])){
    header("Location: ../index.php");
    exit;
}

deny_if_readonly('Anda tidak memiliki izin untuk mengubah lokasi peserta');

if(!isset($_GET['id'])){
    header("Location: lokasi.php");
    exit;
}

$id = (int)$_GET['id'];

$data = mysqli_fetch_assoc(
mysqli_query($conn,
"SELECT * FROM peserta
WHERE id = $id")
);

if(!$data){
    header("Location: lokasi.php");
    exit;
}

if(isset($_POST['update'])){
    verify_csrf();

    $latitude = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conn, $_POST['longitude']);
    $alamat_lokasi = mysqli_real_escape_string($conn, $_POST['alamat_lokasi']);

    mysqli_query($conn,

    "UPDATE peserta SET

    latitude='$latitude',
    longitude='$longitude',
    alamat_lokasi='$alamat_lokasi'

    WHERE id=$id");

    header("Location: lokasi.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Update Lokasi</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link rel="stylesheet"
href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<link rel="stylesheet"
href="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.css"/>

<link rel="stylesheet"
href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css"/>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

body{

background:
linear-gradient(
135deg,
#061220,
#0a1830,
#102d63
);

min-height:100vh;

color:white;

overflow-x:hidden;

}

.page-wrapper{
padding:40px 0;
}

/* GLASS */

.glass-box{

background:
rgba(255,255,255,0.08);

backdrop-filter:blur(18px);

border-radius:28px;

border:
1px solid rgba(255,255,255,0.08);

padding:30px;

box-shadow:
0 10px 40px rgba(0,0,0,0.3);

}

/* MAP */

.map-wrapper{

position:relative;

width:100%;

height:650px;

border-radius:28px;

overflow:hidden;

border:
1px solid rgba(255,255,255,0.08);

margin-bottom:30px;

}

#map{

width:100%;
height:100%;

z-index:1;

}

.leaflet-container{

width:100%;
height:100%;

touch-action:pan-x pan-y;

}

.leaflet-control{
z-index:1000 !important;
}

/* MAP OVERLAY */

.map-overlay-card{

position:absolute;

bottom:20px;

left:20px;

z-index:999;

background:
rgba(15,23,42,0.88);

backdrop-filter:blur(15px);

padding:18px;

border-radius:20px;

width:280px;

border:
1px solid rgba(255,255,255,0.08);

box-shadow:
0 10px 30px rgba(0,0,0,0.25);

}

.overlay-title{

font-size:15px;

font-weight:bold;

margin-bottom:15px;

}

.overlay-item{

display:flex;

justify-content:space-between;

margin-bottom:10px;

font-size:14px;

}

.overlay-item b{

color:#60a5fa;

}

/* TOOLBAR */

.map-toolbar{

position:absolute;

top:20px;

right:20px;

z-index:999;

display:flex;

flex-direction:column;

gap:12px;

}

.map-btn{

width:58px;
height:58px;

border:none;

border-radius:18px;

background:
rgba(15,23,42,0.9);

backdrop-filter:blur(10px);

color:white;

font-size:22px;

transition:0.3s;

box-shadow:
0 10px 30px rgba(0,0,0,0.25);

}

.map-btn:hover{

transform:translateY(-3px);

background:#2563eb;

}

/* FORM */

.form-control{

background:
rgba(255,255,255,0.08);

border:none;

color:white;

height:58px;

border-radius:16px;

padding:0 18px;

}

.form-control:focus{

background:
rgba(255,255,255,0.12);

color:white;

box-shadow:none;

}

.form-control::placeholder{
color:rgba(255,255,255,0.5);
}

textarea.form-control{

height:130px;

padding-top:16px;

resize:none;

}

/* BUTTON */

.modern-btn{

border:none;

border-radius:16px;

padding:12px 22px;

transition:0.3s;

font-weight:600;

}

.modern-btn:hover{

transform:translateY(-2px);

}

/* INFO CARD */

.info-card{

background:
rgba(255,255,255,0.05);

border-radius:22px;

padding:22px;

border:
1px solid rgba(255,255,255,0.08);

margin-bottom:30px;

}

/* MARKER PULSE */

@keyframes pulse{

0%{
box-shadow:
0 0 0 0 rgba(37,99,235,0.7);
}

70%{
box-shadow:
0 0 0 18px rgba(37,99,235,0);
}

100%{
box-shadow:
0 0 0 0 rgba(37,99,235,0);
}

}

/* MOBILE */

@media(max-width:768px){

.map-wrapper{
height:420px;
}

.map-overlay-card{

width:220px;

padding:14px;

}

.glass-box{
padding:20px;
}

}

</style>

</head>

<body>

<div class="container page-wrapper">

<div class="glass-box">

<!-- HEADER -->

<div class="d-flex
justify-content-between
align-items-center
flex-wrap
gap-3
mb-4">

<div>

<h2 class="fw-bold mb-2">

Update Lokasi Peserta

</h2>

<p class="opacity-75 mb-0">

Klik maps untuk menentukan lokasi rumah peserta

</p>

</div>

<div class="d-flex gap-2 flex-wrap">

<a href="lokasi.php"
class="btn btn-secondary modern-btn">

Kembali

</a>

<button
type="button"
onclick="getLocation()"
class="btn btn-success modern-btn">

Lokasi Saya

</button>

</div>

</div>

<!-- INFO -->

<div class="info-card">

<div class="row g-4">

<div class="col-lg-4">

<div class="small opacity-75 mb-1">
Nama Peserta
</div>

<div class="fw-bold fs-5">

<?= h($data['nama_lengkap']) ?>

</div>

</div>

<div class="col-lg-4">

<div class="small opacity-75 mb-1">
Alamat
</div>

<div>

<?= h($data['alamat']) ?>

</div>

</div>

<div class="col-lg-4">

<div class="small opacity-75 mb-1">
Program
</div>

<div class="fw-bold">

<?= h($data['tipe_program']) ?>

</div>

</div>

</div>

</div>

<form method="POST">

<?= csrf_field() ?>

<!-- MAP -->

<div class="map-wrapper">

<div id="map"></div>

<!-- TOOLBAR -->

<div class="map-toolbar">

<button
type="button"
onclick="getLocation()"
class="map-btn">

📍

</button>

<button
type="button"
onclick="focusMarker()"
class="map-btn">

🎯

</button>

<button
type="button"
onclick="toggleSatellite()"
class="map-btn">

🛰️

</button>

</div>

<!-- OVERLAY -->

<div class="map-overlay-card">

<div class="overlay-title">

Informasi Lokasi

</div>

<div class="overlay-item">

<span>Latitude</span>

<b id="lat-preview">

-

</b>

</div>

<div class="overlay-item">

<span>Longitude</span>

<b id="lng-preview">

-

</b>

</div>

</div>

</div>

<!-- FORM -->

<div class="row g-4">

<div class="col-lg-6">

<label class="mb-2">
Latitude
</label>

<input
type="text"
name="latitude"
id="latitude"
class="form-control"
readonly
required>

</div>

<div class="col-lg-6">

<label class="mb-2">
Longitude
</label>

<input
type="text"
name="longitude"
id="longitude"
class="form-control"
readonly
required>

</div>

</div>

<div class="mt-4">

<label class="mb-2">
Alamat Detail
</label>

<textarea
name="alamat_lokasi"
class="form-control"
placeholder="Masukan detail lokasi rumah peserta"><?= h($data['alamat_lokasi']) ?></textarea>

</div>

<div class="mt-4">

<button
type="submit"
name="update"
class="btn btn-primary modern-btn">

Simpan Lokasi

</button>

</div>

</form>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script src="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.js"></script>

<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>

const defaultLat =
<?= (float)($data['latitude'] ?? '1.4748') ?>;

const defaultLng =
<?= (float)($data['longitude'] ?? '124.8421') ?>;

/* MAP */

const map = L.map(
'map',
{
fullscreenControl:true
}
).setView(
[defaultLat,defaultLng],
16
);

/* LAYERS */

const street =
L.tileLayer(
'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
{
attribution:'© OpenStreetMap'
}
);

const satellite =
L.tileLayer(
'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
{
attribution:'© Esri'
}
);

street.addTo(map);

/* CUSTOM ICON */

const customIcon = L.divIcon({

html:`
<div style="
width:22px;
height:22px;
background:#2563eb;
border-radius:50%;
box-shadow:0 0 0 rgba(37,99,235,0.5);
animation:pulse 2s infinite;
border:3px solid white;
"></div>
`,

className:'',

iconSize:[22,22],

iconAnchor:[11,11]

});

/* MARKER */

const marker = L.marker(

[defaultLat,defaultLng],

{
draggable:true,
icon:customIcon
}

).addTo(map);

/* CIRCLE */

const circle = L.circle(

[defaultLat,defaultLng],

{
radius:40,
color:'#2563eb',
fillColor:'#2563eb',
fillOpacity:0.2
}

).addTo(map);

/* UPDATE INPUT */

function updateInputs(lat,lng){

document.getElementById(
'latitude'
).value =
lat.toFixed(7);

document.getElementById(
'longitude'
).value =
lng.toFixed(7);

document.getElementById(
'lat-preview'
).innerText =
lat.toFixed(7);

document.getElementById(
'lng-preview'
).innerText =
lng.toFixed(7);

}

/* INIT */

updateInputs(
defaultLat,
defaultLng
);

/* DRAG */

marker.on(
'drag',
function(){

const pos =
marker.getLatLng();

circle.setLatLng(pos);

updateInputs(
pos.lat,
pos.lng
);

}
);

/* CLICK MAP */

map.on(
'click',
function(e){

marker.setLatLng(e.latlng);

circle.setLatLng(e.latlng);

updateInputs(
e.latlng.lat,
e.latlng.lng
);

marker.bindPopup(
'Lokasi dipilih'
).openPopup();

}
);

/* SEARCH */

L.Control.geocoder({
defaultMarkGeocode:false
})
.on(
'markgeocode',
function(e){

const center =
e.geocode.center;

map.flyTo(center,18);

marker.setLatLng(center);

circle.setLatLng(center);

updateInputs(
center.lat,
center.lng
);

}
)
.addTo(map);

/* GPS */

function getLocation(){

navigator.geolocation.getCurrentPosition(

function(position){

const lat =
position.coords.latitude;

const lng =
position.coords.longitude;

marker.setLatLng([lat,lng]);

circle.setLatLng([lat,lng]);

map.flyTo([lat,lng],18);

updateInputs(lat,lng);

},

function(){

alert(
'GPS gagal diakses'
);

}

);

}

/* FOCUS */

function focusMarker(){

const pos =
marker.getLatLng();

map.flyTo(
[pos.lat,pos.lng],
18
);

}

/* SATELLITE */

let satelliteActive = false;

function toggleSatellite(){

if(!satelliteActive){

map.removeLayer(street);

satellite.addTo(map);

satelliteActive = true;

}else{

map.removeLayer(satellite);

street.addTo(map);

satelliteActive = false;

}

}

/* FIX */

setTimeout(() => {
map.invalidateSize();
},300);

</script>

</body>
</html>