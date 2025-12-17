<?php
// delete_exam.php - API для удаления экзамена

// Подключение к базе данных
$db_file = 'university.db';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Ошибка подключения к БД']);
    exit;
}

// Получение ID экзамена
$exam_id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$exam_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Не указан ID экзамена']);
    exit;
}

// Проверка существования экзамена
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Экзамен не найден']);
    exit;
}

// Удаление экзамена
try {
    $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}