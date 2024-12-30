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
$conn = new mysqli("localhost", "root", "", "zdrapp");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Získání ID pacienta
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Valid Patient ID is required.");
}

$personId = intval($_GET['id']);

// Načtení údajů o pacientovi
$sqlPerson = "SELECT first_name, surname, birth_date FROM persons WHERE id = ?";
$stmtPerson = $conn->prepare($sqlPerson);
$stmtPerson->bind_param("i", $personId);
$stmtPerson->execute();
$resultPerson = $stmtPerson->get_result();

if ($resultPerson->num_rows === 0) {
    die("Patient not found.");
}

$person = $resultPerson->fetch_assoc();

// Načtení diagnóz pacienta
$sqlDiagnoses = "
    SELECT d.name AS diagnosis_name, pd.assigned_at
    FROM person_diagnoses pd
    INNER JOIN diagnoses d ON pd.id = d.id
    WHERE pd.person_id = ?
";
$stmtDiagnoses = $conn->prepare($sqlDiagnoses);
$stmtDiagnoses->bind_param("i", $personId);
$stmtDiagnoses->execute();
$resultDiagnoses = $stmtDiagnoses->get_result();

$diagnoses = [];
while ($row = $resultDiagnoses->fetch_assoc()) {
    $diagnoses[] = $row;
}

// Generování náhodných hodnot
$temperature = mt_rand(360, 380) / 10; // 36.0 - 38.0 °C
$oxygen_saturation = mt_rand(95, 100); // 95 - 100 %
$heart_rate = mt_rand(60, 100);        // 60 - 100 bpm

// Vytvoření lékařské zprávy
$report = "
Medical Report:
Name: {$person['first_name']}
Surname: {$person['surname']}
Birth Date: {$person['birth_date']}
Body Temperature: {$temperature} °C
Oxygen Saturation: {$oxygen_saturation} %
Heart Rate: {$heart_rate} bpm

Diagnoses:
";

foreach ($diagnoses as $diag) {
    $report .= "- {$diag['diagnosis_name']} (Recorded on: {$diag['assigned_at']})\n";
}

// Zpracování formuláře pro uložení zprávy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['updated_report']) && !empty(trim($_POST['updated_report']))) {
        $updatedReport = trim($_POST['updated_report']);

        // Uložení zprávy do databáze
        $sqlInsert = "INSERT INTO medical_reports (person_id, report_text, created_at) VALUES (?, ?, NOW())";
        $stmtInsert = $conn->prepare($sqlInsert);

        if (!$stmtInsert) {
            die("SQL Error: " . $conn->error);
        }

        $stmtInsert->bind_param("is", $personId, $updatedReport);
        if ($stmtInsert->execute()) {
            $message = "Medical report has been saved successfully.";
        } else {
            $message = "Error saving medical report: " . $conn->error;
        }

        $stmtInsert->close();
    } else {
        $message = "Report content cannot be empty.";
    }
}

$stmtPerson->close();
$stmtDiagnoses->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medical Report</title>
    <link rel="stylesheet" href="style.css">
    <style>
        textarea {
            width: 100%;
            height: 300px;
            resize: vertical;
        }
        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Medical Report for <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['surname']); ?></h1>
        <?php if (isset($message)) echo "<p>$message</p>"; ?>
        <form action="" method="POST">
            <textarea name="updated_report"><?php echo htmlspecialchars($report); ?></textarea>
            <br>
            <button type="submit">Save Report</button>
            <a href="show_data.php">Cancel</a>
        </form>
    </div>
</body>
</html>
