<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ModelInterface.php';

class WorkoutPlanModel implements ModelInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array
    {
        return $this->search([]);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT wp.*, u.name AS user_name, pg.name AS goal_name, pl.name AS level_name
                FROM workout_plans wp
                LEFT JOIN users u ON u.id = wp.user_id
                LEFT JOIN plan_goals pg ON pg.id = wp.goal_id
                LEFT JOIN plan_levels pl ON pl.id = wp.level_id
                WHERE wp.id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getCount(): int { return (int) $this->db->query('SELECT COUNT(*) FROM workout_plans')->fetchColumn(); }
    public function describe(): string { return 'Main entity with business logic and M:N exercises.'; }
    public function countRelated(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM workout_plan_exercises WHERE workout_plan_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public function insert(array $data): int
    {
        $sql = 'INSERT INTO workout_plans (title, note, user_id, goal_id, level_id, is_active) VALUES (:title,:note,:user_id,:goal_id,:level_id,:is_active)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data['id'] = $id;
        $sql = 'UPDATE workout_plans SET title=:title, note=:note, user_id=:user_id, goal_id=:goal_id, level_id=:level_id, is_active=:is_active WHERE id=:id';
        return $this->db->prepare($sql)->execute($data);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare('DELETE FROM workout_plans WHERE id = :id')->execute(['id' => $id]);
    }

    public function search(array $filters): array
    {
        $sql = 'SELECT wp.*, u.name AS user_name, pg.name AS goal_name, pl.name AS level_name
                FROM workout_plans wp
                LEFT JOIN users u ON u.id = wp.user_id
                LEFT JOIN plan_goals pg ON pg.id = wp.goal_id
                LEFT JOIN plan_levels pl ON pl.id = wp.level_id
                WHERE 1=1';
        $params = [];
        if (!empty($filters['q'])) { $sql .= ' AND (wp.title LIKE :q OR wp.note LIKE :q)'; $params['q'] = '%' . $filters['q'] . '%'; }
        if (!empty($filters['goal_id'])) { $sql .= ' AND wp.goal_id = :goal_id'; $params['goal_id'] = (int) $filters['goal_id']; }
        if (!empty($filters['level_id'])) { $sql .= ' AND wp.level_id = :level_id'; $params['level_id'] = (int) $filters['level_id']; }
        $sql .= ' ORDER BY wp.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getLatest(int $limit): array
    {
        $stmt = $this->db->prepare('SELECT * FROM workout_plans ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getExercisesForPlan(int $planId): array
    {
        $sql = 'SELECT e.* FROM workout_plan_exercises wpe JOIN exercises e ON e.id = wpe.exercise_id WHERE wpe.workout_plan_id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $planId]);
        return $stmt->fetchAll();
    }

    public function attachExercise(int $planId, int $exerciseId): bool
    {
        return $this->db->prepare('INSERT IGNORE INTO workout_plan_exercises (workout_plan_id, exercise_id) VALUES (:plan_id,:exercise_id)')
            ->execute(['plan_id' => $planId, 'exercise_id' => $exerciseId]);
    }

    public function detachExercise(int $planId, int $exerciseId): bool
    {
        return $this->db->prepare('DELETE FROM workout_plan_exercises WHERE workout_plan_id=:plan_id AND exercise_id=:exercise_id')
            ->execute(['plan_id' => $planId, 'exercise_id' => $exerciseId]);
    }

    public function syncExercises(int $planId, array $exerciseIds): void
    {
        $this->db->prepare('DELETE FROM workout_plan_exercises WHERE workout_plan_id = :id')->execute(['id' => $planId]);
        foreach ($exerciseIds as $exerciseId) {
            $this->attachExercise($planId, (int) $exerciseId);
        }
    }
}
