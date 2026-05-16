<?php
require_once 'Database.php';
require_once 'interfaces/ModelInterface.php';
require_once 'BaseModel.php';
require_once 'MetricCatalogModel.php';
require_once 'ExerciseCatalogModel.php';
require_once 'WorkoutPlanModel.php';

$pdo = Database::getInstance();

// Pomocná funkcia pre spracovanie modelov
function getModelInstance(string $modelName, PDO $pdo): ModelInterface
{
    return match ($modelName) {
        'metric' => new MetricCatalogModel($pdo),
        'exercise' => new ExerciseCatalogModel($pdo),
        'workout_plan' => new WorkoutPlanModel($pdo),
        default => throw new InvalidArgumentException('Neznámy model: ' . $modelName),
    };
}

// Modely dostupné v tomto interface
$availableModels = [
    'metric' => ['label' => 'Metriky', 'model_class' => 'MetricCatalogModel'],
    'exercise' => ['label' => 'Cviky', 'model_class' => 'ExerciseCatalogModel'],
    'workout_plan' => ['label' => 'Tréningové plány', 'model_class' => 'WorkoutPlanModel'],
];

$metricModel = new MetricCatalogModel($pdo);
$exerciseModel = new ExerciseCatalogModel($pdo);
$workoutPlanModel = new WorkoutPlanModel($pdo);

$messages = [];
$errors = [];
$editMetric = null;
$editExercise = null;

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Generická funkcia na spracovanie akcií
function processAction(ModelInterface $model, string $action, array $data): string
{
    return match ($action) {
        'delete' => (function () use ($model, $data): string {
            $model->delete((int) ($data['id'] ?? 0));
            return 'Záznam bol zmazaný.';
        })(),
        default => throw new InvalidArgumentException('Neznáma akcia.'),
    };
}

function processMetricAction(MetricCatalogModel $metricModel, string $action, array $data): string
{
    return match ($action) {
        'insert' => (function () use ($metricModel, $data): string {
            $metricModel->insert(
                (string) ($data['code'] ?? ''),
                (string) ($data['unit'] ?? ''),
                (string) ($data['name'] ?? '')
            );
            return 'Metrika bola pridaná.';
        })(),
        'update' => (function () use ($metricModel, $data): string {
            $metricModel->update(
                (int) ($data['id'] ?? 0),
                (string) ($data['code'] ?? ''),
                (string) ($data['unit'] ?? ''),
                (string) ($data['name'] ?? '')
            );
            return 'Metrika bola upravená.';
        })(),
        'delete' => (function () use ($metricModel, $data): string {
            $metricModel->delete((int) ($data['id'] ?? 0));
            return 'Metrika bola zmazaná.';
        })(),
        default => throw new InvalidArgumentException('Neznáma akcia pre číselník metrics.'),
    };
}

function processExerciseAction(ExerciseCatalogModel $exerciseModel, string $action, array $data): string
{
    return match ($action) {
        'insert' => (function () use ($exerciseModel, $data): string {
            $exerciseModel->insert(
                (string) ($data['name'] ?? ''),
                (string) ($data['description'] ?? ''),
                (int) ($data['metric_id'] ?? 0)
            );
            return 'Cvik bol pridaný.';
        })(),
        'update' => (function () use ($exerciseModel, $data): string {
            $exerciseModel->update(
                (int) ($data['id'] ?? 0),
                (string) ($data['name'] ?? ''),
                (string) ($data['description'] ?? ''),
                (int) ($data['metric_id'] ?? 0)
            );
            return 'Cvik bol upravený.';
        })(),
        'delete' => (function () use ($exerciseModel, $data): string {
            $exerciseModel->delete((int) ($data['id'] ?? 0));
            return 'Cvik bol zmazaný.';
        })(),
        default => throw new InvalidArgumentException('Neznáma akcia pre číselník exercises.'),
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catalog = $_POST['catalog'] ?? '';
    $action = $_POST['action'] ?? '';

    try {
        $messages[] = match ($catalog) {
            'metrics' => processMetricAction($metricModel, $action, $_POST),
            'exercises' => processExerciseAction($exerciseModel, $action, $_POST),
            default => throw new InvalidArgumentException('Neznámy číselník.'),
        };
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

if (isset($_GET['edit_metric'])) {
    $editMetric = $metricModel->getById((int) $_GET['edit_metric']);
}

if (isset($_GET['edit_exercise'])) {
    $editExercise = $exerciseModel->getById((int) $_GET['edit_exercise']);
}

$metrics = $metricModel->getAll();
$exercises = $exerciseModel->getAll();
$workoutPlans = $workoutPlanModel->getAll();

?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Číselníky - správa</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f2f2f2; color:#222; }
        h1, h2, h3 { margin-bottom: 10px; }
        .panel { background:#fff; border:1px solid #ddd; padding:14px; margin-bottom:16px; }
        table { border-collapse: collapse; width: 100%; background:#fff; }
        th, td { border:1px solid #ddd; padding:8px; text-align:left; }
        th { background:#f6f6f6; }
        input, select { padding:6px; margin-right:6px; }
        .ok { color:#10662f; }
        .err { color:#a10000; }
        form.inline { display:inline-block; margin:0; }
    </style>
</head>
<body>
<h1>ciselniky.php — správa číselníkov</h1>

<div class="panel">
    <?php foreach ($messages as $message): ?>
        <p class="ok"><?php echo h($message); ?></p>
    <?php endforeach; ?>

    <?php foreach ($errors as $error): ?>
        <p class="err"><?php echo h($error); ?></p>
    <?php endforeach; ?>
</div>

<div class="panel">
    <h2>Číselník: Metrics</h2>

    <h3><?php echo $editMetric ? 'Upraviť metriku' : 'Pridať metriku'; ?></h3>
    <form method="post">
        <input type="hidden" name="catalog" value="metrics">
        <input type="hidden" name="action" value="<?php echo $editMetric ? 'update' : 'insert'; ?>">
        <?php if ($editMetric): ?>
            <input type="hidden" name="id" value="<?php echo (int) $editMetric['id']; ?>">
        <?php endif; ?>

        <input
            type="text"
            name="code"
            placeholder="code (napr. calories_kcal)"
            value="<?php echo h((string) ($editMetric['code'] ?? '')); ?>"
            pattern="[a-z0-9_]{2,30}"
            required
        >

        <input type="text" name="unit" placeholder="unit" value="<?php echo h((string) ($editMetric['unit'] ?? '')); ?>" required>
        <input type="text" name="name" placeholder="name" value="<?php echo h((string) ($editMetric['name'] ?? '')); ?>" required>
        <button type="submit"><?php echo $editMetric ? 'Uložiť' : 'Pridať'; ?></button>
        <?php if ($editMetric): ?>
            <a href="ciselniky.php">Zrušiť</a>
        <?php endif; ?>
    </form>

    <h3>Zoznam metrík</h3>
    <table>
        <tr>
            <th>ID</th><th>Code</th><th>Unit</th><th>Name</th><th>Použitie</th><th>Akcie</th>
        </tr>
        <?php foreach ($metrics as $metric): ?>
            <tr>
                <td><?php echo (int) $metric['id']; ?></td>
                <td><?php echo h((string) $metric['code']); ?></td>
                <td><?php echo h((string) $metric['unit']); ?></td>
                <td><?php echo h((string) $metric['name']); ?></td>
                <td><?php echo $metricModel->countRelated((int) $metric['id']); ?></td>
                <td>
                    <a href="?edit_metric=<?php echo (int) $metric['id']; ?>">Upraviť</a>
                    <form method="post" class="inline">
                        <input type="hidden" name="catalog" value="metrics">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int) $metric['id']; ?>">
                        <button type="submit">Zmazať</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="panel">
    <h2>Číselník: Exercises</h2>

    <h3><?php echo $editExercise ? 'Upraviť cvik' : 'Pridať cvik'; ?></h3>
    <form method="post">
        <input type="hidden" name="catalog" value="exercises">
        <input type="hidden" name="action" value="<?php echo $editExercise ? 'update' : 'insert'; ?>">
        <?php if ($editExercise): ?>
            <input type="hidden" name="id" value="<?php echo (int) $editExercise['id']; ?>">
        <?php endif; ?>

        <input type="text" name="name" placeholder="name" value="<?php echo h((string) ($editExercise['name'] ?? '')); ?>" required>
        <input type="text" name="description" placeholder="description" value="<?php echo h((string) ($editExercise['description'] ?? '')); ?>">

        <?php $selectedMetricId = (int) ($editExercise['metric_id'] ?? 1); ?>
        <select name="metric_id" required>
            <?php foreach ($metrics as $metric): ?>
                <option value="<?php echo (int) $metric['id']; ?>" <?php echo $selectedMetricId === (int) $metric['id'] ? 'selected' : ''; ?>>
                    <?php echo h((string) $metric['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit"><?php echo $editExercise ? 'Uložiť' : 'Pridať'; ?></button>
        <?php if ($editExercise): ?>
            <a href="ciselniky.php">Zrušiť</a>
        <?php endif; ?>
    </form>

    <h3>Zoznam cvikov</h3>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Description</th><th>Metric</th><th>Použitie</th><th>Akcie</th>
        </tr>
        <?php foreach ($exercises as $exercise): ?>
            <tr>
                <td><?php echo (int) $exercise['id']; ?></td>
                <td><?php echo h((string) $exercise['name']); ?></td>
                <td><?php echo h((string) ($exercise['description'] ?? '')); ?></td>
                <td><?php echo h((string) $exercise['metric_name']); ?></td>
                <td><?php echo $exerciseModel->countRelated((int) $exercise['id']); ?></td>
                <td>
                    <a href="?edit_exercise=<?php echo (int) $exercise['id']; ?>">Upraviť</a>
                    <form method="post" class="inline">
                        <input type="hidden" name="catalog" value="exercises">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int) $exercise['id']; ?>">
                        <button type="submit">Zmazať</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="panel">
    <h2>Hlavná entita: Workout Plans</h2>
    <p><em>Tréningové plány (aj keď sú ich "hlavnou entitou", implementujú ModelInterface rovnako ako číselníky)</em></p>

    <h3>Zoznam tréningových plánov</h3>
    <table>
        <tr>
            <th>ID</th><th>Title</th><th>User</th><th>Cvikov</th><th>Active</th><th>Akcie</th>
        </tr>
        <?php foreach ($workoutPlans as $plan): ?>
            <tr>
                <td><?php echo (int) $plan['id']; ?></td>
                <td><?php echo h((string) $plan['title']); ?></td>
                <td><?php echo h((string) ($plan['user_name'] ?? 'N/A')); ?></td>
                <td><?php echo (int) ($plan['exercise_count'] ?? 0); ?></td>
                <td><?php echo $plan['is_active'] ? '✓' : ''; ?></td>
                <td>
                    <form method="post" class="inline">
                        <input type="hidden" name="catalog" value="workout_plans">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int) $plan['id']; ?>">
                        <button type="submit">Zmazať</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="panel">
    <h2>Polymorfizmus v praxi</h2>
    <p>Všetky modely implementujú <strong>ModelInterface</strong>:</p>
    <ul>
        <li><strong>MetricCatalogModel</strong> — dedí z BaseModel, implementuje interface</li>
        <li><strong>ExerciseCatalogModel</strong> — dedí z BaseModel, implementuje interface</li>
        <li><strong>WorkoutPlanModel</strong> — samostatná trieda, implementuje interface</li>
    </ul>
    <p>Vďaka tomu majú všetky rovnaké metódy: <code>getAll()</code>, <code>getById()</code>, <code>getCount()</code>, <code>delete()</code>, <code>describe()</code>, <code>countRelated()</code></p>
</div>

</body>
</html>
