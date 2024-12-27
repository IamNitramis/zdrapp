<?php
session_start();

// Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<div class='alert'>
            <h2>You must be logged in to access this page.</h2>
            <p><a href='login.php' class='link-button'>Click here to login</a></p>
          </div>";
    exit;
}

// Připojení k databázi
$conn = new mysqli("localhost", "root", "", "zdrapp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Získání ID poznámky
if (!isset($_GET['id'])) {
    die("Note ID is required.");
}

$noteId = intval($_GET['id']);

// Načtení existující poznámky
$sql = "SELECT * FROM diagnosis_notes WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $noteId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Note not found.");
}

$note = $result->fetch_assoc();

// Zpracování formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedNote = $_POST['updated_note'];

    $updateSql = "UPDATE diagnosis_notes SET note = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $updatedNote, $noteId);

    if ($updateStmt->execute()) {
        header("Location: person_details.php?id=" . htmlspecialchars($_GET['person_id']) . "&surname=" . htmlspecialchars($_GET['surname']));
        exit;
    } else {
        echo "Error updating note: " . $conn->error;
    }

    $updateStmt->close();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Note</title>
    <link rel="stylesheet" href="style.css">
    <style>
        textarea {
            width: 300px;
            height: 400px;
            resize: none;
        }       
    </style>
</head>
<body>
    <div class="container">
        <h1>Upravit poznámku pro <?php echo htmlspecialchars($_GET['first_name']); echo " "; ?><?php echo htmlspecialchars($_GET['surname']); ?></h1>
        <p>
            TODO: Přidat Diagnózu a její datum vytvoření
        </p>
        <form action="" method="POST">
            <textarea name="updated_note" required><?php echo htmlspecialchars($note['note']); ?></textarea>
            <br>
            <button type="submit">Save Changes</button>
            <a href="person_details.php?id=<?php echo htmlspecialchars($_GET['person_id']); ?>&surname=<?php echo htmlspecialchars($_GET['surname']); ?>">Cancel</a>
        </form>
    </div>
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
</body>
</html>
