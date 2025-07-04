<?php
session_start();
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
$conn = new mysqli("localhost", "root", "", "zdrapp");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%);
            min-height: 100vh;
        }
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 35px 30px;
            min-width: 220px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #718096;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        @media (max-width: 900px) {
            .stats-container {
                flex-direction: column;
                align-items: center;
            }
            .stat-card {
                width: 100%;
                min-width: unset;
            }
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
                <a href="add_diagnosis.php" class="active">
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