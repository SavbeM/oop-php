DROP TABLE IF EXISTS workout_plan_exercises;
DROP TABLE IF EXISTS workout_plans;
DROP TABLE IF EXISTS exercises;
DROP TABLE IF EXISTS plan_goals;
DROP TABLE IF EXISTS plan_levels;
DROP TABLE IF EXISTS metrics;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE metrics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL
);

CREATE TABLE plan_goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE plan_levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE exercises (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  metric_id INT NOT NULL,
  -- RESTRICT protects metric catalogue rows used by exercises.
  CONSTRAINT fk_exercises_metric FOREIGN KEY (metric_id) REFERENCES metrics(id) ON DELETE RESTRICT
);

CREATE TABLE workout_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  note TEXT NULL,
  user_id INT NULL,
  goal_id INT NOT NULL,
  level_id INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- SET NULL keeps historical plans even if a user account is removed.
  CONSTRAINT fk_wp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  -- RESTRICT protects goals while plans reference them.
  CONSTRAINT fk_wp_goal FOREIGN KEY (goal_id) REFERENCES plan_goals(id) ON DELETE RESTRICT,
  -- RESTRICT protects levels while plans reference them.
  CONSTRAINT fk_wp_level FOREIGN KEY (level_id) REFERENCES plan_levels(id) ON DELETE RESTRICT
);

CREATE TABLE workout_plan_exercises (
  workout_plan_id INT NOT NULL,
  exercise_id INT NOT NULL,
  PRIMARY KEY (workout_plan_id, exercise_id),
  -- CASCADE removes M:N rows when a plan is deleted.
  CONSTRAINT fk_wpe_plan FOREIGN KEY (workout_plan_id) REFERENCES workout_plans(id) ON DELETE CASCADE,
  -- RESTRICT protects exercises that are still linked from plans.
  CONSTRAINT fk_wpe_exercise FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE RESTRICT
);

INSERT INTO users (name) VALUES ('Anna'),('Martin'),('Petra');
INSERT INTO metrics (name) VALUES ('reps'),('seconds'),('minutes'),('kilograms');
INSERT INTO plan_goals (name) VALUES ('Fat Loss'),('Strength'),('Endurance');
INSERT INTO plan_levels (name) VALUES ('Beginner'),('Intermediate'),('Advanced');
INSERT INTO exercises (name, metric_id) VALUES
('Push-ups',1),('Plank',2),('Squat',1),('Running',3),('Deadlift',4),('Burpees',1);

INSERT INTO workout_plans (title,note,user_id,goal_id,level_id,is_active) VALUES
('Morning Starter','Short bodyweight routine',1,1,1,1),
('Power Builder','Focus on heavy lifts',2,2,3,1),
('Cardio Blast','Heart rate endurance plan',3,3,2,1),
('Home Quick Plan','No equipment needed',1,1,1,1),
('Mixed Weekly Plan','Balanced strength and cardio',2,2,2,1);

INSERT INTO workout_plan_exercises (workout_plan_id,exercise_id) VALUES
(1,1),(1,2),(1,6),
(2,3),(2,5),
(3,4),(3,2),
(4,1),(4,3),(4,6),
(5,3),(5,4),(5,5);
