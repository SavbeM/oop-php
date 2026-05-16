<?php
// Pripojenie k databáze pre projekt Workout Tracker (PDO)

// ── 1. Konštanty — údaje o serveri ────────────────────────
define('DB_HOST',    'db');          // názov Docker kontajnera (viď docker-compose.yml)
define('DB_NAME',    'mojprojekt'); // názov databázy
define('DB_USER',    'root');        // používateľské meno pre MySQL
define('DB_PASS',    'root');        // heslo
define('DB_CHARSET', 'utf8mb4');    // plná podpora diakritiky aj emoji

// ── 2. DSN — Data Source Name ─────────────────────────────
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// ── 3. Options — nastavenia správania PDO ─────────────────
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // chyby ako výnimky
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch vracia asociatívne pole
    PDO::ATTR_EMULATE_PREPARES   => false,                  // skutočné prepared statements
];

// ── 4. Vytvorenie PDO objektu ─────────────────────────────
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    // echo "✅ PDO pripojenie úspešné"; // odkomentuj na testovanie

} catch (PDOException $e) {
    die("❌ Chyba pripojenia k databáze Workout Tracker: " . $e->getMessage());
}
// Po tomto riadku je $pdo dostupné v celom skripte.