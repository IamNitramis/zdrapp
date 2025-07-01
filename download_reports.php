<?php
// filepath: c:\xampp\htdocs\ZdrAPP_Secure\download_reports.php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("You must be logged in.");
}

$conn = new mysqli("localhost", "root", "", "zdrapp");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Získání seznamu pacientů
$patients = [];
$result = $conn->query("SELECT id, first_name, surname FROM persons ORDER BY surname, first_name");
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}

// Stažení všech reportů do ZIPu
if (isset($_GET['download_all']) && $_GET['download_all'] == '1') {
    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'reports_zip_');
    $zip->open($tmpFile, ZipArchive::CREATE);

    foreach ($patients as $p) {
        $person_id = $p['id'];
        $sql = "SELECT mr.created_at, mr.report_text, d.name AS diagnosis
                FROM medical_reports mr
                LEFT JOIN diagnosis_notes dn ON mr.diagnosis_note_id = dn.id
                LEFT JOIN diagnoses d ON mr.diagnosis_id = d.id
                WHERE mr.person_id = ?
                ORDER BY mr.created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $person_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }

        if (count($reports) > 0) {
            $filename = "reports_" . $p['surname'] . "_" . $p['first_name'] . ".txt";
            $content = "Medical Reports for " . $p['first_name'] . " " . $p['surname'] . "\n";
            $content .= str_repeat("=", 40) . "\n\n";
            foreach ($reports as $r) {
                $content .= "Datum: " . $r['created_at'] . "\n";
                $content .= "Diagnóza: " . $r['diagnosis'] . "\n";
                $content .= "----------------------------------------\n";
                $content .= $r['report_text'] . "\n";
                $content .= str_repeat("-", 40) . "\n\n";
            }
            $zip->addFromString($filename, $content);
        }
        $stmt->close();
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="all_medical_reports.zip"');
    header('Content-Length: ' . filesize($tmpFile));
    if (ob_get_level()) {
        ob_end_clean();
    }
    flush();
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

// Pokud byl odeslán formulář ke stažení jednoho pacienta
if (isset($_GET['person_id']) && is_numeric($_GET['person_id'])) {
    $person_id = intval($_GET['person_id']);
    $sql = "SELECT mr.created_at, mr.report_text, d.name AS diagnosis
            FROM medical_reports mr
            LEFT JOIN diagnosis_notes dn ON mr.diagnosis_note_id = dn.id
            LEFT JOIN diagnoses d ON mr.diagnosis_id = d.id
            WHERE mr.person_id = ?
            ORDER BY mr.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $person_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }

    // Získání jména pacienta
    $person = $conn->query("SELECT first_name, surname FROM persons WHERE id = $person_id")->fetch_assoc();
    $filename = "reports_" . $person['surname'] . "_" . $person['first_name'] . ".txt";

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo "Medical Reports for " . $person['first_name'] . " " . $person['surname'] . "\n";
    echo str_repeat("=", 40) . "\n\n";
    foreach ($reports as $r) {
        echo "Datum: " . $r['created_at'] . "\n";
        echo "Diagnóza: " . $r['diagnosis'] . "\n";
        echo "----------------------------------------\n";
        echo $r['report_text'] . "\n";
        echo str_repeat("-", 40) . "\n\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Stáhnout lékařské zprávy</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 600px; margin: 40px auto; padding: 30px; border: 1px solid #ddd; border-radius: 8px; }
        select, button { padding: 8px 12px; margin: 10px 0; }
    </style>
</head>
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
<body>
    <div class="container">
        <h2>Stáhnout všechny lékařské zprávy pacienta</h2>
        <form method="get" style="margin-bottom:20px;">
            <label for="person_id">Vyberte pacienta:</label>
            <select name="person_id" id="person_id" required>
                <option value="">-- Vyberte --</option>
                <?php foreach ($patients as $p): ?>
                    <option value="<?php echo $p['id']; ?>">
                        <?php echo htmlspecialchars($p['surname'] . " " . $p['first_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Stáhnout TXT</button>
        </form>
        <form method="get">
            <input type="hidden" name="download_all" value="1">
            <button type="submit" style="background:#e67e22;color:#fff;">Stáhnout ZIP všech pacientů</button>
        </form>
    </div>
</body>
</html>