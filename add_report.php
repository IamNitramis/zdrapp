<?php
require_once __DIR__ . '/includes/auth.php';

// Připojení k databázi
require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
    // Nastavení charsetu na UTF-8 pro správné ukládání diakritiky
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Načtení všech diagnóz
$sqlDiagnoses = "SELECT id, name FROM diagnoses WHERE deleted = 0 ORDER BY name";
$resultDiagnoses = $conn->query($sqlDiagnoses);
if (!$resultDiagnoses) {
    die("Error fetching diagnoses: " . $conn->error);
}

// Zpracování formuláře
$templateText = '';
$selectedDiagnosisId = '';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosisId = intval($_POST['diagnosis_id']);
    $templateText = trim($_POST['template_text']);
    $selectedDiagnosisId = $diagnosisId;

    // Odstraníme prázdné HTML tagy z TinyMCE obsahu
    $cleanedText = trim(strip_tags($templateText));

    if ($diagnosisId && !empty($cleanedText)) {
        // Zjisti, zda už šablona pro tuto diagnózu existuje
        $sqlCheck = "SELECT id FROM templates WHERE diagnosis_id = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("i", $diagnosisId);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            // Update existující šablony
            $sqlUpdate = "UPDATE templates SET template_text = ?, created_at = NOW() WHERE diagnosis_id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("si", $templateText, $diagnosisId);
            if ($stmtUpdate->execute()) {
                $message = "Šablona byla úspěšně aktualizována pro vybranou diagnózu.";
                $messageType = "success";
            } else {
                $message = "Chyba při aktualizaci šablony: " . $conn->error;
                $messageType = "error";
            }
            $stmtUpdate->close();
        } else {
            // Vložení nové šablony
            $sqlInsert = "INSERT INTO templates (diagnosis_id, template_text, created_at) VALUES (?, ?, NOW())";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bind_param("is", $diagnosisId, $templateText);
            if ($stmtInsert->execute()) {
                $message = "Šablona byla úspěšně přiřazena k diagnóze.";
                $messageType = "success";
            } else {
                $message = "Chyba při ukládání šablony: " . $conn->error;
                $messageType = "error";
            }
            $stmtInsert->close();
        }
        $stmtCheck->close();
    } else {
        if (!$diagnosisId) {
            $message = "Prosím vyberte diagnózu.";
        } else {
            $message = "Prosím zadejte text šablony.";
        }
        $messageType = "error";
    }
} elseif (isset($_GET['diagnosis_id'])) {
    // Pokud je diagnóza vybraná přes GET, načti její šablonu
    $selectedDiagnosisId = intval($_GET['diagnosis_id']);
    $sqlTemplate = "SELECT template_text FROM templates WHERE diagnosis_id = ?";
    $stmtTemplate = $conn->prepare($sqlTemplate);
    $stmtTemplate->bind_param("i", $selectedDiagnosisId);
    $stmtTemplate->execute();
    $stmtTemplate->bind_result($templateText);
    $stmtTemplate->fetch();
    $stmtTemplate->close();
}

// Načtení názvu vybrané diagnózy pro zobrazení
$selectedDiagnosisName = '';
if ($selectedDiagnosisId) {
    $sqlDiagnosisName = "SELECT name FROM diagnoses WHERE id = ?";
    $stmtDiagnosisName = $conn->prepare($sqlDiagnosisName);
    $stmtDiagnosisName->bind_param("i", $selectedDiagnosisId);
    $stmtDiagnosisName->execute();
    $stmtDiagnosisName->bind_result($selectedDiagnosisName);
    $stmtDiagnosisName->fetch();
    $stmtDiagnosisName->close();
}
?>

<?php require __DIR__ . '/views/add_report.view.php'; ?>