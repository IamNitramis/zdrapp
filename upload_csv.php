<?php
require_once __DIR__ . '/includes/auth.php';

// Připojení k databázi
require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
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
            $lines = explode("\n", $csvData);
            $inserted_count = 0;
            $skipped_count = 0;
            
            // Načítání a vkládání dat z CSV do databáze
            foreach ($lines as $line_number => $line) {
                $line = trim($line);
                if (empty($line)) continue; // Přeskočit prázdné řádky
                
                // Použij středník jako oddělovač
                $fields = str_getcsv($line, ';');
                if (count($fields) >= 9) { // Očekáváme minimálně 9 sloupců: Jméno, Příjmení, Datum narození, Rodné číslo, Léky, Alergie, Pojišťovna, Other, Swimmer
                    $firstName = $conn->real_escape_string(trim($fields[0]));
                    $surname = $conn->real_escape_string(trim($fields[1]));
                    $dob = $conn->real_escape_string(trim($fields[2]));
                    $idNumber = $conn->real_escape_string(trim($fields[3]));
                    $medications = $conn->real_escape_string(trim($fields[4]));
                    $allergies = $conn->real_escape_string(trim($fields[5]));
                    $insurance = $conn->real_escape_string(trim($fields[6]));
                    $other = $conn->real_escape_string(trim($fields[7]));
                    $swimmer = $conn->real_escape_string(trim($fields[8]));

                    $medications_sql = ($medications === '') ? 'NULL' : "'$medications'";
                    $allergies_sql = ($allergies === '') ? 'NULL' : "'$allergies'";
                    $insurance_sql = ($insurance === '') ? 'NULL' : "'$insurance'";
                    $other_sql = ($other === '') ? 'NULL' : "'$other'";
                    $swimmer_sql = ($swimmer === '') ? 'NULL' : "'$swimmer'";

                    // Validace dat
                    if (!empty($firstName) && !empty($surname) && !empty($dob) && !empty($idNumber)) {
                        // Kontrola, zda osoba již neexistuje
                        $check_sql = "SELECT id FROM persons WHERE ssn = '$idNumber'";
                        $check_result = $conn->query($check_sql);
                        
                        if ($check_result->num_rows == 0) {
                            // Vložení dat do tabulky
                            $sql = "INSERT INTO persons (first_name, surname, birth_date, ssn, medications, allergies, insurance, other, swimmer) 
                                   VALUES ('$firstName', '$surname', '$dob', '$idNumber', $medications_sql, $allergies_sql, $insurance_sql, $other_sql, $swimmer_sql)";
                            if ($conn->query($sql)) {
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