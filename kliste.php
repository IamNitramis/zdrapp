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
    $sql = "INSERT INTO tick_bites (person_id, x, y, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idd", $personId, $x, $y);
    $stmt->execute();
    $stmt->close();
    // Pro AJAX odpověď
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
    <h2>Klikněte na místo, kde vás píchlo klíště</h2>
    <label>
        <input type="radio" name="mode" value="add" id="modeAdd" checked> Přidávat body
    </label>
    <label>
        <input type="radio" name="mode" value="remove" id="modeRemove"> Odstraňovat body
    </label>
    <div class="body-img-container" id="bodyContainer">
        <img src="body.jpg" id="bodyImg" alt="Obraz těla" style="max-width: 100%; height: auto; cursor: crosshair;">
        <!-- Pinpointy budou přidány zde JS -->
    </div>

    <script>
    const bodyImg = document.getElementById('bodyImg');
    const container = document.getElementById('bodyContainer');
    let mode = 'add';
    document.getElementById('modeAdd').addEventListener('change', function() {
        if (this.checked) mode = 'add';
    });
    document.getElementById('modeRemove').addEventListener('change', function() {
        if (this.checked) mode = 'remove';
    });

    // Přidání bodu na obrázek
    function addPinpoint(x, y, id = null) {
        const pin = document.createElement('div');
        pin.className = 'pinpoint';
        pin.style.left = (x * 100) + '%';
        pin.style.top = (y * 100) + '%';
        if (id) pin.dataset.id = id;

        // Kliknutí na bod pro odstranění v režimu "remove"
        pin.addEventListener('click', function(e) {
            if (mode === 'remove') {
                e.preventDefault();
                e.stopPropagation();
                if (confirm('Opravdu chcete tento bod odstranit?')) {
                    if (pin.dataset.id) {
                        fetch('kliste_remove.php?id=' + pin.dataset.id, { method: 'GET' })
                            .then(response => response.text())
                            .then(resp => {
                                if (resp.trim() === 'OK') pin.remove();
                            });
                    } else {
                        pin.remove();
                    }
                }
            }
        });

        container.appendChild(pin);
    }

    bodyImg.addEventListener('click', function(e) {
        if (e.button !== 0) return; // pouze levé tlačítko
        if (mode !== 'add') return; // pouze v režimu přidávání
        const rect = bodyImg.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width;
        const y = (e.clientY - rect.top) / rect.height;

        // AJAX pro uložení bodu
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                addPinpoint(x, y);
            }
        };
        xhr.send('x=' + x + '&y=' + y);
    });

    // Načtení existujících bodů z PHP
    window.onload = function() {
        fetch('kliste_load.php?person_id=<?php echo $personId; ?>')
            .then(response => response.json())
            .then(data => {
                data.forEach(function(pin) {
                    addPinpoint(pin.x, pin.y, pin.id);
                });
            });
    };
    </script>
</body>
</html>