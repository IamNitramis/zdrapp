<?php
$conn = new mysqli("localhost", "root", "", "zdrapp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$personId = $_GET['id'];

// Načtení údajů o osobě pro potvrzení smazání
$sql = "SELECT * FROM persons WHERE id = $personId";
$result = $conn->query($sql);
$person = $result->fetch_assoc();

// Odstranění záznamu
$sql = "DELETE FROM persons WHERE id = $personId";
if ($conn->query($sql) === TRUE) {
    echo "Record deleted successfully";
} else {
    echo "Error deleting record: " . $conn->error;
}

$conn->close();

// Přesměrování zpět na seznam
header("Location: show_data.php");
exit;
?>
