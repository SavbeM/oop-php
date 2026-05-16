<?php

require_once __DIR__ . '/ModelInterface.php';
require_once __DIR__ . '/Database.php';

abstract class BaseModel implements ModelInterface
{
    protected PDO $db;
    protected string $table;

    public function __construct(string $table)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->table = $table;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

    public function insert(array $data): bool
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (name) VALUES (:name)");
        return $stmt->execute(['name' => $data['name']]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET name = :name WHERE id = :id");
        return $stmt->execute(['id' => $id, 'name' => $data['name']]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    abstract public function describe(): string;

    abstract public function countRelated(int $id): int;
}
