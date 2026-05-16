<?php
require_once __DIR__ . '/../models/ModelFactory.php'; require_once __DIR__ . '/../models/MetricCatalogModel.php';
$type = $_GET['type'] ?? 'metric'; $model = ModelFactory::create($type); if(!$model){ die('Invalid catalogue type'); }
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        if(isset($_POST['add'])){ $data=['name'=>trim($_POST['name'])]; if($type==='exercise'){$data['metric_id']=(int)$_POST['metric_id'];} $model->insert($data); }
        if(isset($_POST['edit'])){ $data=['name'=>trim($_POST['name'])]; if($type==='exercise'){$data['metric_id']=(int)$_POST['metric_id'];} $model->update((int)$_POST['id'],$data); }
        if(isset($_POST['delete'])){ $model->delete((int)$_POST['id']); }
        header('Location: /admin/ciselniky.php?type='.$type); exit;
    } catch (Throwable $e) { $message='Cannot delete/update due to related records (RESTRICT).'; }
}
$items=$model->getAll(); $metrics=(new MetricCatalogModel())->getAll();
?><!doctype html><html><body><?php include __DIR__ . '/../includes/admin-nav.php'; ?><h1>Catalogue: <?= htmlspecialchars($type); ?></h1><?php if($message):?><p><?= htmlspecialchars($message); ?></p><?php endif; ?>
<table border="1"><tr><th>ID</th><th>Name</th><th>Related count</th><th>Actions</th></tr><?php foreach($items as $item): ?><tr><td><?= $item['id']; ?></td><td><?= htmlspecialchars($item['name']); ?><?php if($type==='exercise'): ?> (<?= htmlspecialchars($item['metric_name'] ?? ''); ?>)<?php endif; ?></td><td><?= $model->countRelated((int)$item['id']); ?></td><td><form method="post" style="display:inline"><input type="hidden" name="id" value="<?= $item['id']; ?>"><input name="name" value="<?= htmlspecialchars($item['name']); ?>"><?php if($type==='exercise'):?><select name="metric_id"><?php foreach($metrics as $m):?><option value="<?= $m['id']; ?>" <?= (isset($item['metric_id']) && (int)$item['metric_id']===(int)$m['id'])?'selected':''; ?>><?= htmlspecialchars($m['name']); ?></option><?php endforeach;?></select><?php endif; ?><button name="edit" value="1">Edit</button><button name="delete" value="1" onclick="return confirm('Delete item?');">Delete</button></form></td></tr><?php endforeach; ?></table>
<h2>Add new</h2><form method="post"><input name="name" required><?php if($type==='exercise'):?><select name="metric_id"><?php foreach($metrics as $m):?><option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['name']); ?></option><?php endforeach;?></select><?php endif; ?><button name="add" value="1">Add</button></form>
</body></html>
