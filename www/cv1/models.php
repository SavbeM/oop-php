<?php
// =============================================================================
// models.php — všetky PDO modely pre Workout Tracker
// =============================================================================
//
//  Použitie:
//    require_once 'connect.php';   // -> $pdo
//    require_once 'models.php';
//
//    $users     = new UserModel($pdo);
//    $exercises = new ExerciseModel($pdo);
//    ...
//
//  Obsah:
//    1. MetricModel               — číselník metrík
//    2. UserModel                 — používatelia
//    3. ExerciseModel             — cvičenia
//    4. WorkoutPlanModel          — tréningové plány
//    5. WorkoutPlanExerciseModel  — M:N väzba plán ↔ cvik
//    6. ProgressModel             — záznamy progresu
//
//  Relácie (ON DELETE):
//    users → workout_plans            CASCADE
//    users → progress                 CASCADE
//    workout_plans → workout_plan_exercises  CASCADE
//    metrics → exercises              RESTRICT
//    metrics → progress               RESTRICT
//    exercises → workout_plan_exercises RESTRICT
//    exercises → progress             RESTRICT
// =============================================================================


// =============================================================================
// 1. MetricModel — číselník metrík (reps, weight_kg, time_sec, distance_km)
// =============================================================================
//
//  Tabuľka: metrics
//  ┌────┬──────────────┬──────┬───────────────┐
//  │ id │ code (ENUM)  │ unit │ name          │
//  └────┴──────────────┴──────┴───────────────┘
//
//  Táto tabuľka je len číselník — zvyčajne sa nemení.
//  Iné tabuľky (exercises, progress) na ňu odkazujú cez metric_id.
// =============================================================================

class MetricModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function resolveSql(string $queryKey): string
    {
        return match ($queryKey) {
            'all'   => "SELECT id, code, unit, name FROM metrics ORDER BY id",
            'by_id' => "SELECT id, code, unit, name FROM metrics WHERE id = :id",
            'count' => "SELECT COUNT(*) FROM metrics",
            default => throw new InvalidArgumentException('Neznámy SQL dopyt pre MetricModel.'),
        };
    }

    /*
    // ── getAll() ─────────────────────────────────────────
    //  Presunuté do MetricCatalogModel.php
    public function getAll(): array
    {
        $stmt = $this->pdo->query($this->resolveSql('all'));
        return $stmt->fetchAll();
    }

    // ── getById() ────────────────────────────────────────
    //  Presunuté do MetricCatalogModel.php
    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare($this->resolveSql('by_id'));
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ── getCount() ───────────────────────────────────────
    //  Presunuté do MetricCatalogModel.php
    public function getCount(): int
    {
        $stmt = $this->pdo->query($this->resolveSql('count'));
        return (int) $stmt->fetchColumn();
    }
    */
}


// =============================================================================
// 2. UserModel — používatelia aplikácie
// =============================================================================
//
//  Tabuľka: users
//  ┌────┬──────┬───────┬───────────────┬────────────┐
//  │ id │ name │ email │ password_hash │ created_at │
//  └────┴──────┴───────┴───────────────┴────────────┘
//
//  Relácie (ON DELETE):
//    users → workout_plans  : CASCADE  (plány sa zmažú spolu s userom)
//    users → progress        : CASCADE  (záznamy progresu sa zmažú spolu s userom)
// =============================================================================

class UserModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── getAll() ─────────────────────────────────────────
    //  Vráti všetkých používateľov (bez hesiel — password_hash vynecháme).
    public function getAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name, email, created_at FROM users ORDER BY id"
        );
        return $stmt->fetchAll();
    }

    // ── getById() ────────────────────────────────────────
    //  Vráti jedného používateľa podľa id.
    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, created_at FROM users WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ── getByEmail() ─────────────────────────────────────
    //  Vráti používateľa podľa e-mailu (napr. pri prihlasovaní).
    //  Vracia aj password_hash — potrebné na overenie hesla.
    public function getByEmail(string $email): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, password_hash FROM users WHERE email = :email"
        );
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    // ── getCount() ───────────────────────────────────────
    //  Vráti celkový počet používateľov.
    public function getCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        return (int) $stmt->fetchColumn();
    }

    // ── getAllWithPlanCount() ─────────────────────────────
    //  JOIN: ku každému používateľovi pridá počet jeho plánov.
    //  LEFT JOIN — používateľ sa objaví aj keď nemá žiaden plán (COUNT = 0).
    //  GROUP BY — COUNT(*) počítame zvlášť pre každého používateľa.
    public function getAllWithPlanCount(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                 u.id,
                 u.name,
                 u.email,
                 COUNT(wp.id) AS plan_count   -- počet plánov daného usera
             FROM users u
             LEFT JOIN workout_plans wp        -- LEFT = zahrň aj userov bez plánov
                 ON wp.user_id = u.id
             GROUP BY u.id, u.name, u.email
             ORDER BY u.id"
        );
        return $stmt->fetchAll();
        // Každý riadok: ['id' => 1, 'name' => 'Alex Novak', 'email' => '...', 'plan_count' => 2]
    }
}


// =============================================================================
// 3. ExerciseModel — cvičenia s ich primárnou metrikou
// =============================================================================
//
//  Tabuľka: exercises
//  ┌────┬──────┬─────────────┬───────────┐
//  │ id │ name │ description │ metric_id │───► metrics(id)  ON DELETE RESTRICT
//  └────┴──────┴─────────────┴───────────┘
//
//  ON DELETE RESTRICT (metric_id):
//    Nemôžeme zmazať metriku, pokiaľ na ňu odkazuje nejaký cvik.
//    MySQL vyhodí chybu — musíme najprv zmazať / preradiť cviky.
//
//  ON DELETE RESTRICT (exercises vo workout_plan_exercises):
//    Cvik nemôžeme zmazať, kým sa nachádza v nejakom pláne.
// =============================================================================

class ExerciseModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function resolveSql(string $queryKey): string
    {
        return match ($queryKey) {
            'all'             => "SELECT id, name, description, metric_id FROM exercises ORDER BY id",
            'by_id'           => "SELECT id, name, description, metric_id FROM exercises WHERE id = :id",
            'count'           => "SELECT COUNT(*) FROM exercises",
            'all_with_metric' => "SELECT
                 e.id,
                 e.name,
                 e.description,
                 m.name AS metric_name,
                 m.unit AS metric_unit
             FROM exercises e
             INNER JOIN metrics m
                 ON m.id = e.metric_id
             ORDER BY e.id",
            'by_metric_id'    => "SELECT
                 e.id,
                 e.name,
                 e.description,
                 m.name AS metric_name,
                 m.unit AS metric_unit
             FROM exercises e
             INNER JOIN metrics m ON m.id = e.metric_id
             WHERE e.metric_id = :metric_id
             ORDER BY e.id",
            default => throw new InvalidArgumentException('Neznámy SQL dopyt pre ExerciseModel.'),
        };
    }

    /*
    // ── getAll() ─────────────────────────────────────────
    //  Presunuté do ExerciseCatalogModel.php
    public function getAll(): array
    {
        $stmt = $this->pdo->query($this->resolveSql('all'));
        return $stmt->fetchAll();
    }

    // ── getById() ────────────────────────────────────────
    //  Presunuté do ExerciseCatalogModel.php
    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare($this->resolveSql('by_id'));
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ── getCount() ───────────────────────────────────────
    //  Presunuté do ExerciseCatalogModel.php
    public function getCount(): int
    {
        $stmt = $this->pdo->query($this->resolveSql('count'));
        return (int) $stmt->fetchColumn();
    }
    */

    // ── getAllWithMetric() ────────────────────────────────
    //  JOIN: ku každému cviku pridá názov a jednotku metriky.
    //  INNER JOIN — každý cvik musí mať metriku (NOT NULL v schéme),
    //  takže INNER a LEFT dajú rovnaký výsledok, ale INNER je správnejší.
    public function getAllWithMetric(): array
    {
        $stmt = $this->pdo->query($this->resolveSql('all_with_metric'));
        return $stmt->fetchAll();
        // Každý riadok: ['id' => 1, 'name' => 'Push-ups', 'description' => '...', 'metric_name' => 'Repetitions', 'metric_unit' => 'reps']
    }

    // ── getByMetricId() ──────────────────────────────────
    //  Vráti všetky cviky s danou metrikou (napr. všetky silové cviky).
    public function getByMetricId(int $metricId): array
    {
        $stmt = $this->pdo->prepare($this->resolveSql('by_metric_id'));
        $stmt->execute([':metric_id' => $metricId]);
        return $stmt->fetchAll();
    }
}


// =============================================================================
// 4. WorkoutPlanModel — tréningové plány používateľov
// =============================================================================
//
//  Tabuľka: workout_plans
//  ┌────┬─────────┬───────┬──────┬───────────┬────────────┐
//  │ id │ user_id │ title │ note │ is_active │ created_at │
//  └────┴─────────┴───────┴──────┴───────────┴────────────┘
//             │
//             └──► users(id)  ON DELETE CASCADE
//                  Ak zmažeme používateľa, všetky jeho plány sa
//                  automaticky zmažú aj s nimi.
//
//  workout_plans → workout_plan_exercises : CASCADE
//    Ak zmažeme plán, zmažú sa aj všetky väzby na cviky v ňom.
// =============================================================================

class WorkoutPlanModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── getAll() ─────────────────────────────────────────
    //  Vráti všetky plány (bez JOIN-u).
    public function getAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, user_id, title, note, is_active, created_at
             FROM workout_plans
             ORDER BY id"
        );
        return $stmt->fetchAll();
    }

    // ── getById() ────────────────────────────────────────
    //  Vráti jeden plán podľa id.
    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, user_id, title, note, is_active, created_at
             FROM workout_plans
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ── getByUserId() ────────────────────────────────────
    //  Vráti všetky plány daného používateľa.
    public function getByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, title, note, is_active, created_at
             FROM workout_plans
             WHERE user_id = :user_id
             ORDER BY id"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    // ── getCount() ───────────────────────────────────────
    public function getCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM workout_plans");
        return (int) $stmt->fetchColumn();
    }

    // ── getAllWithUser() ──────────────────────────────────
    //  JOIN: ku každému plánu pridá meno a email jeho vlastníka.
    //  INNER JOIN — každý plán má user_id NOT NULL, takže každý
    //  plán musí mať práve jedného vlastníka.
    public function getAllWithUser(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                 wp.id,
                 wp.title,
                 wp.note,
                 wp.is_active,
                 wp.created_at,
                 u.name  AS user_name,   -- meno vlastníka
                 u.email AS user_email   -- email vlastníka
             FROM workout_plans wp
             INNER JOIN users u           -- každý plán patrí práve jednému userovi
                 ON u.id = wp.user_id
             ORDER BY wp.id"
        );
        return $stmt->fetchAll();
        // Každý riadok: ['id' => 1, 'title' => 'Beginner Full Body', ..., 'user_name' => 'Alex Novak', 'user_email' => '...']
    }

    // ── getActiveWithUser() ───────────────────────────────
    //  Rovnaký JOIN, ale iba aktívne plány (is_active = TRUE).
    public function getActiveWithUser(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                 wp.id,
                 wp.title,
                 wp.note,
                 u.name AS user_name
             FROM workout_plans wp
             INNER JOIN users u ON u.id = wp.user_id
             WHERE wp.is_active = TRUE
             ORDER BY wp.id"
        );
        return $stmt->fetchAll();
    }
}


// =============================================================================
// 5. WorkoutPlanExerciseModel — M:N väzba: plán ↔ cvik
// =============================================================================
//
//  Tabuľka: workout_plan_exercises
//  ┌─────────┬─────────────┬────────────┬──────────────┐
//  │ plan_id │ exercise_id │ sort_order │ target_value │
//  └─────────┴─────────────┴────────────┴──────────────┘
//       │           │
//       │           └──► exercises(id)    ON DELETE RESTRICT
//       │                Cvik nemôžeme zmazať, kým je v nejakom pláne.
//       │                Najprv treba odstrániť väzbu z tejto tabuľky.
//       │
//       └──────────────►  workout_plans(id) ON DELETE CASCADE
//                         Ak zmažeme plán, automaticky sa zmažú aj
//                         všetky jeho väzby na cviky.
//
//  Zložený PRIMARY KEY (plan_id, exercise_id):
//    Ten istý cvik môže byť v pláne len raz.
// =============================================================================

class WorkoutPlanExerciseModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── getByPlanId() ─────────────────────────────────────
    //  Vráti všetky cviky daného plánu s detailmi cviku a metriky.
    //  Dvojitý JOIN: workout_plan_exercises → exercises → metrics
    //  ORDER BY sort_order — cviky v poradí definovanom v pláne.
    public function getByPlanId(int $planId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                 e.id           AS exercise_id,
                 e.name         AS exercise_name,
                 e.description,
                 wpe.sort_order,
                 wpe.target_value,
                 m.name         AS metric_name,   -- napr. 'Repetitions'
                 m.unit         AS metric_unit     -- napr. 'reps'
             FROM workout_plan_exercises wpe
             INNER JOIN exercises e               -- cvik musí existovať (RESTRICT)
                 ON e.id = wpe.exercise_id
             INNER JOIN metrics m                 -- metrika cviku
                 ON m.id = e.metric_id
             WHERE wpe.plan_id = :plan_id
             ORDER BY wpe.sort_order"
        );
        $stmt->execute([':plan_id' => $planId]);
        return $stmt->fetchAll();
        // Každý riadok: ['exercise_id' => 1, 'exercise_name' => 'Push-ups', 'sort_order' => 1, 'target_value' => '12.00', 'metric_name' => 'Repetitions', 'metric_unit' => 'reps']
    }

    // ── getByExerciseId() ────────────────────────────────
    //  Vráti všetky plány, v ktorých sa nachádza daný cvik.
    //  Keďže ON DELETE RESTRICT — cvik nemôžeme zmazať kým existujú
    //  tieto záznamy. Táto metóda nám ukáže, kde všade cvik figuruje.
    public function getByExerciseId(int $exerciseId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                 wp.id          AS plan_id,
                 wp.title       AS plan_title,
                 u.name         AS user_name,    -- vlastník plánu
                 wpe.sort_order,
                 wpe.target_value
             FROM workout_plan_exercises wpe
             INNER JOIN workout_plans wp ON wp.id = wpe.plan_id
             INNER JOIN users u          ON u.id  = wp.user_id
             WHERE wpe.exercise_id = :exercise_id
             ORDER BY wp.id"
        );
        $stmt->execute([':exercise_id' => $exerciseId]);
        return $stmt->fetchAll();
    }

    // ── getCount() ───────────────────────────────────────
    //  Celkový počet väzieb plán-cvik vo všetkých plánoch.
    public function getCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM workout_plan_exercises");
        return (int) $stmt->fetchColumn();
    }
}


// =============================================================================
// 6. ProgressModel — záznamy skutočne odcvičeného
// =============================================================================
//
//  Tabuľka: progress
//  ┌────┬─────────┬─────────────┬───────────┬───────┬──────────────┐
//  │ id │ user_id │ exercise_id │ metric_id │ value │ performed_at │
//  └────┴─────────┴─────────────┴───────────┴───────┴──────────────┘
//         │             │             │
//         │             │             └──► metrics(id)   ON DELETE RESTRICT
//         │             │                  Metriku nemôžeme zmazať, kým na
//         │             │                  ňu odkazujú záznamy progresu.
//         │             │
//         │             └──────────────►  exercises(id)  ON DELETE RESTRICT
//         │                              Cvik nemôžeme zmazať, kým má záznamy.
//         │
//         └────────────────────────────►  users(id)      ON DELETE CASCADE
//                                        Ak zmažeme usera, zmažú sa aj
//                                        všetky jeho záznamy progresu.
// =============================================================================

class ProgressModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── getAll() ─────────────────────────────────────────
    //  Vráti všetky záznamy (bez JOIN-u).
    public function getAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, user_id, exercise_id, metric_id, value, performed_at
             FROM progress
             ORDER BY performed_at DESC"
        );
        return $stmt->fetchAll();
    }

    // ── getById() ────────────────────────────────────────
    //  Vráti jeden záznam podľa id.
    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, user_id, exercise_id, metric_id, value, performed_at
             FROM progress
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ── getCount() ───────────────────────────────────────
    public function getCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM progress");
        return (int) $stmt->fetchColumn();
    }

    // ── getAllWithDetails() ───────────────────────────────
    //  JOIN: ku každému záznamu pridá meno usera, cviku a metriky.
    //  Trojitý JOIN: progress → users, exercises → metrics
    //  Toto je najčastejší pohľad — chceme čitateľné dáta, nie len id.
    public function getAllWithDetails(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                 p.id,
                 p.value,
                 p.performed_at,
                 u.name  AS user_name,       -- meno používateľa
                 e.name  AS exercise_name,   -- názov cviku
                 m.unit  AS metric_unit      -- jednotka (reps / kg / sec / km)
             FROM progress p
             INNER JOIN users     u ON u.id = p.user_id      -- ON DELETE CASCADE
             INNER JOIN exercises e ON e.id = p.exercise_id  -- ON DELETE RESTRICT
             INNER JOIN metrics   m ON m.id = p.metric_id    -- ON DELETE RESTRICT
             ORDER BY p.performed_at DESC"
        );
        return $stmt->fetchAll();
        // Každý riadok: ['id' => 1, 'value' => '10.00', 'performed_at' => '...', 'user_name' => 'Alex Novak', 'exercise_name' => 'Push-ups', 'metric_unit' => 'reps']
    }

    // ── getByUserId() ────────────────────────────────────
    //  Vráti históriu progresu jedného používateľa s detailmi.
    public function getByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                 p.id,
                 p.value,
                 p.performed_at,
                 e.name AS exercise_name,
                 m.unit AS metric_unit
             FROM progress p
             INNER JOIN exercises e ON e.id = p.exercise_id
             INNER JOIN metrics   m ON m.id = p.metric_id
             WHERE p.user_id = :user_id
             ORDER BY p.performed_at DESC"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    // ── getByExerciseId() ────────────────────────────────
    //  Vráti všetky záznamy pre daný cvik naprieč všetkými usermi.
    //  Užitočné napr. na zobrazenie rebríčka / leaderboard.
    public function getByExerciseId(int $exerciseId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                 p.id,
                 p.value,
                 p.performed_at,
                 u.name AS user_name,
                 m.unit AS metric_unit
             FROM progress p
             INNER JOIN users   u ON u.id = p.user_id
             INNER JOIN metrics m ON m.id = p.metric_id
             WHERE p.exercise_id = :exercise_id
             ORDER BY p.performed_at DESC"
        );
        $stmt->execute([':exercise_id' => $exerciseId]);
        return $stmt->fetchAll();
    }
}
