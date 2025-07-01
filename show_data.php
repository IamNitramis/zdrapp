<?php
session_start(); // Spustí session, aby bylo možné kontrolovat přihlášení

// Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přístup zamítnut</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: #333;
        }
        .login-warning {
            max-width: 450px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            padding: 50px 40px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-warning i {
            font-size: 4rem;
            color: #e67e22;
            margin-bottom: 20px;
        }
        .login-warning h2 { 
            color: #2d3748; 
            margin-bottom: 15px; 
            font-size: 1.8rem;
            font-weight: 600;
        }
        .login-warning p {
            color: #718096;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .login-warning a {
            display: inline-block;
            padding: 15px 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .login-warning a:hover { 
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-warning">
        <i class="fas fa-lock"></i>
        <h2>Přístup zamítnut</h2>
        <p>Pro zobrazení této stránky se musíte přihlásit.</p>
        <a href="login.php">
            <i class="fas fa-sign-in-alt"></i>
            Přihlásit se
        </a>
    </div>
</body>
</html>
<?php exit; endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seznam pacientů</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .container {
            max-width: 1400px;
            margin: 30px ;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin: 0;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 10px;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .search-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            max-width: 100%;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-box {
            width: 100%;
            max-width: 600px;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-size: 1rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .search-box:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        #dataTable {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        #dataTable thead {
            background: linear-gradient(135deg, #4b5bb7 0%, #764ba2 100%);
            color: white;
        }

        #dataTable th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            background-color:#764ba2;
            transition: background-color 1s ease;
            position: relative;
        }

        #dataTable th:hover {
            background: #4b5bb7
        }

        #dataTable th::after {
            content: '\f0dc';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 10px;
            opacity: 0.6;
        }

        #dataTable tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f5f9;
        }

        #dataTable tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #e6f2ff 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        }

        #dataTable td {
            padding: 16px 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #4a5568;
            font-size: 0.95rem;
        }

        #dataTable td:first-child {
            font-weight: 600;
            color: #667eea;
        }

        .buttons {
            display: flex;
            gap: 10px;
            flex-direction: column;
            align-items: center;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-width: 100px;
            justify-content: center;
        }

        .btn-detail {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #cbd5e0;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #4a5568;
        }

        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            flex: 1;
            min-width: 200px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .medication-pill {
            background: #e6fffa;
            color: #38b2ac;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid #b2f5ea;
        }

        .allergy-pill {
            background: #fed7e2;
            color: #d53f8c;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid #fbb6ce;
        }

        .no-data-pill {
            background: #f7fafc;
            color: #a0aec0;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-style: italic;
            border: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .stats-container {
                flex-direction: column;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .buttons {
                flex-direction: row;
                gap: 5px;
            }
            
            .btn {
                min-width: 80px;
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    </style>

    <script>
        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("dataTable");
            switching = true;
            dir = "asc";

            while (switching) {
                switching = false;
                rows = table.rows;

                for (i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;
                    x = rows[i].getElementsByTagName("TD")[n];
                    y = rows[i + 1].getElementsByTagName("TD")[n];

                    if (dir == "asc") {
                        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir == "desc") {
                        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                }

                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }

        function searchTable() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("dataTable");
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                tr[i].style.display = "none";
                td = tr[i].getElementsByTagName("td");
                for (var j = 0; j < td.length; j++) {
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                            break;
                        }
                    }
                }
            }
        }
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
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Seznam pacientů</h1>
            <div class="subtitle">Přehled všech registrovaných pacientů v systému</div>
        </div>

        <?php
        // Připojení k databázi pro statistiky
        $conn = new mysqli("localhost", "root", "", "zdrapp");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Počet pacientů
        $total_patients = $conn->query("SELECT COUNT(*) as count FROM persons")->fetch_assoc()['count'];
        
        // Počet s medikací
        $with_medication = $conn->query("SELECT COUNT(*) as count FROM persons WHERE medications IS NOT NULL AND medications != ''")->fetch_assoc()['count'];
        
        // Počet s alergiemi
        $with_allergies = $conn->query("SELECT COUNT(*) as count FROM persons WHERE allergies IS NOT NULL AND allergies != ''")->fetch_assoc()['count'];
        ?>

        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-user-friends"></i>
                <div class="stat-number"><?php echo $total_patients; ?></div>
                <div class="stat-label">Celkem pacientů</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-pills"></i>
                <div class="stat-number"><?php echo $with_medication; ?></div>
                <div class="stat-label">S medikací</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="stat-number"><?php echo $with_allergies; ?></div>
                <div class="stat-label">S alergiemi</div>
            </div>
        </div>

        <div class="search-container">
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="🔍 Vyhledat pacienta..." class="search-box">
        </div>

        <div class="table-container">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><i class="fas fa-hashtag"></i> ID</th>
                        <th onclick="sortTable(1)"><i class="fas fa-user"></i> Jméno</th>
                        <th onclick="sortTable(2)"><i class="fas fa-user"></i> Příjmení</th>
                        <th onclick="sortTable(3)"><i class="fas fa-calendar"></i> Datum narození</th>
                        <th onclick="sortTable(4)"><i class="fas fa-id-card"></i> Rodné číslo</th>
                        <th onclick="sortTable(5)"><i class="fas fa-pills"></i> Léky</th>
                        <th onclick="sortTable(6)"><i class="fas fa-exclamation-triangle"></i> Alergie</th>
                        <th><i class="fas fa-cogs"></i> Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Načtení všech osob
                    $sql = "SELECT * FROM persons ORDER BY surname, first_name";
                    $result = $conn->query($sql);

                    $conn->close();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($row['id']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['surname']) . "</td>";
                            echo "<td>" . htmlspecialchars(date('d.m.Y', strtotime($row['birth_date']))) . "</td>";
                            echo "<td>" . htmlspecialchars($row['ssn']) . "</td>";
                            
                            // Léky s barevným označením
                            if (empty($row['medications'])) {
                                echo "<td><span class='no-data-pill'>Žádné</span></td>";
                            } else {
                                echo "<td><span class='medication-pill'>" . htmlspecialchars($row['medications']) . "</span></td>";
                            }
                            
                            // Alergie s barevným označením
                            if (empty($row['allergies'])) {
                                echo "<td><span class='no-data-pill'>Žádné</span></td>";
                            } else {
                                echo "<td><span class='allergy-pill'>" . htmlspecialchars($row['allergies']) . "</span></td>";
                            }
                            
                            echo "<td class='buttons'>";
                            echo "<a href='person_details.php?id=" . $row['id'] . "&surname=" . urlencode($row['surname']) . "' class='btn btn-detail'>";
                            echo "<i class='fas fa-eye'></i> Detail</a>";
                            echo "<a href='delete_person.php?id=" . $row['id'] . "' class='btn btn-danger' onclick='return confirm(\"Opravdu chcete odstranit tento záznam?\");'>";
                            echo "<i class='fas fa-trash'></i> Smazat</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>";
                        echo "<div class='empty-state'>";
                        echo "<i class='fas fa-user-slash'></i>";
                        echo "<h3>Žádní pacienti</h3>";
                        echo "<p>V databázi nejsou žádní zaregistrovaní pacienti.</p>";
                        echo "</div>";
                        echo "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleMenu() {
            var navbar = document.getElementById("navbar");
            navbar.classList.toggle("open");
        }

        // Smooth animations for table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('#dataTable tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
                row.style.animation = 'fadeInUp 0.6s ease forwards';
            });
        });

        // Add CSS for fade in animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>

    
</div>
</body>
</html>