<?php
require_once __DIR__ . '/models/WorkoutPlanModel.php';
require_once __DIR__ . '/models/ExerciseCatalogModel.php';
require_once __DIR__ . '/models/MetricCatalogModel.php';
require_once __DIR__ . '/models/PlanGoalModel.php';
require_once __DIR__ . '/models/PlanLevelModel.php';
$planModel = new WorkoutPlanModel(); $exerciseModel = new ExerciseCatalogModel(); $metricModel = new MetricCatalogModel(); $goalModel = new PlanGoalModel(); $levelModel = new PlanLevelModel();
$latest = $planModel->getLatest(2);
?><!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="/assets/css/style.css"><title>Dashboard</title></head><body><?php include __DIR__ . '/includes/nav.php'; ?>
<h1>Workout Tracker Dashboard</h1>
<ul><li>Total workout plans: <?= $planModel->getCount(); ?></li><li>Total exercises: <?= $exerciseModel->getCount(); ?></li><li>Total metrics: <?= $metricModel->getCount(); ?></li><li>Total goals: <?= $goalModel->getCount(); ?></li><li>Total levels: <?= $levelModel->getCount(); ?></li></ul>
<h2>Latest 2 plans</h2><ul><?php foreach ($latest as $p): ?><li><?= htmlspecialchars($p['title']); ?> (related exercises: <?= $planModel->countRelated((int) $p['id']); ?>)</li><?php endforeach; ?></ul>
</body></html>
