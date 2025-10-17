<?php
// templates/footer.php
?>
<footer class="py-4 bg-light mt-auto">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Hak Cipta &copy; AS - BSPJI Medan <?php echo date('Y'); ?></div>
            <div>
            <div>
                <a href="https://drive.google.com/file/d/1i4lWovs2p2u2m4EEN4Mp36uSPLrfiRWb/view?usp=sharing" target="_blank">Kebijakan Privasi</a>
                &middot;
                <a href="https://drive.google.com/file/d/16TdfIkhhBl2TmSbVzUH_A1Tmr2b9SY4t/view?usp=sharing " target="_blank">Syarat &amp; Ketentuan</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.getElementById('notificationBell');
    const notificationPanel = document.getElementById('notificationPanel');
    const markAllAsReadLink = document.getElementById('markAllAsReadLink');
    const notificationList = document.getElementById('notificationList');
    const notificationBadge = document.getElementById('notificationBadge');

    // Fungsi untuk memanggil backend
    async function markAsRead(mode, notifId = null) {
        try {
            const response = await fetch('<?php echo BASE_URL; ?>/../actions/tandai_dibaca.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mode: mode, notif_id: notifId })
            });
            const result = await response.json();
            return result.success;
        } catch (error) {
            console.error('Error:', error);
            return false;
        }
    }

    // Event listener untuk ikon lonceng
    if (notificationBell) {
        notificationBell.addEventListener('click', function(event) {
            event.stopPropagation();
            notificationPanel.classList.toggle('show');
        });
    }

    // Event listener untuk link "Tandai semua dibaca"
    if (markAllAsReadLink) {
        markAllAsReadLink.addEventListener('click', async function(event) {
            event.preventDefault();
            const success = await markAsRead('semua');
            if (success) {
                // Hapus badge dan class 'unread' dari semua item
                if (notificationBadge) notificationBadge.style.display = 'none';
                notificationList.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
            }
        });
    }

    // Menutup panel jika klik di luar
    window.addEventListener('click', function(event) {
        if (notificationPanel && !notificationPanel.contains(event.target) && !notificationBell.contains(event.target)) {
            notificationPanel.classList.remove('show');
        }
    });
});
</script>

</body>
</html>