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
$conn = new mysqli("localhost", "root", "", "zdrapp");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Načtení všech diagnóz
$sqlDiagnoses = "SELECT id, name FROM diagnoses ORDER BY name";
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

    if ($diagnosisId && !empty($templateText)) {
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
        $message = "Prosím vyberte diagnózu a zadejte text šablony.";
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

$conn->close();
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

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .navbar a:active {
            transform: translateY(0);
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
            margin: 10px auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .page-header h1 {
            font-size: 2.2rem;
            margin: 0 0 10px 0;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 10px;
        }

        .section-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 100%;           /* přidáno */
            max-width: 100%;       /* přidáno */
            box-sizing: border-box;/* přidáno */
        }

        .section-title {
            color: #4a5568;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .section-title i {
            color: #667eea;
        }

        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: #f8f9fa;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            min-height: 500px;
            resize: vertical;
            line-height: 1.6;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(230, 126, 34, 0.3);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .help-container {
            margin-top: 20px;
            text-align: right;
        }

        .help-box {
            background: linear-gradient(135deg, #f8f9ff 0%, #e6f2ff 100%);
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            display: none;
        }

        .help-box.active {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .help-box h4 {
            color: #4a5568;
            margin: 0 0 15px 0;
            font-size: 1.1rem;
        }

        .help-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .help-box li {
            margin-bottom: 8px;
            color: #718096;
        }

        .help-box code {
            background: #667eea;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        }

        .diagnosis-info {
            background: linear-gradient(135deg, #f8f9ff 0%, #e6f2ff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .diagnosis-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .diagnosis-info h3 {
            margin: 0;
            color: #2d3748;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .header-container {
                padding: 0 15px;
            }

            .menu-icon {
                display: block;
            }

            .navbar {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

            .container {
                padding: 10px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .form-actions {
                flex-direction: column;
            }

            .form-textarea {
                min-height: 400px;
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
                <a href="download_reports.php">
                    <i class="fas fa-download"></i>
                    Stáhnout zprávy
                </a>
                <a href="add_report.php">
                    <i class="fas fa-file-medical"></i>
                    Přidat lékařskou zprávu
                </a>
                <a href="statistics.php">
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
            <h1><i class="fas fa-file-medical-alt"></i> Přiřazení šablony k diagnóze</h1>
            <div class="subtitle">Vytvořte nebo upravte šablonu pro generování zdravotních zpráv</div>
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
            
            <form action="" method="POST">
                <div class="form-group">
                    <label for="diagnosis_id" class="form-label">
                        <i class="fas fa-list"></i>
                        Vyberte diagnózu:
                    </label>
                    <select name="diagnosis_id" id="diagnosis_id" class="form-select" required onchange="loadTemplateForDiagnosis(this)">
                        <option value="">-- Vyberte diagnózu --</option>
                        <?php
                        // Znovu načti diagnózy pro select
                        $conn2 = new mysqli("localhost", "root", "", "zdrapp");
                        $sqlDiagnoses2 = "SELECT id, name FROM diagnoses ORDER BY name";
                        $resultDiagnoses2 = $conn2->query($sqlDiagnoses2);
                        while ($row = $resultDiagnoses2->fetch_assoc()):
                        ?>
                            <option value="<?php echo $row['id']; ?>" <?php if ($row['id'] == $selectedDiagnosisId) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endwhile; $conn2->close(); ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="template_text" class="form-label">
                        <i class="fas fa-file-alt"></i>
                        Text šablony:
                    </label>
                    <textarea name="template_text" id="template_text" class="form-textarea" 
                              placeholder="Zadejte text šablony... Můžete používat placeholdery jako {{name}}, {{birth_date}}, {{temperature}} atd." 
                              required><?php echo htmlspecialchars($templateText); ?></textarea>
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
                            <li><code>{{temperature}}</code> – Tělesná teplota</li>
                            <li><code>{{oxygen_saturation}}</code> – Saturace kyslíku</li>
                            <li><code>{{heart_rate}}</code> – Srdeční tep</li>
                            <li><code>{{diagnosis}}</code> – Název diagnózy a datum přiřazení</li>
                            <li><code>{{note}}</code> – Poznámka k diagnóze</li>
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

    <script>
        function toggleMenu() {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('active');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navbar = document.getElementById('navbar');
            const menuIcon = document.querySelector('.menu-icon');
            
            if (!navbar.contains(event.target) && !menuIcon.contains(event.target)) {
                navbar.classList.remove('active');
            }
        });

        // Po změně diagnózy načti stránku s vybranou diagnózou
        function loadTemplateForDiagnosis(sel) {
            var id = sel.value;
            if (id) {
                window.location.href = "?diagnosis_id=" + id;
            } else {
                window.location.href = "add_report.php";
            }
        }

        // Nápověda toggle
        document.getElementById('helpBtn').onclick = function() {
            var box = document.getElementById('helpBox');
            box.classList.toggle('active');
        };

        // Auto-resize textarea
        const textarea = document.getElementById('template_text');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(500, this.scrollHeight) + 'px';
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const diagnosisId = document.getElementById('diagnosis_id').value;
            const templateText = document.getElementById('template_text').value.trim();
            
            if (!diagnosisId) {
                e.preventDefault();
                alert('Prosím vyberte diagnózu.');
                return;
            }
            
            if (!templateText) {
                e.preventDefault();
                alert('Prosím zadejte text šablony.');
                return;
            }
        });
    </script>
</body>
</html>