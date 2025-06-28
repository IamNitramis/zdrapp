<?php
$conn = new mysqli("localhost", "root", "", "zdrapp");
$id = intval($_GET['id']);
$conn->query("DELETE FROM tick_bites WHERE id = $id");
echo "OK";
?>