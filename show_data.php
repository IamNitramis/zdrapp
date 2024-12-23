<?php
session_start(); // Spustí session, aby bylo možné kontrolovat přihlášení

// Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Zobrazení chybové zprávy
    echo "<div style='text-align: center; padding: 50px;'>
            <h2>You must be logged in to access this page.</h2>
            <p><a href='login.php'>Click here to login</a></p>
          </div>";
    exit; // Ukončení skriptu, aby uživatel neměl přístup k dalším částem stránky
}

// Připojení k databázi
$conn = new mysqli("localhost", "root", "", "zdrapp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Načtení všech osob
$sql = "SELECT * FROM persons";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Show Data</title>
    <link rel="stylesheet" href="style.css">
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
    <br>
    <div class="container">
    <h1 align="center">Seznam</h1>
    <br>
        <table id="dataTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">ID</th>
                    <th onclick="sortTable(1)">Jméno</th>
                    <th onclick="sortTable(2)">Příjmení</th>
                    <th onclick="sortTable(3)">Datum narození</th>
                    <th onclick="sortTable(4)">Rodné číslo</th>
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['first_name'] . "</td>";
                        echo "<td>" . $row['surname'] . "</td>";
                        echo "<td>" . $row['birth_date'] . "</td>";
                        echo "<td>" . $row['ssn'] . "</td>";
                        echo "<td class='buttons'>";
                        echo "<a href='person_details.php?id=" . $row['id'] . "&surname=" . $row['surname'] . "'><button class='detail'>Detail</button></a>";
                        echo "<a href='delete_person.php?id=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this record?\");'><button class='delete'>Delete</button></a>";
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
            <a href="show_data.php">Přehled</a>
            <a href="upload_csv.php">Nahrát data</a>
            <a href="add_diagnosis.php">Přidat diagnózu</a>
            <a href="login.php">Login</a>
            <a href="logout.php">Logout</a>
    </div>
</div>
</body>
</html>
