<?php
// exam_form.php - Форма добавления/редактирования экзаменов

// Подключение к базе данных
$db_file = 'university.db';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получение параметров
$exam_id = $_GET['id'] ?? null;
$student_id = $_GET['student_id'] ?? null;
$action = $exam_id ? 'edit' : 'create';

// Получение данных экзамена для редактирования
$exam = null;
if ($action === 'edit' && $exam_id) {
    $stmt = $pdo->prepare("
        SELECT e.*, s.last_name, s.first_name, g.group_number, d.name as discipline_name
        FROM exams e
        JOIN students s ON e.student_id = s.id
        JOIN groups g ON s.group_id = g.id
        JOIN disciplines d ON e.discipline_id = d.id
        WHERE e.id = ?
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();
    
    if ($exam) {
        $student_id = $exam['student_id'];
    }
}

if (!$student_id) {
    header('Location: index.php?error=Не указан студент');
    exit;
}

// Получение информации о студенте
$stmt = $pdo->prepare("
    SELECT s.*, g.direction 
    FROM students s 
    JOIN groups g ON s.group_id = g.id 
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Получение дисциплин для направления студента
$stmt = $pdo->prepare("
    SELECT * FROM disciplines 
    WHERE direction = ? 
    ORDER BY course, semester, name
");
$stmt->execute([$student['direction']]);
$disciplines = $stmt->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discipline_id = $_POST['discipline_id'] ?? '';
    $exam_date = $_POST['exam_date'] ?? '';
    $grade = $_POST['grade'] ?? '';
    $teacher = trim($_POST['teacher'] ?? '');
    $room = trim($_POST['room'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Валидация
    $errors = [];
    if (empty($discipline_id)) $errors[] = "Выберите дисциплину";
    if (empty($exam_date)) $errors[] = "Укажите дату экзамена";
    if (!in_array($grade, ['2', '3', '4', '5'])) $errors[] = "Выберите оценку";
    if (empty($teacher)) $errors[] = "Укажите преподавателя";
    
    if (empty($errors)) {
        try {
            if ($action === 'create') {
                // Создание нового экзамена
                $stmt = $pdo->prepare("
                    INSERT INTO exams 
                    (student_id, discipline_id, exam_date, grade, teacher, room, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$student_id, $discipline_id, $exam_date, $grade, $teacher, $room, $notes]);
                $message = "Экзамен успешно добавлен";
            } else {
                // Обновление экзамена
                $stmt = $pdo->prepare("
                    UPDATE exams SET 
                    discipline_id = ?, exam_date = ?, grade = ?, teacher = ?, room = ?, notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([$discipline_id, $exam_date, $grade, $teacher, $room, $notes, $exam_id]);
                $message = "Экзамен успешно обновлен";
            }
            
            header('Location: exams.php?student_id=' . $student_id . '&message=' . urlencode($message));
            exit;
            
        } catch (PDOException $e) {
            $error = "Ошибка при сохранении: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $action === 'create' ? 'Добавить экзамен' : 'Редактировать экзамен' ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <?php if ($action === 'create'): ?>
                    <i class="fas fa-plus-circle"></i> Добавление экзамена
                <?php else: ?>
                    <i class="fas fa-edit"></i> Редактирование экзамена
                <?php endif; ?>
            </h1>
            <a href="exams.php?student_id=<?= $student_id ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад к экзаменам
            </a>
        </div>
        
        <!-- Информация о студенте -->
        <div class="student-card">
            <div class="student-avatar">
                <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
            </div>
            <div class="student-info">
                <h3><?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name']) ?></h3>
                <div class="student-details">
                    <span class="badge"><?= htmlspecialchars($student['direction']) ?></span>
                </div>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> Обнаружены ошибки:
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label for="discipline_id" class="required">Дисциплина</label>
                    <select id="discipline_id" name="discipline_id" required>
                        <option value="">Выберите дисциплину</option>
                        <?php foreach ($disciplines as $discipline): ?>
                            <option value="<?= $discipline['id'] ?>" 
                                <?= (($exam['discipline_id'] ?? '') == $discipline['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($discipline['name']) ?> 
                                (<?= $discipline['course'] ?> курс, <?= $discipline['semester'] ?> семестр)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="exam_date" class="required">Дата экзамена</label>
                    <input type="date" id="exam_date" name="exam_date" 
                           value="<?= htmlspecialchars($exam['exam_date'] ?? date('Y-m-d')) ?>" 
                           required max="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label for="grade" class="required">Оценка</label>
                    <select id="grade" name="grade" required>
                        <option value="">Выберите оценку</option>
                        <option value="5" <?= (($exam['grade'] ?? '') == 5) ? 'selected' : '' ?>>5 - Отлично</option>
                        <option value="4" <?= (($exam['grade'] ?? '') == 4) ? 'selected' : '' ?>>4 - Хорошо</option>
                        <option value="3" <?= (($exam['grade'] ?? '') == 3) ? 'selected' : '' ?>>3 - Удовлетворительно</option>
                        <option value="2" <?= (($exam['grade'] ?? '') == 2) ? 'selected' : '' ?>>2 - Неудовлетворительно</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="teacher" class="required">Преподаватель</label>
                    <input type="text" id="teacher" name="teacher" 
                           value="<?= htmlspecialchars($exam['teacher'] ?? '') ?>" 
                           required placeholder="ФИО преподавателя">
                </div>
                
                <div class="form-group">
                    <label for="room">Аудитория</label>
                    <input type="text" id="room" name="room" 
                           value="<?= htmlspecialchars($exam['room'] ?? '') ?>" 
                           placeholder="Номер аудитории">
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Примечания</label>
                <textarea id="notes" name="notes" rows="3" 
                          placeholder="Дополнительные сведения об экзамене..."><?= htmlspecialchars($exam['notes'] ?? '') ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php if ($action === 'create'): ?>
                        <i class="fas fa-save"></i> Добавить экзамен
                    <?php else: ?>
                        <i class="fas fa-save"></i> Сохранить изменения
                    <?php endif; ?>
                </button>
                <a href="exams.php?student_id=<?= $student_id ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Отмена
                </a>
            </div>
        </form>
    </div>
    
    <script>
        // Валидация даты экзамена
        document.getElementById('exam_date')?.addEventListener('change', function(e) {
            const selectedDate = new Date(e.target.value);
            const today = new Date();
            if (selectedDate > today) {
                alert('Дата экзамена не может быть в будущем!');
                e.target.value = '<?= date('Y-m-d') ?>';
            }
        });
        
        // Автозаполнение преподавателя
        const teachers = {
            '1': 'Профессор Смирнов А.А.',
            '2': 'Доцент Петров И.С.',
            '3': 'Профессор Кузнецов В.В.',
            '4': 'Доцент Иванова М.П.',
            '5': 'Профессор Орлов К.Д.',
            '6': 'Доцент Белова С.М.',
            '7': 'Доцент Тихонов В.С.',
            '8': 'Профессор Харитонов А.Б.',
            '9': 'Доцент Морозова Т.К.',
            '10': 'Профессор Волков П.Р.',
            '11': 'Доцент Соколова Е.В.',
            '12': 'Профессор Лебедев М.А.',
            '13': 'Доцент Воробьева Л.С.',
            '14': 'Профессор Новиков А.С.',
            '15': 'Доцент Петухова И.М.',
            '16': 'Профессор Зайцев В.П.',
            '17': 'Доцент Сорокина Т.А.',
            '18': 'Профессор Комаров Д.В.',
            '19': 'Доцент Галкина Р.Н.',
            '20': 'Профессор Шестаков Г.М.',
            '21': 'Доцент Филиппова Е.Д.'
        };
        
        document.getElementById('discipline_id')?.addEventListener('change', function(e) {
            const teacherField = document.getElementById('teacher');
            if (!teacherField.value && teachers[e.target.value]) {
                teacherField.value = teachers[e.target.value];
            }
        });
    </script>
</body>
</html>