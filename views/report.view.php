<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editace zdravotnické zprávy - ZDRAPP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="assets/css/all.min.css">    
    <script src="assets/tinymce/js/tinymce/tinymce.min.js"></script>
<script>
  tinymce.init({
    selector: '#updated_report',
    plugins: 'lists table',
    toolbar: 'undo redo | bold italic underline | bullist numlist | table | fontsize',
    menubar: false,
    fontsize_formats: '8pt 10pt 12pt 14pt 18pt 24pt 36pt 48pt',
    branding: false,
    height: 500
  });
</script>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Editace zdravotnické zprávy</h1>
            <div class="subtitle">Úprava zdravotnické zprávy pro pacienta</div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="patient-info-container">
            <div class="patient-info">
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span class="info-label">Pacient:</span>
                    <span class="info-value"><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['surname']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-calendar"></i>
                    <span class="info-label">Datum narození:</span>
                    <span class="info-value"><?php echo htmlspecialchars(date('d.m.Y', strtotime($person['birth_date']))); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-shield-alt"></i>
                    <span class="info-label">Pojišťovna:</span>
                    <span class="info-value"><?php echo !empty($person['insurance']) ? htmlspecialchars($person['insurance']) : '<span style="color:#aaa;">Neuvedeno</span>'; ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-stethoscope"></i>
                    <span class="info-label">Diagnóza:</span>
                    <span class="info-value"><?php echo htmlspecialchars($diagnosis['diagnosis_name']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span class="info-label">Vytvořeno:</span>
                    <span class="info-value"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($assignedAt))); ?></span>
                </div>
            </div>
        </div>

        <div class="form-container">
            <form action="" method="POST">
                <div class="form-group">
                    <label for="updated_report" class="form-label">
                        <i class="fas fa-file-medical"></i>
                        Obsah zdravotnické zprávy
                    </label>
                    <textarea 
                        name="updated_report" 
                        id="updated_report"
                        class="form-textarea"
                        placeholder="Zadejte obsah zdravotnické zprávy..."
                        required
                    ><?php echo $report; ?></textarea>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Uložit zprávu
                    </button>
                    <a href="show_data.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Zrušit
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            var navbar = document.getElementById("navbar");
            navbar.classList.toggle("open");
        }

        // Auto-resize textarea
        const textarea = document.getElementById('updated_report');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(400, this.scrollHeight) + 'px';
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.querySelector('form').submit();
            }
        });

        // Show success message and auto-hide
        <?php if (isset($message) && $message_type === 'success'): ?>
        setTimeout(function() {
            const alert = document.querySelector('.alert-success');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
