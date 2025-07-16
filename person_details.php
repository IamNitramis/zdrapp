<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přístup zamítnut</title>
    <link rel="stylesheet" href="style.css">
    <link href="assets/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%); color: #333; }
        .login-warning { max-width: 450px; background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2); padding: 50px 40px; text-align: center; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .login-warning i { font-size: 4rem; color: #e67e22; margin-bottom: 20px; }
        .login-warning h2 { color: #2d3748; margin-bottom: 15px; font-size: 1.8rem; font-weight: 600; }
        .login-warning p { color: #4a5568; font-size: 1.1rem; margin-bottom: 30px; line-height: 1.5; }
        .login-warning a { display: inline-block; padding: 15px 35px; background: linear-gradient(135deg, #388e3c 0%, #43a047 100%); color: #fff; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease; box-shadow: 0 8px 25px rgba(56, 142, 60, 0.3); }
        .login-warning a:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(56, 142, 60, 0.4); }
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
require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Získání ID osoby z URL
if (!isset($_GET['id'])) {
    die("Person ID is required.");
}
$person_id = intval($_GET['id']);

// Zpracování formuláře pro přidání diagnózy a poznámky
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Uložení medikace a alergií
    if (isset($_POST['save_med_all'])) {
        $medications = isset($_POST['medications']) ? trim($_POST['medications']) : '';
        $allergies = isset($_POST['allergies']) ? trim($_POST['allergies']) : '';
        $user_id = $_SESSION['user_id'] ?? null;
        $sql = "UPDATE persons SET medications = ?, allergies = ?, updated_by = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $medications, $allergies, $user_id, $person_id);
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $person_id);
            exit;
        } else {
            echo "<p style='color: red;'>Chyba při ukládání medikace/alergií: " . $conn->error . "</p>";
        }
        $stmt->close();
    } elseif (isset($_POST['diagnosis_id'], $_POST['note']) && !empty($_POST['note'])) {
        $diagnosis_id = intval($_POST['diagnosis_id']);
        $note = trim($_POST['note']);
        $user_id = $_SESSION['user_id'] ?? null;
        $sql = "INSERT INTO diagnosis_notes (person_id, diagnosis_id, note, updated_by) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo "<p style='color: red;'>Chyba při přípravě dotazu: " . $conn->error . "</p>";
        } else {
            $stmt->bind_param("iisi", $person_id, $diagnosis_id, $note, $user_id);
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $person_id);
                exit;
            } else {
                echo "<p style='color: red;'>Chyba při ukládání poznámky: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
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

// Načtení diagnóz a poznámek osoby včetně uživatele, který upravil
$sql = "SELECT d.name AS diagnosis_name, dn.id AS note_id, dn.note, dn.created_at, u.username AS updated_by_username
        FROM diagnosis_notes dn
        JOIN diagnoses d ON dn.diagnosis_id = d.id
        LEFT JOIN users u ON dn.updated_by = u.id
        WHERE dn.person_id = ?
        ORDER BY dn.created_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Chyba při přípravě dotazu (diagnosis_notes): " . $conn->error);
}
$stmt->bind_param("i", $person_id);
$stmt->execute();
$diagnoses = $stmt->get_result();

// Načtení všech diagnóz pro výběr
$sql = "SELECT * FROM diagnoses WHERE deleted = 0 ORDER BY name ASC";
$all_diagnoses = $conn->query($sql);

// Přehled všech lékařských zpráv pro daného člověka (před uzavřením $conn)
$sql_reports = "SELECT r.id, r.report_text, r.created_at, d.name AS diagnosis_name, d.id AS diagnosis_id, n.note AS note_text, n.id AS note_id FROM medical_reports r JOIN diagnoses d ON r.diagnosis_id = d.id JOIN diagnosis_notes n ON r.diagnosis_note_id = n.id WHERE r.person_id = ? ORDER BY r.created_at DESC";
$stmt_reports = $conn->prepare($sql_reports);
$stmt_reports->bind_param("i", $person_id);
$stmt_reports->execute();
$result_reports = $stmt_reports->get_result();

$stmt->close();
$conn->close();
?>
<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detaily osoby</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        .person-header {
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .person-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .person-header h1 {
            font-size: 2.5rem;
            margin: 0;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .person-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 10px;
            position: relative;
            z-index: 1;
        }

        .section-card {
            background: #f1f8e9;
            border-radius: 15px;
            padding: 25px;
            max-width: 800px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .section-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
        }

        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .section-title {
            color: #2d3748;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #c8e6c9;
            padding-bottom: 10px;
        }

        .section-title i {
            color: #388e3c;
        }

        .diagnosis-card {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        .diagnosis-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
        }

        .diagnosis-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(56, 142, 60, 0.15);
            border-color: #388e3c;
        }

        .diagnosis-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            width: 100%;
        }

        .diagnosis-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .diagnosis-date {
            font-size: 0.9rem;
            color: #4a5568;
            background: #f1f8e9;
            padding: 5px 10px;
            border-radius: 20px;
            border: 1px solid #c8e6c9;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .diagnosis-preview {
            color: #4a5568;
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 15px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #c8e6c9;
            width: 100%;
            box-sizing: border-box;
            text-align: left;
        }

        .diagnosis-user-info {
            color: #4a5568;
            font-size: 0.95rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
        }

        .diagnosis-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            width: 100%;
            margin-top: auto;
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
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
            margin-left: 85px;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(56, 142, 60, 0.3);
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
            overflow: hidden;
        }

        .form-group {
            margin-bottom: 20px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .form-select, input[type="text"], select {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            padding: 12px 16px;
            border: 2px solid #c8e6c9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: #f8f9fa;
        }

        .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #388e3c;
            box-shadow: 0 0 0 3px rgba(56, 142, 60, 0.1);
            background: white;
        }

        .form-textarea {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            padding: 12px 16px;
            border: 2px solid #c8e6c9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: #f8f9fa;
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
            text-align: left;
            display: block;
            margin: 0 auto;
        }

        .form-links {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .form-link {
            color: #388e3c;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .form-link:hover {
            color: #2e7d32;
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
            color: #4a5568;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #a5d6a7;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffcdd2;
        }

        .flex-sections {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }
        .section-notes {
            flex: 2 1 0;
            min-width: 0;
        }
        .section-medalergies {
            flex: 1 1 320px;
            min-width: 320px;
            max-width: 400px;
        }

        /* Přepsání šířky kontejneru pro přizpůsobení obsahu */
        .container {
            max-width: 1000px !important;
            padding: 20px !important;
            box-sizing: border-box !important;
        }

        /* Zajištění, že flex sekce se rozprostřou na celou dostupnou šířku */
        .flex-sections {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Responsive úpravy */
        @media (max-width: 1200px) {
            .flex-sections {
                flex-direction: column;
                gap: 20px;
            }
            .section-medalergies {
                max-width: none;
                min-width: 0;
            }
        }
        
    </style>
</head>
<body>
    
    <div class="container">
        <div class="person-header">
            <h1><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['surname']); ?></h1>
            <div class="subtitle">Detail pacienta a zdravotní záznamy</div>
        </div>
                <div class="section-card section-add-note">
            <h2 class="section-title">
                Přidat diagnózu a nález
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
                        Chybí vám diagnóza? (Přidat diagnózu)
                    </a>
                    <a href="add_report.php" class="form-link">
                        <i class="fas fa-file-medical"></i>
                        Upravit template zdravotnické zprávy
                    </a>
                </div>
                <div class="form-group">
                    <label for="note" class="form-label">
                        <i class="fas fa-sticky-note"></i>
                        Nález:
                    </label>
                    <textarea name="note" id="note" class="form-textarea" 
                              placeholder="Napište, jak probíhalo ošetření, co jste podali za medikaci..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Uložit záznam
                    </button>
                    <a href="kliste.php?person_id=<?php echo $person_id; ?>" class="btn btn-orange">
                        <i class="fas fa-bug"></i>
                        Klíšťata
                    </a>
                </div>
            </form>
        </div>
    
        <div class="flex-sections">
            <div class="section-card section-notes">
                <h2 class="section-title">
                    <i class="fas fa-stethoscope"></i>
                    Diagnózy a nálezy
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
                            <div style="color:#4a5568; font-size:0.95rem; margin-bottom:8px;">
                                <i class="fas fa-user-edit"></i> Upravil: <?php echo $row['updated_by_username'] ? htmlspecialchars($row['updated_by_username']) : '<span style=\"color:#aaa;\">-</span>'; ?>
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
            <div class="section-card section-medalergies">
                <h2 class="section-title">
                    <i class="fas fa-notes-medical"></i>
                    Medikace a alergie
                </h2>
                    <div class="form-group">
                        <label for="medications" class="form-label">
                            <i class="fas fa-pills"></i>
                            Medikace:
                        </label>
                        <input type="text" name="medications" id="medications" class="form-select" value="<?php echo htmlspecialchars($person['medications'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="allergies" class="form-label">
                            <i class="fas fa-allergies"></i>
                            Alergie:
                        </label>
                        <input type="text" name="allergies" id="allergies" class="form-select" value="<?php echo htmlspecialchars($person['allergies'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="other" class="form-label">
                            <i class="fas fa-ellipsis-h"></i>
                            Další:
                        </label>
                        <textarea name="other" id="other" class="form-select" style="resize:vertical;min-height:60px;" disabled><?php echo htmlspecialchars($person['other'] ?? ''); ?></textarea>
                    </div>
            </div>
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

        // Add subtle animation to cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all section cards
        document.querySelectorAll('.section-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>