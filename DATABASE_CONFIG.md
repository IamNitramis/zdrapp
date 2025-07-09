# ZDRAPP - Bezpečná databázová konfigurace

## Nastavení databázového připojení

### 1. Vytvoření konfiguračního souboru

Zkopírujte `.env.example` jako `.env`:
```bash
cp .env.example .env
```

### 2. Upravení konfigurace

Otevřete soubor `.env` a upravte databázové údaje podle vašeho prostředí:

```env
DB_HOST=localhost
DB_USERNAME=váš_username
DB_PASSWORD=vaše_heslo
DB_DATABASE=zdrapp
DB_PORT=3306
```

### 3. Bezpečnost

- **NIKDY** nepřidávejte soubor `.env` do Git repozitáře
- Soubor `.env` obsahuje citlivé údaje
- Soubor `.gitignore` již obsahuje pravidlo pro ignorování `.env`

### 4. Použití v kódu

```php
// Místo starého způsobu:
$conn = new mysqli("localhost", "root", "", "zdrapp");

// Používejte nový způsob:
require_once __DIR__ . '/config/database.php';
$conn = getDatabase();
```

### 5. Výhody nového řešení

- **Bezpečnost**: Citlivé údaje nejsou v kódu
- **Flexibilita**: Různé konfigurace pro různá prostředí
- **Centralizace**: Všechny databázové funkce na jednom místě
- **Error handling**: Lepší zpracování chyb připojení
- **Singleton pattern**: Efektivnější využití připojení

### 6. Migrace existujících souborů

Pro migraci ostatních PHP souborů:

1. Přidejte na začátek: `require_once __DIR__ . '/config/database.php';`
2. Nahraďte: `$conn = new mysqli(...)` za `$conn = getDatabase();`
3. Odstraňte: `if ($conn->connect_error) { die(...) }`

### 7. Prostředí

- **Development**: Použijte `.env` soubor
- **Production**: Nastavte proměnné prostředí na serveru
- **Testing**: Vytvořte `.env.testing` pro testovací databázi

## Příklad migrace

**Před:**
```php
$conn = new mysqli("localhost", "root", "", "zdrapp");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
```

**Po:**
```php
require_once __DIR__ . '/config/database.php';
try {
    $conn = getDatabase();
} catch (Exception $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}
```
