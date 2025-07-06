<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage - ZDRAPP</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .hero-section {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: white;
            padding: 60px 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
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

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-section h1 {
            font-size: 3rem;
            margin: 0 0 20px 0;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-section p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
        }

        .feature-icon {
            font-size: 3rem;
            color: #388e3c;
            margin-bottom: 20px;
            display: block;
        }

        .feature-card h2 {
            color: #2d3748;
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .feature-card p {
            color: #4a5568;
            line-height: 1.6;
            margin: 0;
        }

        .cta-section {
            text-align: center;
            margin-top: 50px;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            color: white;
            text-decoration: none;
            padding: 18px 40px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .cta-button:hover::before {
            left: 100%;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }

        .cta-button i {
            margin-right: 10px;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #388e3c;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #4a5568;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .tech-details {
            margin-top: 50px;
        }

        .tech-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .tech-card h3 {
            color: #2d3748;
            font-size: 1.5rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .tech-card h3 i {
            color: #388e3c;
        }

        .tech-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            text-align: left;
        }

        .tech-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #388e3c;
        }

        .tech-item strong {
            color: #2d3748;
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
                padding: 10px;
            }

            .hero-section {
                padding: 40px 20px;
            }

            .hero-section h1 {
                font-size: 2.2rem;
            }

            .hero-section p {
                font-size: 1.1rem;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .feature-card {
                padding: 25px 20px;
            }

            .cta-button {
                padding: 15px 30px;
                font-size: 1rem;
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
                <a href="stats.php">
                    <i class="fas fa-chart-bar"></i>
                    Statistiky
                </a>
                <a href="faq.php">
                    <i class="fas fa-question-circle"></i>
                    FAQ
                </a>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="hero-section">
            <div class="hero-content">
                <h1><i class="fas fa-user-md"></i> ZDRAPP - Zdravotnický Aplikační Systém</h1>
                <p>Specializovaný systém pro zaznamenávání zdravotních záznamů na zotavovacích akcích</p>
            </div>
        </div>

        <div class="features">
            <div class="feature-card">
                <i class="fas fa-user-injured feature-icon"></i>
                <h2>Správa pacientů</h2>
                <p>Databáze osob s kompletními údaji (jméno, příjmení). Detailní profily pacientů s možností správy zdravotních záznamů.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-bug feature-icon"></i>
                <h2>Sledování klíšťat</h2>
                <p>Interaktivní anatomické schéma s možností označování míst píchnutí. Chronologické číslování a automatické ukládání souřadnic do databáze.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-map-marked-alt feature-icon"></i>
                <h2>Vizuální mapování</h2>
                <p>Kliknutí na obrázek těla pro přesné označení místa píchnutí. Převod pozice myši na relativní souřadnice s vizuálním zobrazením červených pinů.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-file-medical feature-icon"></i>
                <h2>Lékařské zprávy</h2>
                <p>Systém pro přidávání lékařských zpráv a diagnóz. Kompletní dokumentace pro lepší péči o pacienty a sledování zdravotního stavu.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-database feature-icon"></i>
                <h2>Import/Export dat</h2>
                <p>Nahrávání dat z CSV souborů pro hromadné zpracování. Generování a stahování reportů a statistik pro další analýzu.</p>
            </div>
            <div class="feature-card">
    <span class="feature-icon" style="position: relative; display: inline-block;">
        <i class="fas fa-wifi"></i>
        <span style="position: absolute; top: 50%; left: 0; width: 100%; height: 2px; background: red; transform: rotate(-45deg); transform-origin: center;"></span>
    </span>
    <h2>Funguje offline</h2>
    <p>Aplikace funguje plně bez připojení k internetu. Všechny funkce pro sledování a prevenci klíšťových onemocnění běží přímo ve vašem zařízení.</p>
</div>

        </div>

        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number">500+</div>
                <div class="stat-label">Pacientů</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">1200+</div>
                <div class="stat-label">Záznamů píchnutí</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">MySQL</div>
                <div class="stat-label">Databáze</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">PHP</div>
                <div class="stat-label">Backend</div>
            </div>
        </div>

        <div class="cta-section">
            <a href="login.php" class="cta-button">
                <i class="fas fa-stethoscope"></i>
                Začít používat systém
            </a>
        </div>

        <!-- Technické detaily -->
        <div class="tech-details">
            <div class="tech-card">
                <h3><i class="fas fa-code"></i> Technické řešení</h3>
                <div class="tech-grid">
                    <div class="tech-item">
                        <strong>Databáze:</strong> MySQL s tabulkami persons a tick_bites
                    </div>
                    <div class="tech-item">
                        <strong>Backend:</strong> PHP s prepared statements proti SQL injection
                    </div>
                    <div class="tech-item">
                        <strong>Frontend:</strong> JavaScript pro interaktivní mapování
                    </div>
                    <div class="tech-item">
                        <strong>Bezpečnost:</strong> Session management a kontrola oprávnění
                    </div>
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

        // Add subtle animation to feature cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>