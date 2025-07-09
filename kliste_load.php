<?php
require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}
$personId = intval($_GET['person_id']);
$result = $conn->query("SELECT tb.id, tb.x, tb.y, tb.created_at, tb.bite_order, u.username AS updated_by_name FROM tick_bites tb LEFT JOIN users u ON tb.updated_by = u.id WHERE tb.person_id = $personId ORDER BY tb.bite_order ASC;
");
$pins = [];
while ($row = $result->fetch_assoc()) $pins[] = $row;
header('Content-Type: application/json');
echo json_encode($pins);
?>