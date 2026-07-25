<?php
session_start();

// Kontrola, zda už je uživatel přihlášen
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Pokud je uživatel přihlášen, zobrazí JavaScriptovou zprávu a přesměruje na show_data.php
    echo "<script>
            alert('You are already logged in!');
            window.location.href = 'show_data.php';
          </script>";
    exit;
}

// Inicializace chybové zprávy
$error = '';

// Zpracování přihlašovacích údajů po odeslání formuláře
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Připojení k databázi
    require_once __DIR__ . '/config/database.php';
    try {
        $conn = getDatabase();
    } catch (Exception $e) {
        die("Chyba připojení k databázi: " . $e->getMessage());
    }

    // Získání údajů z formuláře
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Načtení uživatele z databáze podle uživatelského jména
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Ověření hesla
        if (password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: show_data.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }

    $conn->close();
}

require __DIR__ . '/views/login.view.php';
?>