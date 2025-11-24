<?php
session_start();

// Si ya está autenticado, ir al dashboard
if(isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit();
?>