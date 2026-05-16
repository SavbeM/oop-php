<?php

require_once 'interfaces/ModelInterface.php';

class WorkoutPlanModel implements ModelInterface
{
    protected PDO $pdo;
    protected string $table = 'workout_plans';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function describe(): string
    {
        return "WorkoutPlanModel | tabuľka: {$this->table} | záznamov: " . $this->getCount();
    }

    public function getAll(): array
    {
        $sql = "SELECT
                    wp.id,
                    wp.user_id,
                    wp.title,
                    wp.note,
                    wp.is_active,
                    wp.created_at,
                    u.name AS user_name,
                    u.email AS user_email,
                    COUNT(wpe.exercise_id) AS exercise_count
                FROM workout_plans wp
                LEFT JOIN users u ON u.id = wp.user_id
                LEFT JOIN workout_plan_exercises wpe ON wpe.plan_id = wp.id
                GROUP BY wp.id, u.name, u.email
                ORDER BY wp.created_at DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false
    {
        $sql = "SELECT
                    wp.id,
                    wp.user_id,
                    wp.title,
                    wp.note,
                    wp.is_active,
                    wp.created_at,
                    u.name AS user_name,
                    u.email AS user_email
                FROM workout_plans wp
                LEFT JOIN users u ON u.id = wp.user_id
                WHERE wp.id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM workout_plans');
        return (int) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM workout_plans WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function countRelated(int $id): int
    {
        // workout_plans sa zvyčajne nemazajú (ak by sme chceli), 
        // používajú sa aj keď sú staré. Soft-delete by bol lepší.
        return 0;
    }

    public function getExercisesForPlan(int $planId): array
    {
        $sql = "SELECT
                    wpe.exercise_id,
                    wpe.sort_order,
                    wpe.target_value,
                    e.name AS exercise_name,
                    e.description AS exercise_description,
                    m.code AS metric_code,
                    m.unit AS metric_unit,
                    m.name AS metric_name
                FROM workout_plan_exercises wpe
                INNER JOIN exercises e ON e.id = wpe.exercise_id
                INNER JOIN metrics m ON m.id = e.metric_id
                WHERE wpe.plan_id = :plan_id
                ORDER BY wpe.sort_order ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':plan_id' => $planId]);
        return $stmt->fetchAll();
    }

    public function insert(mixed ...$args): int
    {
        if (count($args) !== 4) {
            throw new InvalidArgumentException('insert() pre workout_plans očakáva: userId, title, note, isActive.');
        }

        $userId = (int) $args[0];
        $title = $this->validateTitle((string) $args[1]);
        $note = $this->validateNote($args[2]);
        $isActive = (bool) $args[3];

        $this->ensureUserExists($userId);

        $sql = "INSERT INTO workout_plans (user_id, title, note, is_active)
                VALUES (:user_id, :title, :note, :is_active)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':note' => $note,
            ':is_active' => (int) $isActive,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, mixed ...$args): bool
    {
        if (count($args) !== 4) {
            throw new InvalidArgumentException('update() pre workout_plans očakáva: userId, title, note, isActive.');
        }

        $userId = (int) $args[0];
        $title = $this->validateTitle((string) $args[1]);
        $note = $this->validateNote($args[2]);
        $isActive = (bool) $args[3];

        $this->ensureUserExists($userId);

        $sql = "UPDATE workout_plans
                SET user_id = :user_id,
                    title = :title,
                    note = :note,
                    is_active = :is_active
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':title' => $title,
            ':note' => $note,
            ':is_active' => (int) $isActive,
        ]);
    }

    private function validateTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            throw new InvalidArgumentException('Názov plánu nemôže byť prázdny.');
        }
        return $title;
    }

    private function validateNote(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }

        $note = trim($note);
        return $note === '' ? null : $note;
    }

    private function ensureUserExists(int $userId): void
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $userId]);

        if ((int) $stmt->fetchColumn() === 0) {
            throw new InvalidArgumentException('Vybraný používateľ neexistuje.');
        }
    }
}

