<?php
// Připojení k databázi
$conn = new mysqli("localhost", "root", "", "zdrapp");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Získání ID osoby z URL
if (!isset($_GET['id'])) {
    die("Person ID is required.");
}
$person_id = intval($_GET['id']);

// Zpracování formuláře pro přidání diagnózy a poznámky
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['diagnosis_id'], $_POST['note']) && !empty($_POST['note'])) {
        $diagnosis_id = intval($_POST['diagnosis_id']);
        $note = trim($_POST['note']);
        
        $sql = "INSERT INTO diagnosis_notes (person_id, diagnosis_id, note, created_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $person_id, $diagnosis_id, $note);
        
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $person_id);
            exit;
        } else {
            echo "Error: " . $conn->error;
        }
        
        $stmt->close();
    } else {
        echo "<p style='color: red;'>Prosím vyplňte všechna pole formuláře.</p>";
    }
}

// Načtení detailů osoby
$sql = "SELECT * FROM persons WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $person_id);
$stmt->execute();
$result = $stmt->get_result();
$person = $result->fetch_assoc();
if (!$person) {
    die("Person not found.");
}

// Načtení diagnóz a poznámek osoby
$sql = "SELECT d.name AS diagnosis_name, dn.id AS note_id, dn.note, dn.created_at 
        FROM diagnosis_notes dn
        JOIN diagnoses d ON dn.diagnosis_id = d.id
        WHERE dn.person_id = ?
        ORDER BY dn.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $person_id);
$stmt->execute();
$diagnoses = $stmt->get_result();

// Načtení všech diagnóz pro výběr
$sql = "SELECT * FROM diagnoses ORDER BY name ASC";
$all_diagnoses = $conn->query($sql);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detaily osoby</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">

    <style>
        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 20px;
            text-align: center;
        }

        h2 {
            color: #555;
            font-size: 22px;
            margin-top: 30px;
        }

        .diagnosis-container {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .diagnosis-container:hover {
            background-color: #f1f1f1;
        }

        .diagnosis-container strong {
            font-size: 18px;
            color: #333;
        }

        .note-link {
            color: #007bff;
            text-decoration: none;
        }

        .note-link:hover {
            text-decoration: underline;
        }

        .edit-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .edit-button:hover {
            background-color: #218838;
        }

        form {
            margin-top: 20px;
        }

        label {
            font-size: 16px;
            margin-bottom: 8px;
            display: block;
            color: #333;
        }

        select, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
            background-color: #f9f9f9;
        }

        select:focus, textarea:focus, button:focus {
            outline: none;
            border-color: #007bff;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .alert {
            color: red;
            font-size: 16px;
            text-align: center;
            margin-top: 20px;
        }
        textarea{
            height: 60px;
            width: 300px;
        }
        .delete-button2 {
            background: none; /* Žádné pozadí */
            border: none;     /* Žádný rámeček */
            color: red;       /* Červený text */
            cursor: pointer;  /* Ukazatel myši jako ruka */
            font-size: 16px;  /* Velikost textu */
            font-weight: bold; /* Tučný text */
            text-decoration: underline; /* Podtržený text */
            padding: 0; /* Žádné mezery */
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Detail osoby: <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['surname']); ?></h1>
        
        <h2>Diagnózy a poznámky</h2>
        <?php if ($diagnoses->num_rows > 0): ?>
    <?php while ($row = $diagnoses->fetch_assoc()): ?>
        <div class="diagnosis-container" onclick="window.location.href='edit_note.php?id=<?php echo $row['note_id']; ?>&first_name=<?php echo $person['first_name']; ?>&surname=<?php echo $person['surname']; ?>&person_id=<?php echo $person_id; ?>'">
            <strong>Diagnóza: <?php echo htmlspecialchars($row['diagnosis_name']); ?></strong>
            <p>
                <?php echo htmlspecialchars(substr($row['note'], 0, 10)) . (strlen($row['note']) > 10 ? '...' : ''); ?>
            </p>
            <small>Vytvořeno: <?php echo htmlspecialchars($row['created_at']); ?></small>
            <form action="edit_note.php" method="GET" style="display: inline;">
                <input type="hidden" name="id" value="<?php echo $row['note_id']; ?>">
                <input type="hidden" name="first_name" value="<?php echo $person['first_name']; ?>">
                <input type="hidden" name="surname" value="<?php echo $person['surname']; ?>">
                <input type="hidden" name="person_id" value="<?php echo $person_id; ?>">
                <button type="submit" class="edit-button">Upravit poznámku</button>
            </form>
            <form action="delete_note.php" method="POST" style="display: inline;" onsubmit="return confirm('Opravdu chcete tuto poznámku odstranit?');">
                <input type="hidden" name="id" value="<?php echo $row['note_id']; ?>">
                <button type="submit" class="delete-button2">Smazat</button>
            </form>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>Žádné diagnózy nejsou zapsány.</p>
<?php endif; ?>


        <h2>Přidat diagnózu a poznámku</h2>
        <form action="" method="POST" align="center">
            <label for="diagnosis">Vyber diagnózu:</label>
            <select name="diagnosis_id" id="diagnosis" required>
                <?php while ($row = $all_diagnoses->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['id']); ?>">
                        <?php echo htmlspecialchars($row['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <a href="add_diagnosis.php">Přidat diagnózu</a><br><br>
            <a href="add_report.php">Přidat template lékařské zprávy</a>
            <br>
            <br>

            <label for="note">Poznámka:</label>
            <textarea name="note" id="note" placeholder="Napište, jak probíhalo ošetření, co jste podali za medikaci.." required></textarea>

            <button type="submit">Přidat</button>
            <a href="kliste.php?person_id=<?php echo $person_id; ?>">
        <button type="button" style="background:#e67e22;color:#fff;">Klíšťata</button>
    </a>
        </form>
    </div>

    <div class="header">
        <a href="show_data.php" class="logo">
            <img src="logo.png" alt="ZDRAPP Logo" width="50">
        </a>
        <div class="menu-icon" onclick="toggleMenu()">&#9776;</div>
        <div class="navbar" id="navbar">
            <a href="show_data.php">Home</a>
            <a href="upload_csv.php">Upload Data</a>
            <a href="download_reports.php">Stáhnout zprávy</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <script>
document.querySelectorAll('.diagnosis-container form').forEach(function(form) {
    form.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>
</body>
</html>
