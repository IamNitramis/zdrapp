<?php
session_start();

// Kontrola, zda je uživatel přihlášen
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
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Získání ID poznámky
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Valid Note ID is required.");
}

$noteId = intval($_GET['id']);

// Načtení existující poznámky a diagnózy (JOIN mezi diagnosis_notes a diagnoses)
$sql = "
    SELECT dn.note, d.id AS diagnosis_id, d.name AS diagnosis_name, dn.created_at, dn.person_id 
    FROM diagnosis_notes dn
    JOIN diagnoses d ON dn.diagnosis_id = d.id
    WHERE dn.id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $noteId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Note not found.");
}

$note = $result->fetch_assoc();

// Zpracování formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['updated_note']) || empty(trim($_POST['updated_note']))) {
        echo "<p class='alert-error'>Poznámka nemůže být prázdná.</p>";
    } else {
        $updatedNote = trim($_POST['updated_note']);

        $updateSql = "UPDATE diagnosis_notes SET note = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            die("SQL Error: " . $conn->error);
        }

        $updateStmt->bind_param("si", $updatedNote, $noteId);

        if ($updateStmt->execute()) {
            header("Location: person_details.php?id=" . htmlspecialchars($note['person_id']));
            exit;
        } else {
            echo "<p class='alert-error'>Chyba při aktualizaci poznámky: " . $conn->error . "</p>";
        }

        $updateStmt->close();
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravit poznámku</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        .section-card-title {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            max-width: 540px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .section-card-text {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            max-width: 840px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }
        .section-title {
            font-size: 1.15rem;
            padding-bottom: 6px;
            margin-bottom: 12px;
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
            <h1><i class="fas fa-edit"></i> Upravit poznámku</h1>
            <div class="subtitle">Úprava zdravotního záznamu pro <?php echo htmlspecialchars($_GET['first_name'] ?? 'pacienta'); ?> <?php echo htmlspecialchars($_GET['surname'] ?? ''); ?></div>
        </div>
        
        <div class="section-card-title">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i>
                Informace o diagnóze
            </h2>
            
            <div class="diagnosis-info">
                <h3><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($note['diagnosis_name']); ?></h3>
                <p>
                    <i class="fas fa-calendar-alt"></i>
                    <strong>Datum přiřazení:</strong> <?php echo htmlspecialchars(date("d.m.Y H:i", strtotime($note['created_at']))); ?>
                </p>
            </div>
        </div>

        <div class="section-card-text">
            <h2 class="section-title">
                <i class="fas fa-edit"></i>
                Úprava poznámky
            </h2>
            
            <form action="" method="POST" class="form-container">
                <div class="form-group">
                    <label for="updated_note" class="form-label">
                        <i class="fas fa-sticky-note"></i>
                        Obsah poznámky:
                    </label>
                    <textarea name="updated_note" id="updated_note" class="form-textarea" 
                              placeholder="Upravte obsah poznámky..." required><?php echo htmlspecialchars($note['note']); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Uložit změny
                    </button>
                    <a href="person_details.php?id=<?php echo htmlspecialchars($_GET['person_id'] ?? $note['person_id']); ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Zrušit
                    </a>
                    <a href="report.php?diagnosis_note_id=<?php echo htmlspecialchars($noteId); ?>&diagnosis_id=<?php echo htmlspecialchars($note['diagnosis_id']); ?>&person_id=<?php echo htmlspecialchars($note['person_id']); ?>&surname=<?php echo htmlspecialchars($_GET['surname'] ?? ''); ?>" class="btn btn-success">
                        <i class="fas fa-file-medical"></i>
                        Generovat zprávu
                    </a>
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

        // Auto-resize textarea
        const textarea = document.getElementById('updated_note');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(400, this.scrollHeight) + 'px';
        });

        // Smooth scrolling for better UX
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
    </script>
</body>
</html>