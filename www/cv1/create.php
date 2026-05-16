<?php
require_once 'Database.php';

$pdo = Database::getInstance();

// Dáta pre nový tréningový plán
$title = "Full Body Beginner";
$note  = "Základný tréning pre začiatočníkov";

// Prepared statement — bezpečné vkladanie
$sql = "INSERT INTO workout_plans (title, note, user_id)
        VALUES (:title, :note, :user_id)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':title' => $title,
    ':note' => $note,
    ':user_id' => 1,
]);

// Vráti id práve vloženého záznamu
echo "Tréningový plán bol pridaný! ID: " . $pdo->lastInsertId();