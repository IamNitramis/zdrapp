<?php


require_once __DIR__ . '/includes/auth.php';

// Načtení databázové konfigurace
require_once __DIR__ . '/config/database.php';

// Připojení k databázi
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Přidání nové diagnózy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_diagnosis'])) {
    $newDiagnosis = $conn->real_escape_string($_POST['new_diagnosis']);
    // Přednastavený text šablony
    $templateText ="<p><span style='font-size: 14pt;'><strong>ZDRAVOTNICK&Aacute; ZPR&Aacute;VA</strong></span></p>
                    <p><strong>Pacient:</strong> {{name}}</p>
                    <p><strong>Datum narozen&iacute;:</strong> {{birth_date}}</p>
                    <p><strong>Pojišťovna:</strong> {{insurance}}</p>
                    <p>&nbsp;</p>
                    <p><strong>Klinick&eacute; hodnoty</strong></p>
                    <ul>
                    <li><strong>Tělesn&aacute; teplota:</strong> {{temperature}}°</li>
                    <li><strong>Saturace kysl&iacute;kem:</strong> {{oxygen_saturation}}%</li>
                    <li><strong>Srdečn&iacute; tep:</strong> {{heart_rate}}bpm</li>
                    <li><strong>Krevn&iacute; tlak:</strong> {{blood_pressure}} mmHg</li>
                    </ul>
                    <p><strong>Diagnostick&eacute; &uacute;daje</strong></p>
                    <p>&nbsp;</p>
                    <p><strong>Diagn&oacute;za:</strong> {{diagnosis}}</p>
                    <p><strong>Pozn&aacute;mka k diagn&oacute;ze:</strong></p>
                    <p>{{note}}</p>
                    <p>&nbsp;</p>
                    <p>Autor z&aacute;znamu: {{author}}</p>
                    <p>Datum vyhotoven&iacute;: {{current_date}}</p>";
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO diagnoses (name, updated_by) VALUES (?, ?)");
    $stmt->bind_param("si", $newDiagnosis, $user_id);

    if ($stmt->execute()) {
        $diagnosis_id = $conn->insert_id;
        // Vložit šablonu do tabulky templates
        $stmt2 = $conn->prepare("INSERT INTO templates (diagnosis_id, template_text, created_at) VALUES (?, ?, NOW())");
        $stmt2->bind_param("is", $diagnosis_id, $templateText);
        $stmt2->execute();
        $stmt2->close();
        header("Location: add_diagnosis.php");
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
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("UPDATE diagnoses SET name = ?, updated_by = ? WHERE id = ?");
    $stmt->bind_param("sii", $updatedDiagnosis, $user_id, $diagnosisId);

    if ($stmt->execute()) {
        header("Location: add_diagnosis.php");
        exit;
    } else {
        echo "Chyba při úpravě diagnózy: " . $conn->error;
    }
    $stmt->close();
}

// Smazání diagnózy
if (isset($_GET['delete_diagnosis_id'])) {
    $diagnosisId = intval($_GET['delete_diagnosis_id']);
    $deleteSql = "UPDATE diagnoses SET deleted = 1 WHERE id = $diagnosisId";
    if ($conn->query($deleteSql) === TRUE) {
        header("Location: add_diagnosis.php");
        exit;
    } else {
        echo "Chyba při mazání diagnózy: " . $conn->error;
    }
}

// Načtení seznamu diagnóz
$diagnosesSql = "SELECT *, (SELECT username FROM users WHERE users.id = diagnoses.updated_by) AS updated_by_username FROM diagnoses WHERE deleted = 0 ORDER BY name ASC";
$diagnosesResult = $conn->query($diagnosesSql);
$diagnoses = [];
while ($row = $diagnosesResult->fetch_assoc()) {
    $diagnoses[] = $row;
}

// Načtení lékařských zpráv
$reportsSql = "SELECT mr.id, mr.created_at, 
               CONCAT(p.first_name, ' ', p.surname) AS patient_name,
               d.name AS diagnosis, 
               LEFT(mr.report_text, 100) AS report_preview 
               FROM medical_reports mr
               LEFT JOIN persons p ON mr.person_id = p.id
               LEFT JOIN diagnoses d ON mr.diagnosis_id = d.id
               ORDER BY mr.created_at DESC";
$reportsResult = $conn->query($reportsSql);
$medicalReports = [];
if ($reportsResult) {
    while ($row = $reportsResult->fetch_assoc()) {
        $medicalReports[] = $row;
    }
} else {
    // Pokud dotaz selhal, zkusíme jednodušší dotaz
    $reportsSql = "SELECT id, created_at, report_text FROM medical_reports ORDER BY created_at DESC LIMIT 10";
    $reportsResult = $conn->query($reportsSql);
    if ($reportsResult) {
        while ($row = $reportsResult->fetch_assoc()) {
            $medicalReports[] = [
                'id' => $row['id'],
                'created_at' => $row['created_at'],
                'patient_name' => 'N/A',
                'diagnosis' => 'N/A',
                'report_preview' => substr($row['report_text'], 0, 100)
            ];
        }
    }
}

// Statistiky
$total_diagnoses = count($diagnoses);

$conn->close();

require __DIR__ . '/views/add_diagnosis.view.php';
?>