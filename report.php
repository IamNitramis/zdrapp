<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<!DOCTYPE html>
<html lang='cs'>
<head>
    <meta charset='UTF-8'>
    <title>Přístup zamítnut</title>
    <link rel='stylesheet' href='style.css'>
    <link href='assets/css/all.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%);
            color: #333;
        }
        .login-warning {
            max-width: 450px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            padding: 50px 40px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-warning i {
            font-size: 4rem;
            color: #388e3c;
            margin-bottom: 20px;
        }
        .login-warning h2 { 
            color: #2d3748; 
            margin-bottom: 15px; 
            font-size: 1.8rem;
            font-weight: 600;
        }
        .login-warning p {
            color: #4a5568;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .login-warning a {
            display: inline-block;
            padding: 15px 35px;
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(56, 142, 60, 0.3);
        }
        .login-warning a:hover { 
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(56, 142, 60, 0.4);
        }
    </style>
</head>
<body>
    <div class='login-warning'>
        <i class='fas fa-lock'></i>
        <h2>Přístup zamítnut</h2>
        <p>Pro zobrazení této stránky se musíte přihlásit.</p>
        <a href='login.php'>
            <i class='fas fa-sign-in-alt'></i>
            Přihlásit se
        </a>
    </div>
</body>
</html>";
    exit;
}

require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
    // Nastavení charsetu na UTF-8 pro správné ukládání diakritiky
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

if (!isset($_GET['person_id']) || !is_numeric($_GET['person_id'])) {
    die("Valid Patient ID is required.");
}

if (!isset($_GET['diagnosis_id']) || !is_numeric($_GET['diagnosis_id'])) {
    die("Valid Diagnosis ID is required.");
}

// Získání diagnosis_note_id z GET (unikátní pro konkrétní poznámku)
if (!isset($_GET['diagnosis_note_id']) || !is_numeric($_GET['diagnosis_note_id'])) {
    die("Valid diagnosis_note_id is required.");
}
$diagnosisNoteId = intval($_GET['diagnosis_note_id']);

$personId = intval($_GET['person_id']);
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
    echo '<!DOCTYPE html>
    <html lang="cs">
    <head>
        <meta charset="UTF-8">
        <title>Šablona nenalezena</title>
        <link rel="stylesheet" href="style.css">
        <link rel="icon" type="image/png" href="logo.png">
        <link rel="stylesheet" href="assets/css/all.min.css">
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%);
                min-height: 100vh;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .not-found-card {
                background: white;
                border-radius: 16px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.10);
                padding: 40px 30px;
                text-align: center;
                max-width: 420px;
            }
            .not-found-card i {
                font-size: 3rem;
                color: #ff6b6b;
                margin-bottom: 18px;
            }
            .not-found-card h2 {
                color: #388e3c;
                margin-bottom: 12px;
            }
            .not-found-card p {
                color: #4a5568;
                margin-bottom: 22px;
            }
            .not-found-card a {
                display: inline-block;
                padding: 12px 28px;
                background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
                color: #fff;
                border-radius: 30px;
                text-decoration: none;
                font-weight: 600;
                font-size: 1rem;
                transition: background 0.2s, transform 0.2s;
            }
            .not-found-card a:hover {
                background: linear-gradient(135deg, #43a047 0%, #388e3c 100%);
                transform: translateY(-2px) scale(1.04);
            }
        </style>
    </head>
    <body>
        <div class="not-found-card">
            <i class="fas fa-file-excel"></i>
            <h2>Chybí šablona pro tuto diagnózu</h2>
            <p>
                Bohužel nebyla nalezena žádná šablona pro tuto diagnózu.<br>
                Bez šablony není možné automaticky vygenerovat zdravotnickou zprávu.<br>
                <strong>Přidejte šablonu pro tuto diagnózu v sekci níže.</strong>
            </p>
            <a href="add_report.php">
                <i class="fas fa-plus-circle"></i>
                Přidat šablonu pro diagnózu
            </a>
        </div>
    </body>
    </html>';
    exit;
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

// Získání diagnosis_note_id z GET
if (!isset($_GET['diagnosis_note_id']) || !is_numeric($_GET['diagnosis_note_id'])) {
    die("Valid diagnosis_note_id is required.");
}
$diagnosisNoteId = intval($_GET['diagnosis_note_id']);

// Načtení poznámky podle diagnosis_note_id
$sqlNote = "SELECT person_id, diagnosis_id, note, created_at, updated_by FROM diagnosis_notes WHERE id = ?";
$stmtNote = $conn->prepare($sqlNote);
$stmtNote->bind_param("i", $diagnosisNoteId);
$stmtNote->execute();
$resultNote = $stmtNote->get_result();
if ($resultNote->num_rows === 0) {
    die("Diagnosis note not found.");
}
$noteRow = $resultNote->fetch_assoc();
$personId = $noteRow['person_id'];
$diagnosisId = $noteRow['diagnosis_id'];
$noteText = $noteRow['note'];
$assignedAt = $noteRow['created_at'];
$updatedBy = $noteRow['updated_by'];
$stmtNote->close();

// Získání jména autora (updated_by)
$authorName = 'Neznámý';
if ($updatedBy) {
    $sqlUser = "SELECT firstname, lastname FROM users WHERE id = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("i", $updatedBy);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    if ($resultUser->num_rows > 0) {
        $userRow = $resultUser->fetch_assoc();
        $authorName = $userRow['firstname'] . ' ' . $userRow['lastname'];
    }
    $stmtUser->close();
}

// Náhodná data
$temperature = mt_rand(360, 370) / 10; // 36.0 - 38.0 °C
$oxygen_saturation = mt_rand(98, 100); // 98 - 100 %
$heart_rate = mt_rand(60, 100);        // 60 - 100 bpm
$blood_pressure = mt_rand(110, 140) . '/' . mt_rand(70, 90); // 110-140/70-90 mmHg

// Aktuální datum
$current_day = date('d.m.Y');

// Pokud už je report v medical_reports, použij ho. Jinak vygeneruj nový podle šablony a dat.
$sqlReport = "SELECT report_text FROM medical_reports WHERE diagnosis_note_id = ?";
$stmtReport = $conn->prepare($sqlReport);
$stmtReport->bind_param("i", $diagnosisNoteId);
$stmtReport->execute();
$resultReport = $stmtReport->get_result();
if ($resultReport->num_rows > 0) {
    $reportRow = $resultReport->fetch_assoc();
    $report = $reportRow['report_text'];
} else {
    // Nahrazení placeholderů skutečnými hodnotami, včetně {{note}}, {{author}} a {{current_day}}
    $report = str_replace(
        ['{{name}}', '{{birth_date}}', '{{temperature}}', '{{oxygen_saturation}}', '{{heart_rate}}', '{{blood_pressure}}', '{{diagnosis}}', '{{note}}', '{{author}}', '{{current_date}}'],
        [
            htmlspecialchars($person['first_name'] . ' ' . $person['surname']),
            htmlspecialchars($person['birth_date']),
            $temperature,
            $oxygen_saturation,
            $heart_rate,
            $blood_pressure,
            htmlspecialchars($diagnosis['diagnosis_name'] . " (Zaznamenáno: " . $assignedAt . ")"),
            htmlspecialchars($noteText),
            htmlspecialchars($authorName),
            $current_day
        ],
        $template
    );
}
$stmtReport->close();

// Uložení zprávy, pokud je formulář odeslán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['updated_report']) && !empty(trim($_POST['updated_report']))) {
        $updatedReport = trim($_POST['updated_report']);

        // Zjisti aktuálního uživatele
        $currentUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

        // Zjisti, jestli už report existuje pro tuto diagnosis_note_id
        $sqlCheck = "SELECT id FROM medical_reports WHERE diagnosis_note_id = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("i", $diagnosisNoteId);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            // UPDATE existujícího reportu
            $sqlUpdate = "UPDATE medical_reports SET report_text = ?, created_at = NOW(), updated_by = ? WHERE diagnosis_note_id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("sii", $updatedReport, $currentUserId, $diagnosisNoteId);
            if ($stmtUpdate->execute()) {
                $message = "Zdravotnická zpráva byla úspěšně aktualizována.";
                $message_type = "success";
            } else {
                $message = "Chyba při aktualizaci zdravotnické zprávy: " . $conn->error;
                $message_type = "error";
            }
            $stmtUpdate->close();
        } else {
            // INSERT nového reportu
            $sqlInsert = "INSERT INTO medical_reports (person_id, diagnosis_id, diagnosis_note_id, report_text, created_at, updated_by) VALUES (?, ?, ?, ?, NOW(), ?)";
            $stmtInsert = $conn->prepare($sqlInsert);

            if (!$stmtInsert) {
                die("SQL Error: " . $conn->error);
            }

            $stmtInsert->bind_param("iiisi", $personId, $diagnosisId, $diagnosisNoteId, $updatedReport, $currentUserId);
            if ($stmtInsert->execute()) {
                $message = "Zdravotnická zpráva byla úspěšně uložena.";
                $message_type = "success";
            } else {
                $message = "Chyba při ukládání zdravotnické zprávy: " . $conn->error;
                $message_type = "error";
            }
            $stmtInsert->close();
        }
        $stmtCheck->close();
    } else {
        $message = "Obsah zprávy nemůže být prázdný.";
        $message_type = "error";
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
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editace zdravotnické zprávy - ZDRAPP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">    
    <script src="assets/tinymce/js/tinymce/tinymce.min.js"></script>
<script>
  tinymce.init({
    selector: '#updated_report',
    plugins: 'lists table',
    toolbar: 'undo redo | bold italic underline | bullist numlist | table | fontsize',
    menubar: false,
    fontsize_formats: '8pt 10pt 12pt 14pt 18pt 24pt 36pt 48pt',
    branding: false,
    height: 500
  });
</script>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Editace zdravotnické zprávy</h1>
            <div class="subtitle">Úprava zdravotnické zprávy pro pacienta</div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="patient-info-container">
            <div class="patient-info">
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span class="info-label">Pacient:</span>
                    <span class="info-value"><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['surname']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-calendar"></i>
                    <span class="info-label">Datum narození:</span>
                    <span class="info-value"><?php echo htmlspecialchars(date('d.m.Y', strtotime($person['birth_date']))); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-stethoscope"></i>
                    <span class="info-label">Diagnóza:</span>
                    <span class="info-value"><?php echo htmlspecialchars($diagnosis['diagnosis_name']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span class="info-label">Vytvořeno:</span>
                    <span class="info-value"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($assignedAt))); ?></span>
                </div>
            </div>
        </div>

        <div class="form-container">
            <form action="" method="POST">
                <div class="form-group">
                    <label for="updated_report" class="form-label">
                        <i class="fas fa-file-medical"></i>
                        Obsah zdravotnické zprávy
                    </label>
                    <textarea 
                        name="updated_report" 
                        id="updated_report"
                        class="form-textarea"
                        placeholder="Zadejte obsah zdravotnické zprávy..."
                        required
                    ><?php echo $report; ?></textarea>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Uložit zprávu
                    </button>
                    <a href="show_data.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Zrušit
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            var navbar = document.getElementById("navbar");
            navbar.classList.toggle("open");
        }

        // Auto-resize textarea
        const textarea = document.getElementById('updated_report');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(400, this.scrollHeight) + 'px';
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.querySelector('form').submit();
            }
        });

        // Show success message and auto-hide
        <?php if (isset($message) && $message_type === 'success'): ?>
        setTimeout(function() {
            const alert = document.querySelector('.alert-success');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>