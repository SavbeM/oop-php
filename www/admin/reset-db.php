<?php
require_once __DIR__ . '/../models/Database.php';
$message='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['confirm_reset'])){
    try { $sql=file_get_contents(__DIR__ . '/../sql/databaza.sql'); Database::getInstance()->getConnection()->exec($sql); $message='Database reset completed successfully.'; }
    catch(Throwable $e){ $message='Reset failed: '.$e->getMessage(); }
}
?><!doctype html><html><body><?php include __DIR__ . '/../includes/admin-nav.php'; ?><h1>Reset database</h1><p><b>Warning:</b> this will drop and recreate all assignment tables.</p><?php if($message):?><p><?= htmlspecialchars($message); ?></p><?php endif; ?><form method="post" onsubmit="return confirm('Do you really want to reset the database?');"><button name="confirm_reset" value="1">Reset DB</button></form></body></html>
