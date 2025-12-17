<?php
// index.php - Главная страница со списком студентов

// Подключение к базе данных
$db_file = 'university.db';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // Автоматическое создание таблиц если их нет
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
    if (empty($tables)) {
        header('Location: init_database.php');
        exit;
    }
    
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получение параметра фильтрации
$groupFilter = $_GET['group'] ?? '';
$search = $_GET['search'] ?? '';

// Базовый запрос для получения студентов
$sql = "SELECT s.*, g.group_number, g.direction 
        FROM students s 
        JOIN groups g ON s.group_id = g.id";

$params = [];
$where = [];

// Фильтрация по группе
if (!empty($groupFilter)) {
    $where[] = "g.group_number = :group_number";
    $params[':group_number'] = $groupFilter;
}

// Поиск по имени
if (!empty($search)) {
    $where[] = "(s.last_name LIKE :search OR s.first_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

// Сортировка
$sql .= " ORDER BY g.group_number, s.last_name, s.first_name";

// Выполнение запроса
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$students = $stmt->fetchAll();

// Получение списка групп для фильтра
$groups = $pdo->query("SELECT group_number FROM groups ORDER BY group_number")->fetchAll();

// Получение сообщений
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учет студентов и экзаменов</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php if ($message): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h1><i class="fas fa-university"></i> Учет студентов и экзаменов</h1>
            <p>Лабораторная работа 8. CRUD-приложение для работы с БД</p>
        </div>

        <!-- Форма фильтрации -->
        <div class="filter-form">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="group"><i class="fas fa-filter"></i> Фильтр по группе:</label>
                    <select name="group" id="group">
                        <option value="">Все группы</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= htmlspecialchars($group['group_number']) ?>" 
                                <?= ($groupFilter == $group['group_number']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['group_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Поиск:</label>
                    <input type="text" id="search" name="search" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Фамилия или имя...">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Применить
                    </button>
                    <?php if (!empty($groupFilter) || !empty($search)): ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Сбросить
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Таблица студентов -->
        <div class="table-header">
            <h2><i class="fas fa-users"></i> Список студентов</h2>
            <a href="student_form.php?action=create" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Добавить студента
            </a>
        </div>
        
        <?php if (empty($students)): ?>
            <div class="empty-state">
                <i class="fas fa-user-slash fa-3x"></i>
                <h3>Студенты не найдены</h3>
                <p>Добавьте первого студента или измените параметры фильтрации</p>
                <a href="student_form.php?action=create" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Добавить студента
                </a>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Группа</th>
                        <th>Направление</th>
                        <th>Пол</th>
                        <th>Дата рождения</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= $student['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name']) ?></strong>
                                <?php if (!empty($student['middle_name'])): ?>
                                    <br><small><?= htmlspecialchars($student['middle_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge"><?= htmlspecialchars($student['group_number']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($student['direction']) ?></td>
                            <td>
                                <?php if ($student['gender'] == 'М'): ?>
                                    <span class="badge male"><i class="fas fa-male"></i> Мужской</span>
                                <?php else: ?>
                                    <span class="badge female"><i class="fas fa-female"></i> Женский</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d.m.Y', strtotime($student['birth_date'])) ?></td>
                            <td class="actions">
                                <div class="action-buttons">
                                    <a href="student_form.php?action=edit&id=<?= $student['id'] ?>" 
                                       class="btn btn-sm btn-primary" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="student_form.php?action=delete&id=<?= $student['id'] ?>" 
                                       class="btn btn-sm btn-danger" title="Удалить"
                                       onclick="return confirm('Удалить студента <?= htmlspecialchars($student['last_name']) ?>?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="exams.php?student_id=<?= $student['id'] ?>" 
                                       class="btn btn-sm btn-warning" title="Экзамены">
                                        <i class="fas fa-graduation-cap"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="pagination">
                <span class="total">Всего студентов: <?= count($students) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Статистика -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?= count($students) ?></div>
                <div class="stat-label">Студентов</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-value"><?= count($groups) ?></div>
                <div class="stat-label">Групп</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-book"></i></div>
                <?php 
                    $exam_count = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();
                ?>
                <div class="stat-value"><?= $exam_count ?></div>
                <div class="stat-label">Экзаменов</div>
            </div>
        </div>
    </div>
    
    <script>
        // Автоматическое обновление при выборе группы
        document.getElementById('group').addEventListener('change', function() {
            this.form.submit();
        });
        
        // Подтверждение удаления
        function confirmDelete(name) {
            return confirm('Вы действительно хотите удалить студента ' + name + '?');
        }
    </script>
</body>
</html>