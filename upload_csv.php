<?php
session_start(); // Spustí session, aby bylo možné kontrolovat přihlášení

// Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Zobrazení chybové zprávy
    echo "<div style='text-align: center; padding: 50px;'>
            <h2>You must be logged in to access this page.</h2>
            <p><a href='login.php'>Click here to login</a></p>
          </div>";
    exit; // Ukončení skriptu, aby uživatel neměl přístup k dalším částem stránky
}
$conn = new mysqli("localhost", "root", "", "zdrapp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if ($file && is_uploaded_file($file)) {
        $csvData = file_get_contents($file);
        $lines = explode("\n", $csvData);

        // Načítání a vkládání dat z CSV do databáze
        foreach ($lines as $line) {
            $fields = str_getcsv($line);
            if (count($fields) == 4) { // Očekáváme 4 sloupce: Jméno, Příjmení, Datum narození, Rodné číslo
                $firstName = $conn->real_escape_string($fields[0]);
                $surname = $conn->real_escape_string($fields[1]);
                $dob = $conn->real_escape_string($fields[2]);
                $idNumber = $conn->real_escape_string($fields[3]);

                // Vložení dat do tabulky
                $sql = "INSERT INTO persons (first_name, surname, birth_date, ssn) 
                        VALUES ('$firstName', '$surname', '$dob', '$idNumber')";
                $conn->query($sql);
            }
        }

        // Před vložením nových dat uděláme zálohu tabulky
        $backupSql = "CREATE TABLE persons_backup AS SELECT * FROM persons";
        $conn->query($backupSql);

        // Přesměrování na stránku se seznamem osob po úspěšném nahrání CSV
        header("Location: show_data.php");
        exit;
    } else {
        echo "Chyba při nahrávání souboru.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<script>
    function toggleMenu() {
        var navbar = document.getElementById("navbar");
        navbar.classList.toggle("open");
    }
</script>
<div class="header">
    <!-- Logo jako obrázek -->
    <a href="index.php" class="logo">
        <img src="logo.png" alt="MyApp Logo" width="100">
    </a>
    
    <!-- Burger Menu -->
    <div class="menu-icon" onclick="toggleMenu()">&#9776;</div>
    
    <!-- Navigační menu -->
    <div class="navbar" id="navbar">
        <a href="show_data.php">Přehled</a>
        <a href="add_diagnosis.php">Přidat diagnózu</a>
        <a href="upload_csv.php">Nahrát data</a>
        <a href="login.php">Login</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload CSV</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Upload CSV File</h1>
        <form action="upload_csv.php" method="POST" enctype="multipart/form-data">
            <label for="csv_file">Choose CSV file:</label>
            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
            <button type="submit">Upload</button>
        </form>
    </div>
</body>
</html>

