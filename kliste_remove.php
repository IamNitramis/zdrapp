<?php
$conn = new mysqli("localhost", "root", "", "zdrapp");
$id = intval($_GET['id']);

// Zjisti person_id před smazáním
$res = $conn->query("SELECT person_id FROM tick_bites WHERE id = $id");
if ($row = $res->fetch_assoc()) {
    $personId = $row['person_id'];
    $conn->query("DELETE FROM tick_bites WHERE id = $id");
    // Přečísluj pořadí
    $result = $conn->query("SELECT id FROM tick_bites WHERE person_id = $personId ORDER BY created_at ASC, id ASC");
    $order = 1;
    while ($r = $result->fetch_assoc()) {
        $conn->query("UPDATE tick_bites SET bite_order = $order WHERE id = " . $r['id']);
        $order++;
    }
    echo "OK";
} else {
    echo "NOT FOUND";
}
$conn->close();
?>