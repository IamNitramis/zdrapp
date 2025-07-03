<?php
session_start(); // Spustí session, aby bylo možné kontrolovat přihlášení

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
            background: linear-gradient(135deg, #4facfe, #00f2fe);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
// Připojení k databázi
$conn = new mysqli("localhost", "root", "", "zdrapp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
            // Před vložením nových dat uděláme zálohu tabulky
            $timestamp = date('Y_m_d_H_i_s');
            $backupSql = "CREATE TABLE IF NOT EXISTS persons_backup_$timestamp AS SELECT * FROM persons";
            $conn->query($backupSql);
            
            $csvData = file_get_contents($file);
            $lines = explode("\n", $csvData);
            $inserted_count = 0;
            $skipped_count = 0;
            
            // Načítání a vkládání dat z CSV do databáze
            foreach ($lines as $line_number => $line) {
                $line = trim($line);
                if (empty($line)) continue; // Přeskočit prázdné řádky
                
                $fields = str_getcsv($line);
                if (count($fields) >= 6) { // Očekáváme minimálně 6 sloupců: Jméno, Příjmení, Datum narození, Rodné číslo, Léky, Alergie
                    $firstName = $conn->real_escape_string(trim($fields[0]));
                    $surname = $conn->real_escape_string(trim($fields[1]));
                    $dob = $conn->real_escape_string(trim($fields[2]));
                    $idNumber = $conn->real_escape_string(trim($fields[3]));
                    $medications = $conn->real_escape_string(trim($fields[4]));
                    $allergies = $conn->real_escape_string(trim($fields[5]));

                    // Validace dat
                    if (!empty($firstName) && !empty($surname) && !empty($dob) && !empty($idNumber)) {
                        // Kontrola, zda osoba již neexistuje
                        $check_sql = "SELECT id FROM persons WHERE ssn = '$idNumber'";
                        $check_result = $conn->query($check_sql);
                        
                        if ($check_result->num_rows == 0) {
                            // Vložení dat do tabulky
                            $sql = "INSERT INTO persons (first_name, surname, birth_date, ssn, medications, allergies) 
                                   VALUES ('$firstName', '$surname', '$dob', '$idNumber', '$medications', '$allergies')";
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

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nahrání dat - ZDRAPP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
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
        }

        .navbar a:hover, .navbar a.active {
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
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .upload-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .file-upload-area {
            border: 3px dashed #cbd5e0;
            border-radius: 15px;
            padding: 60px 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            background: #f8f9fa;
        }

        .file-upload-area:hover, .file-upload-area.dragover {
            border-color: #667eea;
            background: #f0f4ff;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.1);
        }

        .file-upload-area i {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }

        .file-upload-area:hover i {
            color: #667eea;
        }

        .file-upload-area h3 {
            color: #4a5568;
            font-size: 1.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .file-upload-area p {
            color: #718096;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .file-input {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            top: 0;
            left: 0;
        }

        .upload-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            margin-top: 20px;
        }

        .upload-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .upload-btn:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .alert {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert i {
            font-size: 1.5rem;
        }

        .info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .info-card h3 {
            color: #2d3748;
            font-size: 1.3rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card ul {
            color: #4a5568;
            line-height: 1.6;
            padding-left: 20px;
        }

        .info-card li {
            margin-bottom: 8px;
        }

        .file-info {
            background: #e6f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            display: none;
        }

        .file-info.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin: 15px 0;
            display: none;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 10px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .upload-container {
                padding: 25px;
            }
            
            .file-upload-area {
                padding: 40px 15px;
            }
            
            .navbar {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(102, 126, 234, 0.95);
                backdrop-filter: blur(10px);
                padding: 20px;
                gap: 10px;
            }
            
            .navbar.open {
                display: flex;
            }
            
            .menu-icon {
                display: block;
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
                <a href="upload_csv.php" class="active">
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
            <h1><i class="fas fa-cloud-upload-alt"></i> Nahrání dat</h1>
            <div class="subtitle">Nahrajte CSV soubor s údaji pacientů do systému</div>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
        <?php endif; ?>

        <div class="upload-container">
            <form action="upload_csv.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="file-upload-area" id="uploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>Nahrajte CSV soubor</h3>
                    <p>Přetáhněte soubor sem nebo klikněte pro výběr</p>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="file-input">
                </div>
                
                <div class="file-info" id="fileInfo">
                    <i class="fas fa-file-csv"></i>
                    <span id="fileName"></span>
                    <small id="fileSize"></small>
                </div>
                
                <div class="progress-bar" id="progressBar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="upload-btn" id="uploadBtn" disabled>
                        <i class="fas fa-upload"></i>
                        Nahrát soubor
                    </button>
                </div>
            </form>
        </div>

        <div class="info-card">
            <h3>
                <i class="fas fa-info-circle"></i>
                Požadavky na CSV soubor
            </h3>
            <ul>
                <li><strong>Formát:</strong> CSV soubor s kódováním UTF-8</li>
                <li><strong>Sloupce:</strong> Jméno, Příjmení, Datum narození, Rodné číslo, Léky, Alergie</li>
                <li><strong>Oddělovač:</strong> Čárka (,)</li>
                <li><strong>Maximální velikost:</strong> 10 MB</li>
                <li><strong>Datum narození:</strong> Ve formátu YYYY-MM-DD</li>
                <li><strong>Duplicita:</strong> Záznamy s existujícím rodným číslem budou přeskočeny</li>
            </ul>
        </div>

        <div class="info-card">
            <h3>
                <i class="fas fa-lightbulb"></i>
                Příklad CSV souboru
            </h3>
            <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; font-family: monospace; font-size: 0.9rem;">
                Jan,Novák,1985-03-15,8503150123,Paralen,Alergie na pyl<br>
                Marie,Svobodová,1990-07-22,9007220456,,Penicilin<br>
                Petr,Dvořák,1978-11-08,7811080789,Ibuprofen,
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            var navbar = document.getElementById("navbar");
            navbar.classList.toggle("open");
        }

        // File upload handling
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('csv_file');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');

        // Drag and drop events
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                handleFileSelect();
            }
        });

        // File input change
        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = `(${formatFileSize(file.size)})`;
                fileInfo.classList.add('show');
                uploadBtn.disabled = false;
                
                // Validate file
                if (!file.name.toLowerCase().endsWith('.csv')) {
                    showAlert('Pouze CSV soubory jsou povoleny.', 'error');
                    uploadBtn.disabled = true;
                    return;
                }
                
                if (file.size > 10 * 1024 * 1024) {
                    showAlert('Soubor je příliš velký. Maximální velikost je 10MB.', 'error');
                    uploadBtn.disabled = true;
                    return;
                }
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function showAlert(message, type) {
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i>
                <span>${message}</span>
            `;
            
            const container = document.querySelector('.container');
            const pageHeader = document.querySelector('.page-header');
            container.insertBefore(alert, pageHeader.nextSibling);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Form submission with progress
        uploadForm.addEventListener('submit', function(e) {
            if (!fileInput.files[0]) {
                e.preventDefault();
                showAlert('Vyberte prosím CSV soubor.', 'error');
                return;
            }
            
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Nahrávám...';
            progressBar.style.display = 'block';
            
            // Simulate progress (since we can't track real progress with standard form submission)
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressFill.style.width = progress + '%';
            }, 200);
            
            // Clean up interval after form submission
            setTimeout(() => {
                clearInterval(interval);
                progressFill.style.width = '100%';
            }, 1000);
        });

        // Auto-redirect after successful upload
        <?php if ($success_message): ?>
        setTimeout(() => {
            window.location.href = 'show_data.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>