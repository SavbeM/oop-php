<?php

require_once __DIR__ . '/BaseModel.php';

class MetricCatalogModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct('metrics');
    }

    public function describe(): string
    {
        return 'Measurement units used by exercises.';
    }

    public function countRelated(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM exercises WHERE metric_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
