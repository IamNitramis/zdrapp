<?php
/**
 * Skript pro vytvoření / obnovení administrátorského účtu 'admin' s heslem 'admin'.
 * Lze spustit z prohlížeče (http://localhost/zdrapp/create_admin.php) nebo z příkazové řádky.
 */

require_once __DIR__ . '/config/database.php';

$username = 'admin';
$password = 'admin';
$firstname = 'Admin';
$lastname = 'Systému';
$role = 'admin';

try {
    $conn = getDatabase();

    // Zahashování hesla podle standardu v aplikaci
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Kontrola, zda uživatel admin již existuje
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Uživatel existuje -> Aktualizujeme heslo a role
        $stmt->close();
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, role = ?, firstname = ?, lastname = ? WHERE username = ?");
        $updateStmt->bind_param("sssss", $passwordHash, $role, $firstname, $lastname, $username);
        if ($updateStmt->execute()) {
            $msg = "Účet '$username' byl úspěšně aktualizován s heslem '$password'.";
        } else {
            $msg = "Chyba při aktualizaci uživatele: " . $conn->error;
        }
        $updateStmt->close();
    } else {
        // Uživatel neexistuje -> Vytvoříme ho
        $stmt->close();
        $insertStmt = $conn->prepare("INSERT INTO users (username, password, role, firstname, lastname) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->bind_param("sssss", $username, $passwordHash, $role, $firstname, $lastname);
        if ($insertStmt->execute()) {
            $msg = "Účet '$username' byl úspěšně vytvořen s heslem '$password'.";
        } else {
            $msg = "Chyba při vytváření uživatele: " . $conn->error;
        }
        $insertStmt->close();
    }

    $conn->close();

} catch (Exception $e) {
    $msg = "Chyba: " . $e->getMessage();
}

// Výstup (podporuje CLI i webové zobrazení)
if (php_sapi_name() === 'cli') {
    echo $msg . PHP_EOL;
} else {
    echo "<!DOCTYPE html><html lang='cs'><head><meta charset='UTF-8'><title>Přidání Admina</title>";
    echo "<style>body{font-family:sans-serif;padding:40px;background:#f4f6f9;}.card{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);max-width:500px;margin:auto;}</style></head><body>";
    echo "<div class='card'>";
    echo "<h2>Správa účtu Admin</h2>";
    echo "<p><strong>" . htmlspecialchars($msg) . "</strong></p>";
    echo "<hr><p>Můžete se nyní přihlásit na <a href='login.php'>stránce přihlášení</a> s údaji:<br>Uživatel: <code>admin</code><br>Heslo: <code>admin</code></p>";
    echo "</div></body></html>";
}
