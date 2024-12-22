<?php
include('session_check.php'); // Ověření přihlášení

if ($_SESSION['role'] !== 'admin') {
    echo "You do not have permission to access this page.";
    exit;
}
?>
<div class="header">
    <!-- Logo jako obrázek -->
    <a href="show_data.php" class="logo">
        <img src="logo.png" alt="MyApp Logo" width="100">
    </a>
    
    <!-- Burger Menu -->
    <div class="menu-icon" onclick="toggleMenu()">&#9776;</div>
    
    <!-- Navigační menu -->
    <div class="navbar" id="navbar">
        <a href="show_data.php">Home</a>
        <a href="upload_csv.php">Upload Data</a>

        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
            <!-- Pokud je uživatel přihlášen, nebudeme zobrazovat tlačítko Login -->
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <!-- Pokud není uživatel přihlášen, nebude zde tlačítko Logout -->
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</div>
<script>
    function toggleMenu() {
        var navbar = document.getElementById("navbar");
        navbar.classList.toggle("open");
    }
</script>
<h1>Welcome to the Admin Dashboard</h1>
