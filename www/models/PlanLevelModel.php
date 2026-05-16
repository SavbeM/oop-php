<?php
require_once __DIR__ . '/BaseModel.php';
class PlanLevelModel extends BaseModel
{
    public function __construct() { parent::__construct('plan_levels'); }
    public function describe(): string { return 'Difficulty levels for plans.'; }
    public function countRelated(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM workout_plans WHERE level_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
