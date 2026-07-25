<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Funkce pro vyčištění HTML pro PhpWord
function cleanHtmlForPhpWord($html) {
    if (empty(trim($html))) return '';
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8"><html><body><div>' . $html . '</div></body></html>', LIBXML_HTML_NODEFDTD);
    $body = $dom->getElementsByTagName('body')->item(0);
    $cleanHtml = '';
    if ($body) {
        foreach ($body->childNodes as $child) {
            $cleanHtml .= $dom->saveHTML($child);
        }
    }
    libxml_clear_errors();
    if (empty(trim(strip_tags($cleanHtml)))) return strip_tags($html);
    return trim($cleanHtml);
}

// Generování obrázku těla
function generateBodyPointsImage($points, $outputPath) {
    if (empty($points)) return false;
    $baseImagePath = __DIR__ . '/body.jpg';
    if (!file_exists($baseImagePath)) return false;

    $im = imagecreatefromjpeg($baseImagePath);
    if (!$im) return false;

    $width = imagesx($im);
    $height = imagesy($im);
    $green = imagecolorallocate($im, 56, 142, 60);
    $white = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 0, 0, 0);

    foreach ($points as $p) {
        $px = intval($p['x'] * $width / 100);
        $py = intval($p['y'] * $height / 100);
        imagefilledellipse($im, $px, $py, 20, 20, $green);
        $order = isset($p['order']) ? $p['order'] : '';
        imagestring($im, 5, $px-6, $py-8, $order, $black);
        imagestring($im, 5, $px-7, $py-9, $order, $white);
    }
    $success = imagepng($im, $outputPath);
    imagedestroy($im);
    return $success;
}

// Generování obrázku klíšťat
function generateKlisteImage($ticks, $outputPath) {
    if (empty($ticks)) return false;
    $baseImagePath = __DIR__ . '/body.jpg';
    if (!file_exists($baseImagePath)) return false;

    $im = imagecreatefromjpeg($baseImagePath);
    if (!$im) return false;

    $width = imagesx($im);
    $height = imagesy($im);
    $red = imagecolorallocate($im, 255, 0, 0);
    $white = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 0, 0, 0);

    foreach ($ticks as $t) {
        $px = intval($t['x'] * $width);
        $py = intval($t['y'] * $height);
        imagefilledellipse($im, $px, $py, 16, 16, $red);
        if (isset($t['bite_order'])) {
            $text = $t['bite_order'];
            imagestring($im, 5, $px-6, $py-8, $text, $black);
            imagestring($im, 5, $px-7, $py-9, $text, $white);
        }
    }
    $success = imagepng($im, $outputPath);
    imagedestroy($im);
    return $success;
}

// Generování DOCX bez DB dotazů uvnitř
function generateDocxReport($person, $reports, $bodyPointsByNote, $ticks, $phpWord) {
    $section = $phpWord->addSection();
    $section->addText('ZDRAVOTNICKÉ ZPRÁVY - ' . mb_strtoupper($person['first_name'] . ' ' . $person['surname'], 'UTF-8'), ['bold' => true, 'size' => 16]);
    $section->addTextBreak();

    $reportCount = 0;
    foreach ($reports as $row) {
        $reportCount++;
        if ($reportCount > 1) {
            $section->addPageBreak();
        }
        $section->addTextBreak();

        if (!empty($row['report_text'])) {
            $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);
            libxml_use_internal_errors(true);
            try {
                $cleanText = html_entity_decode($row['report_text'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cleanText = str_replace("\xEF\xBB\xBF", '', $cleanText);
                
                // VŽDY vyčistit přes DOMDocument, protože addHtml mlčky vytvoří poškozený OOXML pokud HTML není striktní XML (např. neuzavřené tagy)
                $betterHtml = cleanHtmlForPhpWord($cleanText);
                if (!empty($betterHtml)) {
                    \PhpOffice\PhpWord\Shared\Html::addHtml($section, $betterHtml, false, false);
                } else {
                    $plainText = strip_tags($cleanText);
                    foreach (explode("\n", $plainText) as $line) {
                        if (!empty(trim($line))) $section->addText(trim($line));
                        else $section->addTextBreak();
                    }
                }
            } catch (Exception $e) {
                $plainText = strip_tags($row['report_text']);
                foreach (explode("\n", $plainText) as $line) {
                    if (!empty(trim($line))) $section->addText(trim($line));
                    else $section->addTextBreak();
                }
            } finally {
                libxml_clear_errors();
                error_reporting($oldErrorReporting);
            }
        } else {
            $section->addText('Žádný text zprávy.', ['italic' => true]);
        }
        $section->addTextBreak();

        // Schéma těla pro danou zprávu
        if (isset($row['diagnosis_note_id']) && !empty($bodyPointsByNote[$row['diagnosis_note_id']])) {
            $note_id = $row['diagnosis_note_id'];
            $points = $bodyPointsByNote[$note_id];
            $diagnosisName = $row['diagnosis'] ? $row['diagnosis'] : 'Neznámá diagnóza';
            
            $imgPath = sys_get_temp_dir() . "/body_points_" . $note_id . "_" . uniqid('', true) . ".png";
            if (generateBodyPointsImage($points, $imgPath)) {
                $section->addText('Schéma těla s body (' . $diagnosisName . '):', ['bold' => true]);
                $section->addImage($imgPath, ['width' => 400]);
                register_shutdown_function(function() use ($imgPath) {
                    // if (file_exists($imgPath)) unlink($imgPath);
                });
            }

            $section->addText('Tabulka bodnutí a poznámek (' . $diagnosisName . '):', ['bold' => true]);
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50]);
            $table->addRow();
            $table->addCell(800)->addText('Pořadí', ['bold' => true]);
            $table->addCell(4600)->addText('Poznámka', ['bold' => true]);
            foreach ($points as $bp) {
                $table->addRow();
                $table->addCell(800)->addText((string)($bp['order'] ?? ''));
                $table->addCell(4600)->addText((string)($bp['description'] ?? ''));
            }
            $section->addTextBreak(2);
        }
    }

    if ($reportCount === 0) {
        $section->addText('Pro tohoto pacienta nebyly nalezeny žádné zdravotnické zprávy.', ['italic' => true]);
    }

    // Klíšťata
    $section->addPageBreak();
    $section->addText('MAPA KLÍŠŤAT:', ['bold' => true, 'size' => 14]);
    $section->addTextBreak();

    $imgPath = sys_get_temp_dir() . "/kliste_" . $person['id'] . "_" . uniqid('', true) . ".png";
    if (generateKlisteImage($ticks, $imgPath)) {
        $section->addImage($imgPath, ['width' => 400]);
        register_shutdown_function(function() use ($imgPath) {
            // if (file_exists($imgPath)) unlink($imgPath);
        });
    } else {
        $section->addText('Žádná klíšťata nebyla zaznamenána.');
    }

    $section->addTextBreak(2);
    $section->addText('TABULKA KLÍŠŤAT:', ['bold' => true, 'size' => 14]);
    $section->addTextBreak();

    if (count($ticks) > 0) {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50]);
        $table->addRow();
        $table->addCell(1200)->addText('Pořadí', ['bold' => true]);
        $table->addCell(2000)->addText('Datum přidání', ['bold' => true]);
        $table->addCell(2000)->addText('Přidal', ['bold' => true]);

        foreach ($ticks as $t) {
            $added_by = 'Neznámý';
            if (!empty($t['firstname']) || !empty($t['lastname'])) {
                $added_by = trim(($t['firstname'] ?? '') . ' ' . ($t['lastname'] ?? ''));
            } elseif (!empty($t['updated_by'])) {
                $added_by = 'ID: ' . $t['updated_by'];
            }
            $table->addRow();
            $table->addCell(1200)->addText((string)($t['bite_order'] ?? ''));
            $table->addCell(2000)->addText((string)($t['created_at'] ?? ''));
            $table->addCell(2000)->addText((string)$added_by);
        }
    } else {
        $section->addText('Žádná klíšťata nebyla zaznamenána.');
    }
}


// HLAVNÍ LOGIKA
$patients = [];
$result = $conn->query("SELECT * FROM persons ORDER BY surname, first_name");
while ($row = $result->fetch_assoc()) {
    $patients[$row['id']] = $row;
}

if (isset($_GET['download_all']) && $_GET['download_all'] == '1') {
    // PRELOAD ALL DATA
    $allReports = [];
    $res = $conn->query("SELECT mr.person_id, mr.created_at, mr.report_text, d.name AS diagnosis, mr.diagnosis_note_id FROM medical_reports mr LEFT JOIN diagnoses d ON mr.diagnosis_id = d.id ORDER BY mr.created_at ASC");
    while ($row = $res->fetch_assoc()) {
        $allReports[$row['person_id']][] = $row;
    }

    $allBodyPoints = [];
    $res = $conn->query("SELECT diagnosis_note_id, x, y, `order`, description FROM person_body_points ORDER BY `order` ASC");
    while ($row = $res->fetch_assoc()) {
        $allBodyPoints[$row['diagnosis_note_id']][] = $row;
    }

    $allTicks = [];
    $res = $conn->query("SELECT tb.person_id, tb.bite_order, tb.created_at, tb.x, tb.y, tb.updated_by, u.firstname, u.lastname FROM tick_bites tb LEFT JOIN users u ON tb.updated_by = u.id ORDER BY tb.bite_order ASC");
    if (!$res) $res = $conn->query("SELECT person_id, bite_order, created_at, x, y, updated_by, '' as firstname, '' as lastname FROM tick_bites ORDER BY bite_order ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $allTicks[$row['person_id']][] = $row;
        }
    }

    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'reports_zip_');
    if ($zip->open($tmpFile, ZipArchive::CREATE) !== TRUE) die("Cannot create ZIP file");

    // 1. Vygenerování samostatných zpráv
    foreach ($patients as $pid => $p) {
        $reports = $allReports[$pid] ?? [];
        $ticks = $allTicks[$pid] ?? [];
        
        $phpWord = new PhpWord();
        generateDocxReport($p, $reports, $allBodyPoints, $ticks, $phpWord);
        
        $docxPath = sys_get_temp_dir() . "/report_" . $pid . ".docx";
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($docxPath);

        $folderName = $p['surname'] . "_" . $p['first_name'] . "/";
        $zip->addFile($docxPath, $folderName . "lekarska_zprava.docx");

        $imgPath = sys_get_temp_dir() . "/kliste_tmp_" . $pid . ".png";
        if (generateKlisteImage($ticks, $imgPath)) {
            $zip->addFile($imgPath, $folderName . "mapa_klistat.png");
        }
    }

    // 2. Vygenerování souhrnné zprávy
    $summaryPhpWord = new PhpWord();
    foreach ($patients as $pid => $p) {
        $reports = $allReports[$pid] ?? [];
        $ticks = $allTicks[$pid] ?? [];
        generateDocxReport($p, $reports, $allBodyPoints, $ticks, $summaryPhpWord); 
    }
    
    $summaryDocxPath = sys_get_temp_dir() . "/vsechny_zpravy.docx";
    $summaryWriter = IOFactory::createWriter($summaryPhpWord, 'Word2007');
    $summaryWriter->save($summaryDocxPath);
    $zip->addFile($summaryDocxPath, "vsechny_zpravy.docx");

    $summaryContent = "SOUHRN VŠECH PACIENTŮ\n========================================\nCelkem pacientů: " . count($patients) . "\nVygenerováno: " . date('Y-m-d H:i:s') . "\n";
    $zip->addFromString("_SOUHRN_VSECH_PACIENTU.txt", $summaryContent);
    $zip->close();

    // Cleanup po uzavření ZIPu
    if (file_exists($summaryDocxPath)) unlink($summaryDocxPath);
    foreach ($patients as $pid => $p) {
        $docxPath = sys_get_temp_dir() . "/report_" . $pid . ".docx";
        $imgPath = sys_get_temp_dir() . "/kliste_tmp_" . $pid . ".png";
        if (file_exists($docxPath)) unlink($docxPath);
        if (file_exists($imgPath)) unlink($imgPath);
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="all_medical_reports_' . date('Y-m-d_H-i-s') . '.zip"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

if (isset($_GET['person_id']) && is_numeric($_GET['person_id'])) {
    $pid = intval($_GET['person_id']);
    if (!isset($patients[$pid])) die("Pacient nebyl nalezen.");
    $p = $patients[$pid];

    $reports = [];
    $res = $conn->query("SELECT mr.created_at, mr.report_text, d.name AS diagnosis, mr.diagnosis_note_id FROM medical_reports mr LEFT JOIN diagnoses d ON mr.diagnosis_id = d.id WHERE mr.person_id = $pid ORDER BY mr.created_at ASC");
    while ($row = $res->fetch_assoc()) $reports[] = $row;

    $bodyPoints = [];
    $res = $conn->query("SELECT pbp.diagnosis_note_id, pbp.x, pbp.y, pbp.`order`, pbp.description FROM person_body_points pbp JOIN medical_reports mr ON pbp.diagnosis_note_id = mr.diagnosis_note_id WHERE mr.person_id = $pid ORDER BY pbp.`order` ASC");
    while ($row = $res->fetch_assoc()) $bodyPoints[$row['diagnosis_note_id']][] = $row;

    $ticks = [];
    $res = $conn->query("SELECT tb.bite_order, tb.created_at, tb.x, tb.y, tb.updated_by, u.firstname, u.lastname FROM tick_bites tb LEFT JOIN users u ON tb.updated_by = u.id WHERE tb.person_id = $pid ORDER BY tb.bite_order ASC");
    if (!$res) $res = $conn->query("SELECT bite_order, created_at, x, y, updated_by, '' as firstname, '' as lastname FROM tick_bites WHERE person_id = $pid ORDER BY bite_order ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) $ticks[] = $row;
    }

    $phpWord = new PhpWord();
    generateDocxReport($p, $reports, $bodyPoints, $ticks, $phpWord);

    $docxPath = sys_get_temp_dir() . "/report_" . $pid . ".docx";
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($docxPath);

    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'report_zip_');
    if ($zip->open($tmpFile, ZipArchive::CREATE) !== TRUE) die("Cannot create ZIP file");

    $folderName = $p['surname'] . "_" . $p['first_name'] . "/";
    $zip->addFile($docxPath, $folderName . "lekarska_zprava.docx");

    $imgPath = sys_get_temp_dir() . "/kliste_tmp_" . $pid . ".png";
    $hasImage = generateKlisteImage($ticks, $imgPath);
    if ($hasImage) $zip->addFile($imgPath, $folderName . "mapa_klistat.png");

    $infoContent = "INFORMACE O PACIENTOVI\n==============================\nJméno: {$p['first_name']}\nPříjmení: {$p['surname']}\nID: $pid\nExport: " . date('Y-m-d H:i:s') . "\n";
    $zip->addFromString($folderName . "info.txt", $infoContent);
    $zip->close();

    if (file_exists($docxPath)) unlink($docxPath);
    if ($hasImage && file_exists($imgPath)) unlink($imgPath);

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="report_' . $p['surname'] . '_' . $p['first_name'] . '_' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

// Zobrazení HTML (pohledu)
$total_patients = count($patients);
$res = $conn->query("SELECT COUNT(DISTINCT person_id) as count FROM tick_bites");
$patients_with_ticks = $res ? $res->fetch_assoc()['count'] : 0;
$res = $conn->query("SELECT COUNT(*) as count FROM medical_reports");
$total_reports = $res ? $res->fetch_assoc()['count'] : 0;
$conn->close();

require __DIR__ . '/views/download_reports.view.php';
?>