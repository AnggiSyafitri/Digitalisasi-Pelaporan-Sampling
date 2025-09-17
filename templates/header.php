<?php
// templates/header.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistem Pelaporan Sampling'; ?></title>
    <link href="<?php echo BASE_URL; ?>/assets/css/styles.css" rel="stylesheet" />
</head>
<body>

<?php 
// Hanya tampilkan header ini jika pengguna sudah login
if (isset($_SESSION['user_id'])): 
?>
<header class="header-dashboard">
    <div class="header-title">
        <img src="<?php echo BASE_URL; ?>/assets/img/BSPJI.jpg" alt="Logo BSPJI" class="header-logo"> <h1>Sistem Pelaporan Sampling</h1>
    </div>
    <div class="user-info">
        <span>Selamat Datang, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong></span>
        <span style="font-size:1.1em; font-weight:400;">Peran: <?php echo htmlspecialchars($_SESSION['role_name']); ?></span>
        <a href="logout.php">Logout</a>
    </div>
</header>
<?php endif; ?>
