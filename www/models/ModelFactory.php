<?php
require_once __DIR__ . '/MetricCatalogModel.php';
require_once __DIR__ . '/ExerciseCatalogModel.php';
require_once __DIR__ . '/PlanGoalModel.php';
require_once __DIR__ . '/PlanLevelModel.php';

class ModelFactory
{
    public static function create(string $key): ?BaseModel
    {
        return match ($key) {
            'metric' => new MetricCatalogModel(),
            'exercise' => new ExerciseCatalogModel(),
            'plan_goal' => new PlanGoalModel(),
            'plan_level' => new PlanLevelModel(),
            default => null,
        };
    }
}
