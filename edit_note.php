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
$conn = new mysqli("localhost", "root", "", "zdrapp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Získání ID poznámky
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Valid Note ID is required.");
}

$noteId = intval($_GET['id']);

// načtení existující poznámky a diagnózy (JOIN mezi diagnosis_notes, diagnoses, users)
$sql = "
    SELECT dn.note, d.id AS diagnosis_id, d.name AS diagnosis_name, dn.created_at, dn.person_id,
           u1.username AS updated_by_username
    FROM diagnosis_notes dn
    JOIN diagnoses d ON dn.diagnosis_id = d.id
    LEFT JOIN users u1 ON dn.updated_by = u1.id
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
$updatedByUsername = $note['updated_by_username'] ?? '';

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
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f1f8e9 0%, #c8e6c9 100%);

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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
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
        }

        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
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
            color: #388e3c;
        }

        .diagnosis-info {
            background: linear-gradient(135deg, #f8f9ff 0%, #e6f2ff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
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
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
        }

        .diagnosis-info h3 {
            margin: 0 0 10px 0;
            color: #2d3748;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .diagnosis-info p {
            margin: 5px 0;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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

        .form-textarea {
            width: 100%;
            max-width: 100%;      /* zabrání přetečení přes rodiče */
            box-sizing: border-box; /* zajistí správné počítání šířky */
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: #f8f9fa;
            font-family: inherit;
            min-height: 400px;
            resize: vertical;
            line-height: 1.6;
            text-align: left;
            display: block;
            margin: 0;            /* odstraní auto-centrování */
        }

        .form-textarea:focus {
            outline: none;
            border-color: #388e3c;
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
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(113, 128, 150, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.3);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #feb2b2;
            display: flex;
            align-items: center;
            gap: 10px;
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
                background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
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
                min-height: 300px;
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
        
        <div class="section-card">
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
                <p>
                    <i class="fas fa-user"></i>
                    <strong>Upravil:</strong> <?php echo $updatedByUsername ? htmlspecialchars($updatedByUsername) : '<span style="color:#aaa;">-</span>'; ?>
                </p>
            </div>
        </div>

        <div class="section-card">
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