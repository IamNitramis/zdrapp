<?php
require_once __DIR__ . '/includes/auth.php';

// Připojení k databázi
require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Získání ID poznámky
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Valid Note ID is required.");
}

$noteId = intval($_GET['id']);

// Načtení existující poznámky a diagnózy (JOIN mezi diagnosis_notes a diagnoses)
$sql = "
    SELECT dn.note, d.id AS diagnosis_id, d.name AS diagnosis_name, dn.created_at, dn.person_id 
    FROM diagnosis_notes dn
    JOIN diagnoses d ON dn.diagnosis_id = d.id
    WHERE dn.id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $noteId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Note not found.");
}

$note = $result->fetch_assoc();

// Zpracování formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['updated_note']) || empty(trim($_POST['updated_note']))) {
        echo "<p class='alert-error'>Poznámka nemůže být prázdná.</p>";
    } else {
        $updatedNote = trim($_POST['updated_note']);

        $updateSql = "UPDATE diagnosis_notes SET note = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            die("SQL Error: " . $conn->error);
        }

        $updateStmt->bind_param("si", $updatedNote, $noteId);

        if ($updateStmt->execute()) {
            // Uložení bodů na těle, pokud byly přidány (přes body_points_json)
            if (!empty($_POST['body_points_json'])) {
                $body_points = json_decode($_POST['body_points_json'], true);
                if (is_array($body_points)) {
                    // Smaž staré body pro tuto poznámku
                    $conn->query("DELETE FROM person_body_points WHERE diagnosis_note_id = " . intval($noteId));
                    $sql_point = "INSERT INTO person_body_points (person_id, diagnosis_note_id, x, y, description, `order`) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_point = $conn->prepare($sql_point);
                    foreach ($body_points as $idx => $point) {
                        $desc = isset($point['description']) ? $point['description'] : '';
                        $order = $idx + 1;
                        $stmt_point->bind_param("iiddsi", $note['person_id'], $noteId, $point['x'], $point['y'], $desc, $order);
                        $stmt_point->execute();
                    }
                    $stmt_point->close();
                }
            }
            header("Location: person_details.php?id=" . htmlspecialchars($note['person_id']));
            exit;
        } else {
            echo "<p class='alert-error'>Chyba při aktualizaci poznámky: " . $conn->error . "</p>";
        }
        $updateStmt->close();
    }
}

// Načtení bodů na těle navázaných na tuto poznámku
$sql_points = "SELECT id, x, y, description, created_at, `order` FROM person_body_points WHERE diagnosis_note_id = ? ORDER BY `order` ASC";
$stmt_points = $conn->prepare($sql_points);
$stmt_points->bind_param("i", $noteId);
$stmt_points->execute();
$result_points = $stmt_points->get_result();
$body_points = [];
while ($row = $result_points->fetch_assoc()) {
    $body_points[] = $row;
}
$stmt_points->close();
$stmt->close();
$conn->close();
?>

<?php require __DIR__ . '/views/edit_note.view.php'; ?>