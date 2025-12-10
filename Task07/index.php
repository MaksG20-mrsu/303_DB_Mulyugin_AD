<?php

define('DB_PATH', 'students.db');
define('CURRENT_YEAR', date('Y'));

function connectDB(): PDO {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}


function getActiveGroups(PDO $pdo): array {
    $sql = "SELECT id, group_number FROM groups 
            WHERE graduation_year <= :current_year 
            ORDER BY group_number";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['current_year' => CURRENT_YEAR]);
    
    return $stmt->fetchAll();
}


function getStudents(PDO $pdo, ?int $groupId = null): array {
    $sql = "SELECT 
                g.group_number,
                g.study_direction,
                s.last_name || ' ' || s.first_name || ' ' || COALESCE(s.middle_name, '') as full_name,
                CASE s.gender 
                    WHEN 'M' THEN 'Мужской'
                    WHEN 'F' THEN 'Женский'
                    ELSE 'Не указан'
                END as gender,
                s.birth_date,
                s.student_card
            FROM students s
            JOIN groups g ON s.group_id = g.id
            WHERE g.graduation_year <= :current_year";
    
    $params = ['current_year' => CURRENT_YEAR];
    
    if ($groupId !== null) {
        $sql .= " AND g.id = :group_id";
        $params['group_id'] = $groupId;
    }
    
    $sql .= " ORDER BY g.group_number, s.last_name, s.first_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}


$pdo = connectDB();


$activeGroups = getActiveGroups($pdo);


$selectedGroupId = $_GET['group_id'] ?? null;
if ($selectedGroupId !== null) {
    $selectedGroupId = (int)$selectedGroupId;
}


$students = getStudents($pdo, $selectedGroupId);


$selectedGroupName = 'Все группы';
if ($selectedGroupId !== null) {
    foreach ($activeGroups as $group) {
        if ($group['id'] == $selectedGroupId) {
            $selectedGroupName = $group['group_number'];
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система учета студентов</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #2c3e50, #4a6491);
            color: white;
            padding: 30px 0;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .controls {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            padding: 12px 30px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-reset {
            background: #95a5a6;
        }
        
        .btn-reset:hover {
            background: #7f8c8d;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .student-count {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .students-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
        }
        
        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 16px;
        }
        
        tbody tr {
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        tbody tr:nth-child(even):hover {
            background: #f0f0f0;
        }
        
        td {
            padding: 16px 15px;
            color: #333;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        footer {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-size: 0.9rem;
            border-top: 1px solid #eee;
            margin-top: 40px;
        }
        
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            th, td {
                padding: 12px 10px;
                font-size: 14px;
            }
            
            .students-table {
                overflow-x: auto;
                display: block;
            }
            
            table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🎓 Система учета студентов</h1>
            <p class="subtitle">Веб-приложение для управления информацией о студентах</p>
        </header>
        
        <main>
            <div class="controls">
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <label for="group-select">Выберите группу для фильтрации:</label>
                        <select id="group-select" name="group_id">
                            <option value="">Все группы</option>
                            <?php foreach ($activeGroups as $group): ?>
                                <option value="<?= htmlspecialchars($group['id']) ?>" 
                                    <?= $selectedGroupId == $group['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group['group_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Применить фильтр</button>
                    <a href="?" class="btn btn-reset">Сбросить</a>
                </form>
            </div>
            
            <div class="results-header">
                <h2>📊 Список студентов: <?= htmlspecialchars($selectedGroupName) ?></h2>
                <div class="student-count">Всего студентов: <?= count($students) ?></div>
            </div>
            
            <div class="students-table">
                <?php if (!empty($students)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Группа</th>
                                <th>Направление подготовки</th>
                                <th>ФИО</th>
                                <th>Пол</th>
                                <th>Дата рождения</th>
                                <th>Студенческий билет</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['group_number']) ?></td>
                                    <td><?= htmlspecialchars($student['study_direction']) ?></td>
                                    <td><?= htmlspecialchars($student['full_name']) ?></td>
                                    <td><?= htmlspecialchars($student['gender']) ?></td>
                                    <td><?= htmlspecialchars($student['birth_date']) ?></td>
                                    <td><?= htmlspecialchars($student['student_card']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>😕 Нет данных для отображения.</p>
                        <p>Выберите другую группу или проверьте наличие студентов в базе данных.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
        <footer>
            <p>Система учета студентов &copy; <?= date('Y') ?></p>
            <p>Используется база данных SQLite</p>
        </footer>
    </div>
</body>
</html>