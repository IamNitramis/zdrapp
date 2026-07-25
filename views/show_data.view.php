<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seznam pacientů - ZDRAPP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">    
    <link rel="stylesheet" href="assets/css/show_data.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="hero-section">
            <div class="hero-content">
                <h1><i class="fas fa-users"></i> Seznam pacientů</h1>
                <div class="subtitle">Přehled všech registrovaných pacientů v systému</div>
            </div>
        </div>

        <?php if (!empty($sql_error)): ?>
            <div class="alert" style="background: #ffebee; color: #b71c1c; border-left: 5px solid #b71c1c;">
                <b>Chyba SQL při kontrole chybějících zpráv:</b><br>
                <?php echo htmlspecialchars($sql_error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($patients_without_reports)): ?>
        <div class="alert-diagnosis">
            <div class="alert-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Upozornění - Chybějící zprávy</h3>
            </div>
            <div class="alert-content">
                <div class="alert-summary">
                    <strong><?php echo $total_notes_without_reports; ?></strong> poznámek u <strong><?php echo count($patients_without_reports); ?></strong> pacientů nemá vygenerovanou zprávu.
                </div>
                <div style="text-align:center;margin-bottom:10px;">
                    <form action="batch_generate_reports.php" method="post" style="display:inline;">
                        <button type="submit" class="btn btn-success" style="background:linear-gradient(135deg,#43a047 0%,#388e3c 100%);color:#fff;padding:8px 22px;font-size:1.08em;border-radius:8px;font-weight:700;box-shadow:0 2px 8px rgba(56,142,60,0.18);margin-bottom:6px;">
                            <i class="fas fa-magic"></i> Automaticky vygenerovat vše
                        </button>
                    </form>
                </div>
                <div>Pacienti s chybějícími zprávami:</div>
                <div class="alert-patients">
                    <?php foreach ($patients_without_reports as $patient): ?>
                        <div class="patient-tag">
                            <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['surname']); ?></strong>
                            <ul style="margin:8px 0 0 0;padding-left:18px;">
                                <?php foreach ($patient['notes'] as $note): ?>
                                    <li style="margin-bottom:6px;display:flex;align-items:center;gap:8px;">
                                        <span style="font-size:0.97em;"><b>Diagnóza:</b> <?php echo htmlspecialchars(mb_strimwidth(strip_tags($note['diagnosis']), 0, 80, '…')); ?></span>
                                        <span style="font-size:0.92em;color:#ffe082;"><i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($note['created_at']))); ?></span>
                                        <a href="report.php?note_id=<?php echo urlencode($note['note_id']); ?>&person_id=<?php echo urlencode($patient['person_id'] ?? $patient['pid']); ?>&diagnosis_id=<?php echo urlencode($note['diagnosis_id'] ?? ''); ?>&diagnosis_note_id=<?php echo urlencode($note['note_id']); ?>" title="Vygenerovat zprávu" style="display:inline-flex;align-items:center;justify-content:center;margin-left:4px;padding:2px 14px;font-size:1em;border-radius:6px;background:linear-gradient(135deg,#ff3d00 0%,#d32f2f 100%);color:#fff;border:2px solid #fff;text-decoration:none;box-shadow:0 2px 8px rgba(255,87,34,0.18);font-weight:700;letter-spacing:0.5px;transition:background 0.2s,color 0.2s,box-shadow 0.2s;min-width:22px;min-height:28px;line-height:1.2;box-shadow:0 2px 8px rgba(255,87,34,0.18);">
                                            <i class="fas fa-file-medical-alt" style="margin-right:6px;color:#fff;"></i>Vygenerovat
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
                        <th onclick="sortTable(7)"><i class="fas fa-shield-alt"></i> Pojišťovna</th>
                        <th onclick="sortTable(8)"><i class="fas fa-ellipsis-h"></i> Další</th>
                        <th><i class="fas fa-cogs"></i> Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($persons)): ?>
                        <?php foreach ($persons as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['surname']); ?></td>
                                <td>
                                    <?php
                                    $birth_date = $row['birth_date'];
                                    if (!empty($birth_date) && preg_match('/^\d{4}-\d{2}-\d{2}/', $birth_date)) {
                                        echo date('d.m.Y', strtotime($birth_date));
                                    } else {
                                        echo htmlspecialchars($birth_date);
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['ssn']); ?></td>
                                <td>
                                    <?php if (!empty($row['medications'])): ?>
                                        <span class='medication-pill'><?php echo htmlspecialchars($row['medications']); ?></span>
                                    <?php else: ?>
                                        <span class='no-data-pill'>Žádné</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['allergies'])): ?>
                                        <span class='allergy-pill'><?php echo htmlspecialchars($row['allergies']); ?></span>
                                    <?php else: ?>
                                        <span class='no-data-pill'>Žádné</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['insurance'])): ?>
                                        <?php echo htmlspecialchars($row['insurance']); ?>
                                    <?php else: ?>
                                        <span class='no-data-pill'>Žádné</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['other'])): ?>
                                        <?php echo htmlspecialchars($row['other']); ?>
                                    <?php else: ?>
                                        <span class='no-data-pill'>Žádné</span>
                                    <?php endif; ?>
                                </td>
                                <td class='buttons'>
                                    <a href='person_details.php?id=<?php echo urlencode($row['id']); ?>' class='btn btn-detail'><i class='fas fa-eye'></i> Detail</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='10' class='empty-state'><i class='fas fa-user-slash'></i><h3>Žádní pacienti nebyli nalezeni.</h3></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('active');
            navbar.classList.toggle('open');
        }

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
            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[1];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const navbarLinks = document.querySelectorAll('.navbar a');
            const navbar = document.getElementById('navbar');
            
            navbarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    navbar.classList.remove('active');
                    navbar.classList.remove('open');
                });
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const navbar = document.getElementById('navbar');
            const menuIcon = document.querySelector('.menu-icon');
            
            if (!navbar.contains(event.target) && !menuIcon.contains(event.target)) {
                navbar.classList.remove('active');
                navbar.classList.remove('open');
            }
        });
    </script>
</body>
</html>
