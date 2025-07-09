<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $note_id = intval($_POST['id']);

    require_once __DIR__ . '/config/database.php';
    try {
        $conn = getDatabase();
    } catch (Exception $e) {
        die("Chyba připojení k databázi: " . $e->getMessage());
    }

    // Zjisti person_id pro redirect
    $result = $conn->query("SELECT person_id FROM diagnosis_notes WHERE id = $note_id");
    $person_id = 0;
    if ($row = $result->fetch_assoc()) {
        $person_id = $row['person_id'];
    }

    $conn->query("DELETE FROM diagnosis_notes WHERE id = $note_id");

    // Přesměrování zpět na detail osoby
    header("Location: person_details.php?id=" . $person_id);
    exit;
} else {
    echo "Neplatný požadavek.";
}
?>