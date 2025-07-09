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

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seznam pacientů - ZDRAPP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">    
    <style>
    /* Lokální styl pro tabulku pacientů – barvy z style.css */
    .table-container {
        margin-top: 30px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        padding: 20px;
        overflow-x: auto;
        max-width: 1200px;
        width: 100%;
    }
    #dataTable {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        font-size: 1.04em;
        min-width: 800px;
    }
    #dataTable thead th {
        background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
        color: #fff;
        font-weight: 700;
        padding: 14px 10px;
        border-bottom: 2px solid #e8f5e9;
        cursor: pointer;
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 2;
        letter-spacing: 0.03em;
        font-size: 1em;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.12);
    }
    #dataTable tbody td {
        padding: 12px 10px;
        border-bottom: 1px solid #e8f5e9;
        background: #fff;
        vertical-align: middle;
        color: #4a5568;
    }
    #dataTable tbody tr:last-child td {
        border-bottom: none;
    }
    #dataTable .medication-pill {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 0.98em;
        font-weight: 500;
        background: #e8f5e9;
        color: #2e7d32;
        margin-right: 2px;
        border: 1px solid #c8e6c9;
    }
    #dataTable .allergy-pill {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 0.98em;
        font-weight: 500;
        background: #fffde7;
        color: #b71c1c;
        margin-right: 2px;
        border: 1px solid #ffe082;
    }
    #dataTable .no-data-pill {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 0.98em;
        font-weight: 500;
        background: #ececec;
        color: #888;
        margin-right: 2px;
        border: 1px solid #e0e0e0;
    }
    #dataTable .buttons {
        display: flex;
        gap: 8px;
    }
    #dataTable .btn {
        padding: 6px 16px;
        border-radius: 8px;
        font-size: 0.98em;
        font-weight: 600;
        text-decoration: none;
        color: #fff;
        background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
        border: none;
        box-shadow: 0 2px 8px rgba(56,142,60,0.10);
        transition: background 0.18s, box-shadow 0.18s, transform 0.18s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    #dataTable .btn-detail {
        background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
    }
    #dataTable .btn-danger {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    }
    #dataTable .btn:hover {
        filter: brightness(1.08);
        transform: translateY(-2px) scale(1.03);
        box-shadow: 0 4px 16px rgba(56,142,60,0.13);
    }
    #dataTable .empty-state {
        text-align: center;
        color: #4a5568;
        font-size: 1.1em;
        padding: 32px 0;
        background: #f8fafc;
        border-radius: 0 0 10px 10px;
    }
    #dataTable .empty-state i {
        font-size: 2.2em;
        margin-bottom: 8px;
        color: #a5d6a7;
    }
    #dataTable .empty-state h3 {
        margin: 0;
        font-size: 1.1em;
        font-weight: 500;
    }
    @media (max-width: 900px) {
        .table-container {
            padding: 10px 2px;
        }
        #dataTable thead th, #dataTable tbody td {
            padding: 8px 4px;
            font-size: 0.97em;
        }
    }
    /* Rozšíření sloupce Datum narození */
    #dataTable th:nth-child(4),
    #dataTable td:nth-child(4) {
        min-width: 140px;
        width: 160px;
        max-width: 200px;
        white-space: nowrap;
    }
    .stats-container {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        justify-content: center;
        width: 100%;
        margin-bottom: 20px;
        max-width: 1200px;
    }
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        flex: 1 1 220px;
        min-width: 200px;
        max-width: 320px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        text-align: center;
        transition: transform 0.3s ease;
        margin-bottom: 0;
    }
    .stat-card:hover {
        transform: translateY(-5px);
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
                <a href="show_data.php" class="active">
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
        <div class="hero-section">
            <div class="hero-content">
                <h1><i class="fas fa-users"></i> Seznam pacientů</h1>
                <div class="subtitle">Přehled všech registrovaných pacientů v systému</div>
            </div>
        </div>

        <?php
        // Načtení databázové konfigurace
        require_once __DIR__ . '/config/database.php';
        
        // Připojení k databázi pro statistiky
        try {
            $conn = getDatabase();
        } catch (Exception $e) {
            die("Chyba připojení k databázi: " . $e->getMessage());
        }

        // Počet pacientů
        $total_patients = $conn->query("SELECT COUNT(*) as count FROM persons")->fetch_assoc()['count'];
        
        // Počet s medikací
        $with_medication = $conn->query("SELECT COUNT(*) as count FROM persons WHERE medications IS NOT NULL AND medications != ''")->fetch_assoc()['count'];
        
        // Počet s alergiemi
        $with_allergies = $conn->query("SELECT COUNT(*) as count FROM persons WHERE allergies IS NOT NULL AND allergies != ''")->fetch_assoc()['count'];

        // Kontrola poznámek bez vygenerované zprávy
        $sql_missing_reports = "
            SELECT p.id AS person_id, p.first_name, p.surname, n.id AS note_id, n.note, n.diagnosis_id, d.name AS diagnosis_text, n.created_at
            FROM persons p
            INNER JOIN diagnosis_notes n ON p.id = n.person_id
            LEFT JOIN diagnoses d ON n.diagnosis_id = d.id
            LEFT JOIN medical_reports r ON n.id = r.diagnosis_note_id
            WHERE r.diagnosis_note_id IS NULL
            ORDER BY p.surname, p.first_name, n.created_at
        ";

        $result_missing = $conn->query($sql_missing_reports);
        $patients_without_reports = [];
        $total_notes_without_reports = 0;

        if ($result_missing === false) {
            echo '<div class="alert" style="background: #ffebee; color: #b71c1c; border-left: 5px solid #b71c1c;">';
            echo '<b>Chyba SQL při kontrole chybějících zpráv:</b><br>';
            echo htmlspecialchars($conn->error);
            echo '<br><small>Dotaz: ' . htmlspecialchars($sql_missing_reports) . '</small>';
            echo '</div>';
        } elseif ($result_missing->num_rows > 0) {
            while ($row = $result_missing->fetch_assoc()) {
                $pid = $row['person_id'];
                if (!isset($patients_without_reports[$pid])) {
                    $patients_without_reports[$pid] = [
                        'first_name' => $row['first_name'],
                        'surname' => $row['surname'],
                        'notes' => []
                    ];
                }
                $patients_without_reports[$pid]['notes'][] = [
                    'note_id' => $row['note_id'],
                    'note_text' => $row['note'],
                    'diagnosis' => $row['diagnosis_text'],
                    'diagnosis_id' => $row['diagnosis_id'], // <-- add this line to ensure diagnosis_id is available
                    'created_at' => $row['created_at']
                ];
                $total_notes_without_reports++;
            }
        }
        ?>

        <?php if (!empty($patients_without_reports)): ?>
        <div class="alert">
            <div class="alert-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Upozornění - Chybějící zprávy</h3>
            </div>
            <div class="alert-content">
                <div class="alert-summary">
                    <strong><?php echo $total_notes_without_reports; ?></strong> poznámek u <strong><?php echo count($patients_without_reports); ?></strong> pacientů nemá vygenerovanou zprávu.
                </div>
                <div>Pacienti s chybějícími zprávami:</div>
                <div class="alert-patients">
                    <?php foreach ($patients_without_reports as $patient): ?>
                        <div class="patient-tag">
                            <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['surname']); ?></strong>
                            <ul style="margin:8px 0 0 0;padding-left:18px;">
                                <?php foreach ($patient['notes'] as $note): ?>
                                    <li style="margin-bottom:6px;display:flex;align-items:center;gap:8px;">
                                        <span style="font-size:0.97em;"><b>Diagnóza:</b> <?php echo htmlspecialchars(mb_strimwidth(strip_tags($note['diagnosis']), 0, 80, '…')); ?></span>
                                        <span style="font-size:0.92em;color:#ffe082;"><i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($note['created_at']))); ?></span>
                                        <a href="report.php?note_id=<?php echo urlencode($note['note_id']); ?>&person_id=<?php echo urlencode($patient['person_id'] ?? $pid); ?>&diagnosis_id=<?php echo urlencode($note['diagnosis_id'] ?? ''); ?>&diagnosis_note_id=<?php echo urlencode($note['note_id']); ?>" title="Vygenerovat zprávu" style="display:inline-flex;align-items:center;justify-content:center;margin-left:4px;padding:2px 14px;font-size:1em;border-radius:6px;background:linear-gradient(135deg,#ff3d00 0%,#d32f2f 100%);color:#fff;border:2px solid #fff;text-decoration:none;box-shadow:0 2px 8px rgba(255,87,34,0.18);font-weight:700;letter-spacing:0.5px;transition:background 0.2s,color 0.2s,box-shadow 0.2s;min-width:22px;min-height:28px;line-height:1.2;box-shadow:0 2px 8px rgba(255,87,34,0.18);">
                                            <i class="fas fa-file-medical-alt" style="margin-right:6px;color:#fff;"></i>Vygenerovat
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-user-friends"></i>
                <div class="stat-number"><?php echo $total_patients; ?></div>
                <div class="stat-label">Celkem pacientů</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-pills"></i>
                <div class="stat-number"><?php echo $with_medication; ?></div>
                <div class="stat-label">S medikací</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="stat-number"><?php echo $with_allergies; ?></div>
                <div class="stat-label">S alergiemi</div>
            </div>
        </div>

        <div class="search-container">
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="🔍 Vyhledat pacienta..." class="search-box">
        </div>

        <div class="table-container">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><i class="fas fa-hashtag"></i> ID</th>
                        <th onclick="sortTable(1)"><i class="fas fa-user"></i> Jméno</th>
                        <th onclick="sortTable(2)"><i class="fas fa-user"></i> Příjmení</th>
                        <th onclick="sortTable(3)"><i class="fas fa-calendar"></i> Datum narození</th>
                        <th onclick="sortTable(4)"><i class="fas fa-id-card"></i> Rodné číslo</th>
                        <th onclick="sortTable(5)"><i class="fas fa-pills"></i> Léky</th>
                        <th onclick="sortTable(6)"><i class="fas fa-exclamation-triangle"></i> Alergie</th>
                        <th><i class="fas fa-cogs"></i> Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Načtení všech osob
                    $sql = "SELECT * FROM persons ORDER BY id ASC";
                    $result = $conn->query($sql);

                    $conn->close();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($row['id']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['surname']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['birth_date']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['ssn']) . "</td>";
                            echo "<td>";
                            if (!empty($row['medications'])) {
                                echo "<span class='medication-pill'>" . htmlspecialchars($row['medications']) . "</span>";
                            } else {
                                echo "<span class='no-data-pill'>Žádné</span>";
                            }
                            echo "</td>";
                            echo "<td>";
                            if (!empty($row['allergies'])) {
                                echo "<span class='allergy-pill'>" . htmlspecialchars($row['allergies']) . "</span>";
                            } else {
                                echo "<span class='no-data-pill'>Žádné</span>";
                            }
                            echo "</td>";
                            echo "<td class='buttons'>";
                            echo "<a href='person_details.php?id=" . urlencode($row['id']) . "' class='btn btn-detail'><i class='fas fa-eye'></i> Detail</a>";
                            echo "<a href='delete_person.php?id=" . urlencode($row['id']) . "' class='btn btn-danger' onclick=\"return confirm('Opravdu chcete smazat tohoto pacienta?');\"><i class='fas fa-trash'></i> Smazat</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='empty-state'><i class='fas fa-user-slash'></i><h3>Žádní pacienti nebyli nalezeni.</h3></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('active');
            navbar.classList.toggle('open');
        }

        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("dataTable");
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
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("dataTable");
            tr = table.getElementsByTagName("tr");
            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[1];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const navbarLinks = document.querySelectorAll('.navbar a');
            const navbar = document.getElementById('navbar');
            
            navbarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    navbar.classList.remove('active');
                    navbar.classList.remove('open');
                });
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const navbar = document.getElementById('navbar');
            const menuIcon = document.querySelector('.menu-icon');
            
            if (!navbar.contains(event.target) && !menuIcon.contains(event.target)) {
                navbar.classList.remove('active');
                navbar.classList.remove('open');
            }
        });
    </script>
</body>
</html>