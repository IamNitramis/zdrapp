<?php
/**
 * Databázová konfigurace pro ZDRAPP
 * Tento soubor obsahuje citlivé údaje - NIKDY jej nepřidávejte do veřejného repozitáře!
 */

// Načtení proměnných prostředí ze souboru .env (pokud existuje)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Databázová konfigurace s fallback hodnotami
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'database' => $_ENV['DB_DATABASE'] ?? 'zdrapp',
    'port' => $_ENV['DB_PORT'] ?? 3306,
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
];

/**
 * Vytvoří a vrátí nové databázové připojení
 * @return mysqli
 * @throws Exception pokud se nepodaří připojit k databázi
 */
function createDatabaseConnection() {
    global $dbConfig;
    
    // Vytvoření připojení
    $conn = new mysqli(
        $dbConfig['host'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['database'],
        $dbConfig['port']
    );
    
    // Kontrola připojení
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Nepodařilo se připojit k databázi. Zkuste to prosím později.");
    }
    
    // Nastavení kódování
    $conn->set_charset($dbConfig['charset']);
    
    return $conn;
}

/**
 * Získá databázové připojení (singleton pattern)
 * @return mysqli
 */
function getDatabase() {
    static $connection = null;
    
    if ($connection === null) {
        $connection = createDatabaseConnection();
    }
    
    return $connection;
}
