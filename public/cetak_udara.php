<?php
include "koneksi.php";

if (!isset($_GET['id'])) die("ID tidak ditemukan");
$id = $_GET['id'];

$formulir = $conn->query("SELECT * FROM formulir_udara WHERE id=$id")->fetch_assoc();
$contoh = $conn->query("SELECT * FROM contoh_udara WHERE formulir_id=$id");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Formulir Udara</title>
<style>
body { font-family: Arial, sans-serif; margin:20px; }
table { width: 100%; border-collapse: collapse; }
th, td { border:1px solid #000; padding:6px; text-align:left; }
</style>
</head>
<body>
    <h2 style="text-align:center;">FORMULIR PENGAMBILAN CONTOH UDARA</h2>
    <p><b>Perusahaan:</b> <?= $formulir['perusahaan'] ?></p>
    <p><b>Alamat:</b> <?= $formulir['alamat'] ?></p>
    <p><b>Tanggal:</b> <?= $formulir['tanggal'] ?></p>

    <h3>Data Contoh udara</h3>
    <table>
        <tr>
            <th>Nama</th><th>Jenis</th><th>Merek</th><th>Kode</th>
            <th>Prosedur</th><th>Parameter</th><th>Baku Mutu</th><th>Catatan</th>
        </tr>
        <?php while($row = $contoh->fetch_assoc()): ?>
        <tr>
            <td><?= $row['nama_contoh'] ?></td>
            <td><?= $row['jenis_contoh'] ?></td>
            <td><?= $row['merek'] ?></td>
            <td><?= $row['kode'] ?></td>
            <td><?= $row['prosedur'] ?></td>
            <td><?= $row['parameter'] ?></td>
            <td><?= $row['baku_mutu'] ?></td>
            <td><?= $row['catatan'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <script>window.print();</script>
</body>
</html>
