<?php
// student_form.php - Форма добавления/редактирования/удаления студентов

// Подключение к базе данных
$db_file = 'university.db';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получение действия и ID
$action = $_GET['action'] ?? 'create';
$id = $_GET['id'] ?? null;

// Получение списка групп
$groups = $pdo->query("SELECT * FROM groups ORDER BY group_number")->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'delete' && isset($_POST['confirm'])) {
        // Удаление студента
        if ($_POST['confirm'] === 'yes' && $id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                $stmt->execute([$id]);
                header('Location: index.php?message=Студент успешно удален');
                exit;
            } catch (PDOException $e) {
                $error = "Ошибка при удалении: " . $e->getMessage();
            }
        } else {
            header('Location: index.php');
            exit;
        }
    } elseif ($action === 'create' || $action === 'edit') {
        // Создание или редактирование студента
        $last_name = trim($_POST['last_name'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $group_id = $_POST['group_id'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $birth_date = $_POST['birth_date'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Валидация
        $errors = [];
        if (empty($last_name)) $errors[] = "Фамилия обязательна";
        if (empty($first_name)) $errors[] = "Имя обязательно";
        if (empty($group_id)) $errors[] = "Выберите группу";
        if (!in_array($gender, ['М', 'Ж'])) $errors[] = "Выберите пол";
        if (empty($birth_date)) $errors[] = "Укажите дату рождения";
        
        if (empty($errors)) {
            try {
                if ($action === 'create') {
                    // Создание нового студента
                    $stmt = $pdo->prepare("
                        INSERT INTO students 
                        (last_name, first_name, middle_name, group_id, gender, birth_date, email, phone)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$last_name, $first_name, $middle_name, $group_id, $gender, $birth_date, $email, $phone]);
                    $message = "Студент успешно добавлен";
                } else {
                    // Обновление студента
                    $stmt = $pdo->prepare("
                        UPDATE students SET 
                        last_name = ?, first_name = ?, middle_name = ?, group_id = ?, 
                        gender = ?, birth_date = ?, email = ?, phone = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$last_name, $first_name, $middle_name, $group_id, $gender, $birth_date, $email, $phone, $id]);
                    $message = "Данные студента обновлены";
                }
                
                header('Location: index.php?message=' . urlencode($message));
                exit;
                
            } catch (PDOException $e) {
                $error = "Ошибка при сохранении: " . $e->getMessage();
            }
        }
    }
}

// Получение данных студента для редактирования
$student = null;
if (($action === 'edit' || $action === 'delete') && $id) {
    $stmt = $pdo->prepare("
        SELECT s.*, g.group_number 
        FROM students s 
        JOIN groups g ON s.group_id = g.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header('Location: index.php?error=Студент не найден');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $action === 'create' ? 'Добавить студента' : 
           ($action === 'delete' ? 'Удалить студента' : 'Редактировать студента') ?>
    </title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <?php if ($action === 'create'): ?>
                    <i class="fas fa-user-plus"></i> Добавление нового студента
                <?php elseif ($action === 'edit'): ?>
                    <i class="fas fa-user-edit"></i> Редактирование студента
                <?php else: ?>
                    <i class="fas fa-user-times"></i> Удаление студента
                <?php endif; ?>
            </h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
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
        
        <?php if ($action === 'delete' && $student): ?>
            <!-- Форма подтверждения удаления -->
            <div class="delete-confirm">
                <div class="warning">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                    <h2>Подтверждение удаления</h2>
                    <p>Вы действительно хотите удалить студента:</p>
                    
                    <div class="student-info">
                        <p><strong>ФИО:</strong> 
                            <?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name'] . ' ' . $student['middle_name']) ?>
                        </p>
                        <p><strong>Группа:</strong> <?= htmlspecialchars($student['group_number']) ?></p>
                        <p><strong>Пол:</strong> <?= $student['gender'] == 'М' ? 'Мужской' : 'Женский' ?></p>
                        <p><strong>Дата рождения:</strong> <?= date('d.m.Y', strtotime($student['birth_date'])) ?></p>
                        <?php if ($student['email']): ?>
                            <p><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <p class="warning-text">
                        <i class="fas fa-warning"></i> Внимание! Все экзамены этого студента также будут удалены!
                    </p>
                </div>
                
                <form method="POST" class="form">
                    <div class="form-actions">
                        <button type="submit" name="confirm" value="yes" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Да, удалить
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Отмена
                        </a>
                    </div>
                </form>
            </div>
            
        <?php else: ?>
            <!-- Форма добавления/редактирования -->
            <form method="POST" class="form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="last_name" class="required">Фамилия</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" 
                               required placeholder="Введите фамилию">
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name" class="required">Имя</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" 
                               required placeholder="Введите имя">
                    </div>
                    
                    <div class="form-group">
                        <label for="middle_name">Отчество</label>
                        <input type="text" id="middle_name" name="middle_name" 
                               value="<?= htmlspecialchars($student['middle_name'] ?? '') ?>" 
                               placeholder="Введите отчество">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="group_id" class="required">Группа</label>
                        <select id="group_id" name="group_id" required>
                            <option value="">Выберите группу</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= $group['id'] ?>" 
                                    <?= (($student['group_id'] ?? '') == $group['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group['group_number']) ?> 
                                    (<?= htmlspecialchars($group['direction']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Пол</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="gender" value="М" 
                                    <?= (($student['gender'] ?? '') == 'М') ? 'checked' : '' ?> required>
                                <span class="radio-label">
                                    <i class="fas fa-male"></i> Мужской
                                </span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="gender" value="Ж" 
                                    <?= (($student['gender'] ?? '') == 'Ж') ? 'checked' : '' ?>>
                                <span class="radio-label">
                                    <i class="fas fa-female"></i> Женский
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="birth_date" class="required">Дата рождения</label>
                        <input type="date" id="birth_date" name="birth_date" 
                               value="<?= htmlspecialchars($student['birth_date'] ?? '2000-01-01') ?>" 
                               required max="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($student['email'] ?? '') ?>" 
                               placeholder="student@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?= htmlspecialchars($student['phone'] ?? '') ?>" 
                               placeholder="+7 (900) 123-45-67">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php if ($action === 'create'): ?>
                            <i class="fas fa-save"></i> Добавить студента
                        <?php else: ?>
                            <i class="fas fa-save"></i> Сохранить изменения
                        <?php endif; ?>
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        // Автоматическое форматирование телефона
        document.getElementById('phone')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value[0] !== '7' && value[0] !== '8') {
                    value = '7' + value;
                }
                let formatted = '+7 (' + value.substring(1, 4) + ') ' + 
                               value.substring(4, 7) + '-' + 
                               value.substring(7, 9) + '-' + 
                               value.substring(9, 11);
                e.target.value = formatted.substring(0, 18);
            }
        });
        
        // Валидация даты рождения
        document.getElementById('birth_date')?.addEventListener('change', function(e) {
            const selectedDate = new Date(e.target.value);
            const today = new Date();
            if (selectedDate > today) {
                alert('Дата рождения не может быть в будущем!');
                e.target.value = '2000-01-01';
            }
        });
    </script>
</body>
</html>