<?php
session_start();
$personId = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;

// Připojení k databázi
$conn = new mysqli("localhost", "root", "", "zdrapp");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

    $sql = "INSERT INTO tick_bites (person_id, x, y, created_at, bite_order) VALUES (?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iddi", $personId, $x, $y, $nextOrder);
    $stmt->execute();
    $stmt->close();
    echo "OK";
    exit;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Klíště - Záznam bodu</title>
    <style>
        .body-img-container {
            position: relative;
            display: inline-block;
        }
        .pinpoint {
            position: absolute;
            width: 16px;
            height: 16px;
            background: red;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            pointer-events: auto; /* změna z none na auto, aby šly zachytit události */
            border: 2px solid #fff;
            box-shadow: 0 0 4px #000;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="show_data.php" class="logo">
            <img src="logo.png" alt="ZDRAPP Logo" width="50">
        </a>
        <div class="menu-icon" onclick="toggleMenu()">&#9776;</div>
        <div class="navbar" id="navbar">
            <a href="show_data.php">Home</a>
            <a href="upload_csv.php">Upload Data</a>
            <a href="download_reports.php">Stáhnout zprávy</a>
            <a href="login.php">Login</a>
        </div>
    </div>
    
    <h2>Klikněte na místo, kde pacienta píchlo klíště</h2>
    <div style="display: flex; gap: 40px;">
        <div>
            <div class="body-img-container" id="bodyContainer" style="min-width:350px;">
                <img src="body.jpg" id="bodyImg" alt="Obraz těla" style="max-width: 100%; height: auto; cursor: crosshair;">
                <!-- Pinpointy budou přidány zde JS -->
            </div>
        </div>
        <div style="min-width:200px;">
            <h3>Seznam bodů</h3>
            <table id="pointsTable" border="1" cellpadding="5" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th>ID bodu</th>
                        <th>Datum přidání</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Body budou doplněny JS -->
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 30px;">
        <a href="person_details.php?id=<?php echo $personId; ?>">
            <button type="button" style="background:#3498db;color:#fff;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;">
                Zpět na detail pacienta
            </button>
        </a>
    </div>

    <script>
    const bodyImg = document.getElementById('bodyImg');
    const container = document.getElementById('bodyContainer');

    // Přidání bodu na obrázek
    function addPinpoint(x, y, id = null, created_at = null, order = null) {
        const pin = document.createElement('div');
        pin.className = 'pinpoint';
        pin.style.left = (x * 100) + '%';
        pin.style.top = (y * 100) + '%';
        if (id) {
            pin.dataset.id = id;
            // Přidej pořadové číslo do bodu
            const label = document.createElement('span');
            label.textContent = order !== null ? order : id;
            label.style.position = 'absolute';
            label.style.left = '50%';
            label.style.top = '50%';
            label.style.transform = 'translate(-50%, -50%)';
            label.style.color = '#fff';
            label.style.fontWeight = 'bold';
            label.style.fontSize = '12px';
            pin.appendChild(label);
        }
        container.appendChild(pin);

        // Přidej do tabulky
        if (id) {
            addPointToTable(id, created_at, order);
        }
    }

    // Přidání řádku do tabulky
    function addPointToTable(id, created_at, order) {
        const tbody = document.getElementById('pointsTable').querySelector('tbody');
        if (document.getElementById('row-point-' + id)) return;
        const tr = document.createElement('tr');
        tr.id = 'row-point-' + id;
        tr.innerHTML = `<td>${order !== null ? order : id}</td>
            <td>${created_at ? created_at : ''}</td>
            <td><button onclick="removePointById(${id})">Odstranit</button></td>`;
        tbody.appendChild(tr);
    }

    // Odebrání řádku z tabulky
    function removePointFromTable(id) {
        const row = document.getElementById('row-point-' + id);
        if (row) row.remove();
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
                });
        }
    };

    bodyImg.addEventListener('click', function(e) {
        if (e.button !== 0) return; // pouze levé tlačítko
        const rect = bodyImg.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width;
        const y = (e.clientY - rect.top) / rect.height;

        // AJAX pro uložení bodu
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Po přidání bodu načti znovu všechny body (včetně nového ID)
                refreshPoints();
            }
        };
        xhr.send('x=' + x + '&y=' + y);
    });

    // Funkce pro načtení a překreslení všech bodů a tabulky
    function refreshPoints() {
        // Odstraň všechny existující pinpointy
        document.querySelectorAll('.pinpoint').forEach(pin => pin.remove());
        // Odstraň všechny řádky v tabulce
        const tbody = document.getElementById('pointsTable').querySelector('tbody');
        tbody.innerHTML = '';
        // Načti znovu z databáze
        fetch('kliste_load.php?person_id=<?php echo $personId; ?>')
            .then(response => response.json())
            .then(data => {
                data.forEach(function(pin) {
                    addPinpoint(pin.x, pin.y, pin.id, pin.created_at, pin.bite_order);
                });
            });
    }

    // Načtení existujících bodů při načtení stránky
    window.onload = refreshPoints;
    </script>
</body>
</html>