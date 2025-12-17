<?php
// exams.php - Страница управления экзаменами студента

// Подключение к базе данных
$db_file = 'university.db';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получение ID студента
$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    header('Location: index.php?error=Не указан студент');
    exit;
}

// Получение информации о студенте
$stmt = $pdo->prepare("
    SELECT s.*, g.group_number, g.direction 
    FROM students s 
    JOIN groups g ON s.group_id = g.id 
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: index.php?error=Студент не найден');
    exit;
}

// Получение экзаменов студента
$stmt = $pdo->prepare("
    SELECT e.*, d.name as discipline_name, d.course, d.semester
    FROM exams e 
    JOIN disciplines d ON e.discipline_id = d.id 
    WHERE e.student_id = ? 
    ORDER BY e.exam_date DESC, d.course, d.semester
");
$stmt->execute([$student_id]);
$exams = $stmt->fetchAll();

// Подсчет статистики
$total_exams = count($exams);
$avg_grade = 0;
if ($total_exams > 0) {
    $sum_grades = 0;
    foreach ($exams as $exam) {
        $sum_grades += $exam['grade'];
    }
    $avg_grade = round($sum_grades / $total_exams, 2);
}

// Получение сообщений
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты экзаменов</title>
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
            <h1><i class="fas fa-graduation-cap"></i> Результаты экзаменов</h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> К списку студентов
                </a>
                <a href="exam_form.php?student_id=<?= $student_id ?>" class="btn btn-success">
                    <i class="fas fa-plus"></i> Добавить экзамен
                </a>
            </div>
        </div>
        
        <!-- Информация о студенте -->
        <div class="student-card">
            <div class="student-avatar">
                <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
            </div>
            <div class="student-info">
                <h2><?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name']) ?></h2>
                <div class="student-details">
                    <span class="badge"><?= htmlspecialchars($student['group_number']) ?></span>
                    <span><?= htmlspecialchars($student['direction']) ?></span>
                    <span>
                        <?php if ($student['gender'] == 'М'): ?>
                            <i class="fas fa-male"></i> Мужской
                        <?php else: ?>
                            <i class="fas fa-female"></i> Женский
                        <?php endif; ?>
                    </span>
                    <span><i class="fas fa-birthday-cake"></i> <?= date('d.m.Y', strtotime($student['birth_date'])) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Статистика экзаменов -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-book"></i></div>
                <div class="stat-value"><?= $total_exams ?></div>
                <div class="stat-label">Всего экзаменов</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value"><?= $avg_grade ?></div>
                <div class="stat-label">Средний балл</div>
            </div>
            <div class="stat-card">
                <?php 
                    $excellent = array_filter($exams, function($exam) { return $exam['grade'] == 5; });
                    $good = array_filter($exams, function($exam) { return $exam['grade'] == 4; });
                    $satisfactory = array_filter($exams, function($exam) { return $exam['grade'] == 3; });
                ?>
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-value"><?= count($excellent) ?></div>
                <div class="stat-label">Отличных оценок</div>
            </div>
        </div>
        
        <!-- Таблица экзаменов -->
        <div class="table-header">
            <h2><i class="fas fa-list"></i> Список экзаменов</h2>
            <span class="total">Всего: <?= $total_exams ?></span>
        </div>
        
        <?php if (empty($exams)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list fa-3x"></i>
                <h3>Нет данных об экзаменах</h3>
                <p>У этого студента пока нет записей об экзаменах</p>
                <a href="exam_form.php?student_id=<?= $student_id ?>" class="btn btn-success">
                    <i class="fas fa-plus"></i> Добавить первый экзамен
                </a>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Дисциплина</th>
                        <th>Курс</th>
                        <th>Семестр</th>
                        <th>Дата экзамена</th>
                        <th>Оценка</th>
                        <th>Преподаватель</th>
                        <th>Аудитория</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $index => $exam): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= htmlspecialchars($exam['discipline_name']) ?></strong></td>
                            <td><?= $exam['course'] ?></td>
                            <td><?= $exam['semester'] ?></td>
                            <td><?= date('d.m.Y', strtotime($exam['exam_date'])) ?></td>
                            <td>
                                <?php 
                                    $grade_class = '';
                                    if ($exam['grade'] == 5) $grade_class = 'grade-excellent';
                                    elseif ($exam['grade'] == 4) $grade_class = 'grade-good';
                                    elseif ($exam['grade'] == 3) $grade_class = 'grade-satisfactory';
                                    else $grade_class = 'grade-bad';
                                ?>
                                <span class="grade <?= $grade_class ?>">
                                    <?= $exam['grade'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($exam['teacher']) ?></td>
                            <td><?= htmlspecialchars($exam['room'] ?? '-') ?></td>
                            <td class="actions">
                                <div class="action-buttons">
                                    <a href="exam_form.php?id=<?= $exam['id'] ?>" 
                                       class="btn btn-sm btn-primary" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" 
                                       onclick="deleteExam(<?= $exam['id'] ?>, '<?= htmlspecialchars($exam['discipline_name']) ?>')"
                                       class="btn btn-sm btn-danger" title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <script>
        function deleteExam(examId, disciplineName) {
            if (confirm('Удалить экзамен по дисциплине "' + disciplineName + '"?')) {
                fetch('delete_exam.php?id=' + examId, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Ошибка при удалении: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Ошибка сети: ' + error);
                });
            }
        }
        
        // Сортировка таблицы
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.querySelector('.data-table');
            if (table) {
                const headers = table.querySelectorAll('th');
                headers.forEach((header, index) => {
                    if (index !== headers.length - 1) { // Исключаем последний столбец с действиями
                        header.style.cursor = 'pointer';
                        header.addEventListener('click', function() {
                            sortTable(index);
                        });
                    }
                });
            }
        });
        
        let currentSortColumn = -1;
        let sortAscending = true;
        
        function sortTable(columnIndex) {
            const table = document.querySelector('.data-table tbody');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            if (currentSortColumn === columnIndex) {
                sortAscending = !sortAscending;
            } else {
                currentSortColumn = columnIndex;
                sortAscending = true;
            }
            
            rows.sort((a, b) => {
                let aText = a.cells[columnIndex].textContent.trim();
                let bText = b.cells[columnIndex].textContent.trim();
                
                // Пытаемся преобразовать в числа для сортировки
                let aNum = parseFloat(aText.replace(',', '.'));
                let bNum = parseFloat(bText.replace(',', '.'));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return sortAscending ? aNum - bNum : bNum - aNum;
                }
                
                // Сортировка по датам
                if (columnIndex === 4) { // Столбец с датой
                    let aDate = new Date(aText.split('.').reverse().join('-'));
                    let bDate = new Date(bText.split('.').reverse().join('-'));
                    return sortAscending ? aDate - bDate : bDate - aDate;
                }
                
                // Сортировка по строкам
                return sortAscending 
                    ? aText.localeCompare(bText, 'ru')
                    : bText.localeCompare(aText, 'ru');
            });
            
            // Очищаем таблицу и добавляем отсортированные строки
            rows.forEach(row => table.appendChild(row));
            
            // Обновляем индикаторы сортировки
            updateSortIndicators(columnIndex);
        }
        
        function updateSortIndicators(columnIndex) {
            const headers = document.querySelectorAll('.data-table th');
            headers.forEach((header, index) => {
                header.classList.remove('sort-asc', 'sort-desc');
                if (index === columnIndex) {
                    header.classList.add(sortAscending ? 'sort-asc' : 'sort-desc');
                }
            });
        }
    </script>
</body>
</html>