<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přístup zamítnut</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .login-warning { max-width: 450px; background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); padding: 50px 40px; text-align: center; }
        .login-warning i { font-size: 4rem; color: #e67e22; margin-bottom: 20px; }
        .login-warning h2 { color: #2d3748; margin-bottom: 15px; font-size: 1.8rem; font-weight: 600; }
        .login-warning p { color: #718096; font-size: 1.1rem; margin-bottom: 30px; }
        .login-warning a { display: inline-block; padding: 15px 35px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease; }
        .login-warning a:hover { transform: translateY(-3px); }
    </style>
</head>
<body>
    <div class="login-warning">
        <i class="fas fa-lock"></i>
        <h2>Přístup zamítnut</h2>
        <p>Pro zobrazení této stránky se musíte přihlásit.</p>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Přihlásit se</a>
    </div>
</body>
</html>
<?php exit; endif; ?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - ZDRAPP</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <script>
        function toggleMenu() {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('active');
            navbar.classList.toggle('open');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // FAQ functionality
            var items = document.querySelectorAll('.faq-question');
            items.forEach(function(item) {
                item.addEventListener('click', function() {
                    var parent = this.parentElement;
                    parent.classList.toggle('active');
                });
            });
            
            // Mobile menu functionality
            const navbarLinks = document.querySelectorAll('.navbar a');
            const navbar = document.getElementById('navbar');
            
            navbarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    navbar.classList.remove('active');
                    navbar.classList.remove('open');
                });
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const navbar = document.getElementById('navbar');
            const menuIcon = document.querySelector('.menu-icon');
            
            if (!navbar.contains(event.target) && !menuIcon.contains(event.target)) {
                navbar.classList.remove('active');
                navbar.classList.remove('open');
            }
        });
    </script>
</head>
<body>
    
    <div class="container">
        <div class="faq-title"><i class="fas fa-question-circle"></i> Často kladené otázky (FAQ)</div>
        <ul class= faq-list>
            <li class="faq-item">
                <div class="faq-question">Jak funguje stránka přehled? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                <ul>
                        <li><b>Detaily osoby</b> slouží k zobrazení a správě zdravotních záznamů konkrétního pacienta. Najdete zde jméno pacienta, přehled všech diagnóz a nálezů, které byly pacientovi přiřazeny, včetně data, poznámky a uživatele, který záznam upravil.</li>
                        <li><b>Přehled pacienta:</b> V horní části vidíte jméno a základní informace o pacient</li>
                        <li><b>Přidání diagnózy a nálezu:</b> Pomocí formuláře po kliknutí na tlačítko detail, lze vybrat diagnózu a přidat k ní nález (popis ošetření, podaná medikace apod.).</li>
                        <li><b>Diagnózy a nálezy:</b> V přehledu níže jsou všechny záznamy, které lze upravit nebo smazat pomocí tlačítek <i>Upravit</i> a <i>Smazat</i>.</li>
                        <li><b>Medikace a alergie:</b> V pravé části jsou zobrazeny informace o medikaci a alergiích pacienta.</li>
                        <li><b>Klíšťata:</b> Tlačítko <i>Klíšťata</i> vás přesměruje na stránku pro evidenci a mapování klíšťat.</li>
                        <li><b>Navigace:</b> V horním menu lze přecházet na další části aplikace (přehled, nahrání dat, přidání diagnózy, zprávy, statistiky, FAQ).</li>
                </ul>
            </li>
        <ul class="faq-list">
            <li class="faq-item">
                <div class="faq-question">Jak zaznamenat a spravovat místa klíšťat u pacienta? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ul style="margin-left: 1.2em;">
                        <li><b>Jak stránku otevřít?</b> V detailu pacienta klikněte na tlačítko Klíšťata.</li>
                        <li><b>Zobrazení schématu:</b> Na stránce se zobrazí obrázek lidského těla a tabulka bodů.</li>
                        <li><b>Přidání bodu:</b> Klikněte myší na místo na obrázku, kde pacienta píchlo klíště. Bod se uloží a zobrazí v tabulce vpravo.</li>
                        <li><b>Očíslování bodů:</b> Každý bod je automaticky očíslován podle pořadí přidání.</li>
                        <li><b>Odstranění bodu:</b> Pro odstranění bodu klikněte na červené tlačítko <i class="fas fa-trash"></i> v tabulce.</li>
                        <li><b>Obnovení záznamů:</b> Všechny body se automaticky načítají při otevření stránky nebo po přidání/odstranění bodu.</li>
                        <li><b>Návrat zpět:</b> Pro návrat na detail pacienta použijte tlačítko <em>Zpět na detail pacienta</em> dole na stránce.</li>
                    </ul>
                    <div style="color:#388e3c; font-size:0.98em; margin-top:0.7em;">
                        Tato stránka slouží k přesnému zaznamenání a správě míst, kde bylo pacientovi odstraněno klíště. Data se využívají v exportech a statistikách.
                    </div>
                </div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak upravit poznámku k diagnóze? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ul style="margin-left: 1.2em;">
                        <li><b>Otevření úpravy:</b> V detailu pacienta klikněte na tlačítko <em>Upravit</em> u vybrané poznámky.</li>
                        <li><b>Úprava obsahu:</b> V zobrazeném formuláři upravte text poznámky podle potřeby. Pole je povinné a nesmí zůstat prázdné.</li>
                        <li><b>Uložení změn:</b> Klikněte na <em>Uložit změny</em>. Poznámka se uloží a vrátíte se zpět na detail pacienta.</li>
                        <li><b>Návrat zpět:</b> Pro návrat bez uložení klikněte na <em>Zpět</em>.</li>
                        <li><b>Vazba na zdravotnickou zprávu:</b> U každé poznámky můžete přejít přímo na tvorbu nebo úpravu zdravotnické zprávy tlačítkem <em>Zdravotnická zpráva</em>.</li>
                    </ul>
                    <div style="color:#388e3c; font-size:0.98em; margin-top:0.7em;">
                        Tato stránka slouží k úpravě zdravotních poznámek přiřazených k diagnóze pacienta.
                    </div>
                </div>
            </li>
            <li class="faq-item">
                <div class="faq-question"> Jak funguje stránka Stáhnout zprávy? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ul style="margin-left: 1.2em;">
                        <li><b>Hromadné stažení:</b> Klikněte na tlačítko <em>Stáhnout vše (ZIP)</em> pro stažení archivu se všemi pacienty, jejich zprávami a mapami klíšťat. Archiv obsahuje složky pro každého pacienta, včetně zpráv, mapy klíšťat a souhrnného souboru.</li>
                        <li><b>Stažení jednoho pacienta:</b> Vyberte pacienta ze seznamu a klikněte na <em>Exportovat DOCX</em>. Stáhne se ZIP archiv se zdravotnickou zprávou, mapou klíšťat a informačním souborem.</li>
                        <li><b>Obsah exportu:</b> Každý ZIP obsahuje:
                            <ul style="margin-left: 1.5em; margin-top: 0.5em;">
                                <li><b>lekarska_zprava.docx</b> – kompletní zdravotnická zpráva s formátovaným textem</li>
                                <li><b>mapa_klistat.png</b> – vizuální mapa všech klíšťat na těle (pokud jsou zaznamenána)</li>
                                <li><b>info.txt</b> – informace o pacientovi a obsahu archivu</li>
                            </ul>
                        </li>
                        <li><b>Statistiky:</b> V horní části stránky vidíte souhrnné počty pacientů, pacientů s klíšťaty a celkový počet zdravotnických zpráv.</li>
                        <li><b>Poznámka:</b> Pokud prohlížeč označí stažený ZIP jako nebezpečný, je to běžné při stahování z lokálního serveru. Soubor můžete bezpečně zachovat.</li>
                    </ul>
                    <div style="color:#388e3c; font-size:0.98em; margin-top:0.7em;">
                        Tato stránka slouží k exportu všech zdravotnických zpráv a map klíšťat pro další zpracování nebo archivaci.
                    </div>
                </div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak funguje stránka přidat diagnózu? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ul style="margin-left: 1.2em;">
                        <li><b>Výběr diagnózy:</b> Nejprve vyberte diagnózu ze seznamu. Pro každou diagnózu lze mít vlastní šablonu zprávy.</li>
                        <li><b>Úprava textu šablony:</b> Do editoru zadejte nebo upravte text šablony. Můžete používat formátování (tučné, seznamy, tabulky) a <b>placeholdery</b> (např. <code>{{name}}</code>, <code>{{birth_date}}</code>).</li>
                        <li><b>Uložení šablony:</b> Po dokončení úprav klikněte na <em>Uložit šablonu</em>. Šablona se uloží k vybrané diagnóze a bude použita při generování zdravotnických zpráv.</li>
                        <li><b>Nápověda k placeholderům:</b> Klikněte na tlačítko <em>Nápověda k placeholderům</em> pro zobrazení seznamu dostupných zástupných výrazů.</li>
                    </ul>
                    <div style="color:#388e3c; font-size:0.98em; margin-top:0.7em;">
                        Tato stránka slouží k úpravě šablon zdravotnických zpráv. Správně nastavená šablona urychlí a zjednoduší generování zpráv pro pacienty.
                    </div>
                </div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak funguje stránka Přidat diagnózu, a jak ji používat? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ul style="margin-left: 1.2em;">
                        <li><b>Přidání nové diagnózy:</b> Vyplňte název nové diagnózy do formuláře a potvrďte tlačítkem <em>Přidat diagnózu</em>. K diagnóze se automaticky vytvoří výchozí šablona zdravotnické zprávy.</li>
                        <li><b>Seznam diagnóz:</b> V tabulce vidíte všechny aktuální diagnózy. Diagnózy lze řadit kliknutím na záhlaví sloupců (ID, Název diagnózy). U každé diagnózy je uvedeno, kdo ji naposledy upravil.</li>
                        <li><b>Odstranění diagnózy:</b> Diagnózu odstraníte kliknutím na červené tlačítko <em>Odstranit</em>. Systém se zeptá na potvrzení.</li>
                    </ul>
                    <div style="color:#388e3c; font-size:0.98em; margin-top:0.7em;">
                        Diagnózy jsou využívány při vytváření zdravotnických zpráv a šablon. Odstraněné diagnózy se v systému dále nenabízejí, ale zůstávají v databázi pro případnou obnovu.
                    </div>
                </div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak mohu upravit nebo smazat pacienta? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">V přehledu pacientů klikněte na tlačítko <b>Detail</b> u konkrétního pacienta. Zde můžete upravit údaje nebo použít tlačítko <b>Smazat</b>.</div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak nahrát data z CSV souboru? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">V horním menu zvolte <b>Nahrát data</b> a nahrajte CSV soubor podle instrukcí na stránce.</div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Kde najdu statistiky o pacientech? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">Statistiky najdete v sekci <b>Statistiky</b> v horním menu. Zobrazí se zde souhrnné informace o pacientech, medikaci a alergiích.</div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak se mohu odhlásit? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">Pro odhlášení klikněte na <b>Logout</b> v pravé části horního menu.</div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Na koho se mohu obrátit v případě technických problémů? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">V případě technických problémů kontaktujte správce systému na tel. čísle <b>+420 735 880 870</b> nebo napište na e-mail <b>gabriel@zgnetworks.eu</b>.</div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak přidám diagnózu a poznámku a vygeneruji zdravotnickou zprávu? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ol style="margin-left: 1.2em;">
                        <li>Otevřete detail pacienta kliknutím na tlačítko <b>Detail</b> v přehledu pacientů.</li>
                        <li>V sekci <b>Přidat diagnózu a poznámku</b> vyberte diagnózu ze seznamu a napište poznámku k ošetření nebo průběhu.</li>
                        <li>Klikněte na tlačítko <b>Přidat záznam</b>. Diagnóza a poznámka se uloží.</li>
                        <li>Po přidání záznamu se v seznamu diagnóz a poznámek zobrazí nová položka.</li>
                        <li>Pro vygenerování zdravotnické zprávy klikněte na ikonu nebo tlačítko <b>Vytvořit zprávu</b> (nebo <b>Report</b>) u konkrétní poznámky/diagnózy.</li>
                        <li>Zobrazí se předvyplněná šablona zprávy, kterou můžete upravit a uložit.</li>
                        <li>Hotovou zprávu lze stáhnout nebo vytisknout podle potřeby.</li>
                    </ol>
                </div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak vytvořit a spravovat šablony zdravotnických zpráv? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ol style="margin-left: 1.2em;">
                        <li>V horním menu klikněte na <b>Přidat zdravotnickou zprávu</b>.</li>
                        <li>Vyberte diagnózu ze seznamu.</li>
                        <li>V poli <b>Text zprávy</b> můžete použít bohatý textový editor pro formátování:</li>
                        <ul style="margin-left: 1.5em; margin-top: 0.5em;">
                            <li><b>Tučné písmo</b> - pro zvýraznění důležitých informací</li>
                            <li><b>Kurzíva</b> - pro poznámky nebo citace</li>
                            <li><b>Podtržené písmo</b> - pro zdůraznění</li>
                            <li><b>Seznamy</b> - pro strukturované informace</li>
                            <li><b>Tabulky</b> - pro přehledné zobrazení dat</li>
                        </ul>
                        <li>Uložte šablonu kliknutím na <b>Uložit zprávu</b>.</li>
                        <li>Uložené šablony pak můžete kopírovat a upravovat pro nové pacienty.</li>
                        <li><b>Tip:</b> Používejte placeholder text jako "{{name}}" nebo "{{birth_date}}" pro části, které se mění u každého pacienta.</li>
                    </ol>
                </div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak přidat novou diagnózu do systému? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ol style="margin-left: 1.2em;">
                        <li>V horním menu klikněte na <b>Přidat diagnózu</b>.</li>
                        <li>Vyplňte formulář pro novou diagnózu:</li>
                        <li>Klikněte na tlačítko <b>Přidat diagnózu</b> pro uložení.</li>
                        <li>Nová diagnóza se automaticky zobrazí v seznamu dostupných diagnóz.</li>
                        <li>Diagnózu pak můžete použít při vytváření zdravotnických zpráv a záznamů.</li>
                        <li><b>Správa diagnóz:</b> Přidané diagnózy můžete později upravit nebo smazat v seznamu diagnóz.</li>
                    </ol>
                </div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak funguje upozornění na chybějící zdravotnické zprávy? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ol style="margin-left: 1.2em;">
                        <li>Při načtení přehledu pacientů systém automaticky zkontroluje, zda některé diagnózy/poznámky nemají vygenerovanou zdravotnickou zprávu.</li>
                        <li>Pokud jsou nalezeny poznámky bez zprávy, zobrazí se v horní části stránky výrazné červené upozornění (alert).</li>
                        <li>Upozornění obsahuje seznam pacientů a konkrétních diagnóz/poznámek, které nemají zprávu, včetně data vytvoření.</li>
                        <li>U každé chybějící zprávy je červené tlačítko <b>Vygenerovat</b>, které vás přesměruje přímo na formulář pro vytvoření zprávy k dané poznámce.</li>
                        <li>Jakmile je zpráva vytvořena, upozornění pro danou poznámku zmizí.</li>
                        <li>Tento mechanismus pomáhá zdravotníkům rychle najít a doplnit chybějící dokumentaci.</li>
                    </ol>
                </div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Kdo vytvořil logo ZDRAPP? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    Logo aplikace ZDRAPP vytvořila <b>Julča Bónová</b>. Také poskytla konzultaci při ranných fázích vývoje aplikace. Díky za to.
                </div>
            </li>     
            <li class="faq-item">
                <div class="faq-question">Jak přidat novou poznámku a diagnózu k pacientovi? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ol style="margin-left: 1.2em;">
                        <li>V přehledu pacientů klikněte na tlačítko <b>Detail</b> u vybraného pacienta.</li>
                        <li>V detailu pacienta najděte sekci <b>Přidat diagnózu a poznámku</b>.</li>
                        <li>Vyberte diagnózu ze seznamu (nebo přidejte novou v sekci <b>Přidat diagnózu</b> v hlavním menu).</li>
                        <li>Do pole poznámky napište popis, průběh nebo další informace k danému případu.</li>
                        <li>Klikněte na tlačítko <b>Přidat záznam</b>. Nová diagnóza a poznámka se uloží a zobrazí v seznamu záznamů pacienta.</li>
                        <li>Ke každé diagnóze/poznámce je třeba následně vygenerovat zdravotnickou zprávu, jinak se při exportování zpráva neukáže.</li>
                    </ol>
                </div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jak přidám nového pacienta? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">Nového pacienta lze přidat pouze přes <b>Nahrát data</b></div>
            </li>
            <li class="faq-item">
                <div class="faq-question">Jaké jsou osvědčené postupy (best practices) pro tvorbu šablon zdravotnických zpráv? <span class="faq-icon"><i class="fas fa-chevron-right"></i></span></div>
                <div class="faq-answer">
                    <ol style="margin-left: 1.2em;">
                        <li>Používejte <b>placeholdry</b> (zástupné texty) pro proměnné části zprávy, např. <code>{{name}}</code>, <code>{{birth_date}}</code>, <code>{{diagnosis}}</code> apod. Tyto hodnoty budou automaticky nahrazeny konkrétními údaji pacienta.</li>
                        <li>Pro víceuživatelská prostředí (kdy zprávy vytváří více zdravotníků) využijte nově podporovaný placeholder <code>{{author}}</code>. Ten bude vygenerován podle přihlášeného uživatele, který zprávu vytvořil. Díky tomu je vždy jasné, kdo zprávu vystavil.</li>
                        <li>Využívejte možnosti formátování (tučné písmo, seznamy, tabulky) pro přehlednost a srozumitelnost zprávy. Všechno toto formátování bude přeneseno do výsledné zprávy ve formátu docx.</li>
                        <li>Před uložením šablony si ji vyzkoušejte na testovacím pacientovi, abyste ověřili správné nahrazení všech placeholderů.</li>
                    </ol>
                    <div style="margin-top: 0.7em; color: #388e3c; font-size: 0.98em;"><b>Tip:</b> Správné použití <code>{{author}}</code> je důležité zejména v týmech, kde zprávy vystavuje více zdravotníků. Umožňuje zpětně dohledat autora každé zprávy.</div>
                </div>
            </li>
        </ul>
    </div>
</body>
</html>