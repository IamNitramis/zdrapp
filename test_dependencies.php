<?php

$required = [
    'bz2',
    'curl',
    'gd',
    'mysqli',
    'pdo_mysql',
    'exif',
    'gettext',
    'mbstring',
];

$loaded = get_loaded_extensions();

$missing = array_filter($required, function ($ext) use ($loaded) {
    return !in_array($ext, $loaded);
});

if (empty($missing)) {
    echo "✅ Všechna požadovaná rozšíření jsou aktivní.\n";
} else {
    echo "❌ Následující rozšíření chybí nebo nejsou aktivní:\n";
    foreach ($missing as $ext) {
        echo " - $ext\n";
    }
    echo "\n🔧 Zkontroluj konfiguraci `php.ini` a povol chybějící rozšíření.\n";
}
?>