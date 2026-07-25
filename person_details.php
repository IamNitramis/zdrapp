<?php

require_once __DIR__ . '/includes/auth.php';

// Připojení k databázi
require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Získání ID osoby z URL
if (!isset($_GET['id'])) {
    die("Person ID is required.");
}
$person_id = intval($_GET['id']);

// Zpracování formuláře pro přidání diagnózy a poznámky
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Uložení medikace a alergií
    if (isset($_POST['save_med_all'])) {
        $medications = isset($_POST['medications']) ? trim($_POST['medications']) : '';
        $allergies = isset($_POST['allergies']) ? trim($_POST['allergies']) : '';
        $user_id = $_SESSION['user_id'] ?? null;
        $sql = "UPDATE persons SET medications = ?, allergies = ?, updated_by = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $medications, $allergies, $user_id, $person_id);
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $person_id);
            exit;
        } else {
            echo "<p style='color: red;'>Chyba při ukládání medikace/alergií: " . $conn->error . "</p>";
        }
        $stmt->close();
    } elseif (isset($_POST['diagnosis_id'], $_POST['note']) && !empty($_POST['note'])) {
        $diagnosis_id = intval($_POST['diagnosis_id']);
        $note = trim($_POST['note']);
        $user_id = $_SESSION['user_id'] ?? null;
        $sql = "INSERT INTO diagnosis_notes (person_id, diagnosis_id, note, updated_by) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo "<p style='color: red;'>Chyba při přípravě dotazu: " . $conn->error . "</p>";
        } else {
            $stmt->bind_param("iisi", $person_id, $diagnosis_id, $note, $user_id);
            if ($stmt->execute()) {
                $diagnosis_note_id = $stmt->insert_id;
                // Uložení bodů na těle, pokud byly přidány
                if (!empty($_POST['body_points_json'])) {
                    $body_points = json_decode($_POST['body_points_json'], true);
                    if (is_array($body_points)) {
                        $sql_point = "INSERT INTO person_body_points (person_id, diagnosis_note_id, x, y, description, `order`) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt_point = $conn->prepare($sql_point);
                        foreach ($body_points as $idx => $point) {
                            $desc = isset($point['description']) ? $point['description'] : '';
                            $order = $idx + 1;
                            $stmt_point->bind_param("iiddsi", $person_id, $diagnosis_note_id, $point['x'], $point['y'], $desc, $order);
                            $stmt_point->execute();
                        }
                        $stmt_point->close();
                    }
                }
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $person_id);
                exit;
            } else {
                echo "<p style='color: red;'>Chyba při ukládání poznámky: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    } else {
        echo "<p style='color: red;'>Prosím vyplňte všechna pole formuláře.</p>";
    }
}

// Načtení detailů osoby
$sql = "SELECT * FROM persons WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $person_id);
$stmt->execute();
$result = $stmt->get_result();
$person = $result->fetch_assoc();
if (!$person) {
    die("Person not found.");
}

// Načtení diagnóz a poznámek osoby včetně uživatele, který upravil
$sql = "SELECT d.name AS diagnosis_name, dn.id AS note_id, dn.note, dn.created_at, u.username AS updated_by_username
        FROM diagnosis_notes dn
        JOIN diagnoses d ON dn.diagnosis_id = d.id
        LEFT JOIN users u ON dn.updated_by = u.id
        WHERE dn.person_id = ?
        ORDER BY dn.created_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Chyba při přípravě dotazu (diagnosis_notes): " . $conn->error);
}
$stmt->bind_param("i", $person_id);
$stmt->execute();
$diagnoses = $stmt->get_result();

// Načtení všech diagnóz pro výběr
$sql = "SELECT * FROM diagnoses WHERE deleted = 0 ORDER BY name ASC";
$all_diagnoses = $conn->query($sql);

// Přehled všech lékařských zpráv pro daného člověka (před uzavřením $conn)
$sql_reports = "SELECT r.id, r.report_text, r.created_at, d.name AS diagnosis_name, d.id AS diagnosis_id, n.note AS note_text, n.id AS note_id FROM medical_reports r JOIN diagnoses d ON r.diagnosis_id = d.id JOIN diagnosis_notes n ON r.diagnosis_note_id = n.id WHERE r.person_id = ? ORDER BY r.created_at DESC";
$stmt_reports = $conn->prepare($sql_reports);
$stmt_reports->bind_param("i", $person_id);
$stmt_reports->execute();
$result_reports = $stmt_reports->get_result();
// Načtení bodů na těle navázaných na jednotlivé diagnosis_note
$sql_points = "SELECT pb.id, pb.x, pb.y, pb.created_at, pb.description, pb.diagnosis_note_id FROM person_body_points pb WHERE pb.person_id = ? ORDER BY pb.created_at ASC";
$stmt_points = $conn->prepare($sql_points);
$stmt_points->bind_param("i", $person_id);
$stmt_points->execute();
$result_points = $stmt_points->get_result();
$body_points_by_note = [];
while ($row = $result_points->fetch_assoc()) {
    $body_points_by_note[$row['diagnosis_note_id']][] = $row;
}
$stmt_points->close();

$stmt->close();
$conn->close();

// Načtení pohledu
require __DIR__ . '/views/person_details.view.php';
?>