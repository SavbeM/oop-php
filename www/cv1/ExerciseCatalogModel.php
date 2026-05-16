<?php

require_once 'BaseModel.php';

class ExerciseCatalogModel extends BaseModel
{
    protected string $table = 'exercises';

    public function describe(): string
    {
        return "ExerciseCatalogModel | tabuľka: {$this->table} | záznamov: " . $this->getCount();
    }

    public function countRelated(int $id): int
    {
        return $this->countUsages([
            'SELECT COUNT(*) FROM workout_plan_exercises WHERE exercise_id = :id',
            'SELECT COUNT(*) FROM progress WHERE exercise_id = :id',
        ], $id);
    }

    /**
     * search() — Filtruje cviky podľa kritérií v poli $filters
     * Dynamicky vytvára WHERE podmienky len pre neprázdne filtre.
     * 
     * Filtre:
     * - 'metric_id' — filtruje podľa metriky (int)
     * - 'hladaj'    — fulltextové vyhľadávanie v názve (string, LIKE)
     */
    public function search(array $filters): array
    {
        // Základ SQL — vždy rovnaký: SELECT s LEFT JOINom na metrics
        $sql = "SELECT
                    e.id,
                    e.name,
                    e.description,
                    e.metric_id,
                    m.name AS metric_name,
                    m.unit AS metric_unit
                 FROM exercises e
                 LEFT JOIN metrics m ON m.id = e.metric_id
                 WHERE 1=1";

        // Pole pre hodnoty — budeme ich pridávať dynamicky
        $params = [];

        // Pridáme podmienky len ak filter prišiel a nie je prázdny
        if (!empty($filters['metric_id'])) {
            $sql .= " AND e.metric_id = :metric_id";
            $params[':metric_id'] = (int) $filters['metric_id'];
        }

        if (!empty($filters['hladaj'])) {
            $sql .= " AND e.name LIKE :hladaj";
            $params[':hladaj'] = '%' . $filters['hladaj'] . '%';
        }

        // Zoradiť výsledky podľa názvu
        $sql .= " ORDER BY e.name";

        // Spustíme prepared statement s dynamickými parametrami
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
