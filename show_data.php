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
    <style>
        body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start; /* Zajištění, že obsah nebude centrován */
        justify-content: flex-start; /* Ujistíme se, že obsah začne od vrchu */
        min-height: 100vh;
        background: linear-gradient(135deg, #4facfe, #00f2fe);
        color: #333;
        }
        .login-warning {
            max-width: 400px;
            margin: 100px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px #ccc;
            padding: 40px 30px;
            text-align: center;
        }
        .login-warning h2 { color: #e67e22; margin-bottom: 20px; }
        .login-warning a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 25px;
            background: #3498db;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s;
        }
        .login-warning a:hover { background: #217dbb; }
    </style>
</head>
<body>
    <div class="login-warning">
        <h2>Přístup zamítnut</h2>
        <p>Pro zobrazení této stránky se musíte přihlásit.</p>
        <a href="login.php">Přihlásit se</a>
    </div>
</body>
</html>
<?php exit; endif; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Show Data</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <script>
        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("dataTable");
            switching = true;
            dir = "asc"; // Nastavení výchozího směru řazení

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
    </script>
</head>

<body>
    <div class="container">
    <h1 align="center">Seznam</h1>
        <table id="dataTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">ID</th>
                    <th onclick="sortTable(1)">Jméno</th>
                    <th onclick="sortTable(2)">Příjmení</th>
                    <th onclick="sortTable(3)">Datum narození</th>
                    <th onclick="sortTable(4)">Rodné číslo</th>
                    <th onclick="sortTable(5)">Léky</th>
                    <th onclick="sortTable(6)">Alergie</th>
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Připojení k databázi
                $conn = new mysqli("localhost", "root", "", "zdrapp");

                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }


                // Načtení všech osob
                $sql = "SELECT * FROM persons";
                $result = $conn->query($sql);

                $conn->close();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['first_name'] . "</td>";
                        echo "<td>" . $row['surname'] . "</td>";
                        echo "<td>" . $row['birth_date'] . "</td>";
                        echo "<td>" . $row['ssn'] . "</td>";
                        echo "<td>" . (empty($row['medications']) ? "Žádné" : $row['medications']) . "</td>";
                        echo "<td>" . (empty($row['allergies']) ? "Žádné" : $row['allergies']) . "</td>";
                        echo "<td class='buttons'>";
                        echo "<a href='person_details.php?id=" . $row['id'] . "&surname=" . $row['surname'] . "'><button class='detail'>Detail</button></a>";
                        echo "<br><br><a href='delete_person.php?id=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this record?\");'><button class='delete'>Delete</button></a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <br>
        
    </div>
   
    <script>
    // Funkce pro přepnutí viditelnosti menu při kliknutí na burger ikonu
    function toggleMenu() {
        var navbar = document.getElementById("navbar");
        navbar.classList.toggle("open");
    }
    </script>

    <div class="header">
        <!-- Logo jako obrázek -->
        <a href="show_data.php" class="logo">
            <img src="logo.png" alt="MyApp Logo" width="100">
        </a>
        
        <!-- Burger Menu -->
        <div class="menu-icon" onclick="toggleMenu()">&#9776;</div>
        
        <!-- Navigační menu -->
        <div class="navbar" id="navbar">
            <a href="upload_csv.php">Nahrát data</a>
            <a href="add_diagnosis.php">Přidat diagnózu</a>
            <a href="download_reports.php">Stáhnout zprávy</a>
            <a href="logout.php">Logout</a>
    </div>
</div>
</body>
</html>
