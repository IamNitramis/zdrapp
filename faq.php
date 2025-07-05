<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přístup zamítnut</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .login-warning { max-width: 450px; background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); padding: 50px 40px; text-align: center; }
        .login-warning i { font-size: 4rem; color: #e67e22; margin-bottom: 20px; }
        .login-warning h2 { color: #2d3748; margin-bottom: 15px; font-size: 1.8rem; font-weight: 600; }
        .login-warning p { color: #718096; font-size: 1.1rem; margin-bottom: 30px; }
        .login-warning a { display: inline-block; padding: 15px 35px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease; }
        .login-warning a:hover { transform: translateY(-3px); }
    </style>
</head>
<body>
    <div class="login-warning">
        <i class="fas fa-lock"></i>
        <h2>Přístup zamítnut</h2>
        <p>Pro zobrazení této stránky se musíte přihlásit.</p>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Přihlásit se</a>
    </div>
</body>
</html>
<?php exit; endif; ?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - ZDRAPP</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%);
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
        .navbar a:active {
            transform: translateY(0);
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
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(56,142,60,0.08);
            padding: 40px 30px;
        }
        .faq-title { text-align: center; font-size: 2.2rem; color: #388e3c; margin-bottom: 30px; }
        .faq-list { list-style: none; padding: 0; margin: 0; }
        .faq-item { border-bottom: 1px solid #e0e0e0; }
        .faq-question { cursor: pointer; padding: 22px 0; font-size: 1.15rem; font-weight: 600; color: #2d3748; display: flex; align-items: center; justify-content: space-between; transition: color 0.2s; }
        .faq-question:hover { color: #43a047; }
        .faq-answer {
            display: block;
            max-height: 0;
            overflow: hidden;
            padding: 0 0 0 0;
            color: #4a5568;
            font-size: 1rem;
            line-height: 1.6;
            transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1), padding 0.4s;
        }
        .faq-item.active .faq-answer {
            max-height: 500px;
            padding: 12px 0 18px 0;
            animation: fadeIn 0.4s;
        }
        .faq-icon { margin-left: 15px; color: #388e3c; font-size: 1.2rem; transition: transform 0.3s; }
        .faq-item.active .faq-icon { transform: rotate(90deg); }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @media (max-width: 600px) { .container { padding: 15px 5px; } .faq-title { font-size: 1.3rem; } }
    </style>
    <script>
        function toggleMenu() {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('active');
        }
        document.addEventListener('click', function(event) {
            const navbar = document.getElementById('navbar');
            const menuIcon = document.querySelector('.menu-icon');
            if (!navbar.contains(event.target) && !menuIcon.contains(event.target)) {
                navbar.classList.remove('active');
            }
        });
        document.addEventListener('DOMContentLoaded', function() {
            var items = document.querySelectorAll('.faq-question');
            items.forEach(function(item) {
                item.addEventListener('click', function() {
                    var parent = this.parentElement;
                    parent.classList.toggle('active');
                });
            });
        });
    </script>
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
                    <i class="fas fa-users"></i>
                    Přehled
                </a>
                <a href="upload_csv.php">
                    <i class="fas fa-upload"></i>
                    Nahrát data
                </a>
                <a href="add_diagnosis.php">
                    <i class="fas fa-plus-circle"></i>
                    Přidat diagnózu
                </a>
                <a href="download_reports.php">
                    <i class="fas fa-download"></i>
                    Stáhnout zprávy
                </a>
                <a href="add_report.php">
                    <i class="fas fa-file-medical"></i>
                    Přidat lékařskou zprávu
                </a>
                <a href="stats.php">
                    <i class="fas fa-chart-bar"></i>
                    Statistiky
                </a>
                <a href="faq.php">
                    <i class="fas fa-question-circle"></i>
                    FAQ
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="faq-title"><i class="fas fa-question-circle"></i> Často kladené otázky (FAQ)</div>
        <ul class="faq-list">
            <li class="faq-item">
                <div class="faq-question">Jak přidám nového pacienta? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">Nového pacienta lze přidat pouze přes <b>Nahrát data</b></div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak mohu upravit nebo smazat pacienta? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">V přehledu pacientů klikněte na tlačítko <b>Detail</b> u konkrétního pacienta. Zde můžete upravit údaje nebo použít tlačítko <b>Smazat</b>.</div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak nahrát data z CSV souboru? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">V horním menu zvolte <b>Nahrát data</b> a nahrajte CSV soubor podle instrukcí na stránce.</div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Kde najdu statistiky o pacientech? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">Statistiky najdete v sekci <b>Statistiky</b> v horním menu. Zobrazí se zde souhrnné informace o pacientech, medikaci a alergiích.</div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak se mohu odhlásit? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">Pro odhlášení klikněte na <b>Logout</b> v pravé části horního menu.</div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Na koho se mohu obrátit v případě technických problémů? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">V případě technických problémů kontaktujte správce systému nebo napište na e-mail <b>gabriel@zgnetworks.cz</b>.</div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak přidám diagnózu a poznámku a vygeneruji lékařskou zprávu? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ol style="margin-left: 1.2em;">
                        <li>Otevřete detail pacienta kliknutím na tlačítko <b>Detail</b> v přehledu pacientů.</li>
                        <li>V sekci <b>Přidat diagnózu a poznámku</b> vyberte diagnózu ze seznamu a napište poznámku k ošetření nebo průběhu.</li>
                        <li>Klikněte na tlačítko <b>Přidat záznam</b>. Diagnóza a poznámka se uloží.</li>
                        <li>Po přidání záznamu se v seznamu diagnóz a poznámek zobrazí nová položka.</li>
                        <li>Pro vygenerování lékařské zprávy klikněte na ikonu nebo tlačítko <b>Vytvořit zprávu</b> (nebo <b>Report</b>) u konkrétní poznámky/diagnózy.</li>
                        <li>Zobrazí se předvyplněná šablona zprávy, kterou můžete upravit a uložit.</li>
                        <li>Hotovou zprávu lze stáhnout nebo vytisknout podle potřeby.</li>
                    </ol>
                </div>
            </li>
        </ul>
    </div>
</body>
</html>
