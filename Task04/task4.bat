#!/bin/bash
chcp 65001

sqlite3 movies_rating.db < db_init.sql

echo "1. Найти все пары пользователей, оценивших один и тот же фильм. Устранить дубликаты, проверить отсутствие пар с самим собой. Для каждой пары должны быть указаны имена пользователей и название фильма, который они ценили. В списке оставить первые 100 записей."
echo --------------------------------------------------
sqlite3 movies_rating.db -box "WITH UserPairs AS (SELECT DISTINCT r1.user_id AS user1_id, r2.user_id AS user2_id, r1.movie_id FROM ratings r1 JOIN ratings r2 ON r1.movie_id = r2.movie_id AND r1.user_id < r2.user_id) SELECT u1.name AS user1_name, u2.name AS user2_name, m.title AS movie_title FROM UserPairs up JOIN users u1 ON up.user1_id = u1.id JOIN users u2 ON up.user2_id = u2.id JOIN movies m ON up.movie_id = m.id ORDER BY u1.name, u2.name, m.title LIMIT 100;"
echo " "

echo "2. Найти 10 самых старых оценок от разных пользователей, вывести названия фильмов, имена пользователей, оценку, дату отзыва в формате ГГГГ-ММ-ДД."
echo --------------------------------------------------
sqlite3 movies_rating.db -box "SELECT DISTINCT m.title AS movie_title, u.name AS user_name, r.rating, date(datetime(r.timestamp, 'unixepoch')) AS rating_date FROM ratings r JOIN movies m ON r.movie_id = m.id JOIN users u ON r.user_id = u.id ORDER BY r.timestamp ASC LIMIT 10;"
echo " "

echo "3. Вывести в одном списке все фильмы с максимальным средним рейтингом и все фильмы с минимальным средним рейтингом. Общий список отсортировать по году выпуска и названию фильма. В зависимости от рейтинга в колонке 'Рекомендуем' для фильмов должно быть написано 'Да' или 'Нет'."
echo --------------------------------------------------
sqlite3 movies_rating.db -box "WITH MovieRatings AS (SELECT m.id, m.title, m.year, AVG(r.rating) AS avg_rating FROM movies m JOIN ratings r ON m.id = r.movie_id GROUP BY m.id, m.title, m.year), MaxMinRatings AS (SELECT MAX(avg_rating) AS max_rating, MIN(avg_rating) AS min_rating FROM MovieRatings) SELECT mr.title, mr.year, mr.avg_rating, CASE WHEN mr.avg_rating = (SELECT max_rating FROM MaxMinRatings) THEN 'Да' ELSE 'Нет' END AS Рекомендуем FROM MovieRatings mr WHERE mr.avg_rating = (SELECT max_rating FROM MaxMinRatings) OR mr.avg_rating = (SELECT min_rating FROM MaxMinRatings) ORDER BY mr.year, mr.title;"
echo " "

echo "4. Вычислить количество оценок и среднюю оценку, которую дали фильмам пользователи-мужчины в период с 2011 по 2014 год."
echo --------------------------------------------------
sqlite3 movies_rating.db -box "SELECT COUNT(*) AS количество_оценок, ROUND(AVG(r.rating), 2) AS средняя_оценка FROM ratings r JOIN users u ON r.user_id = u.id WHERE u.gender = 'M' AND strftime('%Y', datetime(r.timestamp, 'unixepoch')) BETWEEN '2011' AND '2014';"
echo " "

echo "5. Составить список фильмов с указанием средней оценки и количества пользователей, которые их оценили. Полученный список отсортировать по году выпуска и названиям фильмов. В списке оставить первые 20 записей."
echo --------------------------------------------------
sqlite3 movies_rating.db -box "SELECT m.title, m.year, ROUND(AVG(r.rating), 2) AS средняя_оценка, COUNT(DISTINCT r.user_id) AS количество_оценивших FROM movies m JOIN ratings r ON m.id = r.movie_id GROUP BY m.id, m.title, m.year ORDER BY m.year, m.title LIMIT 20;"
echo " "

echo "6. Определить самый распространенный жанр фильма и количество фильмов в этом жанре. Отдельную таблицу для жанров не использовать, жанры нужно извлекать из таблицы movies."
echo --------------------------------------------------
sqlite3 movies_rating.db -box "WITH GenreCounts AS (WITH RECURSIVE split(genre, rest) AS (SELECT '', genres || '|' FROM movies UNION ALL SELECT substr(rest, 1, instr(rest, '|')-1), substr(rest, instr(rest, '|')+1) FROM split WHERE rest != '') SELECT trim(genre) AS clean_genre, COUNT(*) AS genre_count FROM split WHERE genre != '' GROUP BY clean_genre) SELECT clean_genre AS самый_распространенный_жанр, genre_count AS количество_фильмов FROM GenreCounts ORDER BY genre_count DESC LIMIT 1;"
echo " "

echo "7. Вывести список из 10 последних зарегистрированных пользователей в формате 'Фамилия Имя|Дата регистрации' (сначала фамилия, потом имя)."
echo --------------------------------------------------
sqlite3 movies_rating.db -box "SELECT name AS 'Фамилия Имя|Дата регистрации', register_date FROM users ORDER BY register_date DESC LIMIT 10;"
echo " "

echo "8. С помощью рекурсивного CTE определить, на какие дни недели приходился ваш день рождения в каждом году."
echo --------------------------------------------------
sqlite3 movies_rating.db -box "WITH RECURSIVE years(year) AS (SELECT 2000 UNION ALL SELECT year + 1 FROM years WHERE year < 2023) SELECT year AS год, '15-05-' || year AS день_рождения, CASE strftime('%w', '20' || substr('00' || year, -2) || '-05-15') WHEN '0' THEN 'Воскресенье' WHEN '1' THEN 'Понедельник' WHEN '2' THEN 'Вторник' WHEN '3' THEN 'Среда' WHEN '4' THEN 'Четверг' WHEN '5' THEN 'Пятница' WHEN '6' THEN 'Суббота' END AS день_недели FROM years ORDER BY year;"