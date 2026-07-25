<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nahrání dat - ZDRAPP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="logo.png">
    <link href="assets/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-cloud-upload-alt"></i> Nahrání dat</h1>
            <div class="subtitle">Nahrajte CSV soubor s údaji pacientů do systému</div>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
        <?php endif; ?>

        <div class="upload-container">
            <form action="upload_csv.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="file-upload-area" id="uploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>Nahrajte CSV soubor</h3>
                    <p>Přetáhněte soubor sem nebo klikněte pro výběr</p>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="file-input">
                </div>
                
                <div class="file-info" id="fileInfo">
                    <i class="fas fa-file-csv"></i>
                    <span id="fileName"></span>
                    <small id="fileSize"></small>
                </div>
                
                <div class="progress-bar" id="progressBar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="upload-btn" id="uploadBtn" disabled>
                        <i class="fas fa-upload"></i>
                        Nahrát soubor
                    </button>
                </div>
            </form>
        </div>

        <div class="info-cards-wrapper">
            <div class="info-cards-container">
                <div class="info-card-container">
                    <div class="info-card">
                        <h3>
                            <i class="fas fa-info-circle"></i>
                            Požadavky na CSV soubor
                        </h3>
                        <ul>
                            <li><strong>Formát:</strong> CSV soubor s kódováním UTF-8</li>
                            <li><strong>Sloupce:</strong> Jméno, Příjmení, Datum narození, Rodné číslo, Léky, Alergie, Pojišťovna, Other, Swimmer</li>
                            <li><strong>Oddělovač:</strong> Středník (;)</li>
                            <li><strong>Maximální velikost:</strong> 10 MB</li>
                            <li><strong>Datum narození:</strong> Ve formátu YYYY-MM-DD</li>
                            <li><strong>Duplicita:</strong> Záznamy s existujícím rodným číslem budou přeskočeny</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    <script>
        function toggleMenu() {
            var navbar = document.getElementById("navbar");
            navbar.classList.toggle("open");
        }

        // File upload handling
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('csv_file');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');

        // Drag and drop events
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                handleFileSelect();
            }
        });

        // File input change
        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = `(${formatFileSize(file.size)})`;
                fileInfo.classList.add('show');
                uploadBtn.disabled = false;
                
                // Validate file
                if (!file.name.toLowerCase().endsWith('.csv')) {
                    showAlert('Pouze CSV soubory jsou povoleny.', 'error');
                    uploadBtn.disabled = true;
                    return;
                }
                
                if (file.size > 10 * 1024 * 1024) {
                    showAlert('Soubor je příliš velký. Maximální velikost je 10MB.', 'error');
                    uploadBtn.disabled = true;
                    return;
                }
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function showAlert(message, type) {
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i>
                <span>${message}</span>
            `;
            
            const container = document.querySelector('.container');
            const pageHeader = document.querySelector('.page-header');
            container.insertBefore(alert, pageHeader.nextSibling);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Form submission with progress
        uploadForm.addEventListener('submit', function(e) {
            if (!fileInput.files[0]) {
                e.preventDefault();
                showAlert('Vyberte prosím CSV soubor.', 'error');
                return;
            }
            
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Nahrávám...';
            progressBar.style.display = 'block';
            
            // Simulate progress (since we can't track real progress with standard form submission)
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressFill.style.width = progress + '%';
            }, 200);
            
            // Clean up interval after form submission
            setTimeout(() => {
                clearInterval(interval);
                progressFill.style.width = '100%';
            }, 1000);
        });

        // Auto-redirect after successful upload
        <?php if ($success_message): ?>
        setTimeout(() => {
            window.location.href = 'show_data.php';
        }, 3000);
        <?php endif; ?>
    </script>

    <style>
    .info-cards-wrapper {
        max-width: 900px;
        margin: 0 auto 30px auto;
        padding: 0 10px;
    }
    .info-cards-container {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 0;
    }
    .info-card-container {
        max-width: 420px;
        width: 100%;
        padding: 0 8px;
        box-sizing: border-box;
    }
    .info-card {
        flex: 1 1 320px;
        min-width: 260px;
        max-width: 420px;
        margin-bottom: 0;
    }
    @media (max-width: 900px) {
        .info-cards-container {
            flex-direction: column;
            gap: 15px;
        }
        .info-card-container {
            max-width: 100%;
            padding: 0;
        }
        .info-card {
            max-width: 100%;
        }
    }
    </style>
</body>
</html>
