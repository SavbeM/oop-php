<?php
require_once __DIR__ . '/BaseModel.php';
class PlanGoalModel extends BaseModel
{
    public function __construct() { parent::__construct('plan_goals'); }
    public function describe(): string { return 'Goals for training plans.'; }
    public function countRelated(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM workout_plans WHERE goal_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
