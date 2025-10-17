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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<?php 
// Hanya jalankan pengecekan ini jika pengguna sudah login
if (isset($_SESSION['user_id'])) {
    if (!cekWaktuAkses($_SESSION['role_id'])) {
        $_SESSION['flash_error'] = "Sesi Anda telah berakhir karena berada di luar jam kerja yang diizinkan.";
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }

    $current_user_id = $_SESSION['user_id'];
    $sql_notif = "SELECT id, pesan, laporan_id, sudah_dibaca, created_at FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt_notif = $conn->prepare($sql_notif);
    $stmt_notif->bind_param("i", $current_user_id);
    $stmt_notif->execute();
    $result_notif = $stmt_notif->get_result();
    
    $notifikasi = [];
    $unread_count = 0;
    while ($row = $result_notif->fetch_assoc()) {
        $notifikasi[] = $row;
        if ($row['sudah_dibaca'] == 0) {
            $unread_count++;
        }
    }
    $stmt_notif->close();
}
?>

<?php 
// Hanya tampilkan header ini jika pengguna sudah login
if (isset($_SESSION['user_id'])): 
?>

<header class="header-dashboard">
    <div class="header-title">
        <img src="<?php echo BASE_URL; ?>/assets/img/BSPJI.jpg" alt="Logo BSPJI" class="header-logo"> 
        <h1>Sistem Pelaporan Sampling</h1>
    </div>
    <div class="d-flex align-items-center"> 
        
        <div class="notification-wrapper">
            <i class="fa-solid fa-bell notification-bell" id="notificationBell"></i>
            <?php if ($unread_count > 0): ?>
                <span class="notification-badge" id="notificationBadge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
            
            <div class="notification-panel" id="notificationPanel">
                <div class="notification-header">
                    <h5>Notifikasi</h5>
                    <a href="#" id="markAllAsReadLink">Tandai semua dibaca</a>
                </div>
                <ul class="notification-list" id="notificationList">
                    <?php if (empty($notifikasi)): ?>
                        <div class="notification-empty">Tidak ada notifikasi baru.</div>
                    <?php else: ?>
                        <?php foreach ($notifikasi as $notif): 
                            $is_unread = $notif['sudah_dibaca'] == 0 ? 'unread' : '';
                        ?>
                        <li class="notification-item <?php echo $is_unread; ?>" data-notif-id="<?php echo $notif['id']; ?>">
                            <a href="<?php echo BASE_URL . '/detail_laporan.php?id=' . $notif['laporan_id'] . '&notif_id=' . $notif['id']; ?>">
                                <p class="message"><?php echo htmlspecialchars($notif['pesan']); ?></p>
                                <div class="timestamp">
                                    <?php 
                                        $date = new DateTime($notif['created_at']);
                                        echo $date->format('d M Y, H:i');
                                    ?>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="user-info">
            <span>Selamat Datang, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong></span>
            <span style="font-size:1.1em; font-weight:400;">Jabatan: <?php echo htmlspecialchars($_SESSION['role_name']); ?></span>
            <div>
                <a href="profil.php">Profil Saya</a> | 
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</header>
<?php endif; ?>