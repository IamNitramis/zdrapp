<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stáhnout zdravotnické zprávy</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link href="assets/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-download"></i> Stáhnout zdravotnické zprávy</h1>
            <div class="subtitle">Exportujte kompletní zdravotnické zprávy a mapy klíšťat všech pacientů včetně statistik.</div>
            <div class="patient-count">
                Celkem pacientů: <?php echo $total_patients; ?> | 
                S klíšťaty: <?php echo $patients_with_ticks; ?> | 
                Zdravotnických zpráv: <?php echo $total_reports; ?>
            </div>
        </div>

        <div class="download-section">
            <h3><i class="fas fa-file-archive"></i> Hromadné stažení</h3>
            <p>Stáhněte ZIP archiv se všemi pacienty, jejich zprávami a mapami klíšťat.</p>
            <a href="download_reports.php?download_all=1" class="btn btn-primary">
                <i class="fas fa-download"></i> Stáhnout vše (ZIP)
            </a>
        </div>

        <div class="download-section">
            <h3><i class="fas fa-user"></i> Stažení jednoho pacienta</h3>
            <form method="get" action="download_reports.php" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <div class="form-group" style="flex:1; min-width:220px;">
                    <label for="person_id">Vyberte pacienta:</label>
                    <select name="person_id" id="person_id" class="form-control" required>
                        <option value="">-- Vyberte --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['id']); ?>">
                                <?php echo htmlspecialchars($p['surname'] . " " . $p['first_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary" name="export_docx" value="1">
                    <i class="fas fa-file-word"></i> Exportovat DOCX
                </button>
            </form>
        </div>
    </div>
    <script>
        function toggleMenu() {
            var navbar = document.getElementById('navbar');
            navbar.classList.toggle('active');
        }
    </script>
</body>
</html>
