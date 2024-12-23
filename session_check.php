<?php
session_start();

// Zkontrolujeme, zda je uživatel přihlášen
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
