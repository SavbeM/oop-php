<?php
require_once __DIR__ . '/../models/ModelFactory.php'; 
require_once __DIR__ . '/../models/MetricCatalogModel.php';
$type = $_GET['type'] ?? 'metric'; 
$model = ModelFactory::create($type); 
if(!$model){ die('Invalid catalogue type'); }
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        if(isset($_POST['add'])){ $data=['name'=>trim($_POST['name'])]; if($type==='exercise'){$data['metric_id']=(int)$_POST['metric_id'];} $model->insert($data); }
        if(isset($_POST['edit'])){ $data=['name'=>trim($_POST['name'])]; if($type==='exercise'){$data['metric_id']=(int)$_POST['metric_id'];} $model->update((int)$_POST['id'],$data); }
        if(isset($_POST['delete'])){ $model->delete((int)$_POST['id']); }
        header('Location: /admin/ciselniky.php?type='.$type); exit;
    } catch (Throwable $e) { $message='Cannot delete/update due to related records (RESTRICT).'; }
}
$items=$model->getAll(); 
$metrics=(new MetricCatalogModel())->getAll();
?><!doctype html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue: <?= htmlspecialchars($type); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-nav.php'; ?>
    <h1>Catalogue: <?= htmlspecialchars($type); ?></h1>
    <?php if($message):?>
        <p style="padding: 12px; background: #fecaca; color: #991b1b; border-radius: 8px;">
            <?= htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Related count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr>
                    <td><?= $item['id']; ?></td>
                    <td>
                        <?= htmlspecialchars($item['name']); ?>
                        <?php if($type==='exercise'): ?>
                            <br><small style="color: var(--muted);"><?= htmlspecialchars($item['metric_name'] ?? ''); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= $model->countRelated((int)$item['id']); ?></td>
                    <td>
                        <form method="post" style="display:flex; gap: 8px; flex-wrap: wrap;">
                            <input type="hidden" name="id" value="<?= $item['id']; ?>">
                            <input type="text" name="name" value="<?= htmlspecialchars($item['name']); ?>" placeholder="Edit name" required>
                            <?php if($type==='exercise'):?>
                                <select name="metric_id" style="flex: 1; min-width: 120px;">
                                    <?php foreach($metrics as $m):?>
                                        <option value="<?= $m['id']; ?>" <?= (isset($item['metric_id']) && (int)$item['metric_id']===(int)$m['id'])?'selected':''; ?>>
                                            <?= htmlspecialchars($m['name']); ?>
                                        </option>
                                    <?php endforeach;?>
                                </select>
                            <?php endif; ?>
                            <button name="edit" value="1" type="submit">Edit</button>
                            <button name="delete" value="1" type="submit" class="btn-delete" onclick="return confirm('Delete item?');">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <h2>Add new</h2>
    <form method="post">
        <input type="text" name="name" placeholder="Item name" required>
        <?php if($type==='exercise'):?>
            <select name="metric_id" required>
                <option value="">Select metric</option>
                <?php foreach($metrics as $m):?>
                    <option value="<?= $m['id']; ?>">
                        <?= htmlspecialchars($m['name']); ?>
                    </option>
                <?php endforeach;?>
            </select>
        <?php endif; ?>
        <button name="add" value="1" type="submit">Add</button>
    </form>
</body>
</html>
