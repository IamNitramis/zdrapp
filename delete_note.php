<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $note_id = intval($_POST['id']);

    $conn = new mysqli("localhost", "root", "", "zdrapp");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Zjisti person_id pro redirect
    $result = $conn->query("SELECT person_id FROM diagnosis_notes WHERE id = $note_id");
    $person_id = 0;
    if ($row = $result->fetch_assoc()) {
        $person_id = $row['person_id'];
    }

    $conn->query("DELETE FROM diagnosis_notes WHERE id = $note_id");
    $conn->close();

    // Přesměrování zpět na detail osoby
    header("Location: person_details.php?id=" . $person_id);
    exit;
} else {
    echo "Neplatný požadavek.";
}
?>