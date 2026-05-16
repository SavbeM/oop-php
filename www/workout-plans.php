<?php
require_once __DIR__ . '/models/WorkoutPlanModel.php'; 
require_once __DIR__ . '/models/PlanGoalModel.php'; 
require_once __DIR__ . '/models/PlanLevelModel.php';
$model = new WorkoutPlanModel(); 
$goals = (new PlanGoalModel())->getAll(); 
$levels = (new PlanLevelModel())->getAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) { 
    $model->delete((int) $_POST['delete_id']); 
    header('Location: /workout-plans.php'); 
    exit; 
}
$filters = ['q'=>trim($_GET['q'] ?? ''), 'goal_id'=>(int)($_GET['goal_id'] ?? 0), 'level_id'=>(int)($_GET['level_id'] ?? 0)];
$plans = $model->search($filters);
?><!doctype html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workout Plans</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/nav.php'; ?>
    
    <h1>💪 Workout Plans</h1>
    
    <form method="get" style="display: grid; gap: 10px; grid-template-columns: 1fr 200px 200px auto;">
        <input type="text" name="q" value="<?= htmlspecialchars($filters['q']); ?>" placeholder="Search title or note..." />
        <select name="goal_id">
            <option value="">All goals</option>
            <?php foreach($goals as $g): ?>
                <option value="<?= $g['id']; ?>" <?= $filters['goal_id']==$g['id']?'selected':''; ?>>
                    <?= htmlspecialchars($g['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="level_id">
            <option value="">All levels</option>
            <?php foreach($levels as $l): ?>
                <option value="<?= $l['id']; ?>" <?= $filters['level_id']==$l['id']?'selected':''; ?>>
                    <?= htmlspecialchars($l['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">🔍 Filter</button>
    </form>
    
    <div style="overflow-x: auto; margin-top: 20px;">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Goal</th>
                    <th>Level</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($plans)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--muted);">No workout plans found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($plans as $p): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($p['title']); ?></strong>
                            <?php if($p['note']): ?>
                                <br><small style="color: var(--muted);"><?= htmlspecialchars($p['note']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['goal_name'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($p['level_name'] ?? ''); ?></td>
                        <td style="text-align: center;">
                            <a href="/workout-plan-detail.php?id=<?= $p['id']; ?>" style="margin-right: 8px;">View</a>
                            <a href="/workout-plan-upravit.php?id=<?= $p['id']; ?>" style="margin-right: 8px;">Edit</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this plan?');">
                                <input type="hidden" name="delete_id" value="<?= $p['id']; ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="/workout-plan-pridat.php" style="display: inline-block; background: var(--primary); color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 500; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2); border: none;">
            <span style="color: white; font-size: 18px;">➕</span> Add New Plan
        </a>
    </div>
    
    <script src="/assets/js/script.js" defer></script>
</body>
</html>
