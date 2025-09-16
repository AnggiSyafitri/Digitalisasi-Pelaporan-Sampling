<?php
session_start();
echo "<h2>Debug Session</h2>";
if (empty($_SESSION)) {
    echo "Session kosong!<br>";
} else {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}
?>
