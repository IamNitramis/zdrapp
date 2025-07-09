<?php
session_start(); // Spustí session, aby bylo možné kontrolovat přihlášení

// Načtení databázové konfigurace
require_once __DIR__ . '/config/database.php';

// Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přístup zamítnut</title>
    <link rel="stylesheet" href="style.css">
    <link href="assets/css/all.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%);
            color: #333;
        }
        .login-warning {
            max-width: 450px;
            margin: 20px;
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
            color: #4a5568;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .login-warning a {
            display: inline-block;
            padding: 15px 35px;
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(56, 142, 60, 0.3);
        }
        .login-warning a:hover { 
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(56, 142, 60, 0.4);
        }
        
        @media (max-width: 480px) {
            .login-warning {
                margin: 10px;
                padding: 30px 20px;
            }
            .login-warning h2 {
                font-size: 1.5rem;
            }
            .login-warning p {
                font-size: 1rem;
            }
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
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Přidání nové diagnózy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_diagnosis'])) {
    $newDiagnosis = $conn->real_escape_string($_POST['new_diagnosis']);
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO diagnoses (name, updated_by) VALUES (?, ?)");
    $stmt->bind_param("si", $newDiagnosis, $user_id);

    if ($stmt->execute()) {
        header("Location: add_diagnosis.php");
        exit;
    } else {
        echo "Chyba při přidávání diagnózy: " . $conn->error;
    }
    $stmt->close();
}

// Úprava diagnózy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_diagnosis_id'])) {
    $diagnosisId = $_POST['update_diagnosis_id'];
    $updatedDiagnosis = $conn->real_escape_string($_POST['updated_diagnosis']);
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("UPDATE diagnoses SET name = ?, updated_by = ? WHERE id = ?");
    $stmt->bind_param("sii", $updatedDiagnosis, $user_id, $diagnosisId);

    if ($stmt->execute()) {
        header("Location: add_diagnosis.php");
        exit;
    } else {
        echo "Chyba při úpravě diagnózy: " . $conn->error;
    }
    $stmt->close();
}

// Smazání diagnózy
if (isset($_GET['delete_diagnosis_id'])) {
    $diagnosisId = $_GET['delete_diagnosis_id'];
    $deleteSql = "DELETE FROM diagnoses WHERE id = $diagnosisId";
    if ($conn->query($deleteSql) === TRUE) {
        header("Location: add_diagnosis.php");
        exit;
    } else {
        echo "Chyba při mazání diagnózy: " . $conn->error;
    }
}

// Načtení seznamu diagnóz
$diagnosesSql = "SELECT *, (SELECT username FROM users WHERE users.id = diagnoses.updated_by) AS updated_by_username FROM diagnoses ORDER BY name ASC";
$diagnosesResult = $conn->query($diagnosesSql);
$diagnoses = [];
while ($row = $diagnosesResult->fetch_assoc()) {
    $diagnoses[] = $row;
}

// Načtení lékařských zpráv
$reportsSql = "SELECT mr.id, mr.created_at, 
               CONCAT(p.first_name, ' ', p.surname) AS patient_name,
               d.name AS diagnosis, 
               LEFT(mr.report_text, 100) AS report_preview 
               FROM medical_reports mr
               LEFT JOIN persons p ON mr.person_id = p.id
               LEFT JOIN diagnoses d ON mr.diagnosis_id = d.id
               ORDER BY mr.created_at DESC";
$reportsResult = $conn->query($reportsSql);
$medicalReports = [];
if ($reportsResult) {
    while ($row = $reportsResult->fetch_assoc()) {
        $medicalReports[] = $row;
    }
} else {
    // Pokud dotaz selhal, zkusíme jednodušší dotaz
    $reportsSql = "SELECT id, created_at, report_text FROM medical_reports ORDER BY created_at DESC LIMIT 10";
    $reportsResult = $conn->query($reportsSql);
    if ($reportsResult) {
        while ($row = $reportsResult->fetch_assoc()) {
            $medicalReports[] = [
                'id' => $row['id'],
                'created_at' => $row['created_at'],
                'patient_name' => 'N/A',
                'diagnosis' => 'N/A',
                'report_preview' => substr($row['report_text'], 0, 100)
            ];
        }
    }
}

// Statistiky
$total_diagnoses = count($diagnoses);

$conn->close();
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Správa diagnóz</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    
    <script>
        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("diagnosisTable");
            switching = true;
            dir = "asc";

            while (switching) {
                switching = false;
                rows = table.rows;

                for (i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;
                    x = rows[i].getElementsByTagName("TD")[n];
                    y = rows[i + 1].getElementsByTagName("TD")[n];

                    if (dir == "asc") {
                        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir == "desc") {
                        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                }

                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }

        function searchTable() {
            var input, filter, table, tr, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("diagnosisTable");
            tr = table.getElementsByTagName("tr");

            // Hledání v tabulce
            for (i = 1; i < tr.length; i++) {
                tr[i].style.display = "none";
                td = tr[i].getElementsByTagName("td");
                for (var j = 0; j < td.length; j++) {
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                            break;
                        }
                    }
                }
            }

            // Hledání v mobilních kartách
            var cards = document.querySelectorAll('.diagnosis-card');
            cards.forEach(function(card) {
                var cardText = card.textContent || card.innerText;
                if (cardText.toUpperCase().indexOf(filter) > -1) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        }

        function toggleMenu() {
            var navbar = document.getElementById("navbar");
            navbar.classList.toggle("open");
        }

        // Smooth animations for table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('#diagnosisTable tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
                row.style.animation = 'fadeInUp 0.6s ease forwards';
            });
        });
        
        document.addEventListener('DOMContentLoaded', function() {
    const navbarLinks = document.querySelectorAll('.navbar a');
    const navbar = document.getElementById('navbar');
    
    navbarLinks.forEach(link => {
        link.addEventListener('click', function() {
            navbar.classList.remove('open');
        });
    });
});
// Zavření menu při kliknutí mimo
document.addEventListener('click', function(event) {
    const navbar = document.getElementById('navbar');
    const menuIcon = document.querySelector('.menu-icon');
    
    if (!navbar.contains(event.target) && !menuIcon.contains(event.target)) {
        navbar.classList.remove('open');
    }
});

        // Add CSS for fade in animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
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
            <h1><i class="fas fa-stethoscope"></i> Správa diagnóz</h1>
            <div class="subtitle">Přidávání a správa diagnóz v systému</div>
        </div>

        <div class="stats-form-container">
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-stethoscope"></i>
                    <div class="stat-number"><?php echo $total_diagnoses; ?></div>
                    <div class="stat-label">Celkem diagnóz</div>
                </div>
            </div>

            <div class="form-container">
                <h2><i class="fas fa-plus"></i> Přidat novou diagnózu</h2>
                <form action="add_diagnosis.php" method="POST">
                    <div class="form-group">
                        <label for="new_diagnosis">Název diagnózy:</label>
                        <input type="text" name="new_diagnosis" id="new_diagnosis" 
                               class="form-control" placeholder="Zadejte název nové diagnózy" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Přidat diagnózu
                    </button>
                </form>
            </div>
        </div>
        <div class="table-container">
            <h2><i class="fas fa-list"></i> Seznam diagnóz</h2>
            
            <?php if (!empty($diagnoses)): ?>
            <table id="diagnosisTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><i class="fas fa-hashtag"></i> ID</th>
                        <th onclick="sortTable(1)"><i class="fas fa-stethoscope"></i> Název diagnózy</th>
                        <th><i class="fas fa-cogs"></i> Akce</th>
                        <th><i class="fas fa-user-edit"></i> Přidal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diagnoses as $diagnosis): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($diagnosis['id']); ?></strong></td>
                        <td>
                            <span class="diagnosis-name">
                                <?php echo htmlspecialchars($diagnosis['name']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="add_diagnosis.php?delete_diagnosis_id=<?php echo $diagnosis['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Opravdu chcete odstranit tuto diagnózu?')">
                                <i class="fas fa-trash"></i> Odstranit
                            </a>
                        </td>
                        <td>
                            <?php if ($diagnosis['updated_by_username']) {
                                echo htmlspecialchars($diagnosis['updated_by_username']);
                            } else {
                                echo '<span style="color:#aaa;">-</span>';
                            } ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-stethoscope"></i>
                <h3>Žádné diagnózy</h3>
                <p>V systému nejsou zatím žádné diagnózy. Přidejte první diagnózu pomocí formuláře výše.</p>
            </div>
            <?php endif; ?>
        </div>

       
    </div>
</body>
</html>