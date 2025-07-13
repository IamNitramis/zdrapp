<?php
// batch_generate_reports.php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
$conn = getDatabase();

// Najdi všechny poznámky bez zprávy
$sql = "
    SELECT n.id AS note_id, n.diagnosis_id, n.person_id
    FROM diagnosis_notes n
    LEFT JOIN medical_reports r ON n.id = r.diagnosis_note_id
    LEFT JOIN diagnoses d ON n.diagnosis_id = d.id AND d.deleted = 0
    WHERE r.diagnosis_note_id IS NULL
";
$result = $conn->query($sql);

$generated = 0;
$skipped = 0;
$errors = [];

while ($row = $result->fetch_assoc()) {
    $note_id = $row['note_id'];
    $diagnosis_id = $row['diagnosis_id'];
    $person_id = $row['person_id'];

    // Získat šablonu pro diagnózu
    $tpl = $conn->prepare("SELECT template_text FROM templates WHERE diagnosis_id = ?");
    $tpl->bind_param('i', $diagnosis_id);
    $tpl->execute();
    $tpl->bind_result($template_text);
    $tpl->fetch();
    $tpl->close();

    if (empty($template_text)) {
        $skipped++;
        continue;
    }

    // Získat data poznámky a osoby
    $noteQ = $conn->prepare("SELECT note FROM diagnosis_notes WHERE id = ?");
    $noteQ->bind_param('i', $note_id);
    $noteQ->execute();
    $noteQ->bind_result($note_text);
    $noteQ->fetch();
    $noteQ->close();

    $personQ = $conn->prepare("SELECT first_name, surname, birth_date FROM persons WHERE id = ?");
    $personQ->bind_param('i', $person_id);
    $personQ->execute();
    $personQ->bind_result($first_name, $surname, $birth_date);
    $personQ->fetch();
    $personQ->close();

    // Další data pro placeholdery podle report.php
    // Náhodná data
    $temperature = mt_rand(360, 370) / 10; // 36.0 - 37.0 °C
    $oxygen_saturation = mt_rand(98, 100); // 98 - 100 %
    $heart_rate = mt_rand(60, 100);        // 60 - 100 bpm

    // Získat název diagnózy a datum přiřazení
    $diagQ = $conn->prepare("SELECT d.name, n.created_at FROM diagnoses d JOIN diagnosis_notes n ON d.id = n.diagnosis_id WHERE d.id = ? AND n.id = ?");
    $diagQ->bind_param('ii', $diagnosis_id, $note_id);
    $diagQ->execute();
    $diagQ->bind_result($diagnosis_name, $assigned_at);
    $diagQ->fetch();
    $diagQ->close();

    // Získat autora poznámky (updated_by)
    $authorName = 'Neznámý';
    $authorQ = $conn->prepare("SELECT u.firstname, u.lastname FROM diagnosis_notes n LEFT JOIN users u ON n.updated_by = u.id WHERE n.id = ?");
    $authorQ->bind_param('i', $note_id);
    $authorQ->execute();
    $authorQ->bind_result($author_first, $author_last);
    if ($authorQ->fetch() && $author_first && $author_last) {
        $authorName = $author_first . ' ' . $author_last;
    }
    $authorQ->close();

    $current_day = date('d.m.Y');
    $diagnosis_full = $diagnosis_name;
    if ($assigned_at) {
        $diagnosis_full .= " (Zaznamenáno: $assigned_at)";
    }

    // Placeholdery podle report.php
    $report_text = str_replace(
        ['{{name}}', '{{birth_date}}', '{{temperature}}', '{{oxygen_saturation}}', '{{heart_rate}}', '{{diagnosis}}', '{{note}}', '{{author}}', '{{current_date}}'],
        [
            htmlspecialchars($first_name . ' ' . $surname),
            htmlspecialchars($birth_date),
            $temperature,
            $oxygen_saturation,
            $heart_rate,
            htmlspecialchars($diagnosis_full),
            htmlspecialchars($note_text),
            htmlspecialchars($authorName),
            $current_day
        ],
        $template_text
    );

    // Vložení zprávy
    $ins = $conn->prepare("INSERT INTO medical_reports (person_id, report_text, created_at, diagnosis_id, diagnosis_note_id) VALUES (?, ?, NOW(), ?, ?)");
    $ins->bind_param('isii', $person_id, $report_text, $diagnosis_id, $note_id);
    if ($ins->execute()) {
        $generated++;
    } else {
        $errors[] = "Poznámka ID $note_id: " . $conn->error;
    }
    $ins->close();
}

$conn->close();

// Po dokončení přesměruj zpět na seznam pacientů
header('Location: show_data.php');
exit;
