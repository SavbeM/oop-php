<?php
require_once "Database.php";
require_once "models.php";
require_once "MetricCatalogModel.php";
require_once "ExerciseCatalogModel.php";

/*
|--------------------------------------------------------------------------
| WORKOUT TRACKER – ukážka všetkých PDO modelov
|--------------------------------------------------------------------------
| Každá sekcia zodpovedá jednému modelu a volá jeho metódy.
|--------------------------------------------------------------------------
*/

// ── Vytvorenie inštancií všetkých modelov ────────────────
$pdo = Database::getInstance();
$metricModel    = new MetricCatalogModel($pdo);
$userModel      = new UserModel($pdo);
$exerciseModel  = new ExerciseCatalogModel($pdo);
$planModel      = new WorkoutPlanModel($pdo);
$planExModel    = new WorkoutPlanExerciseModel($pdo);
$progressModel  = new ProgressModel($pdo);

// ── Pomocná funkcia na vypísanie poľa ako HTML tabuľky ───
// Príjme pole asociatívnych polí (výsledok fetchAll) a vypíše HTML tabuľku.
function renderTable(array $rows): void
{
    if (empty($rows)) {
        echo "<p><em>Žiadne záznamy.</em></p>";
        return;
    }
    echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse; margin-bottom:8px'>";
    // hlavička — kľúče prvého riadku
    echo "<tr style='background:#e8e8e8'>";
    foreach (array_keys($rows[0]) as $col) {
        echo "<th>$col</th>";
    }
    echo "</tr>";
    // dáta
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars((string)$val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Workout Tracker – Modely</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f5f5f5; }
        h1   { color: #333; }
        h2   { color: #555; margin-top: 30px; border-bottom: 2px solid #ccc; padding-bottom: 4px; }
        h3   { color: #777; margin-top: 16px; }
        table { background: white; }
        th   { text-align: left; }
        .count { font-size: 0.9em; color: #888; margin: 4px 0 8px; }
    </style>
</head>
<body>
<h1>Workout Tracker – ukážka PDO modelov</h1>

<?php

// ==========================================================
//  1. MetricModel — číselník metrík
// ==========================================================
echo "<h2>1. MetricModel</h2>";

echo "<h3>getAll() — všetky metriky</h3>";
renderTable($metricModel->getAll());

echo "<h3>getById(2) — metrika s id = 2</h3>";
$metric = $metricModel->getById(2);
renderTable($metric ? [$metric] : []);

echo "<p class='count'>getCount() = " . $metricModel->getCount() . " metrík</p>";


// ==========================================================
//  2. UserModel — používatelia
// ==========================================================
echo "<h2>2. UserModel</h2>";

echo "<h3>getAll() — všetci používatelia (bez hesiel)</h3>";
renderTable($userModel->getAll());

echo "<h3>getById(1) — používateľ s id = 1</h3>";
$user = $userModel->getById(1);
renderTable($user ? [$user] : []);

echo "<h3>getByEmail('alex@example.com')</h3>";
$byEmail = $userModel->getByEmail('alex@example.com');
// schováme password_hash pred výpisom
if ($byEmail) { unset($byEmail['password_hash']); }
renderTable($byEmail ? [$byEmail] : []);

echo "<h3>getAllWithPlanCount() — JOIN users ↔ workout_plans (LEFT JOIN)</h3>";
renderTable($userModel->getAllWithPlanCount());

echo "<p class='count'>getCount() = " . $userModel->getCount() . " používateľov</p>";


// ==========================================================
//  3. ExerciseModel — cvičenia s FILTROM (search)
// ==========================================================
echo "<h2>3. ExerciseModel — vyhľadávanie a filtrovanie</h2>";

// Zbierame filtre z GET parametrov
$filters = [
    'metric_id' => $_GET['metric_id'] ?? '',
    'hladaj'    => $_GET['hladaj'] ?? '',
];

// Získame všetky metriky pre dropdown
$allMetrics = $metricModel->getAll();

// HTML formulár na filtrovanie
echo "<form method='GET' style='margin: 15px 0; padding: 15px; background: #fff; border: 1px solid #ccc;'>";
echo "<fieldset style='border: none;'>";
echo "<legend>Filtrovanie cvikov</legend>";

// Dropdown pre metriku
echo "<label for='metric_id' style='display: block; margin-bottom: 8px;'>";
echo "Metrika: ";
echo "<select name='metric_id' id='metric_id'>";
echo "<option value=''>-- Všetky metriky --</option>";
foreach ($allMetrics as $metric) {
    $selected = ((string)$filters['metric_id'] === (string)$metric['id']) ? 'selected' : '';
    echo "<option value='{$metric['id']}' $selected>{$metric['name']}</option>";
}
echo "</select>";
echo "</label>";

// Textové pole na hľadanie podľa názvu
echo "<label for='hladaj' style='display: block; margin-bottom: 12px;'>";
echo "Hľadaj názov: ";
echo "<input type='text' name='hladaj' id='hladaj' value='" . htmlspecialchars($filters['hladaj']) . "' />";
echo "</label>";

// Tlačidlá
echo "<button type='submit' style='padding: 6px 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>Hľadať</button>";
echo " ";
echo "<a href='?'><button type='button' style='padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;'>Vynulovať</button></a>";

echo "</fieldset>";
echo "</form>";

// Spustíme search() s aktívnymi filtrami
$cviky = $exerciseModel->search($filters);

echo "<h3>Výsledky vyhľadávania — search()</h3>";
if (empty($cviky)) {
    echo "<p><em>Žiadne cviky sa nezhodujú s filtrami.</em></p>";
} else {
    renderTable($cviky);
    echo "<p class='count'>Nájdených " . count($cviky) . " cvikov</p>";
}

// Informácia o aktívnych filtroch
if (!empty($filters['metric_id']) || !empty($filters['hladaj'])) {
    echo "<p style='color: #666; font-size: 0.9em;'>";
    echo "Aktívne filtre: ";
    $activeFilters = [];
    if (!empty($filters['metric_id'])) {
        $m = $metricModel->getById((int)$filters['metric_id']);
        $activeFilters[] = "Metrika: {$m['name']}";
    }
    if (!empty($filters['hladaj'])) {
        $activeFilters[] = "Názov: '{$filters['hladaj']}'";
    }
    echo implode(", ", $activeFilters);
    echo "</p>";
}


// ==========================================================
//  4. WorkoutPlanModel — tréningové plány
// ==========================================================
echo "<h2>4. WorkoutPlanModel</h2>";

echo "<h3>getAll() — všetky plány (surové dáta)</h3>";
renderTable($planModel->getAll());

echo "<h3>getAllWithUser() — JOIN workout_plans ↔ users (INNER JOIN)</h3>";
renderTable($planModel->getAllWithUser());

echo "<h3>getActiveWithUser() — iba aktívne plány s menom usera</h3>";
renderTable($planModel->getActiveWithUser());

echo "<h3>getByUserId(1) — plány používateľa id = 1</h3>";
renderTable($planModel->getByUserId(1));

echo "<h3>getById(2) — plán s id = 2</h3>";
$plan = $planModel->getById(2);
renderTable($plan ? [$plan] : []);

echo "<p class='count'>getCount() = " . $planModel->getCount() . " plánov</p>";


// ==========================================================
//  5. WorkoutPlanExerciseModel — M:N väzba plán ↔ cvik
// ==========================================================
echo "<h2>5. WorkoutPlanExerciseModel</h2>";

echo "<h3>getByPlanId(1) — cviky plánu id = 1 (dvojitý JOIN: wpe → exercises → metrics)</h3>";
renderTable($planExModel->getByPlanId(1));

echo "<h3>getByPlanId(3) — cviky plánu id = 3</h3>";
renderTable($planExModel->getByPlanId(3));

echo "<h3>getByExerciseId(2) — plány obsahujúce cvik id = 2 (Plank)</h3>";
// ON DELETE RESTRICT — cvik nemôžeme zmazať kým existujú tieto väzby
renderTable($planExModel->getByExerciseId(2));

echo "<p class='count'>getCount() = " . $planExModel->getCount() . " väzieb plán-cvik</p>";


// ==========================================================
//  6. ProgressModel — záznamy progresu
// ==========================================================
echo "<h2>6. ProgressModel</h2>";

echo "<h3>getAll() — všetky záznamy (surové dáta)</h3>";
renderTable($progressModel->getAll());

echo "<h3>getAllWithDetails() — trojitý JOIN: progress → users, exercises, metrics</h3>";
renderTable($progressModel->getAllWithDetails());

echo "<h3>getByUserId(1) — progres používateľa id = 1 (Alex)</h3>";
renderTable($progressModel->getByUserId(1));

echo "<h3>getByExerciseId(2) — záznamy pre cvik id = 2 (Plank) – leaderboard</h3>";
renderTable($progressModel->getByExerciseId(2));

echo "<h3>getById(1) — jeden záznam progresu</h3>";
$prog = $progressModel->getById(1);
renderTable($prog ? [$prog] : []);

echo "<p class='count'>getCount() = " . $progressModel->getCount() . " záznamov progresu</p>";

?>
</body>
</html>