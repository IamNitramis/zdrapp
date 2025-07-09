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
require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
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
    if ($stmt === false) {
        // Fallback pokud sloupec bite_order neexistuje
        $sql = "SELECT x, y FROM tick_bites WHERE person_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            // Pokud stále selže, vrať false
            return false;
        }
        $stmt->bind_param("i", $person_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $width = imagesx($im);
        $height = imagesy($im);
        $red = imagecolorallocate($im, 255, 0, 0);
        while ($row = $result->fetch_assoc()) {
            $px = intval($row['x'] * $width);
            $py = intval($row['y'] * $height);
            imagefilledellipse($im, $px, $py, 16, 16, $red);
        }
        $stmt->close();
    } else {
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
            if (isset($row['bite_order'])) {
                $text = $row['bite_order'];
                imagestring($im, 5, $px-6, $py-8, $text, $black);
                imagestring($im, 5, $px-7, $py-9, $text, $white);
            }
        }
        $stmt->close();
    }
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

    // Tabulka klíšťat - OPRAVENÝ DOTAZ
    $klisteSql = "SELECT tb.bite_order, tb.created_at, tb.x, tb.y, tb.updated_by, u.firstname, u.lastname 
                  FROM tick_bites tb
                  LEFT JOIN users u ON tb.updated_by = u.id
                  WHERE tb.person_id = ? 
                  ORDER BY tb.bite_order ASC";
    $klisteStmt = $conn->prepare($klisteSql);
    if ($klisteStmt === false) {
        // Fallback bez JOIN pokud sloupec neexistuje
        $klisteSql = "SELECT bite_order, created_at, x, y, updated_by FROM tick_bites WHERE person_id = ? ORDER BY bite_order ASC";
        $klisteStmt = $conn->prepare($klisteSql);
    }
    $klisteStmt->bind_param("i", $person_id);
    $klisteStmt->execute();
    $klisteResult = $klisteStmt->get_result();

    $content .= "\nTABULKA KLÍŠŤAT:\n";
    $content .= str_repeat("-", 80) . "\n";
    $content .= sprintf("%-8s %-20s %-10s %-10s %-20s\n", "Pořadí", "Datum přidání", "X pozice", "Y pozice", "Přidal");
    $content .= str_repeat("-", 80) . "\n";
    
    $hasBites = false;
    while ($k = $klisteResult->fetch_assoc()) {
        // OPRAVENÉ ZPRACOVÁNÍ JMÉNA UŽIVATELE
        $added_by = 'Neznámý';
        
        // Pokud existují sloupcové firstname a lastname z JOIN
        if (isset($k['firstname']) && isset($k['lastname'])) {
            $first = trim($k['firstname'] ?? '');
            $last = trim($k['lastname'] ?? '');
            
            if (!empty($first) || !empty($last)) {
                $added_by = trim($first . ' ' . $last);
            } elseif (isset($k['updated_by']) && $k['updated_by'] !== null) {
                $added_by = 'ID: ' . $k['updated_by'];
            }
        } elseif (isset($k['updated_by']) && $k['updated_by'] !== null) {
            $added_by = 'ID: ' . $k['updated_by'];
        }
        
        $content .= sprintf("%-8s %-20s %-10.3f %-10.3f %-20s\n", 
            $k['bite_order'], 
            $k['created_at'], 
            $k['x'], 
            $k['y'],
            $added_by
        );
        $hasBites = true;
    }
    
    if (!$hasBites) {
        $content .= "Žádná klíšťata nebyla zaznamenána.\n";
    }
    
    $content .= str_repeat("-", 80) . "\n\n";
    $klisteStmt->close();

    // Statistiky
    $content .= "STATISTIKY:\n";
    $content .= str_repeat("-", 30) . "\n";
    $content .= "Počet lékařských zpráv: " . count($reports) . "\n";
    
    // Opravený počet klíšťat
    $tickCountSql = "SELECT COUNT(*) as tick_count FROM tick_bites WHERE person_id = ?";
    $tickCountStmt = $conn->prepare($tickCountSql);
    $tickCountStmt->bind_param("i", $person_id);
    $tickCountStmt->execute();
    $tickCountResult = $tickCountStmt->get_result();
    $tickCount = $tickCountResult->fetch_assoc()['tick_count'];
    $tickCountStmt->close();
    
    $content .= "Počet zaznamenaných klíšťat: " . $tickCount . "\n";
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
        'LÉKAŘSKÉ ZPRÁVY - ' . mb_strtoupper($person['first_name'] . ' ' . $person['surname'], 'UTF-8'),
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

    $first = true;
    while ($row = $result->fetch_assoc()) {
        if (!$first) {
            $section->addPageBreak();
        }
        $first = false;
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

    // OPRAVENÝ DOTAZ PRO DOCX
    $klisteSql = "SELECT tb.bite_order, tb.created_at, tb.x, tb.y, tb.updated_by, u.firstname, u.lastname 
                  FROM tick_bites tb
                  LEFT JOIN users u ON tb.updated_by = u.id
                  WHERE tb.person_id = ? 
                  ORDER BY tb.bite_order ASC";
    $klisteStmt = $conn->prepare($klisteSql);
    if ($klisteStmt === false) {
        // Fallback bez JOIN pokud sloupec neexistuje
        $klisteSql = "SELECT bite_order, created_at, x, y, updated_by FROM tick_bites WHERE person_id = ? ORDER BY bite_order ASC";
        $klisteStmt = $conn->prepare($klisteSql);
    }
    $klisteStmt->bind_param("i", $person_id);
    $klisteStmt->execute();
    $klisteResult = $klisteStmt->get_result();

    if ($klisteResult->num_rows > 0) {
        $table = $section->addTable(['borderSize' => 1]);
        $table->addRow();
        $table->addCell(1200)->addText('Pořadí', ['bold' => true]);
        $table->addCell(2000)->addText('Datum přidání', ['bold' => true]);
        $table->addCell(1200)->addText('X pozice', ['bold' => true]);
        $table->addCell(1200)->addText('Y pozice', ['bold' => true]);
        $table->addCell(2000)->addText('Přidal', ['bold' => true]);

        while ($k = $klisteResult->fetch_assoc()) {
            // OPRAVENÉ ZPRACOVÁNÍ JMÉNA UŽIVATELE PRO DOCX
            $added_by = 'Neznámý';
            
            if (isset($k['firstname']) && isset($k['lastname'])) {
                $first = trim($k['firstname'] ?? '');
                $last = trim($k['lastname'] ?? '');
                
                if (!empty($first) || !empty($last)) {
                    $added_by = trim($first . ' ' . $last);
                } elseif (isset($k['updated_by']) && $k['updated_by'] !== null) {
                    $added_by = 'ID: ' . $k['updated_by'];
                }
            } elseif (isset($k['updated_by']) && $k['updated_by'] !== null) {
                $added_by = 'ID: ' . $k['updated_by'];
            }
            
            $table->addRow();
            $table->addCell(1200)->addText($k['bite_order']);
            $table->addCell(2000)->addText($k['created_at']);
            $table->addCell(1200)->addText(number_format($k['x'], 3));
            $table->addCell(1200)->addText(number_format($k['y'], 3));
            $table->addCell(2000)->addText($added_by);
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
    // Nový PhpWord pro souhrnný DOCX
    $summaryPhpWord = new PhpWord();
    $summaryPatients = [];
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
        
        // Přidej do pole pro souhrnný DOCX
        $summaryPatients[] = array(
            'surname' => $p['surname'],
            'first_name' => $p['first_name'],
            'person_id' => $person_id
        );
        $processedCount++;
    }

    // Seřaď pacienty abecedně podle příjmení a jména
    usort($summaryPatients, function($a, $b) {
        $cmp = strcasecmp($a['surname'], $b['surname']);
        if ($cmp === 0) return strcasecmp($a['first_name'], $b['first_name']);
        return $cmp;
    });

    // Vytvoř souhrnný DOCX
    foreach ($summaryPatients as $sp) {
        $person_id = $sp['person_id'];
        generateDocxReport($person_id, $conn, $summaryPhpWord);
        // Oddělte sekce stránkovým zlomem (PhpWord přidává sekce automaticky)
    }
    $summaryDocxPath = sys_get_temp_dir() . "/vsechny_zpravy.docx";
    $summaryWriter = IOFactory::createWriter($summaryPhpWord, 'Word2007');
    $summaryWriter->save($summaryDocxPath);
    $zip->addFile($summaryDocxPath, "vsechny_zpravy.docx");
    register_shutdown_function(function() use ($summaryDocxPath) {
        if (file_exists($summaryDocxPath)) unlink($summaryDocxPath);
    });

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
    
    // Generuj obsah TXT (pro info a případné legacy použití)
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

    // Vytvoř DOCX report
    $phpWord = new PhpWord();
    $docxData = generateDocxReport($person_id, $conn, $phpWord);
    if (!$docxData) {
        die("Pacient nebyl nalezen.");
    }
    $docxPath = sys_get_temp_dir() . "/report_" . $person_id . ".docx";
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($docxPath);

    // Vytvoř ZIP
    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'report_zip_');
    
    if ($zip->open($tmpFile, ZipArchive::CREATE) !== TRUE) {
        die("Cannot create ZIP file");
    }

    // Přidej DOCX do ZIPu
    $zip->addFile($docxPath, $folderName . "lekarska_zprava.docx");

    // Přidej obrázek pokud existuje
    if ($hasImage && file_exists($imgPath)) {
        $zip->addFile($imgPath, $folderName . "mapa_klistat.png");
    }

    // Přidej info.txt
    $infoContent = "INFORMACE O PACIENTOVI\n";
    $infoContent .= str_repeat("=", 30) . "\n";
    $infoContent .= "Jméno: " . $reportData['person']['first_name'] . "\n";
    $infoContent .= "Příjmení: " . $reportData['person']['surname'] . "\n";
    $infoContent .= "ID pacienta: " . $person_id . "\n";
    $infoContent .= "Datum exportu: " . date('Y-m-d H:i:s') . "\n";
    $infoContent .= str_repeat("=", 30) . "\n\n";
    $infoContent .= "OBSAH SLOŽKY:\n";
    $infoContent .= "- lekarska_zprava.docx - kompletní lékařská zpráva\n";
    if ($hasImage) {
        $infoContent .= "- mapa_klistat.png - vizuální mapa klíšťat na těle\n";
    }
    $infoContent .= "- info.txt - tento informační soubor\n";
    $zip->addFromString($folderName . "info.txt", $infoContent);

    $zip->close();

    // Smazání dočasných souborů
    if (file_exists($docxPath)) unlink($docxPath);
    if ($hasImage && file_exists($imgPath)) unlink($imgPath);

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
                <a href="faq.php">
                    <i class="fas fa-question-circle"></i>
                    FAQ
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
