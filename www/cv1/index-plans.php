<?php
require_once 'Database.php';
require_once 'BaseModel.php';
require_once 'WorkoutPlanModel.php';

$pdo = Database::getInstance();
$model = new WorkoutPlanModel($pdo);
$workoutPlans = $model->getAll();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatDate(string $date): string
{
    return date('d.m.Y H:i', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Tréningové plány</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .info {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #004085;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th {
            background-color: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        table td {
            border: 1px solid #ddd;
            padding: 12px;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tr:hover {
            background-color: #f0f0f0;
        }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status.active {
            background-color: #d4edda;
            color: #155724;
        }
        .status.inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .empty {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 40px 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏋️ Tréningové plány</h1>
        
        <div class="info">
            <strong>Info:</strong> <?= h($model->describe()) ?>
        </div>

        <?php if (empty($workoutPlans)): ?>
            <div class="empty">
                <p>Žiadne tréningové plány na zobrazenie.</p>
            </div>
        <?php else: ?>
            <table>
                <tr>
                    <th>Názov</th>
                    <th>Používateľ</th>
                    <th>Cvikov</th>
                    <th>Poznámka</th>
                    <th>Stav</th>
                    <th>Vytvorené</th>
                    <th>Akcia</th>
                </tr>
                <?php foreach ($workoutPlans as $plan): ?>
                <tr>
                    <td><?= h($plan['title']) ?></td>
                    <td><?= h($plan['user_name'] ?? 'N/A') ?></td>
                    <td style="text-align: center;"><?= $plan['exercise_count'] ?></td>
                    <td><?= h($plan['note'] ?? '—') ?></td>
                    <td>
                        <span class="status <?= $plan['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $plan['is_active'] ? 'Aktívny' : 'Neaktívny' ?>
                        </span>
                    </td>
                    <td><?= formatDate($plan['created_at']) ?></td>
                    <td>
                        <a href="plan-detail.php?id=<?= $plan['id'] ?>">Podrobnosti</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
