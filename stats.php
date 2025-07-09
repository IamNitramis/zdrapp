<?php
session_start();

// Načtení databázové konfigurace
require_once __DIR__ . '/config/database.php';

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
// Připojení k databázi
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Získání statistik
$total_patients = $conn->query("SELECT COUNT(*) FROM persons")->fetch_row()[0];
$total_diagnoses = $conn->query("SELECT COUNT(*) FROM diagnoses")->fetch_row()[0];
$total_reports = $conn->query("SELECT COUNT(*) FROM medical_reports")->fetch_row()[0];
$total_notes = $conn->query("SELECT COUNT(*) FROM diagnosis_notes")->fetch_row()[0];
$total_ticks = $conn->query("SELECT COUNT(*) FROM tick_bites")->fetch_row()[0];
$patients_with_ticks = $conn->query("SELECT COUNT(DISTINCT person_id) FROM tick_bites")->fetch_row()[0];
$patients_with_reports = $conn->query("SELECT COUNT(DISTINCT person_id) FROM medical_reports")->fetch_row()[0];
$conn->close();
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiky systému</title>
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
                <a href="download_reports.php">
                    <i class="fas fa-download"></i>
                    Stáhnout zprávy
                </a>
                <a href="add_report.php">
                    <i class="fas fa-file-medical"></i>
                    Přidat lékařskou zprávu
                </a>
                <a href="stats.php" class="active">
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
            <h1><i class="fas fa-chart-bar"></i> Statistiky systému</h1>
            <div class="subtitle">Přehled základních statistik aplikace ZDRAPP</div>
        </div>
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo $total_patients; ?></div>
                <div class="stat-label">Pacientů</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-stethoscope"></i>
                <div class="stat-number"><?php echo $total_diagnoses; ?></div>
                <div class="stat-label">Diagnóz</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-notes-medical"></i>
                <div class="stat-number"><?php echo $total_reports; ?></div>
                <div class="stat-label">Lékařských zpráv</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clipboard-list"></i>
                <div class="stat-number"><?php echo $total_notes; ?></div>
                <div class="stat-label">Poznámek</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-bug"></i>
                <div class="stat-number"><?php echo $total_ticks; ?></div>
                <div class="stat-label">Záznamů klíšťat</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-check"></i>
                <div class="stat-number"><?php echo $patients_with_ticks; ?></div>
                <div class="stat-label">Pacientů s klíšťaty</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-md"></i>
                <div class="stat-number"><?php echo $patients_with_reports; ?></div>
                <div class="stat-label">Pacientů s lékařskou zprávou</div>
            </div>
        </div>
    </div>
    <script>
        function toggleMenu() {
            var navbar = document.getElementById("navbar");
            navbar.classList.toggle("open");
        }
    </script>
</body>
</html>