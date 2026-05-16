<?php

/**
 * Database — Singleton pre PDO
 * Zaraďuje vytvorenie a správu jediného pripojenia k databáze.
 */
class Database
{
    private static ?PDO $instancia = null;

    // Private konštruktor — zabraňuje new Database()
    private function __construct() {}

    /**
     * getInstance() — Vráti jedinú inštanciu PDO pripojenia
     * Ak neexistuje, vytvorí ju s konfiguráciou z konstánt.
     */
    public static function getInstance(): PDO
    {
        if (self::$instancia === null) {
            // ── Konštanty — údaje o serveri ────────────────────────────
            $host = 'db';                // názov Docker kontajnera
            $dbName = 'mojprojekt';      // názov databázy
            $user = 'root';              // používateľské meno pre MySQL
            $pass = 'root';              // heslo
            $charset = 'utf8mb4';        // plná podpora diakritiky aj emoji

            // ── DSN — Data Source Name ─────────────────────────────────
            $dsn = "mysql:host=$host;dbname=$dbName;charset=$charset";

            // ── Options — nastavenia správania PDO ─────────────────────
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instancia = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die("❌ Chyba pripojenia k databáze: " . $e->getMessage());
            }
        }

        return self::$instancia;
    }

    // Zabraňujeme klonovanию inštancie
    private function __clone() {}
}
