<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detaily osoby</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/person_details.css">
</head>
<body>
    
    <div class="container">
        <!-- Schéma těla bude zobrazeno u každé poznámky/diagnózy v sekci poznámek -->
        <div class="person-header">
            <h1><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['surname']); ?></h1>
            <div class="subtitle">Detail pacienta a zdravotní záznamy</div>
        </div>
        <div class="section-card section-add-note">
            <h2 class="section-title">
                Přidat diagnózu a nález
            </h2>
            <form action="" method="POST" class="form-container">
                <div class="form-group">
                    <label for="diagnosis" class="form-label">
                        <i class="fas fa-list-alt"></i>
                        Vyber diagnózu:
                    </label>
                    <select name="diagnosis_id" id="diagnosis" class="form-select" required>
                        <option value="">-- Vyberte diagnózu --</option>
                        <?php while ($row = $all_diagnoses->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['id']); ?>">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-links">
                    <a href="add_diagnosis.php" class="form-link">
                        <i class="fas fa-plus"></i>
                        Chybí vám diagnóza? (Přidat diagnózu)
                    </a>
                    <a href="add_report.php" class="form-link">
                        <i class="fas fa-file-medical"></i>
                        Upravit template zdravotnické zprávy
                    </a>
                </div>
                <div class="form-group">
                    <label for="note" class="form-label">
                        <i class="fas fa-sticky-note"></i>
                        Nález:
                    </label>
                    <textarea name="note" id="note" class="form-textarea" 
                              placeholder="Napište, jak probíhalo ošetření, co jste podali za medikaci..." required></textarea>
                </div>
                <!-- Schéma těla pro nově přidávanou poznámku (body points budou navázány na nově vzniklou diagnosis_note) -->
                <div class="form-group" style="display:flex;flex-direction:column;align-items:center;">
                    <label class="form-label" style="align-self:center;">
                        <i class="fas fa-user"></i>
                        Přidat body na schéma těla (klíšťata, vpichy, atd.)
                    </label>
                    <div class="body-img-container" id="body-img-new" style="position:relative;max-width:400px;margin:0 auto;">
                        <img src="body.jpg" alt="Schéma těla" style="width:100%;max-width:400px;display:block;cursor:pointer;">
                        <!-- Body points budou přidávány kliknutím, JS doplní souřadnice do hidden inputů -->
                    </div>
                    <div id="new-body-points-list" style="margin-top:10px;text-align:center;"></div>
                </div>
                <input type="hidden" name="body_points_json" id="body_points_json">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Uložit záznam
                    </button>
                    <a href="kliste.php?person_id=<?php echo $person_id; ?>" class="btn btn-orange">
                        <i class="fas fa-bug"></i>
                        Klíšťata
                    </a>
                </div>
            </form>
        </div>
    
        <div class="flex-sections">
            <div class="section-card section-notes">
                <h2 class="section-title">
                    <i class="fas fa-stethoscope"></i>
                    Diagnózy a nálezy
                </h2>
                <?php if ($diagnoses->num_rows > 0): ?>
                    <?php while ($row = $diagnoses->fetch_assoc()): ?>
                        <div class="diagnosis-card" onclick="window.location.href='edit_note.php?id=<?php echo $row['note_id']; ?>&first_name=<?php echo $person['first_name']; ?>&surname=<?php echo $person['surname']; ?>&person_id=<?php echo $person_id; ?>'">
                            <div class="diagnosis-header">
                                <h3 class="diagnosis-name">
                                    <i class="fas fa-diagnosis"></i>
                                    <?php echo htmlspecialchars($row['diagnosis_name']); ?>
                                </h3>
                                <span class="diagnosis-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?>
                                </span>
                            </div>
                            <div class="diagnosis-preview">
                                <?php echo htmlspecialchars(substr($row['note'], 0, 150)) . (strlen($row['note']) > 150 ? '...' : ''); ?>
                                <?php
                                // Zobraz body na schématu těla pro tuto poznámku/diagnózu
                                $note_id = $row['note_id'];
                                if (!empty($body_points_by_note[$note_id])) {
                                    echo '<div class="body-points-list" style="margin-top:10px;text-align:left;">';
                                    echo '<strong>Body na schématu těla:</strong><br>';
                                    foreach ($body_points_by_note[$note_id] as $idx => $point) {
                                        $num = $idx + 1;
                                        echo "#{$num}: X={$point['x']}%, Y={$point['y']}%";
                                        if (!empty($point['description'])) {
                                            echo " <span style='color:#388e3c;'>({$point['description']})</span>";
                                        }
                                        echo '<br>';
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <div style="color:#4a5568; font-size:0.95rem; margin-bottom:8px;">
                                <i class="fas fa-user-edit"></i> Upravil: <?php echo $row['updated_by_username'] ? htmlspecialchars($row['updated_by_username']) : '<span style="color:#aaa;">-</span>'; ?>
                            </div>
                            <div class="diagnosis-actions">
                                <form action="edit_note.php" method="GET" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $row['note_id']; ?>">
                                    <input type="hidden" name="first_name" value="<?php echo $person['first_name']; ?>">
                                    <input type="hidden" name="surname" value="<?php echo $person['surname']; ?>">
                                    <input type="hidden" name="person_id" value="<?php echo $person_id; ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-edit"></i>
                                        Upravit
                                    </button>
                                </form>
                                <form action="delete_note.php" method="POST" style="display: inline;" onsubmit="return confirm('Opravdu chcete tuto poznámku odstranit?');">
                                    <input type="hidden" name="id" value="<?php echo $row['note_id']; ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i>
                                        Smazat
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-notes-medical"></i>
                        <p>Žádné diagnózy nejsou zapsány.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="section-card section-medalergies">
                <h2 class="section-title">
                    <i class="fas fa-notes-medical"></i>
                    Medikace a alergie
                </h2>
                    <div class="form-group">
                        <label for="medications" class="form-label">
                            <i class="fas fa-pills"></i>
                            Medikace:
                        </label>
                        <input type="text" name="medications" id="medications" class="form-select" value="<?php echo htmlspecialchars($person['medications'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="allergies" class="form-label">
                            <i class="fas fa-allergies"></i>
                            Alergie:
                        </label>
                        <input type="text" name="allergies" id="allergies" class="form-select" value="<?php echo htmlspecialchars($person['allergies'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="other" class="form-label">
                            <i class="fas fa-ellipsis-h"></i>
                            Další:
                        </label>
                        <textarea name="other" id="other" class="form-select" style="resize:vertical;min-height:60px;" disabled><?php echo htmlspecialchars($person['other'] ?? ''); ?></textarea>
                    </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('active');
        }

        document.querySelectorAll('.diagnosis-card form').forEach(function(form) {
            form.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navbar = document.getElementById('navbar');
            const menuIcon = document.querySelector('.menu-icon');
            
            if (!navbar.contains(event.target) && !menuIcon.contains(event.target)) {
                navbar.classList.remove('active');
            }
        });

        // Add subtle animation to cards on scroll
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

        // Observe all section cards
        document.querySelectorAll('.section-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        // JS pro přidávání bodů na schéma těla
        const bodyImg = document.getElementById('body-img-new');
        const img = bodyImg ? bodyImg.querySelector('img') : null;
        let newBodyPoints = [];

        if (img) {
            img.addEventListener('click', function(e) {
                const rect = img.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                const pointId = newBodyPoints.length + 1;
                const point = { id: pointId, x: x.toFixed(1), y: y.toFixed(1), description: '' };
                newBodyPoints.push(point);
                renderNewBodyPoints();
            });
        }

        function renderNewBodyPoints() {
            // Remove old points
            const container = document.getElementById('body-img-new');
            container.querySelectorAll('.pinpoint').forEach(el => el.remove());
            // Add new points
            newBodyPoints.forEach((point, idx) => {
                const div = document.createElement('div');
                div.className = 'pinpoint';
                div.style.position = 'absolute';
                div.style.left = point.x + '%';
                div.style.top = point.y + '%';
                div.style.width = '20px';
                div.style.height = '20px';
                div.style.background = '#388e3c';
                div.style.borderRadius = '50%';
                div.style.border = '2px solid #fff';
                div.style.boxShadow = '0 2px 8px rgba(56,142,60,0.2)';
                div.style.display = 'flex';
                div.style.alignItems = 'center';
                div.style.justifyContent = 'center';
                div.style.color = '#fff';
                div.style.fontSize = '13px';
                div.style.cursor = 'pointer';
                div.title = point.description || '';
                div.innerHTML = `<span>${point.id}</span>`;
                // možnost odebrání bodu kliknutím pravým tlačítkem
                div.addEventListener('contextmenu', function(ev) {
                    ev.preventDefault();
                    newBodyPoints.splice(idx, 1);
                    renderNewBodyPoints();
                });
                container.appendChild(div);
            });
            // Zobrazit seznam bodů pod obrázkem
            const list = document.getElementById('new-body-points-list');
            if (newBodyPoints.length === 0) {
                list.innerHTML = '<span style="color:#aaa;">Žádné body nejsou přidány.</span>';
            } else {
                list.innerHTML = '<strong>Přidané body:</strong><br>' + newBodyPoints.map((p, i) =>
                    `#${p.id}: X=${p.x}%, Y=${p.y}% <input type='text' placeholder='Popis' value='${p.description}' style='width:120px;' onchange='window.updateBodyPointDesc(${i}, this.value)'> <span style='color:#d00;cursor:pointer;' onclick='window.removeBodyPoint(${i})'>✖</span>`
                ).join('<br>');
            }
            document.getElementById('body_points_json').value = JSON.stringify(newBodyPoints);
        }

        window.updateBodyPointDesc = function(idx, val) {
            newBodyPoints[idx].description = val;
            renderNewBodyPoints();
        }
        window.removeBodyPoint = function(idx) {
            newBodyPoints.splice(idx, 1);
            renderNewBodyPoints();
        }

        // Body se uloží pouze při submitu formuláře
        const form = document.querySelector('.section-add-note form');
        if (form) {
            form.addEventListener('submit', function() {
                document.getElementById('body_points_json').value = JSON.stringify(newBodyPoints);
            });
        }
    </script>
</body>
</html>
