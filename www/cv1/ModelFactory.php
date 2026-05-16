<?php

require_once 'interfaces/ModelInterface.php';
require_once 'MetricCatalogModel.php';
require_once 'ExerciseCatalogModel.php';
require_once 'WorkoutPlanModel.php';

/**
 * ModelFactory — Factory pattern na vytvorenie inštancií modelov
 * 
 * Centralizuje logiku vytvarania modelov a ukrýva detaily inicializácie.
 * Eliminuje potrebe na import jednotlivých modelov — volajúci kód
 * nepotrebuje vedieť ako sa modely vytvárajú.
 */
class ModelFactory
{
    /**
     * vytvor() — Vytvorí a vráti inštanciu modelu podľa typu
     * 
     * @param string $typ        — typ modelu: 'metric', 'exercise', 'workout_plan'
     * @param PDO $pdo          — PDO pripojenie k databáze
     * @return ModelInterface   — inicializovaný model
     * 
     * @throws InvalidArgumentException — ak typ modelu nie je podporovaný
     */
    public static function vytvor(string $typ, PDO $pdo): ModelInterface
    {
        return match ($typ) {
            'metric'       => new MetricCatalogModel($pdo),
            'exercise'     => new ExerciseCatalogModel($pdo),
            'workout_plan' => new WorkoutPlanModel($pdo),
            default        => throw new InvalidArgumentException(
                "Neznámy model: $typ. " .
                "Podporované modely: 'metric', 'exercise', 'workout_plan'."
            ),
        };
    }
}
