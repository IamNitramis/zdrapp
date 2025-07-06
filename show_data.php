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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%);
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
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }

        .hero-section {
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
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

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-section h1 {
            font-size: 2.5rem;
            margin: 0;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-section .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 10px;
        }

        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .stat-card {
            background: #f1f8e9;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            min-width: 200px;
            flex: 1 1 0;
            max-width: 300px;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #388e3c;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #4a5568;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .search-container {
            background: #f1f8e9;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 100%;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-box {
            width: 100%;
            max-width: 600px;
            padding: 15px 20px;
            border: 2px solid #c8e6c9;
            border-radius: 50px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .search-box:focus {
            outline: none;
            border-color: #388e3c;
            box-shadow: 0 0 0 3px rgba(56, 142, 60, 0.1);
        }

        .table-container {
            background: #f1f8e9;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        #dataTable {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        #dataTable thead {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: white;
        }

        #dataTable th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            transition: background-color 1s ease;
            position: relative;
        }

        

        #dataTable th::after {
            content: '\f0dc';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 10px;
            opacity: 0.6;
        }

        #dataTable tbody tr {
            border-bottom: 1px solid #e8f5e9;
        }

        #dataTable tbody tr:hover {
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e9 100%);
            box-shadow: 0 4px 15px rgba(56, 142, 60, 0.1);
        }

        #dataTable td {
            padding: 16px 15px;
            border-bottom: 1px solid #e8f5e9;
            color: #2d3748;
            font-size: 0.95rem;
        }

        #dataTable td:first-child {
            font-weight: 600;
            color: #388e3c;
        }

        .buttons {
            display: flex;
            gap: 10px;
            flex-direction: column;
            align-items: center;
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
            min-width: 100px;
            justify-content: center;
        }

        .btn-detail {
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
            color: white;
        }

        .btn-detail:hover {
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #4a5568;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #a5d6a7;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #2d3748;
        }

        .medication-pill {
            background: #e8f5e9;
            color: #388e3c;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid #c8e6c9;
        }

        .allergy-pill {
            background: #ffebee;
            color: #d32f2f;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid #ffcdd2;
        }

        .no-data-pill {
            background: #f5f5f5;
            color: #757575;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-style: italic;
            border: 1px solid #e0e0e0;
        }

        /* Alert styles */
        .alert {
            background: linear-gradient(135deg, #ff8a80 0%, #ff5722 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(255, 87, 34, 0.3);
            border-left: 5px solid #d32f2f;
        }

        .alert-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .alert-header i {
            font-size: 1.5rem;
            color: #ffeb3b;
        }

        .alert-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .alert-content {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }

        .alert-patients {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .patient-tag {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .alert-summary {
            font-size: 1.1rem;
            margin-bottom: 10px;
            font-weight: 500;
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
            
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .buttons {
                flex-direction: row;
                gap: 5px;
            }
            
            .btn {
                min-width: 80px;
                font-size: 0.8rem;
                padding: 6px 12px;
            }

            .alert-patients {
                flex-direction: column;
            }
        }
    </style>

    <script>
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

                    if (n === 0) { // ID sloupec - řadit číselně
                        var xVal = parseInt(x.textContent || x.innerText, 10);
                        var yVal = parseInt(y.textContent || y.innerText, 10);
                        if (dir == "asc") {
                            if (xVal > yVal) {
                                shouldSwitch = true;
                                break;
                            }
                        } else if (dir == "desc") {
                            if (xVal < yVal) {
                                shouldSwitch = true;
                                break;
                            }
                        }
                    } else { // Ostatní sloupce - řadit textově
                        if (dir == "asc") {
                            if ((x.textContent || x.innerText).toLowerCase() > (y.textContent || y.innerText).toLowerCase()) {
                                shouldSwitch = true;
                                break;
                            }
                        } else if (dir == "desc") {
                            if ((x.textContent || x.innerText).toLowerCase() < (y.textContent || y.innerText).toLowerCase()) {
                                shouldSwitch = true;
                                break;
                            }
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
        }

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
        // Připojení k databázi pro statistiky
        $conn = new mysqli("localhost", "root", "", "zdrapp");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
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
                <div class="alert-patients" style="flex-direction:column;gap:18px;align-items:stretch;">
                    <?php foreach ($patients_without_reports as $patient): ?>
                        <div class="patient-tag" style="background:rgba(255,255,255,0.15);padding:12px 18px;">
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
</body>
</html>