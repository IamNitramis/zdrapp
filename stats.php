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
$patients_with_wasp_stings = $conn->query("SELECT COUNT(DISTINCT person_id) FROM person_body_points")->fetch_row()[0];
// Získání všech diagnóz pro výběr
$diagnoses = $conn->query("SELECT id, name FROM diagnoses WHERE deleted = 0 ORDER BY name ASC");

$selected_diagnosis_id = isset($_GET['diagnosis_id']) ? intval($_GET['diagnosis_id']) : null;
$points_count = null;
$top_person = null;
$selected_diagnosis_name = null;
if ($selected_diagnosis_id) {
    // Získání názvu diagnózy
    $sql_name = "SELECT name FROM diagnoses WHERE id = ?";
    $stmt_name = $conn->prepare($sql_name);
    $stmt_name->bind_param("i", $selected_diagnosis_id);
    $stmt_name->execute();
    $stmt_name->bind_result($selected_diagnosis_name);
    $stmt_name->fetch();
    $stmt_name->close();

    // Počet bodů pro danou diagnózu
    $sql = "SELECT COUNT(pb.id) FROM person_body_points pb
            JOIN diagnosis_notes dn ON pb.diagnosis_note_id = dn.id
            WHERE dn.diagnosis_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_diagnosis_id);
    $stmt->execute();
    $stmt->bind_result($points_count);
    $stmt->fetch();
    $stmt->close();

    // Osoba s nejvíce body pro danou diagnózu
    $sql2 = "SELECT p.first_name, p.surname, COUNT(pb.id) as body_points
            FROM person_body_points pb
            JOIN diagnosis_notes dn ON pb.diagnosis_note_id = dn.id
            JOIN persons p ON dn.person_id = p.id
            WHERE dn.diagnosis_id = ?
            GROUP BY p.id
            ORDER BY body_points DESC
            LIMIT 1";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $selected_diagnosis_id);
    $stmt2->execute();
    $stmt2->bind_result($first_name, $surname, $body_points);
    if ($stmt2->fetch()) {
        $top_person = ["first_name" => $first_name, "surname" => $surname, "body_points" => $body_points];
    }
    $stmt2->close();
}

// Osoba s nejvíce klíšťaty
$sql_top_ticks = "SELECT p.first_name, p.surname, COUNT(tb.id) as tick_count
                 FROM tick_bites tb
                 JOIN persons p ON tb.person_id = p.id
                 GROUP BY p.id
                 ORDER BY tick_count DESC
                 LIMIT 1";
$result_top_ticks = $conn->query($sql_top_ticks);
$top_tick_person = $result_top_ticks->fetch_assoc();

$conn->close();
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiky systému</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link href="assets/css/all.min.css" rel="stylesheet">
</head>
<style>
    .container {
        min-height: 750px;
    }
    .diagnosis-selector {
        background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        text-align: center;
    }
    .diagnosis-selector h3 {
        color: white;
        margin: 0 0 15px 0;
        font-size: 1.2em;
        font-weight: 600;
    }
    .diagnosis-selector select {
        background: white;
        border: none;
        border-radius: 8px;
        padding: 12px 20px;
        font-size: 16px;
        min-width: 250px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .diagnosis-selector select:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transform: translateY(-1px);
    }
    .diagnosis-selector select:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
    }
</style>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Statistiky systému</h1>
            <div class="subtitle">Přehled základních statistik aplikace ZDRAPP</div>
        </div>
        <div class="stats-container">
            <div class="diagnosis-selector">
                <h3><i class="fas fa-search"></i> Statistiky podle diagnózy</h3>
                <form method="get">
                    <select name="diagnosis_id" id="diagnosis_id" onchange="this.form.submit()">
                        <option value="">-- Vyberte diagnózu pro podrobné statistiky --</option>
                        <?php if ($diagnoses) while($d = $diagnoses->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>" <?php if($selected_diagnosis_id==$d['id']) echo 'selected'; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>
            <?php if ($selected_diagnosis_id): ?>
                <div class="stat-card" style="background:#eaf6ff;">
                    <i class="fas fa-dot-circle"></i>
                    <div class="stat-number"><?php echo $points_count; ?></div>
                    <div class="stat-label">Počet bodů pro <?php echo htmlspecialchars($selected_diagnosis_name); ?></div>
                </div>
                <?php if ($top_person): ?>
                <div class="stat-card" style="background:#fffbe6;">
                    <i class="fas fa-crown"></i>
                    <div class="stat-number"><?php echo htmlspecialchars($top_person['first_name'] . ' ' . $top_person['surname']); ?></div>
                    <div class="stat-label">Nejvíce bodů: <?php echo $top_person['body_points']; ?></div>
                </div>
                <?php else: ?>
                <div class="stat-card" style="background:#f8f9fa;">
                    <i class="fas fa-info-circle"></i>
                    <div class="stat-number">-</div>
                    <div class="stat-label">Žádný pacient pro tuto diagnózu</div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
           
            <div class="stat-card" style="background:#ffebee;">
                <i class="fas fa-bug"></i>
                <div class="stat-number"><?php echo $total_ticks; ?></div>
                <div class="stat-label">Celkem klíšťat</div>
            </div>
            
            <?php
            ?>
            
            <?php if ($top_tick_person): ?>
            <div class="stat-card" style="background:#fff3e0;">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="stat-number"><?php echo htmlspecialchars($top_tick_person['first_name'] . ' ' . $top_tick_person['surname']); ?></div>
                <div class="stat-label">Nejvíce klíšťat: <?php echo $top_tick_person['tick_count']; ?></div>
            </div>
            <?php else: ?>
            <div class="stat-card" style="background:#f8f9fa;">
                <i class="fas fa-info-circle"></i>
                <div class="stat-number">-</div>
                <div class="stat-label">Žádné záznamy klíšťat</div>
            </div>
            <?php endif; ?>
            
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
                <div class="stat-label">Zdravotnických zpráv</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-check"></i>
                <div class="stat-number"><?php echo $patients_with_ticks; ?></div>
                <div class="stat-label">Pacientů s klíšťaty</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-md"></i>
                <div class="stat-number"><?php echo $patients_with_reports; ?></div>
                <div class="stat-label">Pacientů se zdravotnickou zprávou</div>
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