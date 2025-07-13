
# 🩺 ZDRAPP

## 🖥️ Použití XAMPP pro ZDRAPP

### 1️⃣ Instalace XAMPP

1. Stáhni XAMPP z oficiální stránky: https://www.apachefriends.org/cz/index.html
2. Spusť instalátor a nainstaluj do výchozí složky `C:\xampp`.
3. Po instalaci spusť **XAMPP Control Panel** (ikona na ploše nebo v nabídce Start).

### 2️⃣ Spuštění služeb

V XAMPP Control Panelu spusť:
- **Apache** (webserver)
- **MySQL** (databáze)

Obě služby musí svítit zeleně (Running).

### 3️⃣ Umístění aplikace

Zkopíruj složku projektu (např. `zdrapp`) do `C:\xampp\htdocs\`.
Výsledná cesta: `C:\xampp\htdocs\zdrapp`

### 4️⃣ Přístup k aplikaci

Otevři prohlížeč a zadej:

```
http://localhost/zdrapp
```

Pokud máš nastavený VirtualHost, použij vlastní doménu (viz níže).

### 5️⃣ Správa XAMPP

- **Spuštění/ukončení služeb:** tlačítka Start/Stop v XAMPP Control Panelu
- **Logy:** klikni na tlačítko "Logs" u Apache/MySQL pro zobrazení chyb
- **phpMyAdmin:** rychlý přístup na http://localhost/phpmyadmin pro správu databáze
- **Konfigurace PHP:** soubor `C:\xampp\php\php.ini` (lze otevřít přímo z Control Panelu)

### 6️⃣ Nejčastější problémy

- **Port 80/3306 je obsazen:** Změň port v nastavení XAMPP nebo ukonči jiný program (např. Skype, IIS)
- **Chyba "Access denied for user 'root'@'localhost'":** Zkontroluj heslo v `.env` a v phpMyAdmin
- **Změny v kódu se neprojevují:** Vymaž cache prohlížeče nebo restartuj Apache

---

## ⚙️ Nastavení připojení k databázi

### 1️⃣ Vytvoř konfigurační soubor `.env`

Zkopíruj soubor `.env.example` jako `.env`:

```bash
cp .env.example .env
```

---

### 2️⃣ Uprav přihlašovací údaje k databázi

Otevři soubor `.env` a nastav hodnoty dle svého prostředí:

```env
DB_HOST=localhost
DB_USERNAME=vas_uzivatel
DB_PASSWORD=vase_heslo
DB_DATABASE=zdrapp
DB_PORT=3306
```

---

### 3️⃣ Bezpečnostní upozornění

- 🔒 **Nikdy** nezaznamenávej `.env` do verzovacího systému (Git)!
- `.env` obsahuje citlivé údaje – přístup k databázi
- Soubor `.gitignore` už obsahuje pravidlo pro ignoraci `.env`

---

## 🛠️ Vytvoření databáze pro ZdrAPP

### 🔹 Krok 1: Vytvoření databáze

#### 🧭 Varianta A: Přes phpMyAdmin

1. Otevři [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. Přihlas se jako `root` (v XAMPP obvykle bez hesla)
3. Klikni na **„Databáze“**
4. Zadej název databáze: `zdrapp`
5. Vyber kódování: `utf8mb4_czech_ci`
6. Klikni na **„Vytvořit“**

#### 🧱 Varianta B: Přes příkazový řádek

```powershell
cd C:\xampp\mysql\bin
.\mysql.exe -u root -p
```

```sql
CREATE DATABASE zdrapp CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;
USE zdrapp;
```

---

### 🔹 Krok 2: Import databázové struktury

#### 📥 Import přes phpMyAdmin

1. Vyber databázi `zdrapp`
2. Klikni na **„Import“**
3. Vyber soubor `server/zdrapp_import.sql`
4. Klikni na **„Provést“**

#### 💻 Import přes příkazový řádek

```powershell
cd C:\xampp\mysql\bin
.\mysql.exe -u root -p zdrapp < "C:\xampp\htdocs\ZdrAPP_Secure\server\zdrapp_import.sql"
```

---

### 🔹 Krok 3: Ověření připojení

1. Ujisti se, že v `.env` jsou správné údaje
2. Spusť aplikaci v prohlížeči a otestuj načtení

---

## 🚨 Nejčastější problémy a jejich řešení

### ❌ Nelze se připojit k databázi

- Ověř, že běží **MySQL služba** ve XAMPP
- Zkontroluj údaje v `.env`
- Ujisti se, že databáze `zdrapp` existuje

### ❌ Problémy s kódováním (diakritika)

- Ujisti se, že databáze má `utf8mb4_czech_ci`
- PHP soubory musí být uloženy jako **UTF-8 bez BOM**

---

# 🌐 Nastavení lokální domény a VirtualHost (Windows + XAMPP)

## 1️⃣ Lokální DNS záznam – soubor `hosts`

1. Otevři jako administrátor soubor:

```
C:\Windows\System32\drivers\etc\hosts
```

2. Přidej řádek:

```
127.0.0.1 moje-aplikace.local
```

3. Ulož.

---

## 2️⃣ VirtualHost konfigurace (Apache – XAMPP)

1. Otevři soubor:

```
C:\xampp\apache\conf\extra\httpd-vhosts.conf
```

2. Na konec přidej:

```apache
<VirtualHost *:80>
    ServerName moje-aplikace.local
    DocumentRoot "C:/xampp/htdocs/moje-aplikace"
    <Directory "C:/xampp/htdocs/moje-aplikace">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. Povol načítání VirtualHostů v hlavním konfiguráku Apache:

Otevři:

```
C:\xampp\apache\conf\httpd.conf
```

Najdi řádek:

```apache
#Include conf/extra/httpd-vhosts.conf
```

Odkomentuj ho (odstraň `#`):

```apache
Include conf/extra/httpd-vhosts.conf
```

4. Restartuj Apache přes **XAMPP Control Panel**

✅ Hotovo! Otevři v prohlížeči:

```
http://moje-aplikace.local
```

---

## 📦 Požadovaná rozšíření PHP

| Rozšíření   | Popis                                           |
|-------------|--------------------------------------------------|
| `bz2`       | Práce s komprimovanými soubory                  |
| `curl`      | HTTP požadavky (API, externí komunikace)        |
| `gd`        | Manipulace s obrázky                            |
| `mysqli`    | Připojení k MySQL databázi                      |
| `pdo_mysql` | Moderní PDO přístup k MySQL                     |
| `exif`      | Metadata obrázků (např. orientace z fotoaparátu)|
| `gettext`   | Lokalizace / vícejazyčnost                      |
| `mbstring`  | Práce s UTF-8 a vícbytovými znaky               |

---

## 🛠️ Jak povolit rozšíření v `php.ini`

1. Najdi svůj `php.ini`:

   ```bash
   php --ini
   ```

   nebo v PHP skriptu:

   ```php
   <?php phpinfo(); ?>
   ```

2. Otevři `php.ini` v editoru (např. VS Code)

3. Najdi řádky s `extension=...` a **odkomentuj je**:

   ```ini
   ;extension=curl     →   extension=curl
   ;extension=gd       →   extension=gd
   ;extension=mbstring →   extension=mbstring
   ```

4. Restartuj Apache, aby se změny projevily
