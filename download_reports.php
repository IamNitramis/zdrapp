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

// Funkce pro generování obrázku s klíšťaty
function generateKlisteImage($person_id, $conn, $outputPath) {
    $baseImagePath = __DIR__ . '/body.jpg'; // základní obrázek těla
    if (!file_exists($baseImagePath)) return false;

    $im = imagecreatefromjpeg($baseImagePath);
    if (!$im) return false;

    $sql = "SELECT x, y, bite_order FROM tick_bites WHERE person_id = ? ORDER BY bite_order ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $person_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $width = imagesx($im);
    $height = imagesy($im);

    $red = imagecolorallocate($im, 255, 0, 0);
    $white = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 0, 0, 0);

    while ($row = $result->fetch_assoc()) {
        $px = intval($row['x'] * $width);
        $py = intval($row['y'] * $height);
        imagefilledellipse($im, $px, $py, 16, 16, $red);
        $text = $row['bite_order'];
        imagestring($im, 5, $px-6, $py-8, $text, $black);
        imagestring($im, 5, $px-7, $py-9, $text, $white);
    }

    $stmt->close();
    $success = imagepng($im, $outputPath);
    imagedestroy($im);
    return $success;
}

// Funkce pro generování obsahu TXT souboru
function generateReportContent($person_id, $conn) {
    // Získání jména pacienta
    $personSql = "SELECT first_name, surname FROM persons WHERE id = ?";
    $personStmt = $conn->prepare($personSql);
    $personStmt->bind_param("i", $person_id);
    $personStmt->execute();
    $personResult = $personStmt->get_result();
    $person = $personResult->fetch_assoc();
    $personStmt->close();
    
    if (!$person) {
        return null;
    }

    // Získání lékařských zpráv
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
    $stmt->close();

    // Začátek obsahu
    $content = "LÉKAŘSKÉ ZPRÁVY - " . strtoupper($person['first_name'] . " " . $person['surname']) . "\n";
    $content .= str_repeat("=", 60) . "\n\n";
    
    // Lékařské zprávy
    $content .= "LÉKAŘSKÉ ZPRÁVY:\n";
    $content .= str_repeat("-", 60) . "\n";
    
    if (count($reports) > 0) {
        foreach ($reports as $i => $r) {
            $content .= "Zpráva č. " . ($i + 1) . "\n";
            $content .= "Datum: " . ($r['created_at'] ? $r['created_at'] : 'N/A') . "\n";
            $content .= "Diagnóza: " . ($r['diagnosis'] ? $r['diagnosis'] : 'Nezadána') . "\n";
            $content .= str_repeat("-", 40) . "\n";
            $content .= ($r['report_text'] ? $r['report_text'] : 'Žádný text zprávy') . "\n";
            $content .= str_repeat("-", 60) . "\n\n";
        }
    } else {
        $content .= "Žádné lékařské zprávy nebyly nalezeny.\n\n";
    }

    // Tabulka klíšťat
    $klisteSql = "SELECT bite_order, created_at, x, y FROM tick_bites WHERE person_id = ? ORDER BY bite_order ASC";
    $klisteStmt = $conn->prepare($klisteSql);
    $klisteStmt->bind_param("i", $person_id);
    $klisteStmt->execute();
    $klisteResult = $klisteStmt->get_result();

    $content .= "\nTABULKA KLÍŠŤAT:\n";
    $content .= str_repeat("-", 60) . "\n";
    $content .= sprintf("%-8s %-20s %-10s %-10s\n", "Pořadí", "Datum přidání", "X pozice", "Y pozice");
    $content .= str_repeat("-", 60) . "\n";
    
    $hasBites = false;
    while ($k = $klisteResult->fetch_assoc()) {
        $content .= sprintf("%-8s %-20s %-10.3f %-10.3f\n", 
            $k['bite_order'], 
            $k['created_at'], 
            $k['x'], 
            $k['y']
        );
        $hasBites = true;
    }
    
    if (!$hasBites) {
        $content .= "Žádná klíšťata nebyla zaznamenána.\n";
    }
    
    $content .= str_repeat("-", 60) . "\n\n";
    $klisteStmt->close();

    // Statistiky
    $content .= "STATISTIKY:\n";
    $content .= str_repeat("-", 30) . "\n";
    $content .= "Počet lékařských zpráv: " . count($reports) . "\n";
    $content .= "Počet zaznamenaných klíšťat: " . ($hasBites ? mysqli_num_rows($klisteResult) : 0) . "\n";
    $content .= "Vygenerováno: " . date('Y-m-d H:i:s') . "\n";
    $content .= str_repeat("=", 60) . "\n";

    return [
        'content' => $content,
        'person' => $person,
        'has_bites' => $hasBites
    ];
}

// BLOK PRO ZIP VŠECH PACIENTŮ
if (isset($_GET['download_all']) && $_GET['download_all'] == '1') {
    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'reports_zip_');
    
    if ($zip->open($tmpFile, ZipArchive::CREATE) !== TRUE) {
        die("Cannot create ZIP file");
    }

    $processedCount = 0;
    foreach ($patients as $p) {
        $person_id = $p['id'];
        
        // Generuj obsah
        $reportData = generateReportContent($person_id, $conn);
        if (!$reportData) continue;
        
        // Vytvoř složku pro pacienta (název složky = příjmení_jméno)
        $folderName = $p['surname'] . "_" . $p['first_name'] . "/";
        
        // TXT soubor do složky pacienta
        $filename = $folderName . "lekarske_zpravy.txt";
        $zip->addFromString($filename, $reportData['content']);

        // Přidej obrázek s klíšťaty do složky pacienta (pokud existují klíšťata)
        if ($reportData['has_bites']) {
            $imgPath = sys_get_temp_dir() . "/kliste_" . $p['id'] . ".png";
            if (generateKlisteImage($person_id, $conn, $imgPath)) {
                $imgName = $folderName . "mapa_klistat.png";
                $zip->addFile($imgPath, $imgName);
                // Smazání bude provedeno až po zavření ZIP
                register_shutdown_function(function() use ($imgPath) {
                    if (file_exists($imgPath)) {
                        unlink($imgPath);
                    }
                });
            }
        }
        
        // Přidej informační soubor do složky pacienta
        $infoContent = "INFORMACE O PACIENTOVI\n";
        $infoContent .= str_repeat("=", 30) . "\n";
        $infoContent .= "Jméno: " . $p['first_name'] . "\n";
        $infoContent .= "Příjmení: " . $p['surname'] . "\n";
        $infoContent .= "ID pacienta: " . $p['id'] . "\n";
        $infoContent .= "Datum exportu: " . date('Y-m-d H:i:s') . "\n";
        $infoContent .= str_repeat("=", 30) . "\n\n";
        $infoContent .= "OBSAH SLOŽKY:\n";
        $infoContent .= "- lekarske_zpravy.txt - kompletní lékařské zprávy a seznam klíšťat\n";
        if ($reportData['has_bites']) {
            $infoContent .= "- mapa_klistat.png - vizuální mapa klíšťat na těle\n";
        }
        $infoContent .= "- info.txt - tento informační soubor\n";
        
        $zip->addFromString($folderName . "info.txt", $infoContent);
        
        $processedCount++;
    }

    // Přidej souhrnný soubor do root složky
    $summaryContent = "SOUHRN VŠECH PACIENTŮ\n";
    $summaryContent .= str_repeat("=", 40) . "\n";
    $summaryContent .= "Celkem pacientů: " . count($patients) . "\n";
    $summaryContent .= "Zpracováno: " . $processedCount . "\n";
    $summaryContent .= "Vygenerováno: " . date('Y-m-d H:i:s') . "\n";
    $summaryContent .= str_repeat("=", 40) . "\n\n";
    $summaryContent .= "STRUKTURA ARCHIVU:\n";
    $summaryContent .= "Každý pacient má vlastní složku pojmenovanou 'Příjmení_Jméno'\n";
    $summaryContent .= "obsahující:\n";
    $summaryContent .= "  - lekarske_zpravy.txt (kompletní data)\n";
    $summaryContent .= "  - mapa_klistat.png (pokud má klíšťata)\n";
    $summaryContent .= "  - info.txt (informace o pacientovi)\n\n";
    $summaryContent .= "SEZNAM PACIENTŮ:\n";
    $summaryContent .= str_repeat("-", 40) . "\n";
    
    foreach ($patients as $p) {
        $summaryContent .= "📁 " . $p['surname'] . "_" . $p['first_name'] . "/ (ID: " . $p['id'] . ")\n";
    }
    
    $zip->addFromString("_SOUHRN_VSECH_PACIENTU.txt", $summaryContent);
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="all_medical_reports_' . date('Y-m-d_H-i-s') . '.zip"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    if (ob_get_level()) {
        ob_end_clean();
    }
    flush();
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

// BLOK PRO JEDNOHO PACIENTA
if (isset($_GET['person_id']) && is_numeric($_GET['person_id'])) {
    $person_id = intval($_GET['person_id']);
    
    // Generuj obsah
    $reportData = generateReportContent($person_id, $conn);
    if (!$reportData) {
        die("Pacient nebyl nalezen.");
    }

    $folderName = $reportData['person']['surname'] . "_" . $reportData['person']['first_name'] . "/";
    $imgPath = sys_get_temp_dir() . "/kliste_" . $person_id . ".png";

    // Vygeneruj obrázek s klíšťaty
    $hasImage = false;
    if ($reportData['has_bites']) {
        $hasImage = generateKlisteImage($person_id, $conn, $imgPath);
    }

    // Vytvoř ZIP
    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'report_zip_');
    
    if ($zip->open($tmpFile, ZipArchive::CREATE) !== TRUE) {
        die("Cannot create ZIP file");
    }
    
    // Přidej soubory do složky pacienta
    $zip->addFromString($folderName . "lekarske_zpravy.txt", $reportData['content']);
    
    if ($hasImage && file_exists($imgPath)) {
        $zip->addFile($imgPath, $folderName . "mapa_klistat.png");
    }
    
    // Přidej informační soubor
    $infoContent = "INFORMACE O PACIENTOVI\n";
    $infoContent .= str_repeat("=", 30) . "\n";
    $infoContent .= "Jméno: " . $reportData['person']['first_name'] . "\n";
    $infoContent .= "Příjmení: " . $reportData['person']['surname'] . "\n";
    $infoContent .= "ID pacienta: " . $person_id . "\n";
    $infoContent .= "Datum exportu: " . date('Y-m-d H:i:s') . "\n";
    $infoContent .= str_repeat("=", 30) . "\n\n";
    $infoContent .= "OBSAH SLOŽKY:\n";
    $infoContent .= "- lekarske_zpravy.txt - kompletní lékařské zprávy a seznam klíšťat\n";
    if ($hasImage) {
        $infoContent .= "- mapa_klistat.png - vizuální mapa klíšťat na těle\n";
    }
    $infoContent .= "- info.txt - tento informační soubor\n";
    
    $zip->addFromString($folderName . "info.txt", $infoContent);
    
    $zip->close();

    // Smazání dočasného obrázku
    if ($hasImage && file_exists($imgPath)) {
        unlink($imgPath);
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="report_' . $reportData['person']['surname'] . '_' . $reportData['person']['first_name'] . '_' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    if (ob_get_level()) {
        ob_end_clean();
    }
    flush();
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Stáhnout lékařské zprávy</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 600px; margin: 160px auto; padding: 30px; border: 1px solid #ddd; border-radius: 8px; }
        select, button { padding: 8px 12px; margin: 10px 0; }
        .download-section { margin-bottom: 30px; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        .download-section h3 { margin-top: 0; color: #333; }
        .button-primary { background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .button-secondary { background: #e67e22; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .button-primary:hover { background: #2980b9; }
        .button-secondary:hover { background: #d35400; }
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
        <h2>Stáhnout lékařské zprávy</h2>
        
        <div class="download-section">
            <h3>Jednotlivý pacient</h3>
            <p>Stáhněte kompletní zprávy pro vybraného pacienta (TXT soubor + obrázek s klíšťaty, pokud existují).</p>
            <form method="get">
                <label for="person_id">Vyberte pacienta:</label><br>
                <select name="person_id" id="person_id" required style="width: 100%; max-width: 400px;">
                    <option value="">-- Vyberte pacienta --</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['id']); ?>">
                            <?php echo htmlspecialchars($p['surname'] . " " . $p['first_name'] . " (ID: " . $p['id'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>
                <button type="submit" class="button-primary">Stáhnout ZIP pro pacienta</button>
            </form>
        </div>
        
        <div class="download-section">
            <h3>Všichni pacienti</h3>
            <p>Stáhněte zprávy pro všechny pacienty v jednom ZIP archívu. Obsahuje TXT soubory s detaily, obrázky s klíšťaty a souhrnný přehled.</p>
            <form method="get">
                <input type="hidden" name="download_all" value="1">
                <button type="submit" class="button-secondary">Stáhnout ZIP všech pacientů</button>
            </form>
            <p><small>Celkem pacientů v databázi: <strong><?php echo count($patients); ?></strong></small></p>
        </div>
    </div>

    <script>
        function toggleMenu() {
            var navbar = document.getElementById("navbar");
            if (navbar.style.display === "block") {
                navbar.style.display = "none";
            } else {
                navbar.style.display = "block";
            }
        }
    </script>
</body>
</html>