<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<!DOCTYPE html>
<html lang='cs'>
<head>
    <meta charset='UTF-8'>
    <title>Přístup zamítnut</title>
    <link rel='stylesheet' href='style.css'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
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

$conn = new mysqli("localhost", "root", "", "zdrapp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
                Bez šablony není možné automaticky vygenerovat lékařskou zprávu.<br>
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
$sqlNote = "SELECT person_id, diagnosis_id, note, created_at FROM diagnosis_notes WHERE id = ?";
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
$stmtNote->close();

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
    $sqlReport = "SELECT * FROM medical_reports WHERE diagnosis_note_id = ?";
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
                $message = "Lékařská zpráva byla úspěšně aktualizována.";
                $message_type = "success";
            } else {
                $message = "Chyba při aktualizaci lékařské zprávy: " . $conn->error;
                $message_type = "error";
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
                $message = "Lékařská zpráva byla úspěšně uložena.";
                $message_type = "success";
            } else {
                $message = "Chyba při ukládání lékařské zprávy: " . $conn->error;
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
    <title>Editace lékařské zprávy - ZDRAPP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            padding: 15px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
            transition: box-shadow 0.3s ease;
        }

        .logo:hover img {
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
        }

        .navbar {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .menu-icon {
            display: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .menu-icon:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            min-height: calc(100vh - 80px);
        }

        .page-header {
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .page-header h1 {
            font-size: 2.2rem;
            margin: 0;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .page-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 10px;
            position: relative;
            z-index: 1;
        }

        .patient-info {
            background: #f1f8e9;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background:rgb(255, 255, 255);
            border-radius: 10px;
            border-left: 4px solid #388e3c;
        }

        .info-item i {
            color: #388e3c;
            font-size: 1.2rem;
            width: 20px;
        }

        .info-label {
            font-weight: 600;
            color: #2d3748;
            margin-right: 10px;
        }

        .info-value {
            color: #4a5568;
        }

        .form-container {
            background: #f1f8e9;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .form-textarea {
            width: 100%;
            min-height: 400px;
            padding: 20px;
            border: 2px solid #c8e6c9;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            resize: vertical;
            background: white;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #388e3c;
            box-shadow: 0 0 0 3px rgba(56, 142, 60, 0.1);
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 150px;
            justify-content: center;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(56, 142, 60, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(56, 142, 60, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #757575 0%, #616161 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(117, 117, 117, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(117, 117, 117, 0.4);
        }

        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%);
            color: #2e7d32;
            border-left: 4px solid #388e3c;
        }

        .alert-error {
            background: linear-gradient(135deg, #ffcdd2 0%, #ef9a9a 100%);
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .alert i {
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .container {
                margin: 0;
                padding: 15px;
                background: linear-gradient(135deg, #f1f8e9 0%, #c8e6c9 100%);
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .patient-info {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }

            .navbar {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
                flex-direction: column;
                padding: 20px;
                gap: 10px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            }

            .navbar.active {
                display: flex;
            }

            .navbar a {
                text-align: center;
                width: 100%;
                margin: 0;
            }

            .menu-icon {
                display: block;
            }

            .form-textarea {
                min-height: 300px;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .container > * {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
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
    <div class="header">
        <div class="header-container">
            <a href="show_data.php" class="logo">
                <img src="logo.png" alt="ZDRAPP Logo">
                <span>ZDRAPP</span>
            </a>
            <div class="menu-icon" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="navbar" id="navbar">
                <a href="show_data.php">
                    <i class="fas fa-users"></i>
                    Přehled
                </a>
                <a href="upload_csv.php">
                    <i class="fas fa-upload"></i>
                    Nahrát data
                </a>
                <a href="add_diagnosis.php">
                    <i class="fas fa-plus-circle"></i>
                    Přidat diagnózu
                </a>
                <a href="download_reports.php">
                    <i class="fas fa-download"></i>
                    Stáhnout zprávy
                </a>
                <a href="add_report.php">
                    <i class="fas fa-file-medical"></i>
                    Přidat lékařskou zprávu
                </a>
                <a href="stats.php">
                    <i class="fas fa-chart-bar"></i>
                    Statistiky
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Editace lékařské zprávy</h1>
            <div class="subtitle">Úprava lékařské zprávy pro pacienta</div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

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

        <div class="form-container">
            <form action="" method="POST">
                <div class="form-group">
                    <label for="updated_report" class="form-label">
                        <i class="fas fa-file-medical"></i>
                        Obsah lékařské zprávy
                    </label>
                    <textarea 
                        name="updated_report" 
                        id="updated_report"
                        class="form-textarea"
                        placeholder="Zadejte obsah lékařské zprávy..."
                        required
                    ><?php echo htmlspecialchars($report); ?></textarea>
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