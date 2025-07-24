<?php
session_start();

// Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<div class='alert'>
            <h2>You must be logged in to access this page.</h2>
            <p><a href='login.php' class='link-button'>Click here to login</a></p>
          </div>";
    exit;
}

// Připojení k databázi
require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Získání ID poznámky
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Valid Note ID is required.");
}

$noteId = intval($_GET['id']);

// Načtení existující poznámky a diagnózy (JOIN mezi diagnosis_notes a diagnoses)
$sql = "
    SELECT dn.note, d.id AS diagnosis_id, d.name AS diagnosis_name, dn.created_at, dn.person_id 
    FROM diagnosis_notes dn
    JOIN diagnoses d ON dn.diagnosis_id = d.id
    WHERE dn.id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $noteId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Note not found.");
}

$note = $result->fetch_assoc();

// Zpracování formuláře
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['updated_note']) || empty(trim($_POST['updated_note']))) {
        echo "<p class='alert-error'>Poznámka nemůže být prázdná.</p>";
    } else {
        $updatedNote = trim($_POST['updated_note']);

        $updateSql = "UPDATE diagnosis_notes SET note = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            die("SQL Error: " . $conn->error);
        }

        $updateStmt->bind_param("si", $updatedNote, $noteId);

        if ($updateStmt->execute()) {
            // Uložení bodů na těle, pokud byly přidány (přes body_points_json)
            if (!empty($_POST['body_points_json'])) {
                $body_points = json_decode($_POST['body_points_json'], true);
                if (is_array($body_points)) {
                    // Smaž staré body pro tuto poznámku
                    $conn->query("DELETE FROM person_body_points WHERE diagnosis_note_id = " . intval($noteId));
                    $sql_point = "INSERT INTO person_body_points (person_id, diagnosis_note_id, x, y, description, `order`) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_point = $conn->prepare($sql_point);
                    foreach ($body_points as $idx => $point) {
                        $desc = isset($point['description']) ? $point['description'] : '';
                        $order = $idx + 1;
                        $stmt_point->bind_param("iiddsi", $note['person_id'], $noteId, $point['x'], $point['y'], $desc, $order);
                        $stmt_point->execute();
                    }
                    $stmt_point->close();
                }
            }
            header("Location: person_details.php?id=" . htmlspecialchars($note['person_id']));
            exit;
        } else {
            echo "<p class='alert-error'>Chyba při aktualizaci poznámky: " . $conn->error . "</p>";
        }
        $updateStmt->close();
    }
}

// Načtení bodů na těle navázaných na tuto poznámku
$sql_points = "SELECT id, x, y, description, created_at, `order` FROM person_body_points WHERE diagnosis_note_id = ? ORDER BY `order` ASC";
$stmt_points = $conn->prepare($sql_points);
$stmt_points->bind_param("i", $noteId);
$stmt_points->execute();
$result_points = $stmt_points->get_result();
$body_points = [];
while ($row = $result_points->fetch_assoc()) {
    $body_points[] = $row;
}
$stmt_points->close();
$stmt->close();
$conn->close();
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravit poznámku</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        .section-card-title {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            max-width: 540px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .section-card-text {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            max-width: 840px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }
        .section-title {
            font-size: 1.15rem;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Upravit poznámku</h1>
            <div class="subtitle">Úprava zdravotního záznamu pro <?php echo htmlspecialchars($_GET['first_name'] ?? 'pacienta'); ?> <?php echo htmlspecialchars($_GET['surname'] ?? ''); ?></div>
        </div>
        
        <div class="section-card-title">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i>
                Informace o diagnóze
            </h2>
            
            <div class="diagnosis-info">
                <h3><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($note['diagnosis_name']); ?></h3>
                <p>
                    <i class="fas fa-calendar-alt"></i>
                    <strong>Datum přiřazení:</strong> <?php echo htmlspecialchars(date("d.m.Y H:i", strtotime($note['created_at']))); ?>
                </p>
            </div>
        </div>

        <div class="section-card-text">
            <h2 class="section-title">
                <i class="fas fa-edit"></i>
                Úprava poznámky
            </h2>
            <!-- Schéma těla s body navázanými na tuto poznámku -->
            <div class="form-group" style="display:flex;flex-direction:column;align-items:center;">
                <label class="form-label" style="align-self:center;">
                    <i class="fas fa-user"></i>
                    Body na schématu těla (klíšťata, vpichy, atd.)
                </label>
                <div class="body-img-container" style="position:relative;max-width:400px;margin:0 auto;">
                    <img src="body.jpg" alt="Schéma těla" style="width:100%;max-width:400px;display:block;">
                    <?php foreach ($body_points as $point): ?>
                        <div class="pinpoint" style="position:absolute;left:<?php echo $point['x']; ?>%;top:<?php echo $point['y']; ?>%;width:20px;height:20px;background:#388e3c;border-radius:50%;border:2px solid #fff;box-shadow:0 2px 8px rgba(56,142,60,0.2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;cursor:pointer;" title="<?php echo htmlspecialchars($point['description'] ?? ''); ?>">
                            <span><?php echo $point['order']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:10px;text-align:center;">
                    <?php if (count($body_points) === 0): ?>
                        <span style="color:#aaa;">Žádné body nejsou zaznamenány.</span>
                    <?php else: ?>
                        <strong>Přidané body:</strong>
                        <table class="body-points-table" style="width:100%;max-width:500px;margin:10px auto;border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f5f5f5;">
                                    <th style="padding:6px;border-bottom:1px solid #ddd;">#</th>
                                    <th style="padding:6px;border-bottom:1px solid #ddd;">Pozice</th>
                                    <th style="padding:6px;border-bottom:1px solid #ddd;">Poznámka</th>
                                    <th style="padding:6px;border-bottom:1px solid #ddd;width:32px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($body_points as $idx => $p): ?>
                                <tr style="border-bottom:1px solid #eee;">
                                    <td style="text-align:center;padding:6px;font-weight:bold;color:#388e3c;">
                                        <i class="fas fa-map-pin"></i> <?php echo isset($p['order']) ? $p['order'] : $p['id']; ?>
                                    </td>
                                    <td style="text-align:center;padding:6px;">
                                        <span title="Souřadnice">
                                            <i class="fas fa-crosshairs"></i> X=<?php echo $p['x']; ?>%, Y=<?php echo $p['y']; ?>%
                                        </span>
                                    </td>
                                    <td style="padding:6px;">
                                        <span class="desc-text" id="desc-<?php echo $idx; ?>" onclick="startInlineEditDesc(<?php echo $idx; ?>)" style="cursor:pointer;display:inline-block;min-width:80px;">
                                            <?php echo $p['description'] ? htmlspecialchars($p['description']) : '<span style=\'color:#aaa;\'>Bez poznámky</span>'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center;padding:6px;">
                                        <button type="button" class="btn btn-xs btn-delete-point" style="background:none;color:#d32f2f;border:none;padding:0 4px;font-size:18px;line-height:1;cursor:pointer;" title="Smazat bod" onclick="deletePoint(<?php echo $idx; ?>)"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            <form action="" method="POST" class="form-container">
                <div class="form-group">
                    <label for="updated_note" class="form-label">
                        <i class="fas fa-sticky-note"></i>
                        Obsah poznámky:
                    </label>
                    <textarea name="updated_note" id="updated_note" class="form-textarea" style="min-height:60px;max-height:400px;" 
                              placeholder="Upravte obsah poznámky..." required><?php echo htmlspecialchars($note['note']); ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Uložit změny
                    </button>
                    <a href="person_details.php?id=<?php echo htmlspecialchars($_GET['person_id'] ?? $note['person_id']); ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Zpět
                    </a>
                    <a href="report.php?diagnosis_note_id=<?php echo htmlspecialchars($noteId); ?>&diagnosis_id=<?php echo htmlspecialchars($note['diagnosis_id']); ?>&person_id=<?php echo htmlspecialchars($note['person_id']); ?>&surname=<?php echo htmlspecialchars($_GET['surname'] ?? ''); ?>" class="btn btn-success">
                        <i class="fas fa-file-medical"></i>
                        Zdravotnická zpráva
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Body points JS (edit/delete)
        let bodyPoints = <?php echo json_encode($body_points); ?>;

        function editDesc(idx) {
            const descSpan = document.getElementById('desc-' + idx);
            const currentDesc = bodyPoints[idx].description || '';
            const newDesc = prompt('Upravte poznámku bodu:', currentDesc);
            if (newDesc !== null) {
                bodyPoints[idx].description = newDesc;
                descSpan.textContent = newDesc ? '(' + newDesc + ')' : '';
                updateHiddenInput();
            }
        }

        function deletePoint(idx) {
            if (confirm('Opravdu chcete tento bod smazat?')) {
                bodyPoints.splice(idx, 1);
                updateHiddenInput();
                location.reload(); // reload to update list visually
            }
        }

        function updateHiddenInput() {
            // update hidden input for body_points_json
            let input = document.getElementById('body_points_json');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'body_points_json';
                input.id = 'body_points_json';
                document.querySelector('form.form-container').appendChild(input);
            }
            input.value = JSON.stringify(bodyPoints);
        }

        // In-line edit for description
        function startInlineEditDesc(idx) {
            const descSpan = document.getElementById('desc-' + idx);
            if (!descSpan) return;
            // If already editing, do nothing
            if (descSpan.querySelector('input')) return;
            const currentDesc = bodyPoints[idx].description || '';
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentDesc;
            input.style.minWidth = '80px';
            input.style.fontSize = '1em';
            input.style.padding = '2px 6px';
            input.style.borderRadius = '4px';
            input.style.border = '1px solid #bbb';
            input.onblur = function() { finishInlineEditDesc(idx, input.value); };
            input.onkeydown = function(e) {
                if (e.key === 'Enter') {
                    input.blur();
                } else if (e.key === 'Escape') {
                    descSpan.textContent = currentDesc ? currentDesc : 'Bez poznámky';
                }
            };
            descSpan.textContent = '';
            descSpan.appendChild(input);
            input.focus();
        }

        function finishInlineEditDesc(idx, newDesc) {
            const descSpan = document.getElementById('desc-' + idx);
            bodyPoints[idx].description = newDesc;
            descSpan.innerHTML = newDesc ? escapeHtml(newDesc) : '<span style="color:#aaa;">Bez poznámky</span>';
            updateHiddenInput();
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // On page load, set hidden input
        document.addEventListener('DOMContentLoaded', updateHiddenInput);
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

        // Auto-resize textarea (menší výchozí výška)
        const textarea = document.getElementById('updated_note');
        textarea.style.height = '60px';
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(400, Math.max(60, this.scrollHeight)) + 'px';
        });

        // Smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>