<?php
require_once 'Database.php';
require_once 'MetricCatalogModel.php';
require_once 'ExerciseCatalogModel.php';

$pdo = Database::getInstance();
$metricModel = new MetricCatalogModel($pdo);
$exerciseModel = new ExerciseCatalogModel($pdo);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function renderTable(array $rows): void
{
    if (empty($rows)) {
        echo '<p><em>Žiadne záznamy.</em></p>';
        return;
    }

    echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;margin-bottom:10px;background:#fff;'>";
    echo '<tr style="background:#ececec;">';
    foreach (array_keys($rows[0]) as $column) {
        echo '<th>' . h((string) $column) . '</th>';
    }
    echo '</tr>';

    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . h((string) ($value ?? '')) . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>';
}

$messages = [];
$errors = [];

$exerciseInsertedId = null;

try {
    $exerciseInsertedId = $exerciseModel->insert(
        'Demo Exercise ' . date('His'),
        'Dočasný záznam pre test insert()',
        1
    );
    $messages[] = 'ExerciseModel insert(): vložené id = ' . $exerciseInsertedId;

    $exerciseModel->update(
        $exerciseInsertedId,
        'Demo Exercise Updated ' . date('His'),
        'Upravený testovací záznam',
        1
    );
    $messages[] = 'ExerciseModel update(): úspech pre id = ' . $exerciseInsertedId;

    $usedCount = $exerciseModel->countItems($exerciseInsertedId);
    $messages[] = 'ExerciseModel countItems(' . $exerciseInsertedId . ') = ' . $usedCount;

    $exerciseModel->delete($exerciseInsertedId);
    $messages[] = 'ExerciseModel delete(): záznam bol zmazaný.';
} catch (Throwable $e) {
    $errors[] = 'ExerciseModel chyba: ' . $e->getMessage();
}

try {
    $exerciseModel->insert('   ', 'Neplatný názov', 1);
} catch (Throwable $e) {
    $messages[] = 'Validácia prázdneho názvu (ExerciseModel): ' . $e->getMessage();
}

$metricInsertDemo = 'MetricModel insert/update/delete demo sa preskočilo: všetky enum code hodnoty sú už obsadené alebo používané.';
try {
    $allMetrics = $metricModel->getAll();
    $usedCodes = array_column($allMetrics, 'code');
    $allowedCodes = ['reps', 'weight_kg', 'time_sec', 'distance_km'];
    $freeCode = null;

    foreach ($allowedCodes as $code) {
        if (!in_array($code, $usedCodes, true)) {
            $freeCode = $code;
            break;
        }
    }

    if ($freeCode !== null) {
        $newMetricId = $metricModel->insert($freeCode, 'demo', 'Demo Metric');
        $metricModel->update($newMetricId, $freeCode, 'demo_u', 'Demo Metric Updated');
        $metricModel->delete($newMetricId);
        $metricInsertDemo = 'MetricModel insert/update/delete demo: úspech.';
    }
} catch (Throwable $e) {
    $errors[] = 'MetricModel CRUD demo chyba: ' . $e->getMessage();
}

try {
    $metricModel->insert('reps', 'reps', '   ');
} catch (Throwable $e) {
    $messages[] = 'Validácia prázdneho názvu (MetricModel): ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Použitie číselníkových modelov</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background:#f3f3f3; }
        h1, h2, h3 { color:#333; }
        .box { background:#fff; border:1px solid #ddd; padding:12px 14px; margin-bottom:12px; }
        .ok { color:#0a662e; }
        .err { color:#a50000; }
    </style>
</head>
<body>
<h1>pouzi_Model.php — demo metód číselníkových modelov</h1>

<div class="box">
    <h2>Výsledky operácií</h2>
    <?php foreach ($messages as $message): ?>
        <p class="ok"><?php echo h($message); ?></p>
    <?php endforeach; ?>
    <p class="ok"><?php echo h($metricInsertDemo); ?></p>
    <?php foreach ($errors as $error): ?>
        <p class="err"><?php echo h($error); ?></p>
    <?php endforeach; ?>
</div>

<div class="box">
    <h2>MetricCatalogModel</h2>
    <h3>getAll()</h3>
    <?php renderTable($metricModel->getAll()); ?>

    <h3>getById(1)</h3>
    <?php
    $metricById = $metricModel->getById(1);
    renderTable($metricById ? [$metricById] : []);
    ?>

    <p><strong>getCount()</strong>: <?php echo $metricModel->getCount(); ?></p>

    <h3>countItems(1)</h3>
    <p><?php echo $metricModel->countItems(1); ?></p>
</div>

<div class="box">
    <h2>ExerciseCatalogModel</h2>
    <h3>getAll()</h3>
    <?php renderTable($exerciseModel->getAll()); ?>

    <h3>getById(1)</h3>
    <?php
    $exerciseById = $exerciseModel->getById(1);
    renderTable($exerciseById ? [$exerciseById] : []);
    ?>

    <p><strong>getCount()</strong>: <?php echo $exerciseModel->getCount(); ?></p>

    <h3>countItems(1)</h3>
    <p><?php echo $exerciseModel->countItems(1); ?></p>
</div>

</body>
</html>
