<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h2>Selamat datang, <?php echo $_SESSION['username']; ?>!</h2>
    <a href="logout.php">Logout</a>
    <hr>

    <h3>Pilih Formulir</h3>
    <ul>
        <li><a href="formulirudara.php">Formulir Pengambilan Udara</a></li>
        <li><a href="formulirair.php">Formulir Pengambilan Air</a></li>
    </ul>
</body>
</html>