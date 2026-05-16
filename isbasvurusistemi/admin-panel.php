<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: admin-giris.php");
    exit();
}

include("baglan.php");

$toplam = sqlsrv_query($conn, "SELECT COUNT(*) as toplam FROM adaylar");
$toplamSonuc = sqlsrv_fetch_array($toplam, SQLSRV_FETCH_ASSOC);

$onay = sqlsrv_query($conn, "SELECT COUNT(*) as sayi FROM adaylar WHERE durum='Onaylandı'");
$onaySonuc = sqlsrv_fetch_array($onay, SQLSRV_FETCH_ASSOC);

$red = sqlsrv_query($conn, "SELECT COUNT(*) as sayi FROM adaylar WHERE durum='Reddedildi'");
$redSonuc = sqlsrv_fetch_array($red, SQLSRV_FETCH_ASSOC);

$bekle = sqlsrv_query($conn, "SELECT COUNT(*) as sayi FROM adaylar WHERE durum='Beklemede'");
$bekleSonuc = sqlsrv_fetch_array($bekle, SQLSRV_FETCH_ASSOC);





if(isset($_GET['onay'])){

    sqlsrv_query(
        $conn,
        "UPDATE ADAYLAR
        SET Durum='Onaylandı'
        WHERE AdayId=?",
        array($_GET['onay'])
    );
}

if(isset($_GET['reddet'])){

    sqlsrv_query(
        $conn,
        "UPDATE ADAYLAR
        SET Durum='Reddedildi'
        WHERE AdayId=?",
        array($_GET['reddet'])
    );
}

$arama = "";

if(isset($_GET['arama'])){
    $arama = $_GET['arama'];
}

$filtre = "";

if(isset($_GET['durum'])){
    $filtre = $_GET['durum'];
}

$sql = "
SELECT * FROM ADAYLAR
WHERE
(
Ad LIKE ?
OR Soyad LIKE ?
OR Eposta LIKE ?
)
";

$params = array(
    "%$arama%",
    "%$arama%",
    "%$arama%"
);

if($filtre != ""){

    $sql .= " AND Durum=?";
    $params[] = $filtre;
}

$sql .= " ORDER BY AdayId DESC";

$sonuc = sqlsrv_query($conn,$sql,$params);

$toplam = sqlsrv_fetch_array(
sqlsrv_query($conn,
"SELECT COUNT(*) AS sayi FROM ADAYLAR"),
SQLSRV_FETCH_ASSOC);

$onay = sqlsrv_fetch_array(
sqlsrv_query($conn,
"SELECT COUNT(*) AS sayi
FROM ADAYLAR
WHERE Durum='Onaylandı'"),
SQLSRV_FETCH_ASSOC);

$red = sqlsrv_fetch_array(
sqlsrv_query($conn,
"SELECT COUNT(*) AS sayi
FROM ADAYLAR
WHERE Durum='Reddedildi'"),
SQLSRV_FETCH_ASSOC);

$bekle = sqlsrv_fetch_array(
sqlsrv_query($conn,
"SELECT COUNT(*) AS sayi
FROM ADAYLAR
WHERE Durum='Beklemede'"),
SQLSRV_FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="tr">
<head>

<meta charset="UTF-8">

<title>Admin Paneli</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="style.css">

</head>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const ctx = document.getElementById('grafik');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Onaylanan', 'Reddedilen', 'Bekleyen'],
        datasets: [{
            label: 'Başvurular',
            data: [
                <?php echo $onaySonuc['sayi']; ?>,
                <?php echo $redSonuc['sayi']; ?>,
                <?php echo $bekleSonuc['sayi']; ?>
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

</script>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <button onclick="darkMode()" class="btn btn-dark">
    Dark Mode
</button>

<div class="container">

<span class="navbar-brand fw-bold">
Admin Paneli
</span>

<div>

<span class="text-white me-3">
<?php echo $_SESSION['admin']; ?>
</span>

<a href="cikis.php"
class="btn btn-danger">

Çıkış Yap

</a>

</div>

</div>

</nav>

<div class="container mt-5">

    <div class="row text-center mb-4">

        <div class="col-md-3">
            <div class="card shadow p-3">
                <h3><?php echo $toplamSonuc['toplam']; ?></h3>
                <p>Toplam Başvuru</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow p-3 bg-success text-white">
                <h3><?php echo $onaySonuc['sayi']; ?></h3>
                <p>Onaylanan</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow p-3 bg-danger text-white">
                <h3><?php echo $redSonuc['sayi']; ?></h3>
                <p>Reddedilen</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow p-3 bg-warning text-dark">
                <h3><?php echo $bekleSonuc['sayi']; ?></h3>
                <p>Bekleyen</p>
            </div>
        </div>

    </div>

    <div class="card shadow p-4">
        <canvas id="grafik"></canvas>
    </div>

</div>

<div class="container py-5">

<div class="row g-4 mb-5">

<div class="col-md-3">
<div class="card shadow border-0 p-4 text-center">
<h3><?php echo $toplam['sayi']; ?></h3>
<p>Toplam Başvuru</p>
</div>
</div>

<div class="col-md-3">
<div class="card shadow border-0 p-4 text-center">
<h3><?php echo $onay['sayi']; ?></h3>
<p>Onaylandı</p>
</div>
</div>

<div class="col-md-3">
<div class="card shadow border-0 p-4 text-center">
<h3><?php echo $red['sayi']; ?></h3>
<p>Reddedildi</p>
</div>
</div>

<div class="col-md-3">
<div class="card shadow border-0 p-4 text-center">
<h3><?php echo $bekle['sayi']; ?></h3>
<p>Beklemede</p>
</div>
</div>

</div>

<div class="panel-box">

<form method="GET"
class="row g-3 mb-4">

<div class="col-md-6">

<input type="text"
name="arama"
class="form-control"
placeholder="Aday ara">

</div>

<div class="col-md-4">

<select name="durum"
class="form-select">

<option value="">
Tüm Durumlar
</option>

<option value="Beklemede">
Beklemede
</option>

<option value="Onaylandı">
Onaylandı
</option>

<option value="Reddedildi">
Reddedildi
</option>

</select>

</div>

<div class="col-md-2">

<button class="btn btn-primary w-100">
Filtrele
</button>

</div>

</form>

<div class="table-responsive">

<table class="table table-hover align-middle">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Ad Soyad</th>
<th>Eposta</th>
<th>CV</th>
<th>Durum</th>
<th>İşlem</th>

</tr>

</thead>

<tbody>

<?php
while($row = sqlsrv_fetch_array($sonuc,SQLSRV_FETCH_ASSOC)){
?>

<tr>

<td>
<?php echo $row['AdayId']; ?>
</td>

<td>
<?php echo $row['Ad']." ".$row['Soyad']; ?>
</td>

<td>
<?php echo $row['Eposta']; ?>
</td>

<td>

<a href="uploads/<?php echo $row['CV']; ?>"
target="_blank"
class="btn btn-secondary btn-sm">

CV Gör

</a>

</td>

<td>

<?php

if($row['Durum']=="Onaylandı"){

echo "<span class='badge bg-success'>
Onaylandı
</span>";

}elseif($row['Durum']=="Reddedildi"){

echo "<span class='badge bg-danger'>
Reddedildi
</span>";

}else{

echo "<span class='badge bg-warning text-dark'>
Beklemede
</span>";
}

?>

</td>

<td>

<a href="?onay=<?php echo $row['AdayId']; ?>"
class="btn btn-success btn-sm">

Onayla

</a>

<a href="?reddet=<?php echo $row['AdayId']; ?>"
class="btn btn-danger btn-sm">

Reddet

</a>

</td>

</tr>

<?php
}
?>

<script>

function darkMode(){

    document.body.classList.toggle("dark-mode");

    if(document.body.classList.contains("dark-mode")){
        localStorage.setItem("tema", "dark");
    }else{
        localStorage.setItem("tema", "light");
    }
}

if(localStorage.getItem("tema") === "dark"){
    document.body.classList.add("dark-mode");
}

</script>

</tbody>

</table>

</div>

</div>

</div>

</body>
</html>