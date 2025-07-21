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
            <?php
            $current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            function navActive($file) {
                global $current;
                // Zvýrazní i pokud je v URL parametr (např. add_diagnosis.php?id=1)
                return strpos($current, $file) === 0 ? 'active' : '';
            }
            ?>
            <a href="show_data.php" class="<?php echo navActive('show_data.php'); ?>">
                <i class="fas fa-users"></i>
                Přehled
            </a>
            <a href="upload_csv.php" class="<?php echo navActive('upload_csv.php'); ?>">
                <i class="fas fa-upload"></i>
                Nahrát data
            </a>
            <a href="add_diagnosis.php" class="<?php echo navActive('add_diagnosis.php'); ?>">
                <i class="fas fa-plus-circle"></i>
                Přidat diagnózu
            </a>
            <a href="download_reports.php" class="<?php echo navActive('download_reports.php'); ?>">
                <i class="fas fa-download"></i>
                Stáhnout zprávy
            </a>
            <a href="add_report.php" class="<?php echo navActive('add_report.php'); ?>">
                <i class="fas fa-file-medical"></i>
                Upravit šablonu
            </a>
            <a href="stats.php" class="<?php echo navActive('stats.php'); ?>">
                <i class="fas fa-chart-bar"></i>
                Statistiky
            </a>
            <a href="faq.php" class="<?php echo navActive('faq.php'); ?>">
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
</script>
