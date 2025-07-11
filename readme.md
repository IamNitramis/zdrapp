# ZDRAPP - Bezpečná databázová konfigurace

## Nastavení databázového připojení

### 1. Vytvoření konfiguračního souboru

Zkopírujte `.env.example` jako `.env`:

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

---

## 📊 Vytvoření nové databáze pro ZdrAPP

### Krok 1: Vytvoření databáze

#### Varianta A: Přes phpMyAdmin
1. Otevřete phpMyAdmin v prohlížeči: `http://localhost/phpmyadmin/`
2. Přihlaste se pomocí uživatelského jména `root` (obvykle bez hesla v lokálním prostředí)
3. Klikněte na záložku **"Databáze"**
4. Do pole **"Název databáze"** zadejte: `zdrapp`
5. Vyberte kódování: `utf8mb4_czech_ci` (pro správnou podporu diakritiky)
6. Klikněte na tlačítko **"Vytvořit"**

#### Varianta B: Přes příkazový řádek MySQL
1. Otevřete příkazový řádek (cmd) nebo PowerShell
2. Přejděte do složky MySQL:
   ```powershell
   cd C:\xampp\mysql\bin
   ```
3. Přihlaste se do MySQL:
   ```powershell
   .\mysql.exe -u root -p
   ```
4. Vytvořte databázi:
   ```sql
   CREATE DATABASE zdrapp CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;
   USE zdrapp;
   ```

### Krok 2: Import struktury databáze

#### Pokud máte SQL soubor ze složky `server/`:
1. **Přes phpMyAdmin:**
   - Vyberte databázi `zdrapp`
   - Klikněte na záložku **"Import"**
   - Klikněte **"Procházet"** a vyberte soubor `server/zdrapp_import.sql`
   - Klikněte **"Provést"**

2. **Přes příkazový řádek:**
   ```powershell
   cd C:\xampp\mysql\bin
   .\mysql.exe -u root -p zdrapp < "C:\xampp\htdocs\htdocs\ZdrAPP_Secure\server\zdrapp_import.sql"
   ```

### Krok 4
3: Ověření připojení

1. Upravte soubor `.env` s údaji o databázi
2. Otestujte připojení spuštěním aplikace v prohlížeči

### 🚨 Řešení problémů

**Chyba připojení:**
- Zkontrolujte, zda běží MySQL služba v XAMPP
- Ověřte správnost údajů v `.env` souboru
- Zkontrolujte, zda databáze `zdrapp` existuje

**Chyba kódování:**
- Ujistěte se, že databáze používá `utf8mb4_czech_ci`
- Zkontrolujte, zda PHP soubory jsou uloženy v UTF-8 bez BOM

---

# Nastavení lokální domény a VirtualHost pro XAMPP na Windows

Tento návod popisuje, jak ve Windows nastavit vlastní lokální doménu (např. `moje-aplikace.local`) pomocí souboru `hosts` a nakonfigurovat VirtualHost v Apache (XAMPP), aby se projekt načítal přes tuto doménu.

---

## 🖥️ 1. Úprava souboru `hosts` (lokální DNS záznam)

1. Otevři soubor:
C:\Windows\System32\drivers\etc\hosts


2. Přidej na konec souboru řádek:
127.0.0.1 moje-aplikace.local


3. Ulož soubor.

---

## ⚙️ 2. Nastavení VirtualHost v Apache (XAMPP)

1. Otevři soubor:
C:\xampp\apache\conf\extra\httpd-vhosts.conf


2. Přidej na konec souboru blok pro svou aplikaci:
<VirtualHost *:80>
    ServerName moje-aplikace.local
    DocumentRoot "C:/xampp/htdocs/moje-aplikace"
    <Directory "C:/xampp/htdocs/moje-aplikace">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

Ujisti se, že je v hlavním konfiguračním souboru Apache povolen soubor httpd-vhosts.conf:

Otevři:
C:\xampp\apache\conf\httpd.conf
Najdi a odkomentuj (odstraň #) tento řádek:

Include conf/extra/httpd-vhosts.conf
Restartuj Apache přes XAMPP Control Panel.

✅ Hotovo
Teď můžeš ve svém prohlížeči otevřít:

http://moje-aplikace.local
a měla by se načíst tvoje aplikace z htdocs/moje-aplikace.

## Požadovaná rozšíření PHP

| Rozšíření   | Popis                                           |
|-------------|--------------------------------------------------|
| `bz2`       | Komprese souborů                                |
| `curl`      | HTTP požadavky (často bývá vypnuté)             |
| `gd`        | Zpracování obrázků (často bývá vypnuté)         |
| `mysqli`    | MySQL databáze                                  |
| `pdo_mysql` | Moderní přístup k MySQL                         |
| `exif`      | Čtení metadata z obrázků                        |
| `gettext`   | Lokalizace a překlady                           |
| `mbstring`  | Podpora pro práci s UTF-8 a vícbytovými znaky   |

---

## 🔧 Jak povolit rozšíření v `php.ini`

1. Najdi svůj konfigurační soubor `php.ini`:
   - V příkazové řádce můžeš spustit:  
     ```bash
     php --ini
     ```
   - Nebo v PHP skriptu zobrazit:
     ```php
     <?php phpinfo(); ?>
     ```

2. Otevři soubor `php.ini` v textovém editoru (např. Notepad, VS Code, nano).

3. Najdi řádky s požadovanými rozšířeními. Budou vypadat třeba takto (mohou být zakomentované pomocí `;`):

   ```ini
   ;extension=curl
   ;extension=gd
   ;extension=mbstring
