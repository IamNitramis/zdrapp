<?php
$conn = new mysqli("localhost", "root", "", "zdrapp");
$personId = intval($_GET['person_id']);
$result = $conn->query("SELECT id, x, y, created_at FROM tick_bites WHERE person_id = $personId");
$pins = [];
while ($row = $result->fetch_assoc()) $pins[] = $row;
header('Content-Type: application/json');
echo json_encode($pins);
?>