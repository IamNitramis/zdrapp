<?php
session_start(); // Spustí session, aby bylo možné kontrolovat přihlášení
require_once __DIR__ . '/vendor/autoload.php';
    use PhpOffice\PhpWord\PhpWord;
    use PhpOffice\PhpWord\IOFactory;
// Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přístup zamítnut</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
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
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
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
            color: #e67e22;
            margin-bottom: 20px;
        }
        .login-warning h2 { 
            color: #2d3748; 
            margin-bottom: 15px; 
            font-size: 1.8rem;
            font-weight: 600;
        }
        .login-warning p {
            color: #718096;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .login-warning a {
            display: inline-block;
            padding: 15px 35px;
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .login-warning a:hover { 
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-warning">
        <i class="fas fa-lock"></i>
        <h2>Přístup zamítnut</h2>
        <p>Pro zobrazení této stránky se musíte přihlásit.</p>
        <a href="login.php">
            <i class="fas fa-sign-in-alt"></i>
            Přihlásit se
        </a>
    </div>
</body>
</html>
<?php exit; endif; ?>

<?php
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

// Nejdřív přidejte novou funkci pro generování DOCX:
function generateDocxReport($person_id, $conn, $phpWord) {
    // Získání dat pacienta
    $personSql = "SELECT first_name, surname FROM persons WHERE id = ?";
    $personStmt = $conn->prepare($personSql);
    $personStmt->bind_param("i", $person_id);
    $personStmt->execute();
    $personResult = $personStmt->get_result();
    $person = $personResult->fetch_assoc();
    $personStmt->close();
    
    if (!$person) return null;

    // Vytvoření nové sekce
    $section = $phpWord->addSection();
    
    // Nadpis
    $section->addText(
        'LÉKAŘSKÉ ZPRÁVY - ' . strtoupper($person['first_name'] . ' ' . $person['surname']),
        ['bold' => true, 'size' => 16]
    );
    $section->addTextBreak();

    // Získání lékařských zpráv
    $sql = "SELECT mr.created_at, mr.report_text, d.name AS diagnosis 
            FROM medical_reports mr
            LEFT JOIN diagnoses d ON mr.diagnosis_id = d.id
            WHERE mr.person_id = ?
            ORDER BY mr.created_at ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $person_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Přidání zpráv
    $section->addText('LÉKAŘSKÉ ZPRÁVY:', ['bold' => true, 'size' => 14]);
    $section->addTextBreak();

    while ($row = $result->fetch_assoc()) {
        $section->addText('Datum: ' . $row['created_at'], ['bold' => true]);
        $section->addText('Diagnóza: ' . ($row['diagnosis'] ?? 'Nezadána'), ['bold' => true]);
        $section->addTextBreak();
        
        // Přidání textu zprávy (může obsahovat HTML)
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $row['report_text']);
        $section->addTextBreak();
    }
    $stmt->close();

    // Přidání obrázku klíšťat
    $section->addPageBreak();
    $section->addText('MAPA KLÍŠŤAT:', ['bold' => true, 'size' => 14]);
    $section->addTextBreak();

    // Generování a přidání obrázku
    $imgPath = sys_get_temp_dir() . "/kliste_" . $person_id . ".png";
    if (generateKlisteImage($person_id, $conn, $imgPath)) {
        $section->addImage($imgPath, ['width' => 400]);
        register_shutdown_function(function() use ($imgPath) {
            if (file_exists($imgPath)) unlink($imgPath);
        });
    } else {
        $section->addText('Žádná klíšťata nebyla zaznamenána.');
    }

    // Přidání tabulky klíšťat
    $section->addTextBreak(2);
    $section->addText('TABULKA KLÍŠŤAT:', ['bold' => true, 'size' => 14]);
    $section->addTextBreak();

    $klisteSql = "SELECT bite_order, created_at, x, y FROM tick_bites WHERE person_id = ? ORDER BY bite_order ASC";
    $klisteStmt = $conn->prepare($klisteSql);
    $klisteStmt->bind_param("i", $person_id);
    $klisteStmt->execute();
    $klisteResult = $klisteStmt->get_result();

    if ($klisteResult->num_rows > 0) {
        $table = $section->addTable(['borderSize' => 1]);
        $table->addRow();
        $table->addCell(1500)->addText('Pořadí', ['bold' => true]);
        $table->addCell(2500)->addText('Datum přidání', ['bold' => true]);
        $table->addCell(1500)->addText('X pozice', ['bold' => true]);
        $table->addCell(1500)->addText('Y pozice', ['bold' => true]);

        while ($k = $klisteResult->fetch_assoc()) {
            $table->addRow();
            $table->addCell(1500)->addText($k['bite_order']);
            $table->addCell(2500)->addText($k['created_at']);
            $table->addCell(1500)->addText(number_format($k['x'], 3));
            $table->addCell(1500)->addText(number_format($k['y'], 3));
        }
    }
    $klisteStmt->close();

    return [
        'person' => $person,
        'section' => $section
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
        $folderName = $p['surname'] . "_" . $p['first_name'] . "/";
        
        // Vytvoř DOCX pro pacienta
        $phpWord = new PhpWord();
        $reportData = generateDocxReport($person_id, $conn, $phpWord);
        if (!$reportData) continue;
        
        // Ulož DOCX
        $docxPath = sys_get_temp_dir() . "/report_" . $p['id'] . ".docx";
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($docxPath);
        
        // Přidej DOCX do ZIPu
        $zip->addFile($docxPath, $folderName . "lekarska_zprava.docx");
        
        // Generuj a přidej obrázek klíšťat
        $imgPath = sys_get_temp_dir() . "/kliste_" . $person_id . ".png";
        $hasImage = generateKlisteImage($person_id, $conn, $imgPath);
        if ($hasImage && file_exists($imgPath)) {
            $zip->addFile($imgPath, $folderName . "mapa_klistat.png");
        }
        
        // Registruj smazání dočasných souborů
        register_shutdown_function(function() use ($docxPath, $imgPath) {
            if (file_exists($docxPath)) unlink($docxPath);
            if (file_exists($imgPath)) unlink($imgPath);
        });
        
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

// Export do DOCX
if (isset($_GET['export_docx']) && isset($_GET['person_id']) && is_numeric($_GET['person_id'])) {
    $person_id = intval($_GET['person_id']);
    
    $phpWord = new PhpWord();
    $reportData = generateDocxReport($person_id, $conn, $phpWord);
    
    if (!$reportData) {
        die("Pacient nebyl nalezen.");
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment;filename="report_' . $reportData['person']['surname'] . '_' . $reportData['person']['first_name'] . '.docx"');
    header('Cache-Control: max-age=0');
    
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit;
}

// Statistiky
$total_patients = count($patients);

// Získání počtu pacientů s klíšťaty
$patients_with_ticks_result = $conn->query("SELECT COUNT(DISTINCT person_id) as count FROM tick_bites");
$patients_with_ticks = $patients_with_ticks_result->fetch_assoc()['count'];

// Získání celkového počtu lékařských zpráv
$total_reports_result = $conn->query("SELECT COUNT(*) as count FROM medical_reports");
$total_reports = $total_reports_result->fetch_assoc()['count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stáhnout lékařské zprávy</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
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

        .navbar a.active {
            background: rgba(255, 255, 255, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin: 0;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 10px;
        }

        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            flex: 1;
            min-width: 200px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #388e3c;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .download-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .download-section:hover {
            transform: translateY(-2px);
        }

        .download-section h3 {
            color: #2d3748;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .download-section h3 i {
            color: #388e3c;
        }

        .download-section p {
            color: #718096;
            margin-bottom: 20px;
            line-height: 1.6;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #388e3c;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 140px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(230, 126, 34, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(230, 126, 34, 0.4);
        }

        .patient-count {
            background:rgb(108, 165, 111);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(116, 185, 255, 0.3);
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

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }

            .header-container {
                flex-direction: column;
                align-items: flex-start;
                padding: 0 10px;
            }

            .navbar {
                flex-direction: column;
                gap: 10px;
                width: 100%;
                margin-top: 10px;
            }

            .stat-card {
                min-width: unset;
                padding: 15px;
            }

            .download-section {
                padding: 15px;
            }
        }
    </style>
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
                <a href="download_reports.php" class="active">
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
            <h1><i class="fas fa-download"></i> Stáhnout lékařské zprávy</h1>
            <div class="subtitle">Exportujte kompletní lékařské zprávy a mapy klíšťat všech pacientů včetně statistik.</div>
            <div class="patient-count">
                Celkem pacientů: <?php echo $total_patients; ?> | 
                S klíšťaty: <?php echo $patients_with_ticks; ?> | 
                Lékařských zpráv: <?php echo $total_reports; ?>
            </div>
        </div>

        <div class="download-section">
            <h3><i class="fas fa-file-archive"></i> Hromadné stažení</h3>
            <p>Stáhněte ZIP archiv se všemi pacienty, jejich zprávami a mapami klíšťat.</p>
            <a href="download_reports.php?download_all=1" class="btn btn-primary">
                <i class="fas fa-download"></i> Stáhnout vše (ZIP)
            </a>
        </div>

        <div class="download-section">
            <h3><i class="fas fa-user"></i> Stažení jednoho pacienta</h3>
            <form method="get" action="download_reports.php" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <div class="form-group" style="flex:1; min-width:220px;">
                    <label for="person_id">Vyberte pacienta:</label>
                    <select name="person_id" id="person_id" class="form-control" required>
                        <option value="">-- Vyberte --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['surname'] . " " . $p['first_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary" name="export_docx" value="1">
                    <i class="fas fa-file-word"></i> Exportovat DOCX
                </button>
            </form>
        </div>
    </div>
    <script>
        function toggleMenu() {
            var navbar = document.getElementById('navbar');
            navbar.classList.toggle('active');
        }
    </script>
</body>
</html>
