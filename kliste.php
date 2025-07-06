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

$personId = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;

// Připojení k databázi
$conn = new mysqli("localhost", "root", "", "zdrapp");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Získání jména pacienta
$personName = '';
if ($personId > 0) {
    $stmt = $conn->prepare("SELECT first_name, surname FROM persons WHERE id = ?");
    $stmt->bind_param("i", $personId);
    $stmt->execute();
    $stmt->bind_result($firstName, $surname);
    if ($stmt->fetch()) {
        $personName = htmlspecialchars($firstName . " " . $surname);
    }
    $stmt->close();
}

// Uložení bodu po kliknutí
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['x'], $_POST['y'])) {
    $x = floatval($_POST['x']);
    $y = floatval($_POST['y']);

    // Zjisti další pořadí pro tohoto pacienta
    $getOrder = $conn->prepare("SELECT IFNULL(MAX(bite_order),0)+1 AS next_order FROM tick_bites WHERE person_id = ?");
    $getOrder->bind_param("i", $personId);
    $getOrder->execute();
    $getOrder->bind_result($nextOrder);
    $getOrder->fetch();
    $getOrder->close();

    // Získání ID uživatele pro updated_by
    $updated_by = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    if (!$updated_by) {
        http_response_code(400);
        echo "Chyba: Uživatelské ID není k dispozici.";
        exit;
    }

    $sql = "INSERT INTO tick_bites (person_id, x, y, created_at, bite_order, updated_by) VALUES (?, ?, ?, NOW(), ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo "Chyba při přípravě dotazu: " . $conn->error;
        exit;
    }
    $stmt->bind_param("iddii", $personId, $x, $y, $nextOrder, $updated_by);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo "Chyba při ukládání bodu: " . $stmt->error;
        $stmt->close();
        exit;
    }
    $stmt->close();
    echo "OK";
    exit;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klíště - Záznam bodu</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%);
            min-height: 100vh;
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
            margin: 0 0 15px 0;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-section .subtitle {
            font-size: 1.2rem;
            margin-bottom: 10px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .instructions {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border: 1px solid #4caf50;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .instructions::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #388e3c 0%, #43a047 100%);
        }

        .instructions h3 {
            margin: 0 0 15px 0;
            color: #2e7d32;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .instructions p {
            margin: 0;
            color: #2d3748;
            line-height: 1.6;
        }

        .main-content {
            display: flex;
            flex-direction: row;
            gap: 30px;
            margin-bottom: 30px;
            width: 100%;
            box-sizing: border-box;
        }

        .section-card {
            border-radius: 20px;
            flex: 1 1 0;
            min-width: 0;
            height: 800px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin: 0;
            box-sizing: border-box;
            overflow: hidden;
            position: relative;
        }

        .section-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .section-card:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .section-title {
            color: #2e7d32;
            font-size: 1.6rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 2px solid #c8e6c9;
            padding-bottom: 15px;
            font-weight: 600;
        }

        .section-title i {
            color: #388e3c;
            font-size: 1.4rem;
        }

        .body-img-container {
            position: relative;
            display: inline-block;
            width: 100%;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            background: #ffffff;
            transition: transform 0.3s ease;
        }

        .body-img-container img {
            max-width: 100%;
            height: auto;
            cursor: crosshair;
            display: block;
            transition: filter 0.3s ease;
        }

        .body-img-container:hover img {
            filter: brightness(1.05);
        }

        .pinpoint {
            position: absolute;
            width: 22px;
            height: 22px;
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            pointer-events: auto;
            border: 3px solid #fff;
            box-shadow: 0 6px 20px rgba(244, 67, 54, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulseGlow 2s infinite;
        }

        .pinpoint:hover {
            transform: translate(-50%, -50%) scale(1.3);
            box-shadow: 0 8px 25px rgba(244, 67, 54, 0.7);
        }

        .pinpoint span {
            color: #fff;
            font-weight: bold;
            font-size: 11px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.6);
        }

        @keyframes pulseGlow {
            0% { box-shadow: 0 6px 20px rgba(244, 67, 54, 0.5); }
            50% { box-shadow: 0 6px 30px rgba(244, 67, 54, 0.8); }
            100% { box-shadow: 0 6px 20px rgba(244, 67, 54, 0.5); }
        }

        .points-table-wrapper {
            max-height: 720px;
            overflow-y: auto;
            width: 100%;
            border-radius: 10px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .points-table {
            width: 100%;
            min-width: 0;
            table-layout: fixed;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
        }

        .points-table th,
        .points-table td {
            word-break: break-word;
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .points-table th {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: white;
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .points-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e8f5e9;
            transition: background-color 0.3s ease;
        }

        .points-table th:first-child,
        .points-table td:first-child {
            width: 60px;
            min-width: 50px;
            max-width: 80px;
            text-align: center;
        }

        .points-table tr:hover td {
            background-color: #f1f8e9;
        }

        .points-table tr:last-child td {
            border-bottom: none;
        }

        .btn, .btn-danger, .btn-primary {
            white-space: nowrap;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(244, 67, 54, 0.4);
        }

        .btn-primary {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: white;
            padding: 15px 30px;
            font-size: 1.1rem;
            box-shadow: 0 6px 20px rgba(56, 142, 60, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(56, 142, 60, 0.4);
        }

        .back-button-container {
            text-align: center;
            margin-top: 40px;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #4caf50;
        }

        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: #388e3c;
            opacity: 0.7;
        }

        .empty-state div {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .empty-state small {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        @media (max-width: 1024px) {
            .main-content {
                flex-direction: column;
                gap: 20px;
            }
            
            .section-card {
                height: auto;
                min-height: 400px;
            }
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
            
            .hero-section {
                padding: 30px 20px;
            }
            
            .hero-section h1 {
                font-size: 2rem;
            }

            .pinpoint {
                width: 20px;
                height: 20px;
            }

            .pinpoint span {
                font-size: 10px;
            }

            .points-table th,
            .points-table td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }

            .section-card {
                padding: 20px;
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
                <h1><i class="fas fa-bug"></i> Záznam míst píchnutí klíšťat</h1>
                <?php if ($personName): ?>
                    <div class="subtitle" style="font-size:1.3rem; margin-top:15px;">
                        Pacient: <strong><?php echo $personName; ?></strong>
                    </div>
                <?php endif; ?>
                <div class="subtitle">Klikněte na obrázek těla pro označení místa vpichu</div>
            </div>
        </div>

        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Návod k použití</h3>
            <p>Klikněte myší na místo na obrázku těla, kde pacienta píchlo klíště. Bod se automaticky uloží a zobrazí v tabulce vpravo. Pro odstranění bodu použijte tlačítko "Odstranit" v tabulce.</p>
        </div>

        <div class="main-content">
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-user-injured"></i>
                    Anatomické schéma
                </h2>
                
                <div class="body-img-container" id="bodyContainer">
                    <img src="body.jpg" id="bodyImg" alt="Obraz těla">
                    <!-- Pinpointy budou přidány zde JS -->
                </div>
            </div>

            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    Záznam bodů
                </h2>
                <div class="points-table-wrapper">
                    <table class="points-table" id="pointsTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> Pořadí</th>
                                <th><i class="fas fa-calendar"></i> Datum</th>
                                <th><i class="fas fa-user-edit"></i> Přidal</th>
                                <th><i class="fas fa-cogs"></i> Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="empty-state" id="emptyState">
                                <td colspan="4">
                                    <i class="fas fa-mouse-pointer"></i>
                                    <div>Zatím nebyly zaznamenány žádné body</div>
                                    <small>Klikněte na obrázek pro přidání prvního bodu</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="back-button-container">
            <a href="person_details.php?id=<?php echo $personId; ?>" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                Zpět na detail pacienta
            </a>
        </div>
    </div>

    <script>
    const bodyImg = document.getElementById('bodyImg');
    const container = document.getElementById('bodyContainer');

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

    // Přidání bodu na obrázek
    function addPinpoint(x, y, id = null, created_at = null, order = null, updated_by_name = null) {
        const pin = document.createElement('div');
        pin.className = 'pinpoint';
        pin.style.left = (x * 100) + '%';
        pin.style.top = (y * 100) + '%';
        if (id) {
            pin.dataset.id = id;
            // Přidej pořadové číslo do bodu
            const label = document.createElement('span');
            label.textContent = order !== null ? order : id;
            pin.appendChild(label);
        }
        container.appendChild(pin);

        // Přidej do tabulky
        if (id) {
            addPointToTable(id, created_at, order, updated_by_name);
        }
    }

    // Přidání řádku do tabulky
    function addPointToTable(id, created_at, order, updated_by_name) {
        const tbody = document.getElementById('pointsTable').querySelector('tbody');
        const emptyState = document.getElementById('emptyState');
        if (emptyState) {
            emptyState.style.display = 'none';
        }
        if (document.getElementById('row-point-' + id)) return;
        const tr = document.createElement('tr');
        tr.id = 'row-point-' + id;
        tr.innerHTML = `
            <td><strong>${order !== null ? order : id}</strong></td>
            <td>${created_at ? new Date(created_at).toLocaleDateString('cs-CZ') + ' ' + new Date(created_at).toLocaleTimeString('cs-CZ', {hour: '2-digit', minute: '2-digit'}) : ''}</td>
            <td>${updated_by_name ? updated_by_name : ''}</td>
            <td>
                <button class="btn btn-danger" onclick="removePointById(${id})">
                    <i class="fas fa-trash"></i>
                    Odstranit
                </button>
            </td>`;
        tbody.appendChild(tr);
    }

    // Odebrání řádku z tabulky
    function removePointFromTable(id) {
        const row = document.getElementById('row-point-' + id);
        if (row) row.remove();
        
        // Zobraz prázdný stav pokud není žádný bod
        const tbody = document.getElementById('pointsTable').querySelector('tbody');
        const emptyState = document.getElementById('emptyState');
        if (tbody.children.length === 1 && emptyState) {
            emptyState.style.display = 'table-row';
        }
    }

    // Odstranění bodu přes tlačítko v tabulce
    window.removePointById = function(id) {
        if (confirm('Opravdu chcete tento bod odstranit?')) {
            fetch('kliste_remove.php?id=' + id, { method: 'GET' })
                .then(response => response.text())
                .then(resp => {
                    if (resp.trim() === 'OK') {
                        // Odstraň pin z obrázku
                        const pin = document.querySelector('.pinpoint[data-id="' + id + '"]');
                        if (pin) pin.remove();
                        removePointFromTable(id);
                    }
                })
                .catch(error => {
                    console.error('Chyba při odstraňování bodu:', error);
                    alert('Nastala chyba při odstraňování bodu. Zkuste to prosím znovu.');
                });
        }
    };

    bodyImg.addEventListener('click', function(e) {
        if (e.button !== 0) return; // pouze levé tlačítko

        // Získej skutečnou pozici obrázku na stránce
        const rect = bodyImg.getBoundingClientRect();

        // Pozice kliknutí relativně k obrázku
        const x = (e.clientX - rect.left) / bodyImg.width;
        const y = (e.clientY - rect.top) / bodyImg.height;

        // Vizuální feedback - dočasný bod
        const tempPin = document.createElement('div');
        tempPin.className = 'pinpoint';
        tempPin.style.left = (x * 100) + '%';
        tempPin.style.top = (y * 100) + '%';
        tempPin.style.opacity = '0.6';
        container.appendChild(tempPin);

        // AJAX pro uložení bodu
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            tempPin.remove();
            if (xhr.status === 200) {
                refreshPoints();
            } else {
                alert('Nastala chyba při ukládání bodu. Zkuste to prosím znovu.');
            }
        };
        xhr.onerror = function() {
            tempPin.remove();
            alert('Nastala chyba při ukládání bodu. Zkuste to prosím znovu.');
        };
        xhr.send('x=' + x + '&y=' + y);
    });

    // Funkce pro načtení a překreslení všech bodů a tabulky
    function refreshPoints() {
        // Odstraň všechny existující pinpointy
        document.querySelectorAll('.pinpoint').forEach(pin => pin.remove());
        // Odstraň všechny řádky v tabulce kromě prázdného stavu
        const tbody = document.getElementById('pointsTable').querySelector('tbody');
        const emptyState = document.getElementById('emptyState');
        tbody.innerHTML = '';
        if (emptyState) {
            tbody.appendChild(emptyState);
        }
        
        // Načti znovu z databáze
        fetch('kliste_load.php?person_id=<?php echo $personId; ?>')
            .then(response => response.json())
            .then(points => {
                if (points.length === 0) {
                    // Zobraz prázdný stav
                    const emptyState = document.getElementById('emptyState');
                    if (emptyState) emptyState.style.display = 'table-row';
                } else {
                    points.forEach(point => {
                        addPinpoint(point.x, point.y, point.id, point.created_at, point.bite_order, point.updated_by_name);
                    });
                }
            })
            .catch(error => {
                console.error('Chyba při načítání bodů:', error);
                alert('Nastala chyba při načítání bodů. Zkuste to prosím znovu.');
            });
    }

    // Načti body při načtení stránky
    document.addEventListener('DOMContentLoaded', function() {
        refreshPoints();
    });
    </script>
</body>
</html>