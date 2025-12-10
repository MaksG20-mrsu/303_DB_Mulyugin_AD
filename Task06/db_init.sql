-- Таблица сотрудников
CREATE TABLE employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    phone TEXT UNIQUE,
    email TEXT UNIQUE,
    hire_date DATE NOT NULL DEFAULT (date('now')),
    dismissal_date DATE NULL,
    salary_percent REAL NOT NULL CHECK (salary_percent > 0 AND salary_percent <= 100),
    status TEXT NOT NULL CHECK (status IN ('working', 'dismissed')) DEFAULT 'working',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица услуг
CREATE TABLE services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    duration_minutes INTEGER NOT NULL CHECK (duration_minutes > 0),
    price REAL NOT NULL CHECK (price > 0),
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица графиков работы
CREATE TABLE work_schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    work_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    UNIQUE(employee_id, work_date)
);

-- Таблица клиентов
CREATE TABLE clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    phone TEXT UNIQUE,
    email TEXT,
    car_model TEXT NOT NULL,
    car_number TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица заказов 
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    order_date DATE NOT NULL DEFAULT (date('now')),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    total_amount REAL NOT NULL CHECK (total_amount >= 0),
    status TEXT NOT NULL CHECK (status IN ('scheduled', 'in_progress', 'completed', 'cancelled')) DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT
);

-- Таблица выполненных работ( это связь заказов и услуг)
CREATE TABLE order_services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL CHECK (quantity > 0) DEFAULT 1,
    unit_price REAL NOT NULL CHECK (unit_price >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
);

-- Таблица выплат зарплат
CREATE TABLE salary_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    payment_date DATE NOT NULL DEFAULT (date('now')),
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    total_revenue REAL NOT NULL CHECK (total_revenue >= 0),
    salary_amount REAL NOT NULL CHECK (salary_amount >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT
);

-- Индексы для оптимизации запросов
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_employees_dismissal_date ON employees(dismissal_date);
CREATE INDEX idx_work_schedules_employee_date ON work_schedules(employee_id, work_date);
CREATE INDEX idx_orders_employee_date ON orders(employee_id, order_date);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_order_services_order_id ON order_services(order_id);
CREATE INDEX idx_salary_payments_employee_period ON salary_payments(employee_id, period_start, period_end);
CREATE INDEX idx_clients_phone ON clients(phone);

-- Триггер для автоматического обновления статуса сотрудника
CREATE TRIGGER update_employee_status
AFTER UPDATE OF dismissal_date ON employees
FOR EACH ROW
BEGIN
    UPDATE employees 
    SET status = CASE 
        WHEN NEW.dismissal_date IS NULL THEN 'working' 
        ELSE 'dismissed' 
    END
    WHERE id = NEW.id;
END;

-- Триггер для расчета общей суммы заказа
CREATE TRIGGER calculate_order_total
AFTER INSERT ON order_services
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET total_amount = (
        SELECT SUM(quantity * unit_price) 
        FROM order_services 
        WHERE order_id = NEW.order_id
    )
    WHERE id = NEW.order_id;
END;

-- Триггер для обновления суммы заказа при удалении услуги
CREATE TRIGGER update_order_total_on_delete
AFTER DELETE ON order_services
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET total_amount = (
        SELECT COALESCE(SUM(quantity * unit_price), 0) 
        FROM order_services 
        WHERE order_id = OLD.order_id
    )
    WHERE id = OLD.order_id;
END;


-- Сотрудники
INSERT INTO employees (first_name, last_name, phone, email, salary_percent, status) VALUES
('Дмитрий', 'Волков', '+79161112233', 'dmitry.volkov@mail.ru', 35.0, 'working'),
('Анна', 'Морозова', '+79161112234', 'anna.morozova@mail.ru', 27.0, 'working'),
('Павел', 'Зайцев', '+79161112235', 'pavel.zaytsev@mail.ru', 32.0, 'working'),
('Ирина', 'Лебедева', '+79161112236', 'irina.lebedeva@mail.ru', 24.0, 'dismissed');

-- Услуги
INSERT INTO services (name, description, duration_minutes, price) VALUES
('Замена масла ДВС', 'Замена моторного масла и масляного фильтра', 45, 1700.00),
('Ремонт тормозной системы', 'Замена тормозных колодок и дисков', 120, 4500.00),
('Регулировка развала-схождения', 'Настройка углов установки колес', 75, 2800.00),
('Компьютерная диагностика', 'Диагностика электронных систем автомобиля', 30, 1500.00),
('Замена воздушного фильтра', 'Замена фильтра воздушной системы', 20, 800.00),
('Балансировка колес', 'Балансировка всех четырех колес', 60, 1200.00);

-- Графики работы
INSERT INTO work_schedules (employee_id, work_date, start_time, end_time) VALUES
(1, '2024-02-10', '08:00', '17:00'),
(1, '2024-02-11', '08:00', '17:00'),
(1, '2024-02-12', '08:00', '17:00'),
(2, '2024-02-10', '09:00', '18:00'),
(2, '2024-02-11', '09:00', '18:00'),
(3, '2024-02-10', '10:00', '19:00'),
(3, '2024-02-12', '10:00', '19:00');

-- Клиенты
INSERT INTO clients (first_name, last_name, phone, car_model, car_number) VALUES
('Михаил', 'Соколов', '+79165554433', 'Kia Rio', 'E111FF777'),
('Ольга', 'Попова', '+79165554432', 'Hyundai Solaris', 'F222GG777'),
('Артем', 'Новиков', '+79165554431', 'Skoda Octavia', 'G333HH777'),
('Юлия', 'Воробьева', '+79165554430', 'Volkswagen Polo', 'H444II777');

-- Заказы
INSERT INTO orders (client_id, employee_id, order_date, start_time, end_time, total_amount, status, notes) VALUES
(1, 1, '2024-02-10', '09:00', '10:30', 2500.00, 'completed', 'Клиент просил проверить дополнительно АКБ'),
(2, 2, '2024-02-10', '11:00', '12:00', 1500.00, 'completed', 'Срочная диагностика перед поездкой'),
(3, 1, '2024-02-11', '14:00', '15:15', 5300.00, 'scheduled', 'Комплексное ТО'),
(4, 3, '2024-02-12', '10:00', '11:00', 2800.00, 'in_progress', 'Жалоба на вибрацию на руле');

-- Выполненные работы в заказах
INSERT INTO order_services (order_id, service_id, quantity, unit_price) VALUES
(1, 1, 1, 1700.00),
(1, 5, 1, 800.00),
(2, 4, 1, 1500.00),
(3, 1, 1, 1700.00),
(3, 4, 1, 1500.00),
(3, 5, 1, 800.00),
(3, 6, 1, 1300.00),
(4, 3, 1, 2800.00);

-- Выплаты зарплат
INSERT INTO salary_payments (employee_id, payment_date, period_start, period_end, total_revenue, salary_amount) VALUES
(1, '2024-02-09', '2024-01-25', '2024-02-08', 42000.00, 14700.00),
(2, '2024-02-09', '2024-01-25', '2024-02-08', 38000.00, 10260.00),
(3, '2024-02-09', '2024-01-25', '2024-02-08', 35000.00, 11200.00);


-- Отчет по выручке мастеров за период
SELECT 
    e.id,
    e.first_name || ' ' || e.last_name as employee_name,
    e.salary_percent || '%' as percent,
    COUNT(o.id) as orders_count,
    COALESCE(SUM(o.total_amount), 0) as total_revenue,
    COALESCE(SUM(o.total_amount), 0) * e.salary_percent / 100 as calculated_salary
FROM employees e
LEFT JOIN orders o ON o.employee_id = e.id 
    AND o.order_date BETWEEN '2024-02-01' AND '2024-02-29'
    AND o.status = 'completed'
WHERE e.status = 'working'
GROUP BY e.id;

-- Статистика по услугам
SELECT 
    s.name as service_name,
    COUNT(os.id) as times_ordered,
    SUM(os.quantity) as total_quantity,
    SUM(os.quantity * os.unit_price) as total_revenue
FROM services s
LEFT JOIN order_services os ON os.service_id = s.id
LEFT JOIN orders o ON o.id = os.order_id AND o.status = 'completed'
GROUP BY s.id
ORDER BY total_revenue DESC;

-- Занятость мастера на определенную дату
SELECT 
    e.first_name || ' ' || e.last_name as employee_name,
    ws.work_date,
    ws.start_time as schedule_start,
    ws.end_time as schedule_end,
    o.start_time as order_start,
    o.end_time as order_end,
    c.first_name || ' ' || c.last_name as client_name,
    o.status
FROM work_schedules ws
JOIN employees e ON e.id = ws.employee_id
LEFT JOIN orders o ON o.employee_id = ws.employee_id 
    AND o.order_date = ws.work_date
    AND o.status IN ('scheduled', 'in_progress')
LEFT JOIN clients c ON o.client_id = c.id
WHERE ws.employee_id = 1 AND ws.work_date = '2024-02-11'
ORDER BY ws.start_time, o.start_time;

-- Отчет по уволенным сотрудникам с их историей работы
SELECT 
    e.first_name || ' ' || e.last_name as employee_name,
    e.hire_date,
    e.dismissal_date,
    COUNT(o.id) as total_orders,
    COALESCE(SUM(o.total_amount), 0) as total_revenue
FROM employees e
LEFT JOIN orders o ON o.employee_id = e.id AND o.status = 'completed'
WHERE e.status = 'dismissed'
GROUP BY e.id;