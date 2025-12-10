CREATE TABLE IF NOT EXISTS groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_number VARCHAR(10) NOT NULL UNIQUE,
    study_direction VARCHAR(100) NOT NULL,
    graduation_year INTEGER NOT NULL CHECK(graduation_year >= 2024),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    gender VARCHAR(1) CHECK(gender IN ('M', 'F')),
    birth_date DATE NOT NULL,
    student_card VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
);


CREATE INDEX idx_groups_graduation_year ON groups(graduation_year);
CREATE INDEX idx_students_group_id ON students(group_id);
CREATE INDEX idx_students_last_name ON students(last_name);


INSERT INTO groups (group_number, study_direction, graduation_year) VALUES
('1', 'Prikladnaya matematika', 2024),
('2', 'Prikladnaya informatika', 2024),
('3', 'Fundamentalnaya matematika', 2025),
('4', 'Programmnaya inzheneriya', 2025),
('5', 'Informatsionnaya bezopasnost', 2026);

INSERT INTO students (group_id, first_name, last_name, middle_name, gender, birth_date, student_card) VALUES
(1, 'Ivan', 'Petrov', 'Sergeevich', 'M', '2002-05-15', '301'),
(1, 'Maria', 'Ivanova', 'Alexandrovna', 'F', '2001-12-03', '302'),
(1, 'Alexey', 'Sidorov', 'Vladimirovich', 'M', '2002-08-22', '303'),
(2, 'Ekaterina', 'Smirnova', 'Dmitrievna', 'F', '2001-11-30', '304'),
(2, 'Dmitry', 'Kuznetsov', 'Igorevich', 'M', '2002-03-18', '305'),
(2, 'Anna', 'Popova', 'Sergeevna', 'F', '2002-07-25', '306'),
(3, 'Pavel', 'Volkov', 'Andreevich', 'M', '2003-02-14', '307'),
(3, 'Olga', 'Lebedeva', 'Viktorovna', 'F', '2003-06-08', '308'),
(4, 'Sergey', 'Kozlov', 'Petrovich', 'M', '2003-09-11', '309'),
(5, 'Natalya', 'Novikova', 'Olegovna', 'F', '2004-01-20', '310');