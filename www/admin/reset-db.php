<?php
require_once __DIR__ . '/../models/Database.php';
$message='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['confirm_reset'])){
    try { 
        $sql=file_get_contents(__DIR__ . '/../sql/databaza.sql'); 
        Database::getInstance()->getConnection()->exec($sql); 
        $message='✅ Database reset completed successfully. All tables recreated with demo data.'; 
    }
    catch(Throwable $e){ 
        $message='❌ Reset failed: '.$e->getMessage(); 
    }
}
?><!doctype html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Database</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-nav.php'; ?>
    
    <div style="max-width: 600px;">
        <h1>⚠️ Reset Database</h1>
        
        <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; color: #78350f;">
            <strong>Warning:</strong> This will drop and recreate all tables, losing all current data. Demo data will be restored.
        </div>
        
        <?php if($message):?>
            <div style="background: <?= str_contains($message, '✅') ? '#dcfce7' : '#fee2e2'; ?>; border: 1px solid <?= str_contains($message, '✅') ? '#bbf7d0' : '#fecaca'; ?>; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; color: <?= str_contains($message, '✅') ? '#166534' : '#991b1b'; ?>;">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" onsubmit="return confirm('Do you really want to reset the entire database? This cannot be undone.');">
            <button name="confirm_reset" value="1" type="submit" style="background: #dc2626; padding: 12px 20px; font-size: 16px;">
                Yes, Reset Database
            </button>
            <button type="button" onclick="window.location='/admin/index.php'" style="background: #6b7280;">
                Cancel
            </button>
        </form>
    </div>
</body>
</html>
