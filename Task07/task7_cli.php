<?php
define('DB_PATH', 'students.db');
define('CURRENT_YEAR', date('Y'));


function connectDB() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage() . PHP_EOL);
    }
}


function getActiveGroups(PDO $pdo): array {
    $sql = "SELECT DISTINCT group_number FROM groups 
            WHERE graduation_year <= :current_year 
            ORDER BY group_number";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['current_year' => CURRENT_YEAR]);
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}


function isValidGroup(string $group, array $activeGroups): bool {
    return in_array($group, $activeGroups) || $group === '';
}


function getStudents(PDO $pdo, string $groupFilter = ''): array {
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
    
    if ($groupFilter !== '') {
        $sql .= " AND g.group_number = :group_number";
        $params['group_number'] = $groupFilter;
    }
    
    $sql .= " ORDER BY g.group_number, s.last_name, s.first_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}


function drawTable(array $students): void {
    if (empty($students)) {
        echo "Нет данных для отображения." . PHP_EOL;
        return;
    }
    
    
    $colWidths = [
        'group' => max(12, mb_strlen('Группа')),
        'direction' => max(40, mb_strlen('Направление')),
        'name' => max(30, mb_strlen('ФИО')),
        'gender' => max(10, mb_strlen('Пол')),
        'birth' => max(12, mb_strlen('Дата рождения')),
        'card' => max(20, mb_strlen('Студ. билет'))
    ];
    
    foreach ($students as $student) {
        $colWidths['group'] = max($colWidths['group'], mb_strlen($student['group_number']));
        $colWidths['direction'] = max($colWidths['direction'], mb_strlen($student['study_direction']));
        $colWidths['name'] = max($colWidths['name'], mb_strlen($student['full_name']));
        $colWidths['gender'] = max($colWidths['gender'], mb_strlen($student['gender']));
        $colWidths['birth'] = max($colWidths['birth'], mb_strlen($student['birth_date']));
        $colWidths['card'] = max($colWidths['card'], mb_strlen($student['student_card']));
    }
    
    
    echo "┌" . str_repeat("─", $colWidths['group'] + 2) 
         . "┬" . str_repeat("─", $colWidths['direction'] + 2)
         . "┬" . str_repeat("─", $colWidths['name'] + 2)
         . "┬" . str_repeat("─", $colWidths['gender'] + 2)
         . "┬" . str_repeat("─", $colWidths['birth'] + 2)
         . "┬" . str_repeat("─", $colWidths['card'] + 2) . "┐" . PHP_EOL;
    

    printf("│ %-{$colWidths['group']}s │ %-{$colWidths['direction']}s │ %-{$colWidths['name']}s │ %-{$colWidths['gender']}s │ %-{$colWidths['birth']}s │ %-{$colWidths['card']}s │" . PHP_EOL,
           'Группа', 'Направление', 'ФИО', 'Пол', 'Дата рождения', 'Студ. билет');
    

    echo "├" . str_repeat("─", $colWidths['group'] + 2)
         . "┼" . str_repeat("─", $colWidths['direction'] + 2)
         . "┼" . str_repeat("─", $colWidths['name'] + 2)
         . "┼" . str_repeat("─", $colWidths['gender'] + 2)
         . "┼" . str_repeat("─", $colWidths['birth'] + 2)
         . "┼" . str_repeat("─", $colWidths['card'] + 2) . "┤" . PHP_EOL;
    

    foreach ($students as $student) {
        printf("│ %-{$colWidths['group']}s │ %-{$colWidths['direction']}s │ %-{$colWidths['name']}s │ %-{$colWidths['gender']}s │ %-{$colWidths['birth']}s │ %-{$colWidths['card']}s │" . PHP_EOL,
               $student['group_number'],
               $student['study_direction'],
               $student['full_name'],
               $student['gender'],
               $student['birth_date'],
               $student['student_card']);
    }
    

    echo "└" . str_repeat("─", $colWidths['group'] + 2)
         . "┴" . str_repeat("─", $colWidths['direction'] + 2)
         . "┴" . str_repeat("─", $colWidths['name'] + 2)
         . "┴" . str_repeat("─", $colWidths['gender'] + 2)
         . "┴" . str_repeat("─", $colWidths['birth'] + 2)
         . "┴" . str_repeat("─", $colWidths['card'] + 2) . "┘" . PHP_EOL;
    
    echo PHP_EOL . "Всего студентов: " . count($students) . PHP_EOL;
}


function main(): void {
    echo "=========================================" . PHP_EOL;
    echo "   СИСТЕМА УЧЕТА СТУДЕНТОВ (CLI ВЕРСИЯ)   " . PHP_EOL;
    echo "=========================================" . PHP_EOL . PHP_EOL;
    

    $pdo = connectDB();
    

    $activeGroups = getActiveGroups($pdo);
    
    if (empty($activeGroups)) {
        echo "Нет активных групп." . PHP_EOL;
        return;
    }
    

    echo "Доступные группы:" . PHP_EOL;
    foreach ($activeGroups as $group) {
        echo "  • {$group}" . PHP_EOL;
    }
    
    echo PHP_EOL . "Введите номер группы для фильтрации (или нажмите Enter для всех групп): ";
    $input = trim(fgets(STDIN));
    
 
    if ($input !== '' && !isValidGroup($input, $activeGroups)) {
        echo "Ошибка: Группа '{$input}' не найдена или не является активной." . PHP_EOL;
        return;
    }

    $students = getStudents($pdo, $input);
    
    echo PHP_EOL;
    

    if ($input !== '') {
        echo "Студенты группы {$input}:" . PHP_EOL;
    } else {
        echo "Все студенты (все активные группы):" . PHP_EOL;
    }
    
    echo str_repeat("=", 80) . PHP_EOL . PHP_EOL;
    
    drawTable($students);
}


if (PHP_SAPI === 'cli') {
    main();
} else {
    die("Этот скрипт предназначен для запуска из командной строки." . PHP_EOL);
}