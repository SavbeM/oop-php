<?php

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $dsn = 'mysql:host=db;port=3306;dbname=fitness_tracker;charset=utf8mb4';
        $this->connection = new PDO($dsn, 'root', 'root', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
