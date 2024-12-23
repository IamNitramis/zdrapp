<?php
session_start(); // Spustí session, aby bylo možné kontrolovat přihlášení

// Kontrola, zda je uživatel přihlášen (např. administrátor)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Zobrazení chybové zprávy
    echo "<div style='text-align: center; padding: 50px;' >
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

// Přidání nové diagnózy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_diagnosis'])) {
    $newDiagnosis = $conn->real_escape_string($_POST['new_diagnosis']);
    $stmt = $conn->prepare("INSERT INTO diagnoses (name) VALUES (?)");
    $stmt->bind_param("s", $newDiagnosis);

    if ($stmt->execute()) {
        header("Location: add_diagnosis.php"); // Přesměrování na tuto stránku, aby se zobrazil nový seznam diagnóz
        exit;
    } else {
        echo "Chyba při přidávání diagnózy: " . $conn->error;
    }
    $stmt->close();
}

// Úprava diagnózy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_diagnosis_id'])) {
    $diagnosisId = $_POST['update_diagnosis_id'];
    $updatedDiagnosis = $conn->real_escape_string($_POST['updated_diagnosis']);
    $stmt = $conn->prepare("UPDATE diagnoses SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $updatedDiagnosis, $diagnosisId);

    if ($stmt->execute()) {
        header("Location: add_diagnosis.php"); // Přesměrování na tuto stránku, aby se zobrazil aktualizovaný seznam diagnóz
        exit;
    } else {
        echo "Chyba při úpravě diagnózy: " . $conn->error;
    }
    $stmt->close();
}

// Smazání diagnózy
if (isset($_GET['delete_diagnosis_id'])) {
    $diagnosisId = $_GET['delete_diagnosis_id'];
    $deleteSql = "DELETE FROM diagnoses WHERE id = $diagnosisId";
    if ($conn->query($deleteSql) === TRUE) {
        header("Location: add_diagnosis.php"); // Přesměrování na tuto stránku, aby se seznam diagnóz aktualizoval
        exit;
    } else {
        echo "Chyba při mazání diagnózy: " . $conn->error;
    }
}

// Načtení seznamu diagnóz
$diagnosesSql = "SELECT * FROM diagnoses ORDER BY name ASC";
$diagnosesResult = $conn->query($diagnosesSql);
$diagnoses = [];
while ($row = $diagnosesResult->fetch_assoc()) {
    $diagnoses[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přidat novou diagnózu</title>
    <link rel="stylesheet" href="style.css">
</head>
<div class="header">
    <a href="show_data.php" class="logo">
        <img src="logo.png" alt="MyApp Logo" width="100">
    </a>
    <div class="menu-icon" onclick="toggleMenu()">&#9776;</div>
    <div class="navbar" id="navbar">
        <a href="show_data.php">Přehled</a>
        <a href="upload_csv.php">Nahrát data</a>
        <a href="add_diagnosis.php">Přidat diagnózu</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<body>
    <div class="container">
        <h1>Přidat novou diagnózu</h1>

        <!-- Formulář pro přidání nové diagnózy -->
        <form action="add_diagnosis.php" method="POST">
            <label for="new_diagnosis">Název diagnózy:</label>
            <input type="text" name="new_diagnosis" id="new_diagnosis" placeholder="Zadejte název nové diagnózy" required>
            <button type="submit">Přidat diagnózu</button>
        </form>

        <h2>Existující diagnózy</h2>
        <ul>
            <?php foreach ($diagnoses as $diagnosis): ?>
                <li>
                    <?php echo htmlspecialchars($diagnosis['name']); ?>

                    <!-- Formulář pro úpravu diagnózy -->
                    <form action="add_diagnosis.php" method="POST" style="display:inline;">
                        <input type="hidden" name="update_diagnosis_id" value="<?php echo $diagnosis['id']; ?>">
                        <input type="text" name="updated_diagnosis" value="<?php echo htmlspecialchars($diagnosis['name']); ?>" required>
                        <button type="submit">Upravit</button>
                    </form>

                    <!-- Smazání diagnózy -->
                    <a href="add_diagnosis.php?delete_diagnosis_id=<?php echo $diagnosis['id']; ?>" onclick="return confirm('Opravu chcete opravdu smazat?')">Odstranit</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <p><a href="show_data.php">Zpět na přehled osob</a></p>
    </div>
</body>
</html>
