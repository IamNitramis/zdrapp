<?php
// Připojení k databázi
$conn = new mysqli("localhost", "root", "", "zdrapp");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Získání ID osoby z URL
if (!isset($_GET['id'])) {
    die("Person ID is required.");
}
$person_id = intval($_GET['id']);

// Zpracování formuláře pro přidání diagnózy a poznámky
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['diagnosis_id'], $_POST['note']) && !empty($_POST['note'])) {
        $diagnosis_id = intval($_POST['diagnosis_id']);
        $note = trim($_POST['note']);
        
        $sql = "INSERT INTO diagnosis_notes (person_id, diagnosis_id, note, created_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $person_id, $diagnosis_id, $note);
        
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $person_id);
            exit;
        } else {
            echo "Error: " . $conn->error;
        }
        
        $stmt->close();
    } else {
        echo "<p style='color: red;'>Prosím vyplňte všechna pole formuláře.</p>";
    }
}

// Načtení detailů osoby
$sql = "SELECT * FROM persons WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $person_id);
$stmt->execute();
$result = $stmt->get_result();
$person = $result->fetch_assoc();
if (!$person) {
    die("Person not found.");
}

// Načtení diagnóz a poznámek osoby
$sql = "SELECT d.name AS diagnosis_name, dn.id AS note_id, dn.note, dn.created_at 
        FROM diagnosis_notes dn
        JOIN diagnoses d ON dn.diagnosis_id = d.id
        WHERE dn.person_id = ?
        ORDER BY dn.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $person_id);
$stmt->execute();
$diagnoses = $stmt->get_result();

// Načtení všech diagnóz pro výběr
$sql = "SELECT * FROM diagnoses ORDER BY name ASC";
$all_diagnoses = $conn->query($sql);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detaily osoby</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .person-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .person-header h1 {
            font-size: 2.5rem;
            margin: 0;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .person-header .subtitle {
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
            transform: translateY(-5px);
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
            color: #667eea;
        }

        .diagnosis-card {
            background: linear-gradient(135deg, #f8f9ff 0%, #e6f2ff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .diagnosis-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .diagnosis-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
            border-color: #667eea;
        }

        .diagnosis-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .diagnosis-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
        }

        .diagnosis-date {
            font-size: 0.9rem;
            color: #718096;
            background: #f7fafc;
            padding: 5px 10px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }

        .diagnosis-preview {
            color: #4a5568;
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 15px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .diagnosis-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-orange {
            background: linear-gradient(135deg, #ff9500 0%, #ff8c00 100%);
            color: white;
        }

        .btn-orange:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 149, 0, 0.3);
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

        .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: #f8f9fa;
        }

        .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: #f8f9fa;
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
            box-sizing: border-box;
            text-align: left; /* zarovnání textu vlevo */
            display: block;   /* zajistí správné zarovnání v rodiči */
            margin: 0 auto;   /* horizontální centrování, pokud je potřeba */
        }

        .form-links {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .form-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .form-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #718096;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #cbd5e0;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #feb2b2;
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
            
            .person-header h1 {
                font-size: 2rem;
            }
            
            .diagnosis-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .diagnosis-actions {
                justify-content: center;
            }
            
            .form-actions {
                flex-direction: column;
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
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="person-header">
            <h1><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['surname']); ?></h1>
            <div class="subtitle">Detail pacienta a zdravotní záznamy</div>
        </div>
        
        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-stethoscope"></i>
                Diagnózy a poznámky
            </h2>
            
            <?php if ($diagnoses->num_rows > 0): ?>
                <?php while ($row = $diagnoses->fetch_assoc()): ?>
                    <div class="diagnosis-card" onclick="window.location.href='edit_note.php?id=<?php echo $row['note_id']; ?>&first_name=<?php echo $person['first_name']; ?>&surname=<?php echo $person['surname']; ?>&person_id=<?php echo $person_id; ?>'">
                        <div class="diagnosis-header">
                            <h3 class="diagnosis-name">
                                <i class="fas fa-diagnosis"></i>
                                <?php echo htmlspecialchars($row['diagnosis_name']); ?>
                            </h3>
                            <span class="diagnosis-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?>
                            </span>
                        </div>
                        
                        <div class="diagnosis-preview">
                            <?php echo htmlspecialchars(substr($row['note'], 0, 150)) . (strlen($row['note']) > 150 ? '...' : ''); ?>
                        </div>
                        
                        <div class="diagnosis-actions">
                            <form action="edit_note.php" method="GET" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $row['note_id']; ?>">
                                <input type="hidden" name="first_name" value="<?php echo $person['first_name']; ?>">
                                <input type="hidden" name="surname" value="<?php echo $person['surname']; ?>">
                                <input type="hidden" name="person_id" value="<?php echo $person_id; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-edit"></i>
                                    Upravit
                                </button>
                            </form>
                            <form action="delete_note.php" method="POST" style="display: inline;" onsubmit="return confirm('Opravdu chcete tuto poznámku odstranit?');">
                                <input type="hidden" name="id" value="<?php echo $row['note_id']; ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i>
                                    Smazat
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-notes-medical"></i>
                    <p>Žádné diagnózy nejsou zapsány.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="section-card">
            <h2 class="section-title">
                <i class="fas fa-plus-circle"></i>
                Přidat diagnózu a poznámku
            </h2>
            
            <form action="" method="POST" class="form-container">
                <div class="form-group">
                    <label for="diagnosis" class="form-label">
                        <i class="fas fa-list-alt"></i>
                        Vyber diagnózu:
                    </label>
                    <select name="diagnosis_id" id="diagnosis" class="form-select" required>
                        <option value="">-- Vyberte diagnózu --</option>
                        <?php while ($row = $all_diagnoses->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['id']); ?>">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-links">
                    <a href="add_diagnosis.php" class="form-link">
                        <i class="fas fa-plus"></i>
                        Přidat diagnózu
                    </a>
                    <a href="add_report.php" class="form-link">
                        <i class="fas fa-file-medical"></i>
                        Přidat template lékařské zprávy
                    </a>
                </div>

                <div class="form-group">
                    <label for="note" class="form-label">
                        <i class="fas fa-sticky-note"></i>
                        Poznámka:
                    </label>
                    <textarea name="note" id="note" class="form-textarea" 
                              placeholder="Napište, jak probíhalo ošetření, co jste podali za medikaci..." required></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Přidat záznam
                    </button>
                    <a href="kliste.php?person_id=<?php echo $person_id; ?>" class="btn btn-orange">
                        <i class="fas fa-bug"></i>
                        Klíšťata
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

        document.querySelectorAll('.diagnosis-card form').forEach(function(form) {
            form.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navbar = document.getElementById('navbar');
            const menuIcon = document.querySelector('.menu-icon');
            
            if (!navbar.contains(event.target) && !menuIcon.contains(event.target)) {
                navbar.classList.remove('active');
            }
        });
    </script>
</body>
</html>