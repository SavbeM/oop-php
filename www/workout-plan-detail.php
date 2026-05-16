<?php
require_once __DIR__ . '/models/WorkoutPlanModel.php';
$model = new WorkoutPlanModel(); $id = (int) ($_GET['id'] ?? 0); $plan = $id > 0 ? $model->getById($id) : null;
?><!doctype html><html><body><?php include __DIR__ . '/includes/nav.php'; ?><h1>Workout plan detail</h1>
<?php if(!$plan): ?><p>Invalid or missing plan ID.</p><?php else: $exercises = $model->getExercisesForPlan($id); ?>
<p><b>Title:</b> <?= htmlspecialchars($plan['title']); ?></p><p><b>Note:</b> <?= htmlspecialchars($plan['note']); ?></p><p><b>User:</b> <?= htmlspecialchars($plan['user_name']); ?></p><p><b>Goal:</b> <?= htmlspecialchars($plan['goal_name']); ?></p><p><b>Level:</b> <?= htmlspecialchars($plan['level_name']); ?></p>
<h3>Exercises</h3><ul><?php foreach($exercises as $e): ?><li><?= htmlspecialchars($e['name']); ?></li><?php endforeach; ?></ul><?php endif; ?>
</body></html>
