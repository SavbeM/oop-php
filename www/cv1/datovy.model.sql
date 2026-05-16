-- =============================================================================
-- 🏋️ WORKOUT TRACKER – jednoduchý, rozšíriteľný dátový model
-- =============================================================================
-- Databáza : MySQL 8.x
-- Kódovanie : utf8mb4
-- Reset     : mysql -u root -p workout_tracker < workout_tracker.sql
-- =============================================================================
--
-- SCHÉMA (jednoduchá)
-- =============================================================================
--
--  ┌──────────────┐
--  │   metrics    │  (číselník / enum-like)
--  │ id, code, unit
--  └──────┬───────┘
--         │
--  ┌──────▼────────┐          ┌──────────────────┐
--  │   exercises   │          │      users       │
--  │ id, name,...  │          │ id, name, email  │
--  │ metric_id  ───┼─────────►│ created_at       │
--  └──────┬────────┘          └────────┬─────────┘
--         │                            │
--         │                            │
--  ┌──────▼──────────────┐             │
--  │   workout_plans      │◄────────────┘
--  │ id, user_id, title   │
--  └──────┬──────────────┘
--         │
--  ┌──────▼──────────────┐
--  │ workout_plan_exercise │  (M:N)
--  │ plan_id, exercise_id  │
--  │ target_value, order   │
--  └──────────────────────┘
--
--  ┌────────────────────────────────────────────┐
--  │                progress                    │
--  │ id, user_id, exercise_id, metric_id        │
--  │ value, performed_at                        │
--  └────────────────────────────────────────────┘
--
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- DROP (správne poradie)
-- =============================================================================
DROP TABLE IF EXISTS progress;
DROP TABLE IF EXISTS workout_plan_exercises;
DROP TABLE IF EXISTS workout_plans;
DROP TABLE IF EXISTS exercises;
DROP TABLE IF EXISTS metrics;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- ČÍSELNÍK: METRICS (enum-like cez tabuľku = ľahko rozšíriteľné)
-- =============================================================================
CREATE TABLE metrics (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(30) NOT NULL,
  unit VARCHAR(20) NOT NULL,
  name VARCHAR(60) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_metrics_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Metričky (číselník). Code je unikátny identifikátor metriky.';

-- =============================================================================
-- USERS
-- =============================================================================
CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Používatelia';

-- =============================================================================
-- EXERCISES (má reláciu na Metric)
-- =============================================================================
CREATE TABLE exercises (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  metric_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_exercises_name (name),
  KEY idx_exercises_metric (metric_id),
  CONSTRAINT fk_exercises_metric
    FOREIGN KEY (metric_id) REFERENCES metrics(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Ucviky. Každý cvik má primárnu metriku (reps / weight / time / distance).';

-- =============================================================================
-- WORKOUT PLANS (má reláciu na User)
-- =============================================================================
CREATE TABLE workout_plans (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  note VARCHAR(255) NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_plans_user (user_id),
  CONSTRAINT fk_plans_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Tréningové plány používateľa';

-- =============================================================================
-- M:N: WorkoutPlan ↔ Exercise
-- (target_value = cieľ pre metriku daného cviku v pláne)
-- =============================================================================
CREATE TABLE workout_plan_exercises (
  plan_id BIGINT UNSIGNED NOT NULL,
  exercise_id BIGINT UNSIGNED NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  target_value DECIMAL(10,2) NULL,
  PRIMARY KEY (plan_id, exercise_id),
  KEY idx_wpe_exercise (exercise_id),
  CONSTRAINT fk_wpe_plan
    FOREIGN KEY (plan_id) REFERENCES workout_plans(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_wpe_exercise
    FOREIGN KEY (exercise_id) REFERENCES exercises(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Cvaky v pláne (poradie + cieľová hodnota)';

-- =============================================================================
-- PROGRESS (user + metric + exercise + value)
-- =============================================================================
CREATE TABLE progress (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  exercise_id BIGINT UNSIGNED NOT NULL,
  metric_id INT UNSIGNED NOT NULL,
  value DECIMAL(10,2) NOT NULL,
  performed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_progress_user_time (user_id, performed_at),
  KEY idx_progress_exercise_time (exercise_id, performed_at),

  CONSTRAINT fk_progress_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_progress_exercise
    FOREIGN KEY (exercise_id) REFERENCES exercises(id)
    ON DELETE RESTRICT,

  CONSTRAINT fk_progress_metric
    FOREIGN KEY (metric_id) REFERENCES metrics(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Záznamy progresu: čo používateľ reálne odcvičil';
  
-- =============================================================================
-- SEED DATA (demo dáta)
-- =============================================================================

-- METRICS
INSERT INTO metrics (code, unit, name) VALUES
('reps',        'reps', 'Repetitions'),
('weight_kg',   'kg',   'Weight'),
('time_sec',    'sec',  'Time'),
('distance_km', 'km',   'Distance');

-- USERS
INSERT INTO users (name, email, password_hash) VALUES
('Alex Novak', 'alex@example.com', '$2b$12$DEMO_HASH_ALEX'),
('Mira Koval', 'mira@example.com', '$2b$12$DEMO_HASH_MIRA');

-- EXERCISES (metric_id podľa INSERT vyššie: 1=reps,2=weight_kg,3=time_sec,4=distance_km)
INSERT INTO exercises (name, description, metric_id) VALUES
('Push-ups', 'Classic push-ups', 1),
('Plank', 'Hold plank position', 3),
('Squat (bodyweight)', 'Bodyweight squat', 1),
('Running', 'Easy pace run', 4),
('Deadlift', 'Barbell deadlift', 2);

-- WORKOUT PLANS
-- Alex (id=1)
INSERT INTO workout_plans (user_id, title, note, is_active) VALUES
(1, 'Beginner Full Body', '3x per week', TRUE),
(1, 'Cardio Starter', 'Light cardio days', TRUE);

-- Mira (id=2)
INSERT INTO workout_plans (user_id, title, note, is_active) VALUES
(2, 'Strength Basics', 'Focus on technique', TRUE);

-- PLAN ↔ EXERCISES (target_value podľa metriky cviku)
-- Plan 1 (Alex Beginner Full Body) -> push-ups, squat, plank
INSERT INTO workout_plan_exercises (plan_id, exercise_id, sort_order, target_value) VALUES
(1, 1, 1, 12),   -- Push-ups target 12 reps
(1, 3, 2, 15),   -- Squat target 15 reps
(1, 2, 3, 45);   -- Plank target 45 sec

-- Plan 2 (Alex Cardio Starter) -> running, plank
INSERT INTO workout_plan_exercises (plan_id, exercise_id, sort_order, target_value) VALUES
(2, 4, 1, 2.5),  -- Running target 2.5 km
(2, 2, 2, 60);   -- Plank target 60 sec

-- Plan 3 (Mira Strength Basics) -> deadlift, squat, plank
INSERT INTO workout_plan_exercises (plan_id, exercise_id, sort_order, target_value) VALUES
(3, 5, 1, 40),   -- Deadlift target 40 kg
(3, 3, 2, 20),   -- Squat target 20 reps
(3, 2, 3, 50);   -- Plank target 50 sec

-- PROGRESS (user + exercise + metric + value)
-- Alex 
INSERT INTO progress (user_id, exercise_id, metric_id, value, performed_at) VALUES
(1, 1, 1, 10, '2026-02-01 18:00:00'), -- Push-ups 10 reps
(1, 2, 3, 40, '2026-02-01 18:10:00'), -- Plank 40 sec
(1, 4, 4, 2.2,'2026-02-03 19:00:00'), -- Running 2.2 km
(1, 1, 1, 12, '2026-02-05 18:00:00'); -- Push-ups 12 reps

-- Mira 
INSERT INTO progress (user_id, exercise_id, metric_id, value, performed_at) VALUES
(2, 5, 2, 35, '2026-02-02 17:30:00'), -- Deadlift 35 kg
(2, 2, 3, 45, '2026-02-02 17:40:00'); -- Plank 45 sec