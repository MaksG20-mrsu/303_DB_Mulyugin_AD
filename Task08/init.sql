-- Создание таблицы групп
CREATE TABLE IF NOT EXISTS groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_number VARCHAR(10) UNIQUE NOT NULL,
    direction VARCHAR(100) NOT NULL,
    year_start INTEGER NOT NULL
);

-- Создание таблицы студентов
CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    group_id INTEGER NOT NULL,
    gender VARCHAR(1) CHECK(gender IN ('М', 'Ж')),
    birth_date DATE,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
);

-- Создание таблицы дисциплин
CREATE TABLE IF NOT EXISTS disciplines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    direction VARCHAR(100) NOT NULL,
    course INTEGER NOT NULL CHECK(course BETWEEN 1 AND 6),
    semester INTEGER NOT NULL CHECK(semester BETWEEN 1 AND 12)
);

-- Создание таблицы экзаменов
CREATE TABLE IF NOT EXISTS exams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    discipline_id INTEGER NOT NULL,
    exam_date DATE NOT NULL,
    grade INTEGER CHECK(grade BETWEEN 2 AND 5),
    teacher VARCHAR(100),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (discipline_id) REFERENCES disciplines(id) ON DELETE CASCADE
);

-- Вставка тестовых данных для групп
INSERT INTO groups (group_number, direction, year_start) VALUES
('ИС-101', 'Информационные системы', 2023),
('ИС-102', 'Информационные системы', 2023),
('ПИ-201', 'Программная инженерия', 2022);

-- Вставка тестовых данных для дисциплин
INSERT INTO disciplines (name, direction, course, semester) VALUES
('Математика', 'Информационные системы', 1, 1),
('Программирование', 'Информационные системы', 1, 1),
('Базы данных', 'Информационные системы', 2, 3),
('Веб-разработка', 'Информационные системы', 2, 4),
('ООП', 'Программная инженерия', 2, 4);

-- Вставка тестовых данных для студентов
INSERT INTO students (last_name, first_name, middle_name, group_id, gender, birth_date) VALUES
('Иванов', 'Иван', 'Иванович', 1, 'М', '2002-05-15'),
('Петрова', 'Мария', 'Сергеевна', 1, 'Ж', '2003-02-20'),
('Сидоров', 'Алексей', 'Петрович', 2, 'М', '2002-11-10'),
('Кузнецова', 'Елена', 'Владимировна', 3, 'Ж', '2001-07-03');

-- Вставка тестовых данных для экзаменов
INSERT INTO exams (student_id, discipline_id, exam_date, grade, teacher) VALUES
(1, 1, '2024-01-20', 5, 'Проф. Смирнов'),
(1, 2, '2024-01-25', 4, 'Доц. Петров'),
(2, 1, '2024-01-21', 3, 'Проф. Смирнов'),
(2, 2, '2024-01-26', 5, 'Доц. Петров'),
(3, 3, '2024-06-15', 4, 'Проф. Иванов'),
(4, 5, '2024-06-20', 5, 'Доц. Сидорова');

-- Проверка данных
SELECT 'Группы' as Таблица, COUNT(*) as Количество FROM groups
UNION ALL SELECT 'Студенты', COUNT(*) FROM students
UNION ALL SELECT 'Дисциплины', COUNT(*) FROM disciplines
UNION ALL SELECT 'Экзамены', COUNT(*) FROM exams;