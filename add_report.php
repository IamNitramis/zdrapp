<?php
session_start();

// Kontrola přihlášení uživatele
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<div class='alert'>
            <h2>You must be logged in to access this page.</h2>
            <p><a href='login.php' class='link-button'>Click here to login</a></p>
          </div>";
    exit;
}

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

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přiřazení šablony k diagnóze - ZDRAPP</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <script src="assets/tinymce/js/tinymce/tinymce.min.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-file-medical-alt"></i> Přiřazení šablony k diagnóze</h1>
            <div class="subtitle">Vytvořte nebo upravte šablonu pro generování zdravotnických zpráv</div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($selectedDiagnosisName): ?>
            <div class="section-card">
                <div class="diagnosis-info">
                    <h3>
                        <i class="fas fa-stethoscope"></i>
                        Aktuálně upravujete šablonu pro: <?php echo htmlspecialchars($selectedDiagnosisName); ?>
                    </h3>
                </div>
            </div>
        <?php endif; ?>

        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-cog"></i>
                Konfigurace šablony
            </h2>
            
            <form id="templateForm" action="" method="POST" novalidate>
                <div class="form-group">
                    <label for="diagnosis_id" class="form-label">
                        <i class="fas fa-list"></i>
                        Vyberte diagnózu:
                    </label>
                    <select name="diagnosis_id" id="diagnosis_id" class="form-select" onchange="loadTemplateForDiagnosis(this)">
                        <option value="">-- Vyberte diagnózu --</option>
                        <?php
                        // Znovu načti diagnózy pro select
                        $sqlDiagnoses2 = "SELECT id, name FROM diagnoses WHERE deleted = 0 ORDER BY name";
                        $resultDiagnoses2 = $conn->query($sqlDiagnoses2);
                        if ($resultDiagnoses2) {
                            while ($row = $resultDiagnoses2->fetch_assoc()):
                        ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($row['id'] == $selectedDiagnosisId) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php 
                            endwhile; 
                        } else {
                            echo "<option value=''>Chyba při načítání diagnóz</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="template_text" class="form-label">
                        <i class="fas fa-file-alt"></i>
                        Text šablony:
                    </label>
                    <textarea name="template_text" id="template_text" class="form-textarea" 
                              placeholder="Zadejte text šablony... Můžete používat placeholdery jako {{name}}, {{birth_date}}, {{current_day}} atd."><?php echo htmlspecialchars($templateText); ?></textarea>
                </div>

                <div class="help-container">
                    <button type="button" id="helpBtn" class="btn btn-secondary">
                        <i class="fas fa-question-circle"></i>
                        Nápověda k placeholderům
                    </button>
                    <div id="helpBox" class="help-box">
                        <h4><i class="fas fa-info-circle"></i> Dostupné placeholdery:</h4>
                        <ul>
                            <li><code>{{name}}</code> – Jméno a příjmení pacienta</li>
                            <li><code>{{birth_date}}</code> – Datum narození</li>
                            <li><code>{{temperature}}</code> – Tělesná teplota (náhodně generována)</li>
                            <li><code>{{oxygen_saturation}}</code> – Saturace kyslíkem (náhodně generováno)</li>
                            <li><code>{{heart_rate}}</code> – Srdeční tep (náhodně generován)</li>
                            <li><code>{{blood_pressure}}</code> – Krevní tlak (náhodně generován)</li>
                            <li><code>{{diagnosis}}</code> – Název diagnózy a datum přiřazení</li>
                            <li><code>{{note}}</code> – Poznámka k diagnóze</li>
                            <li><code>{{author}}</code> – Autor poslední změny zprávy. (doporučujeme používat na konci zprávy jako podpis)</li>
                            <li><code>{{current_day}}</code> – Aktuální datum (formát dd.mm.rrrr)</li>
                        </ul>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Uložit šablonu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- VLOŽTE TENTO KOMPLETNÍ BLOK PŘED </body> -->
    <script>
        // Zjednodušená inicializace TinyMCE
        tinymce.init({
            selector: '#template_text',
            plugins: 'lists table',
            toolbar: 'undo redo | bold italic underline | bullist numlist | table | fontsize',
            menubar: false,
            branding: false,
            height: 400
        });

        let isSubmitting = false;

        // Načtení šablony po změně diagnózy
        function loadTemplateForDiagnosis(sel) {
            if (isSubmitting) {
                return;
            }
            
            const id = sel.value;
            if (id) {
                window.location.href = "?diagnosis_id=" + id;
            } else {
                window.location.href = "add_report.php";
            }
        }

        // Zobrazení nápovědy
        document.getElementById('helpBtn').onclick = function() {
            document.getElementById('helpBox').classList.toggle('active');
        };

        // Mobilní menu
        function toggleMenu() {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const navbar = document.getElementById('navbar');
            const menuIcon = document.querySelector('.menu-icon');
            
            if (menuIcon && !navbar.contains(event.target) && !menuIcon.contains(event.target)) {
                navbar.classList.remove('active');
            }
        });

        // Jednoduchá validace před odesláním
        document.getElementById('templateForm').addEventListener('submit', function(e) {
            // Jednoduše uložíme obsah z TinyMCE
            if (tinymce.get('template_text')) {
                tinymce.get('template_text').save();
            }
            
            // Základní validace
            const diagnosisId = document.getElementById('diagnosis_id').value;
            if (!diagnosisId) {
                alert('Prosím vyberte diagnózu.');
                e.preventDefault();
                return false;
            }
            
            isSubmitting = true;
            // Nechej formulář se odeslat normálně
        });
    </script>
</body>
</html>