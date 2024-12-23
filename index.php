<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage - ZDRAPP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <a href="show_data.php" class="logo">
            <img src="logo.png" alt="ZDRAPP Logo" width="50">
        </a>
        <div class="menu-icon" onclick="toggleMenu()">&#9776;</div>
        <div class="navbar" id="navbar">
            <a href="show_data.php">Home</a>
            <a href="upload_csv.php">Upload Data</a>
            <a href="login.php">Login</a>
        </div>
    </div>

    <div class="container">
        <h1>Welcome to ZDRAPP</h1>
        <p align="center">ZDRAPP is your all-in-one application for managing personal data and notes efficiently.</p>
        <div class="features">
            <div class="feature">
                <h2>Manage People</h2>
                <p>View, edit, and delete person records with ease.</p>
            </div>
            <div class="feature">
                <h2>Add Notes</h2>
                <p>Keep track of important information with detailed notes.</p>
            </div>
            <div class="feature">
                <h2>CSV Upload</h2>
                <p>Bulk upload data directly into the system using CSV files.</p>
            </div>
        </div>
        <a href="show_data.php" class="cta-button">Get Started</a>
    </div>

    <script>
        function toggleMenu() {
            var navbar = document.getElementById("navbar");
            navbar.classList.toggle("open");
        }
    </script>
</body>
</html>
