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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ZDRAPP</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            padding: 15px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
            transition: box-shadow 0.3s ease;
        }

        .logo:hover img {
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
        }

        .navbar {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .navbar a.active {
            background: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }

        .menu-icon {
            display: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .menu-icon:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
        }

        .login-wrapper {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 500px;
        }

        .login-hero {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .login-hero-content {
            position: relative;
            z-index: 1;
        }

        .login-hero h1 {
            font-size: 2.5rem;
            margin: 0 0 20px 0;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .login-hero p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            margin: 0;
        }

        .login-hero .hero-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .login-form-container {
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form h2 {
            color: #2d3748;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 600;
            text-align: center;
        }

        .login-form .subtitle {
            color: #4a5568;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: #2d3748;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #388e3c;
            background: white;
            box-shadow: 0 0 0 3px rgba(56, 142, 60, 0.1);
        }

        .form-group .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            margin-top: 12px;
        }

        .login-btn {
            width: 100%;
            padding: 15px 20px;
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(56, 142, 60, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
            border: 1px solid #feb2b2;
        }

        .error-message i {
            margin-right: 8px;
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
        }

        .login-footer a {
            color: #388e3c;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: #2e7d32;
        }

        @media (max-width: 768px) {
            .header-container {
                padding: 0 15px;
            }

            .menu-icon {
                display: block;
            }

            .navbar {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
                flex-direction: column;
                padding: 20px;
                gap: 10px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            }

            .navbar.active {
                display: flex;
            }

            .navbar a {
                text-align: center;
                width: 100%;
                margin: 0;
            }

            .container {
                padding: 20px 15px;
            }

            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 400px;
            }

            .login-hero {
                padding: 30px 20px;
            }

            .login-hero h1 {
                font-size: 2rem;
            }

            .login-hero .hero-icon {
                font-size: 3rem;
            }

            .login-form-container {
                padding: 30px 20px;
            }

            .login-form h2 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="show_data.php" class="logo">
                <img src="logo.png" alt="ZDRAPP Logo">
                <span>ZDRAPP</span>
            </a>
            <div class="menu-icon" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="navbar" id="navbar">
                <a href="show_data.php">
                    <i class="fas fa-home"></i>
                    Home
                </a>
                <a href="upload_csv.php">
                    <i class="fas fa-upload"></i>
                    Upload Data
                </a>
                <a href="download_reports.php">
                    <i class="fas fa-download"></i>
                    Stáhnout zprávy
                </a>
                <a href="login.php" class="active">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="login-wrapper">
            <div class="login-hero">
                <div class="login-hero-content">
                    <i class="fas fa-user-shield hero-icon"></i>
                    <h1>Vítejte zpět!</h1>
                    <p>Přihlaste se do zdravotnického systému ZDRAPP pro správu pacientů.</p>
                </div>
            </div>
            
            <div class="login-form-container">
                <form class="login-form" method="POST" action="login.php">
                    <h2>Přihlášení</h2>
                    <p class="subtitle">Zadejte své přihlašovací údaje</p>
                    
                    <?php if ($error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="username">Uživatelské jméno</label>
                        <input type="text" id="username" name="username" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Heslo</label>
                        <input type="password" id="password" name="password" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    
                    <button type="submit" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Přihlásit se
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>Zapomněli jste heslo? <a href="#" onclick="alert('Kontaktujte administrátora systému')">Obnovit heslo</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('active');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navbar = document.getElementById('navbar');
            const menuIcon = document.querySelector('.menu-icon');
            
            if (!navbar.contains(event.target) && !menuIcon.contains(event.target)) {
                navbar.classList.remove('active');
            }
        });

        // Add focus/blur effects to form inputs
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>