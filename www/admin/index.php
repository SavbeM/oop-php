<?php ?><!doctype html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/admin-nav.php'; ?>
    <h1>Admin Dashboard</h1>
    <p>Manage catalogues (metrics, exercises) and reset database.</p>
    
    <div style="display: grid; gap: 16px; margin-top: 20px;">
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow);">
            <h3 style="margin-top: 0;">📊 Catalogues</h3>
            <p>Manage system reference data:</p>
            <form style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" onclick="window.location='/admin/ciselniky.php?type=metric'" style="background: #3b82f6;">Metrics</button>
                <button type="button" onclick="window.location='/admin/ciselniky.php?type=exercise'" style="background: #10b981;">Exercises</button>
                <button type="button" onclick="window.location='/admin/ciselniky.php?type=plan_goal'" style="background: #f59e0b;">Goals</button>
                <button type="button" onclick="window.location='/admin/ciselniky.php?type=plan_level'" style="background: #8b5cf6;">Levels</button>
            </form>
        </div>
        
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow);">
            <h3 style="margin-top: 0;">⚠️ Database</h3>
            <p>Reset database to initial state with demo data.</p>
            <form method="get" action="/admin/reset-db.php">
                <button type="submit" style="background: #ef4444;">Reset Database</button>
            </form>
        </div>
    </div>
</body>
</html>
