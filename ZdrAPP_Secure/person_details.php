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
// Připojení k databázi
$conn = new mysqli("localhost", "root", "", "zdrapp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Načtení údajů o osobě
$personId = $_GET['id'];
$surname = $_GET['surname'];

// Načtení osoby
$sql = "SELECT * FROM persons WHERE id = $personId";
$result = $conn->query($sql);
$person = $result->fetch_assoc();

// Pokud osoba neexistuje, přesměrujeme zpět na seznam
if (!$person) {
    header("Location: show_data.php");
    exit;
}

// Načtení záznamů pro tuto osobu
$noteSql = "SELECT * FROM notes WHERE person_id = $personId ORDER BY created_at DESC";
$noteResult = $conn->query($noteSql);

// Přidání nové poznámky
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_note'])) {
    $note = $_POST['new_note'];
    $insertSql = "INSERT INTO notes (person_id, note) VALUES ($personId, '$note')";
    if ($conn->query($insertSql) === TRUE) {
        header("Location: person_details.php?id=$personId&surname=$surname");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}

// Úprava poznámky
if (isset($_POST['edit_note']) && isset($_POST['note_id'])) {
    $noteId = $_POST['note_id'];
    $note = $_POST['edit_note'];
    $updateSql = "UPDATE notes SET note = '$note' WHERE id = $noteId";
    if ($conn->query($updateSql) === TRUE) {
        header("Location: person_details.php?id=$personId&surname=$surname");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}

// Smazání poznámky
if (isset($_GET['delete_note_id'])) {
    $noteId = $_GET['delete_note_id'];
    $deleteSql = "DELETE FROM notes WHERE id = $noteId";
    if ($conn->query($deleteSql) === TRUE) {
        header("Location: person_details.php?id=$personId&surname=$surname");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Person Details</title>
    <link rel="stylesheet" href="style.css">
</head>
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
        <a href="login.php">Login</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<body>
    <div class="container">
        <h1><?php echo $person['first_name'] . ' ' . $person['surname']; ?></h1>
        <h2>Přidat záznam</h2>
        <form action="" method="POST">
            <textarea name="new_note" placeholder="Vepiš zde záznam" required></textarea>
            <button type="submit">Uložit záznam</button>
        </form>

        <h2>Existující záznamy</h2>
        <ul>
            <?php
            if ($noteResult->num_rows > 0) {
                while ($note = $noteResult->fetch_assoc()) {
                    echo "<li>";
                    echo "<p>" . htmlspecialchars($note['note']) . "</p>";
                    echo "<small>Vytvořeno: " . $note['created_at'] . "</small>";
                    echo "<form action='' method='POST' style='display:inline;'>
                            <input type='hidden' name='note_id' value='" . $note['id'] . "'>
                            <input type='text' name='edit_note' value='" . htmlspecialchars($note['note']) . "'>
                            <button type='submit'>Upravit</button>
                          </form>";
                    echo " <a href='?id=$personId&surname=$surname&delete_note_id=" . $note['id'] . "'>Odstranit</a>";
                    echo "</li>";
                }
            } else {
                echo "<li>Žádné existující záznamy</li>";
            }
            ?>
        </ul>
    </div>
</body>
</html>
