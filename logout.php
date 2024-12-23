<?php
session_start(); // Start session
session_unset(); // Smaže všechny session proměnné
session_destroy(); // Zničí session
header("Location: login.php"); // Přesměruje na přihlašovací stránku
exit;
?>