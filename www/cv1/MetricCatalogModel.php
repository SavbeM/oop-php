<?php

require_once 'BaseModel.php';

class MetricCatalogModel extends BaseModel
{
    protected string $table = 'metrics';

    public function describe(): string
    {
        return "MetricCatalogModel | tabuľka: {$this->table} | záznamov: " . $this->getCount();
    }

    public function countRelated(int $id): int
    {
        return $this->countUsages([
            'SELECT COUNT(*) FROM exercises WHERE metric_id = :id',
            'SELECT COUNT(*) FROM progress WHERE metric_id = :id',
        ], $id);
    }
}
