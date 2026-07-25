<?php
require_once __DIR__ . '/includes/auth.php';

// Načtení databázové konfigurace
require_once __DIR__ . '/config/database.php';



$personId = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;

// Připojení k databázi pomocí nové funkce
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
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

// Uložení poznámky k bodu (AJAX inline poznámka)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['note'])) {
    $id = intval($_POST['id']);
    $note = trim($_POST['note']);
    try {
        $conn = getDatabase();
    } catch (Exception $e) {
        http_response_code(500);
        echo 'Chyba připojení k databázi';
        exit;
    }
    $stmt = $conn->prepare("UPDATE tick_bites SET note = ? WHERE id = ?");
    if (!$stmt) {
        http_response_code(500);
        echo 'Chyba při přípravě dotazu';
        exit;
    }
    $stmt->bind_param("si", $note, $id);
    if ($stmt->execute()) {
        echo 'OK';
    } else {
        http_response_code(500);
        echo 'Chyba při ukládání poznámky';
    }
    $stmt->close();
    $conn->close();
    exit;
}
?>
<?php require __DIR__ . '/views/kliste.view.php'; ?>