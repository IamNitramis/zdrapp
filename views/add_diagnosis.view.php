<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Správa diagnóz</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    
    <script>
        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("diagnosisTable");
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
            var input, filter, table, tr, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("diagnosisTable");
            tr = table.getElementsByTagName("tr");

            // Hledání v tabulce
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

            // Hledání v mobilních kartách
            var cards = document.querySelectorAll('.diagnosis-card');
            cards.forEach(function(card) {
                var cardText = card.textContent || card.innerText;
                if (cardText.toUpperCase().indexOf(filter) > -1) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        }

        function toggleMenu() {
            var navbar = document.getElementById("navbar");
            navbar.classList.toggle("open");
        }

        // Smooth animations for table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('#diagnosisTable tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
                row.style.animation = 'fadeInUp 0.6s ease forwards';
            });
        });
        
        document.addEventListener('DOMContentLoaded', function() {
    const navbarLinks = document.querySelectorAll('.navbar a');
    const navbar = document.getElementById('navbar');
    
    navbarLinks.forEach(link => {
        link.addEventListener('click', function() {
            navbar.classList.remove('open');
        });
    });
});
// Zavření menu při kliknutí mimo
document.addEventListener('click', function(event) {
    const navbar = document.getElementById('navbar');
    const menuIcon = document.querySelector('.menu-icon');
    
    if (!navbar.contains(event.target) && !menuIcon.contains(event.target)) {
        navbar.classList.remove('open');
    }
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
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-stethoscope"></i> Správa diagnóz</h1>
            <div class="subtitle">Přidávání a správa diagnóz v systému</div>
        </div>

        <div class="stats-form-container">
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-stethoscope"></i>
                    <div class="stat-number"><?php echo $total_diagnoses; ?></div>
                    <div class="stat-label">Celkem diagnóz</div>
                </div>
            </div>

            <div class="form-container">
                <h2><i class="fas fa-plus"></i> Přidat novou diagnózu</h2>
                <form action="add_diagnosis.php" method="POST">
                    <div class="form-group">
                        <label for="new_diagnosis">Název diagnózy:</label>
                        <input type="text" name="new_diagnosis" id="new_diagnosis" 
                               class="form-control" placeholder="Zadejte název nové diagnózy" required>
                    </div>
                    <!-- Výchozí šablona se nyní přidává automaticky v PHP -->
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Přidat diagnózu
                    </button>
                </form>
            </div>
        </div>
        <div class="table-container">
            <h2><i class="fas fa-list"></i> Seznam diagnóz</h2>
            
            <?php if (!empty($diagnoses)): ?>
            <table id="diagnosisTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><i class="fas fa-hashtag"></i> ID</th>
                        <th onclick="sortTable(1)"><i class="fas fa-stethoscope"></i> Název diagnózy</th>
                        <th><i class="fas fa-cogs"></i> Akce</th>
                        <th><i class="fas fa-user-edit"></i> Přidal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diagnoses as $diagnosis): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($diagnosis['id']); ?></strong></td>
                        <td>
                            <span class="diagnosis-name">
                                <?php echo htmlspecialchars($diagnosis['name']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="add_diagnosis.php?delete_diagnosis_id=<?php echo $diagnosis['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Opravdu chcete odstranit tuto diagnózu?')">
                                <i class="fas fa-trash"></i> Odstranit
                            </a>
                        </td>
                        <td>
                            <?php if ($diagnosis['updated_by_username']) {
                                echo htmlspecialchars($diagnosis['updated_by_username']);
                            } else {
                                echo '<span style="color:#aaa;">-</span>';
                            } ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-stethoscope"></i>
                <h3>Žádné diagnózy</h3>
                <p>V systému nejsou zatím žádné diagnózy. Přidejte první diagnózu pomocí formuláře výše.</p>
            </div>
            <?php endif; ?>
        </div>

       
    </div>
</body>
</html>
