<?php


require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Počet pacientů
$total_patients = $conn->query("SELECT COUNT(*) as count FROM persons")->fetch_assoc()['count'];

// Počet s medikací
$with_medication = $conn->query("SELECT COUNT(*) as count FROM persons WHERE medications IS NOT NULL AND medications != ''")->fetch_assoc()['count'];

// Počet s alergiemi
$with_allergies = $conn->query("SELECT COUNT(*) as count FROM persons WHERE allergies IS NOT NULL AND allergies != ''")->fetch_assoc()['count'];

// Kontrola poznámek bez vygenerované zprávy
$sql_missing_reports = "
    SELECT p.id AS person_id, p.first_name, p.surname, n.id AS note_id, n.note, n.diagnosis_id, d.name AS diagnosis_text, n.created_at
    FROM persons p
    INNER JOIN diagnosis_notes n ON p.id = n.person_id
    LEFT JOIN diagnoses d ON n.diagnosis_id = d.id AND d.deleted = 0
    LEFT JOIN medical_reports r ON n.id = r.diagnosis_note_id
    WHERE r.diagnosis_note_id IS NULL
    ORDER BY p.surname, p.first_name, n.created_at
";

$result_missing = $conn->query($sql_missing_reports);
$patients_without_reports = [];
$total_notes_without_reports = 0;
$sql_error = '';

if ($result_missing === false) {
    $sql_error = $conn->error;
} elseif ($result_missing->num_rows > 0) {
    while ($row = $result_missing->fetch_assoc()) {
        $pid = $row['person_id'];
        if (!isset($patients_without_reports[$pid])) {
            $patients_without_reports[$pid] = [
                'pid' => $pid,
                'first_name' => $row['first_name'],
                'surname' => $row['surname'],
                'notes' => []
            ];
        }
        $patients_without_reports[$pid]['notes'][] = [
            'note_id' => $row['note_id'],
            'note_text' => $row['note'],
            'diagnosis' => $row['diagnosis_text'],
            'diagnosis_id' => $row['diagnosis_id'],
            'created_at' => $row['created_at']
        ];
        $total_notes_without_reports++;
    }
}

// Načtení všech osob
$sql = "SELECT * FROM persons ORDER BY id ASC";
$result = $conn->query($sql);
$persons = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $persons[] = $row;
    }
}

$conn->close();

// Vykreslení prezentační vrstvy
require __DIR__ . '/views/show_data.view.php';
?>