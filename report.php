<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<div class='alert'>
            <h2>You must be logged in to access this page.</h2>
            <p><a href='login.php' class='link-button'>Click here to login</a></p>
          </div>";
    exit;
}

$conn = new mysqli("localhost", "root", "", "zdrapp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Valid Patient ID is required.");
}

if (!isset($_GET['diagnosis_id']) || !is_numeric($_GET['diagnosis_id'])) {
    die("Valid Diagnosis ID is required.");
}

$personId = intval($_GET['id']);
$diagnosisId = intval($_GET['diagnosis_id']);

$sqlPerson = "SELECT first_name, surname, birth_date FROM persons WHERE id = ?";
$stmtPerson = $conn->prepare($sqlPerson);
$stmtPerson->bind_param("i", $personId);
$stmtPerson->execute();
$resultPerson = $stmtPerson->get_result();

if ($resultPerson->num_rows === 0) {
    die("Patient not found.");
}

$person = $resultPerson->fetch_assoc();

// Nejprve zkus najít diagnózu přiřazenou pacientovi
$sqlDiagnosis = "
    SELECT d.name AS diagnosis_name, pd.assigned_at
    FROM person_diagnoses pd
    INNER JOIN diagnoses d ON pd.diagnosis_id = d.id
    WHERE pd.person_id = ? AND pd.diagnosis_id = ?
";
$stmtDiagnosis = $conn->prepare($sqlDiagnosis);
$stmtDiagnosis->bind_param("ii", $personId, $diagnosisId);
$stmtDiagnosis->execute();
$resultDiagnosis = $stmtDiagnosis->get_result();

if ($resultDiagnosis->num_rows === 0) {
    // Pokud není přiřazena, najdi diagnózu pouze podle diagnosis_id
    $sqlDiagnosis = "SELECT name AS diagnosis_name FROM diagnoses WHERE id = ?";
    $stmtDiagnosis = $conn->prepare($sqlDiagnosis);
    $stmtDiagnosis->bind_param("i", $diagnosisId);
    $stmtDiagnosis->execute();
    $resultDiagnosis = $stmtDiagnosis->get_result();

    if ($resultDiagnosis->num_rows === 0) {
        die("Diagnosis not found.");
    }

    $diagnosis = $resultDiagnosis->fetch_assoc();
} else {
    $diagnosis = $resultDiagnosis->fetch_assoc();
}

// Načtení šablony z tabulky templates
$sqlTemplate = "SELECT template_text FROM templates WHERE diagnosis_id = ?";
$stmtTemplate = $conn->prepare($sqlTemplate);
$stmtTemplate->bind_param("i", $diagnosisId);
$stmtTemplate->execute();
$resultTemplate = $stmtTemplate->get_result();

if ($resultTemplate->num_rows === 0) {
    die("No template found for this diagnosis.");
}

$template = $resultTemplate->fetch_assoc()['template_text'];

// Najdi poslední diagnosis_note_id pro daného pacienta a diagnózu
$sqlNoteId = "SELECT id, note, created_at FROM diagnosis_notes WHERE person_id = ? AND diagnosis_id = ? ORDER BY created_at DESC LIMIT 1";
$stmtNoteId = $conn->prepare($sqlNoteId);
$stmtNoteId->bind_param("ii", $personId, $diagnosisId);
$stmtNoteId->execute();
$resultNoteId = $stmtNoteId->get_result();
$diagnosisNoteId = null;
$noteText = '';
$assignedAt = null;
if ($row = $resultNoteId->fetch_assoc()) {
    $diagnosisNoteId = $row['id'];
    $noteText = $row['note'];
    $assignedAt = $row['created_at'];
}
$stmtNoteId->close();

// Náhodná data
$temperature = mt_rand(360, 370) / 10; // 36.0 - 38.0 °C
$oxygen_saturation = mt_rand(98, 100); // 98 - 100 %
$heart_rate = mt_rand(60, 100);        // 60 - 100 bpm

// Nahrazení placeholderů skutečnými hodnotami, včetně {{note}}
$report = str_replace(
    ['{{name}}', '{{birth_date}}', '{{temperature}}', '{{oxygen_saturation}}', '{{heart_rate}}', '{{diagnosis}}', '{{note}}'],
    [
        htmlspecialchars($person['first_name'] . ' ' . $person['surname']),
        htmlspecialchars($person['birth_date']),
        $temperature,
        $oxygen_saturation,
        $heart_rate,
        htmlspecialchars($diagnosis['diagnosis_name'] . " (Recorded on: " . $assignedAt . ")"),
        htmlspecialchars($noteText)
    ],
    $template
);

// Načtení uložené zprávy z medical_reports podle diagnosis_note_id
if ($diagnosisNoteId) {
    $sqlReport = "SELECT report_text FROM medical_reports WHERE diagnosis_note_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmtReport = $conn->prepare($sqlReport);
    $stmtReport->bind_param("i", $diagnosisNoteId);
    $stmtReport->execute();
    $resultReport = $stmtReport->get_result();

    if ($resultReport->num_rows > 0) {
        $reportRow = $resultReport->fetch_assoc();
        $report = $reportRow['report_text'];
    }
    $stmtReport->close();
}

// Uložení zprávy, pokud je formulář odeslán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['updated_report']) && !empty(trim($_POST['updated_report']))) {
        $updatedReport = trim($_POST['updated_report']);

        // Zjisti, jestli už report existuje pro tuto diagnosis_note_id
        $sqlCheck = "SELECT id FROM medical_reports WHERE diagnosis_note_id = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("i", $diagnosisNoteId);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            // UPDATE existujícího reportu
            $sqlUpdate = "UPDATE medical_reports SET report_text = ?, created_at = NOW() WHERE diagnosis_note_id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("si", $updatedReport, $diagnosisNoteId);
            if ($stmtUpdate->execute()) {
                $message = "Medical report has been updated successfully.";
            } else {
                $message = "Error updating medical report: " . $conn->error;
            }
            $stmtUpdate->close();
        } else {
            // INSERT nového reportu
            $sqlInsert = "INSERT INTO medical_reports (person_id, diagnosis_id, diagnosis_note_id, report_text, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmtInsert = $conn->prepare($sqlInsert);

            if (!$stmtInsert) {
                die("SQL Error: " . $conn->error);
            }

            $stmtInsert->bind_param("iiis", $personId, $diagnosisId, $diagnosisNoteId, $updatedReport);
            if ($stmtInsert->execute()) {
                $message = "Medical report has been saved successfully.";
            } else {
                $message = "Error saving medical report: " . $conn->error;
            }
            $stmtInsert->close();
        }
        $stmtCheck->close();
    } else {
        $message = "Report content cannot be empty.";
    }
}

// Pokud byl formulář odeslán a úspěšně uložen, ponecháme v textarea uživatelský vstup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updated_report'])) {
    $report = $_POST['updated_report'];
}

$stmtPerson->close();
$stmtDiagnosis->close();
$stmtTemplate->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medical Report</title>
    <link rel="stylesheet" href="style.css">
    <style>
        textarea {
            width: 100%;
            height: 300px;
            resize: vertical;
        }
        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
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
            <a href="download_reports.php">Stáhnout zprávy</a>
            <a href="login.php">Login</a>
        </div>
    </div>
    <div class="container">
        <h1>Edit Medical Report for <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['surname']); ?></h1>
        <?php if (isset($message)) echo "<p>$message</p>"; ?>
        <form action="" method="POST">
            <textarea name="updated_report" style="width:100%;height:500px;resize:vertical;"><?php echo htmlspecialchars($report); ?></textarea>
            <br>
            <button type="submit">Save Report</button>
            <a href="show_data.php">Cancel</a>
        </form>
    </div>
</body>
</html>
