INSERT INTO users (name, email, gender, occupation_id) 
VALUES 
('Alexandr Mulyugin', 'aleksandrmulyugin@yandex.ru', 'M', (SELECT id FROM occupations WHERE name = 'student')),
('Alexandra Ovsyankina', 'alexandra.ovsyankina@yandex.ru', 'F', (SELECT id FROM occupations WHERE name = 'student')),
('Yaroslav Rozanov', 'yaroslav.rozanov@yandex.ru', 'M', (SELECT id FROM occupations WHERE name = 'student')),
('Alexey Ferafontov', 'aleksey.ferafontov@yandex.ru', 'M', (SELECT id FROM occupations WHERE name = 'programmer')),
('Andrey Chesnokov', 'andrey.chesnokov@yandex.ru', 'M', (SELECT id FROM occupations WHERE name = 'educator'));

INSERT INTO movies (id, title, year) 
VALUES 
(100000, 'Interstellar', 2014),
(100001, 'Inception', 2010),
(100002, 'Green Book', 2018);

INSERT INTO movie_genres (movie_id, genre_id)
VALUES 
(100000, (SELECT id FROM genres WHERE name = 'Sci-Fi')),
(100000, (SELECT id FROM genres WHERE name = 'Adventure')),
(100000, (SELECT id FROM genres WHERE name = 'Drama')),
(100001, (SELECT id FROM genres WHERE name = 'Action')),
(100001, (SELECT id FROM genres WHERE name = 'Sci-Fi')),
(100001, (SELECT id FROM genres WHERE name = 'Thriller')),
(100002, (SELECT id FROM genres WHERE name = 'Comedy')),
(100002, (SELECT id FROM genres WHERE name = 'Drama'));

INSERT INTO ratings (user_id, movie_id, rating, timestamp)
VALUES 
((SELECT id FROM users WHERE name = 'Alexandr Mulyugin'), 100000, 5.0, strftime('%s', 'now')),
((SELECT id FROM users WHERE name = 'Alexandr Mulyugin'), 100001, 4.5, strftime('%s', 'now')),
((SELECT id FROM users WHERE name = 'Alexandr Mulyugin'), 100002, 4.0, strftime('%s', 'now'));

INSERT INTO tags (user_id, movie_id, tag, timestamp)
VALUES 
((SELECT id FROM users WHERE name = 'Alexandr Mulyugin'), 100000, 'space', strftime('%s', 'now')),
((SELECT id FROM users WHERE name = 'Alexandr Mulyugin'), 100001, 'dreams', strftime('%s', 'now')),
((SELECT id FROM users WHERE name = 'Alexandr Mulyugin'), 100002, 'friendship', strftime('%s', 'now'));