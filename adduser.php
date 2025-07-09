<?php
session_start();

// Načtení databázové konfigurace
require_once __DIR__ . '/config/database.php';

// Only allow access if logged in and is admin (optional, can be removed if not needed)
// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
//     header('Location: login.php');
//     exit;
// }

// Připojení k databázi
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? 'user');
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');

    if ($username === '' || $password === '' || $firstname === '' || $lastname === '') {
        $message = '<div style="color:red;">Vyplňte všechna pole.</div>';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role, firstname, lastname) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $hash, $role, $firstname, $lastname);
        if ($stmt->execute()) {
            $message = '<div style="color:green;">Uživatel úspěšně přidán.</div>';
        } else {
            $message = '<div style="color:red;">Chyba: ' . htmlspecialchars($conn->error) . '</div>';
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přidat uživatele</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .adduser-container { max-width: 400px; margin: 60px auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(56,142,60,0.10); padding: 32px 28px; }
        h2 { text-align: center; color: #388e3c; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: 600; color: #2d3748; margin-bottom: 7px; }
        input, select { width: 100%; padding: 11px 14px; border: 2px solid #c8e6c9; border-radius: 7px; font-size: 1rem; background: #f8f9fa; box-sizing: border-box; }
        input:focus, select:focus { outline: none; border-color: #388e3c; background: #fff; }
        .btn { width: 100%; background: linear-gradient(135deg, #388e3c 0%, #43a047 100%); color: #fff; border: none; border-radius: 7px; padding: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: linear-gradient(135deg, #43a047 0%, #388e3c 100%); }
        .message { margin-bottom: 18px; text-align: center; }
    </style>
</head>
<body>
    <div class="adduser-container">
        <h2><i class="fas fa-user-plus"></i> Přidat uživatele</h2>
        <?php if ($message) echo '<div class="message">' . $message . '</div>'; ?>
        <form method="post" autocomplete="off">
            <div class="form-group">
                <label for="username">Uživatelské jméno:</label>
                <input type="text" name="username" id="username" required>
            </div>
             <div class="form-group">
                <label for="firstname">Jméno:</label>
                <input type="text" name="firstname" id="firstname" required>
            </div>
            <div class="form-group">
                <label for="lastname">Příjmení:</label>
                <input type="text" name="lastname" id="lastname" required>
            </div>
            <div class="form-group">
                <label for="password">Heslo:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select name="role" id="role">
                    <option value="user">Uživatel</option>
                    <option value="admin">Administrátor</option>
                </select>
            </div>
            <button type="submit" class="btn"><i class="fas fa-save"></i> Přidat</button>
        </form>
    </div>
</body>
</html>
