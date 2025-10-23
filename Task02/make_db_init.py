import os
import sqlite3
from datetime import datetime


def sanitize_sql_value(value):
    """Очистка значений для безопасной вставки в SQL"""
    if value is None:
        return 'NULL'
    return str(value).replace("'", "''")


def guess_separator(text_line):
    """Определение разделителя в строке данных"""
    separators = [',', ';', '|', '\t', '::']
    for sep in separators:
        if sep in text_line:
            return sep
    return ','


def create_database_schema():
    """Формирование SQL-скрипта для инициализации базы данных"""

    print("Запуск процесса формирования SQL-скрипта...")

    try:
        with open('db_init.sql', 'w', encoding='utf-8') as sql_file:
            print("Инициализация файла db_init.sql...")

            # Добавляем заголовок
            sql_file.write('-- SQL Script for Database Initialization\n')
            sql_file.write(f'-- Generated: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}\n\n')

            # Очистка существующих таблиц
            sql_file.write('-- Removing existing tables\n')
            tables = ['tags', 'ratings', 'movies', 'users']
            for table in tables:
                sql_file.write(f'DROP TABLE IF EXISTS {table};\n')
            sql_file.write('\n')

            # Создание структуры таблиц
            sql_file.write('-- Creating database structure\n')

            # Таблица фильмов
            sql_file.write('''CREATE TABLE movies (
    id INTEGER PRIMARY KEY,
    title TEXT NOT NULL,
    year INTEGER,
    genres TEXT
);\n\n''')

            # Таблица оценок
            sql_file.write('''CREATE TABLE ratings (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL,
    movie_id INTEGER NOT NULL,
    rating REAL NOT NULL,
    timestamp INTEGER NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movies(id)
);\n\n''')

            # Таблица тегов
            sql_file.write('''CREATE TABLE tags (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL,
    movie_id INTEGER NOT NULL,
    tag TEXT NOT NULL,
    timestamp INTEGER NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movies(id)
);\n\n''')

            # Таблица пользователей
            sql_file.write('''CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT,
    gender TEXT,
    register_date TEXT,
    occupation TEXT
);\n\n''')

            # Загрузка информации о фильмах
            print("Поиск файлов с данными о фильмах...")
            movie_files = ['movies.csv', 'movies.dat', 'movies.txt']
            movies_loaded = False

            for movie_file in movie_files:
                file_path = f'dataset/{movie_file}'
                if os.path.exists(file_path):
                    print(f"Обнаружен файл: {file_path}")
                    movies_loaded = True
                    sql_file.write('-- Importing movies data\n')

                    with open(file_path, 'r', encoding='utf-8') as data_file:
                        skip_header = True
                        records_count = 0

                        for data_line in data_file:
                            if skip_header and movie_file.endswith('.csv'):
                                skip_header = False
                                continue
                            skip_header = False

                            separator = guess_separator(data_line)
                            fields = data_line.strip().split(separator)

                            if len(fields) >= 3:
                                film_id = fields[0]
                                film_title = sanitize_sql_value(fields[1])
                                film_genres = sanitize_sql_value(fields[2])
                                sql_file.write(
                                    f"INSERT INTO movies (id, title, genres) VALUES ({film_id}, '{film_title}', '{film_genres}');\n"
                                )
                                records_count += 1

                        print(f"Загружено {records_count} записей о фильмах")
                    break

            if not movies_loaded:
                print("Внимание: Файлы с данными о фильмах не обнаружены")

            # Загрузка рейтингов
            print("Поиск файлов с рейтингами...")
            rating_files = ['ratings.csv', 'ratings.dat', 'ratings.txt']
            ratings_loaded = False

            for rating_file in rating_files:
                file_path = f'dataset/{rating_file}'
                if os.path.exists(file_path):
                    print(f"Обнаружен файл: {file_path}")
                    ratings_loaded = True
                    sql_file.write('\n-- Importing ratings data\n')

                    with open(file_path, 'r', encoding='utf-8') as data_file:
                        skip_header = True
                        records_count = 0

                        for data_line in data_file:
                            if skip_header and rating_file.endswith('.csv'):
                                skip_header = False
                                continue
                            skip_header = False

                            separator = guess_separator(data_line)
                            fields = data_line.strip().split(separator)

                            if len(fields) >= 4:
                                user_id = fields[0]
                                film_id = fields[1]
                                user_rating = fields[2]
                                rating_time = fields[3]
                                sql_file.write(
                                    f"INSERT INTO ratings (user_id, movie_id, rating, timestamp) VALUES ({user_id}, {film_id}, {user_rating}, {rating_time});\n"
                                )
                                records_count += 1

                        print(f"Загружено {records_count} оценок")
                    break

            if not ratings_loaded:
                print("Внимание: Файлы с рейтингами не обнаружены")

            # Загрузка тегов
            print("Поиск файлов с тегами...")
            tag_files = ['tags.csv', 'tags.dat', 'tags.txt']
            tags_loaded = False

            for tag_file in tag_files:
                file_path = f'dataset/{tag_file}'
                if os.path.exists(file_path):
                    print(f"Обнаружен файл: {file_path}")
                    tags_loaded = True
                    sql_file.write('\n-- Importing tags data\n')

                    with open(file_path, 'r', encoding='utf-8') as data_file:
                        skip_header = True
                        records_count = 0

                        for data_line in data_file:
                            if skip_header and tag_file.endswith('.csv'):
                                skip_header = False
                                continue
                            skip_header = False

                            separator = guess_separator(data_line)
                            fields = data_line.strip().split(separator)

                            if len(fields) >= 4:
                                user_id = fields[0]
                                film_id = fields[1]
                                tag_text = sanitize_sql_value(fields[2])
                                tag_time = fields[3]
                                sql_file.write(
                                    f"INSERT INTO tags (user_id, movie_id, tag, timestamp) VALUES ({user_id}, {film_id}, '{tag_text}', {tag_time});\n"
                                )
                                records_count += 1

                        print(f"Загружено {records_count} тегов")
                    break

            if not tags_loaded:
                print("Внимание: Файлы с тегами не обнаружены")

            # Загрузка пользователей
            print("Поиск файлов с пользователями...")
            user_files = ['users.txt', 'users.csv', 'users.dat']
            users_loaded = False

            for user_file in user_files:
                file_path = f'dataset/{user_file}'
                if os.path.exists(file_path):
                    print(f"Обнаружен файл: {file_path}")
                    users_loaded = True
                    sql_file.write('\n-- Importing users data\n')

                    with open(file_path, 'r', encoding='utf-8') as data_file:
                        skip_header = True
                        records_count = 0

                        for data_line in data_file:
                            if skip_header and user_file.endswith('.csv'):
                                skip_header = False
                                continue
                            skip_header = False

                            separator = guess_separator(data_line)
                            fields = data_line.strip().split(separator)

                            if len(fields) >= 5:
                                user_id = fields[0]
                                user_name = sanitize_sql_value(fields[1])
                                user_email = sanitize_sql_value(fields[2])
                                user_gender = sanitize_sql_value(fields[3])
                                user_reg_date = sanitize_sql_value(fields[4])
                                user_job = sanitize_sql_value(fields[5]) if len(fields) > 5 else 'NULL'
                                sql_file.write(
                                    f"INSERT INTO users (id, name, email, gender, register_date, occupation) VALUES ({user_id}, '{user_name}', '{user_email}', '{user_gender}', '{user_reg_date}', '{user_job}');\n"
                                )
                                records_count += 1

                        print(f"Загружено {records_count} пользователей")
                    break

            if not users_loaded:
                print("Внимание: Файлы с пользователями не обнаружены")

            # Финальные проверки
            sql_file.write('\n-- Data validation queries\n')
            sql_file.write("SELECT 'Movies: ' || COUNT(*) FROM movies;\n")
            sql_file.write("SELECT 'Ratings: ' || COUNT(*) FROM ratings;\n")
            sql_file.write("SELECT 'Tags: ' || COUNT(*) FROM tags;\n")
            sql_file.write("SELECT 'Users: ' || COUNT(*) FROM users;\n")

            print("SQL-скрипт успешно сформирован")

    except Exception as error:
        print(f"Ошибка при создании SQL-скрипта: {error}")
        return False

    return True


def main():
    """Основная функция выполнения ETL процесса"""
    print("Запуск ETL процесса для базы данных...")
    if create_database_schema():
        print("SQL-скрипт db_init.sql успешно создан!")
        print("Для применения скрипта выполните: sqlite3 movies_rating.db < db_init.sql")
    else:
        print("Произошла ошибка при создании SQL-скрипта!")


if __name__ == "__main__":
    main()