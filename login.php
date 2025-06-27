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
    $conn = new mysqli("localhost", "root", "", "zdrapp");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>
<div class="header">
    <!-- Logo jako obrázek -->
    <a href="show_data.php" class="logo">
        <img src="logo.png" alt="MyApp Logo" width="100">
    </a>
    
    <!-- Burger Menu -->
    <div class="menu-icon" onclick="toggleMenu()">&#9776;</div>
    
    <!-- Navigační menu -->
    <div class="navbar" id="navbar">
        <a href="show_data.php">Přehled</a>
        <a href="upload_csv.php">Nahrát data</a>
        <a href="add_diagnosis.php">Přidat diagnózu</a>
    </div>
</div>
<script>
    function toggleMenu() {
        var navbar = document.getElementById("navbar");
        navbar.classList.toggle("open");
    }
</script>

<body>
    <div class="login-container">
        <h2>Login</h2>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>


</html>
