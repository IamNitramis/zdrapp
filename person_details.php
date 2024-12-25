<?php
session_start(); // Spustí session, aby bylo možné kontrolovat přihlášení

// Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<div style='text-align: center; padding: 50px;'>
            <h2>You must be logged in to access this page.</h2>
            <p><a href='login.php'>Click here to login</a></p>
          </div>";
    exit;
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

// Načtení seznamu diagnóz
$diagnosesSql = "SELECT * FROM diagnoses ORDER BY name ASC";
$diagnosesResult = $conn->query($diagnosesSql);
$diagnoses = [];
while ($row = $diagnosesResult->fetch_assoc()) {
    $diagnoses[] = $row;
}

// Přidání diagnózy k osobě
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['diagnosis_id'])) {
    $diagnosisId = $_POST['diagnosis_id'];
    $stmt = $conn->prepare("INSERT INTO person_diagnoses (person_id, diagnosis_id, assigned_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $personId, $diagnosisId);
    if ($stmt->execute()) {
        header("Location: person_details.php?id=$personId&surname=$surname");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
    $stmt->close();
}

// Načtení diagnóz přiřazených k osobě
$sql = "SELECT pd.id AS record_id, d.id, d.name, pd.assigned_at 
        FROM person_diagnoses pd 
        JOIN diagnoses d ON pd.diagnosis_id = d.id 
        WHERE pd.person_id = $personId";
$personDiagnosesResult = $conn->query($sql);
$personDiagnoses = [];
while ($row = $personDiagnosesResult->fetch_assoc()) {
    $personDiagnoses[] = $row;
}

// Zpracování změny času přiřazení diagnózy
if (isset($_POST['update_assigned_at']) && isset($_POST['new_assigned_at']) && isset($_POST['diagnosis_id'])) {
    $newAssignedAt = $_POST['new_assigned_at'];
    $diagnosisId = $_POST['diagnosis_id'];
    $newAssignedAt = date('Y-m-d H:i:s', strtotime($newAssignedAt));

    $updateSql = "UPDATE person_diagnoses SET assigned_at = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $newAssignedAt, $diagnosisId);

    if ($stmt->execute()) {
        header("Location: person_details.php?id=$personId&surname=$surname");
        exit;
    } else {
        echo "Chyba při aktualizaci: " . $conn->error;
    }
    $stmt->close();
}

// Smazání diagnózy
if (isset($_GET['delete_diagnosis_id'])) {
    $recordId = $_GET['delete_diagnosis_id'];
    $deleteSql = "DELETE FROM person_diagnoses WHERE id = $recordId";
    if ($conn->query($deleteSql) === TRUE) {
        header("Location: person_details.php?id=$personId&surname=$surname");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}

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

// Zpracování úprav poznámek
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_note']) && isset($_POST['note_id'])) {
    $updatedNote = $_POST['updated_note'];
    $noteId = $_POST['note_id'];

    $updateSql = "UPDATE notes SET note = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $updatedNote, $noteId);

    if ($stmt->execute()) {
        header("Location: person_details.php?id=$personId&surname=$surname");
        exit;
    } else {
        echo "Chyba při aktualizaci poznámky: " . $conn->error;
    }
    $stmt->close();
}

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
        <h1><?php echo $person['first_name'] . ' ' . $person['surname']; ?></h1>

        <h2>Přidat záznam</h2>
        <form action="" method="POST">
            <textarea name="new_note" placeholder="Vepiš zde záznam" required></textarea>
            <button type="submit">Uložit záznam</button>
        </form>

        <h2>Existující záznamy</h2>
<div style="display: flex; flex-direction: column; gap: 1rem;">
    <?php if ($noteResult->num_rows > 0): ?>
        <?php while ($note = $noteResult->fetch_assoc()): ?>
            <div style="padding: 1rem; border: 1px solid #ccc; border-radius: 5px;" id="note-<?php echo $note['id']; ?>">
                <p style="margin: 0 0 0.5rem; font-size: 1rem;" id="note-text-<?php echo $note['id']; ?>">
                    <?php echo htmlspecialchars($note['note']); ?>
                </p>
                <small style="display: block; margin-bottom: 0.5rem; color: #666;">Vytvořeno: <?php echo htmlspecialchars($note['created_at']); ?></small>
                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <button 
                        type="button" 
                        onclick="showEditForm(<?php echo $note['id']; ?>)" 
                        style="background-color: #007bff; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 3px; cursor: pointer;">
                        Upravit
                    </button>
                    <a href="?id=<?php echo htmlspecialchars($personId); ?>&surname=<?php echo htmlspecialchars($surname); ?>&delete_note_id=<?php echo htmlspecialchars($note['id']); ?>"
                       style="color: #dc3545; text-decoration: none; font-weight: bold;">Odstranit</a>
                </div>
                <form action="" method="POST" id="edit-form-<?php echo $note['id']; ?>" style="display: none; margin-top: 1rem;">
                    <input type="hidden" name="note_id" value="<?php echo htmlspecialchars($note['id']); ?>">
                    <input type="text" name="updated_note" value="<?php echo htmlspecialchars($note['note']); ?>" 
                           style="padding: 0.5rem; border: 1px solid #ccc; border-radius: 3px; width: 100%;">
                    <button type="submit" name="update_note" 
                            style="background-color: #007bff; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 3px; cursor: pointer; margin-top: 0.5rem;">
                        Uložit
                    </button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="font-style: italic; color: #666;">Žádné existující záznamy</div>
    <?php endif; ?>
</div>

<script>
    function showEditForm(noteId) {
        // Skryje text a zobrazí formulář pro úpravu
        const noteText = document.getElementById('note-text-' + noteId);
        const editForm = document.getElementById('edit-form-' + noteId);
        
        if (noteText && editForm) {
            noteText.style.display = 'none';
            editForm.style.display = 'block';
        }
    }
</script>


        <h2>Přidat diagnózu</h2>
        <form action="" method="POST">
            <label for="diagnosis">Vyber diagnózu:</label>
            <select name="diagnosis_id" id="diagnosis" required>
                <?php foreach ($diagnoses as $diagnosis): ?>
                    <option value="<?php echo $diagnosis['id']; ?>">
                        <?php echo htmlspecialchars($diagnosis['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Přiřadit diagnózu</button>
        </form>

        <h2>Přiřazené diagnózy</h2>
<div style="display: flex; flex-direction: column; gap: 1rem;">
    <?php if (count($personDiagnoses) > 0): ?>
        <?php foreach ($personDiagnoses as $diagnosis): ?>
            <div style="padding: 0.5rem; border: 1px solid #ccc; border-radius: 5px;">
                <strong><?php echo htmlspecialchars($diagnosis['name']); ?></strong>
                <div style="margin-top: 0.5rem;">
                    <small>Datum: <?php echo htmlspecialchars($diagnosis['assigned_at']); ?></small>
                </div>
                <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <form action="" method="POST" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="hidden" name="diagnosis_id" value="<?php echo htmlspecialchars($diagnosis['record_id']); ?>">
                        <label for="new_assigned_at" style="font-size: 0.9rem;">Nový datum a čas:</label>
                        <input type="datetime-local" name="new_assigned_at" value="<?php echo date('Y-m-d\TH:i', strtotime($diagnosis['assigned_at'])); ?>" required>
                        <button type="submit" name="update_assigned_at" style="background-color: #007bff; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 3px; cursor: pointer;">Upravit</button>
                    </form>
                    <form action="" method="GET" style="display: flex; align-items: center;">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($personId); ?>">
                        <input type="hidden" name="surname" value="<?php echo htmlspecialchars($surname); ?>">
                        <input type="hidden" name="delete_diagnosis_id" value="<?php echo htmlspecialchars($diagnosis['record_id']); ?>">
                        <button type="submit" style="background-color: #dc3545; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 3px; cursor: pointer;">Odstranit</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="font-style: italic;">Žádné diagnózy nebyly přiřazeny.</div>
    <?php endif; ?>
</div>

        <br>
        <a href="add_diagnosis.php">Přidat diagnózu do seznamu</a>
    </div>
</body>
</html>
