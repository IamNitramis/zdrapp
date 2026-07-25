<?php
require_once __DIR__ . '/includes/auth.php';

// Připojení k databázi
require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
    // 1. DŮLEŽITÉ: Vynutit UTF-8 pro spojení s DB
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

$success_message = '';
$error_message = '';

// Zpracování nahrání CSV souboru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $filename = $_FILES['csv_file']['name'];
    $filesize = $_FILES['csv_file']['size'];
    $filetype = $_FILES['csv_file']['type'];
    
    // Validace souboru
    $allowed_extensions = ['csv'];
    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $max_file_size = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file_extension, $allowed_extensions)) {
        $error_message = "Pouze CSV soubory jsou povoleny.";
    } elseif ($filesize > $max_file_size) {
        $error_message = "Soubor je příliš velký. Maximální velikost je 10MB.";
    } elseif ($file && is_uploaded_file($file)) {
        try {
            $csvData = file_get_contents($file);
            
            // 2. DŮLEŽITÉ: Odstranění UTF-8 BOM hlavičky, pokud v souboru je (způsobovalo ď»ż na začátku)
            $csvData = preg_replace('/^\xEF\xBB\xBF/', '', $csvData);
            
            $lines = explode("\n", $csvData);
            $inserted_count = 0;
            $skipped_count = 0;
            
            // Připravené dotazy (Prepared Statements)
            $check_stmt = $conn->prepare("SELECT id FROM persons WHERE ssn = ?");
            $insert_stmt = $conn->prepare("INSERT INTO persons (first_name, surname, birth_date, ssn, medications, allergies, insurance, other, swimmer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Načítání a vkládání dat z CSV do databáze
            foreach ($lines as $line_number => $line) {
                $line = trim($line);
                if (empty($line)) continue; // Přeskočit prázdné řádky
                
                // Použij středník jako oddělovač
                $fields = str_getcsv($line, ';');
                
                // Přeskočit hlavičku, pokud první řádek obsahuje název sloupce 'first_name' nebo 'Jméno'
                if ($line_number === 0 && (strtolower($fields[0]) === 'first_name' || strtolower($fields[0]) === 'jméno')) {
                    continue;
                }

                if (count($fields) >= 9) {
                    $firstName = trim($fields[0]);
                    $surname   = trim($fields[1]);
                    $dob       = trim($fields[2]);
                    $idNumber  = trim($fields[3]);
                    
                    $medications = trim($fields[4]) === '' ? null : trim($fields[4]);
                    $allergies   = trim($fields[5]) === '' ? null : trim($fields[5]);
                    $insurance   = trim($fields[6]) === '' ? null : trim($fields[6]);
                    $other       = trim($fields[7]) === '' ? null : trim($fields[7]);
                    $swimmer     = trim($fields[8]) === '' ? null : trim($fields[8]);

                    // Validace povinných polí
                    if (!empty($firstName) && !empty($surname) && !empty($dob) && !empty($idNumber)) {
                        // Kontrola duplicity
                        $check_stmt->bind_param("s", $idNumber);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        
                        if ($check_result->num_rows == 0) {
                            // Vložení záznamu
                            $insert_stmt->bind_param("sssssssss", 
                                $firstName, 
                                $surname, 
                                $dob, 
                                $idNumber, 
                                $medications, 
                                $allergies, 
                                $insurance, 
                                $other, 
                                $swimmer
                            );
                            
                            if ($insert_stmt->execute()) {
                                $inserted_count++;
                            }
                        } else {
                            $skipped_count++;
                        }
                    } else {
                        $skipped_count++;
                    }
                } else {
                    $skipped_count++;
                }
            }
            
            $check_stmt->close();
            $insert_stmt->close();
            
            if ($inserted_count > 0) {
                $success_message = "Úspěšně nahráno $inserted_count záznamů.";
                if ($skipped_count > 0) {
                    $success_message .= " $skipped_count záznamů bylo přeskočeno (duplicitní nebo neplatná data).";
                }
            } else {
                $error_message = "Žádné záznamy nebyly vloženy. Zkontrolujte formát CSV souboru.";
            }
            
        } catch (Exception $e) {
            $error_message = "Chyba při zpracování souboru: " . $e->getMessage();
        }
    } else {
        $error_message = "Chyba při nahrávání souboru.";
    }
}

$conn->close();
?>

<?php require __DIR__ . '/views/upload_csv.view.php'; ?>