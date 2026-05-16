<?php

require_once __DIR__ . '/BaseModel.php';

class ExerciseCatalogModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct('exercises');
    }

    public function getAll(): array
    {
        $sql = 'SELECT e.*, m.name AS metric_name FROM exercises e LEFT JOIN metrics m ON e.metric_id = m.id ORDER BY e.id ASC';
        return $this->db->query($sql)->fetchAll();
    }

    public function insert(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO exercises (name, metric_id) VALUES (:name, :metric_id)');
        return $stmt->execute(['name' => $data['name'], 'metric_id' => $data['metric_id']]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE exercises SET name = :name, metric_id = :metric_id WHERE id = :id');
        return $stmt->execute(['id' => $id, 'name' => $data['name'], 'metric_id' => $data['metric_id']]);
    }

    public function describe(): string
    {
        return 'Exercise catalogue with linked metric.';
    }

    public function countRelated(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM workout_plan_exercises WHERE exercise_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
