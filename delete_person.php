<?php
// Nastavení připojení k databázi
$conn = new mysqli("localhost", "root", "", "zdrapp");

// Kontrola připojení
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Získání ID osoby z URL a jeho sanitizace
$personId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Načtení údajů o osobě pro potvrzení smazání
$sql = "SELECT * FROM persons WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $personId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Person not found.";
    $stmt->close();
    $conn->close();
    exit;
}

$person = $result->fetch_assoc();
$stmt->close();

// Odstranění záznamu
$sql = "DELETE FROM persons WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $personId);

if ($stmt->execute()) {
    echo "Record deleted successfully.";
} else {
    echo "Error deleting record: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Přesměrování zpět na seznam
header("Location: show_data.php");
exit;
?>
