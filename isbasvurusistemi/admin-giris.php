<?php

session_start();

include("baglan.php");

$hata = "";

if(isset($_POST['giris'])){

    $kullanici = $_POST['kullanici'];
    $sifre = $_POST['sifre'];

    $sql = "SELECT * FROM ADMIN
            WHERE KullaniciAdi=? AND Sifre=?";

    $params = array($kullanici, $sifre);

    $sonuc = sqlsrv_query($conn, $sql, $params);

    if($row = sqlsrv_fetch_array($sonuc, SQLSRV_FETCH_ASSOC)){

        $_SESSION['admin'] =
        $row['Ad'].' '.$row['Soyad'];

        header("Location: admin-panel.php");

    }else{

        $hata = "Kullanıcı adı veya şifre yanlış";
    }
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>

<meta charset="UTF-8">

<title>Admin Giriş</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="style.css">

</head>

<body class="login-page">

<div class="login-box">

<h2 class="mb-4 text-center">
Admin Giriş
</h2>

<?php
if($hata != ""){
    echo "<div class='alert alert-danger'>$hata</div>";
}
?>

<form method="POST">

<input type="text"
name="kullanici"
class="form-control mb-3"
placeholder="Kullanıcı Adı">

<input type="password"
name="sifre"
class="form-control mb-3"
placeholder="Şifre">

<button type="submit"
name="giris"
class="btn btn-dark w-100">

Giriş Yap

</button>

</form>

</div>

</body>
</html>