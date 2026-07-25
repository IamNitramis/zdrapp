<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ZDRAPP</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
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
