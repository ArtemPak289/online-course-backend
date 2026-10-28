# Online Course Platform Backend

Полнофункциональный REST API бэкенд для платформы онлайн-обучения на базе **Laravel 12**, **PostgreSQL 16**, **Redis 7** и **Docker Compose**.

Проект разработан в строгом соответствии с требованиями к чистой архитектуре (**Services > Controllers**), без сторонних стартовых пакетов аутентификации (собственная реализация API-токенов без Laravel Sanctum/Breeze/Fortify и реляционная RBAC-система с Laravel Policies).

---

## 🚀 Технологический стек и архитектура

* **Язык & Фреймворк**: PHP 8.5 (`php:8.5-fpm-alpine`) / Laravel 12
* **База данных**: PostgreSQL 16 (`pgsql` — основная БД `online_course` и изолированная тестовая БД `online_course_test`)
* **Кэш, Сессии & Очереди**: Redis 7 (`phpredis`)
* **Веб-сервер**: Nginx 1.25 (`http://localhost:8000`)
* **Архитектура**:
  * **Тонкие контроллеры** (`app/Http/Controllers/Api/`) — только приём запроса и возврат JSON/файлового ответа.
  * **Слой сервисов** (`app/Services/`) — вся бизнес-логика и транзакции БД (`AuthService`, `ProfileService`, `UserManagementService`, `CategoryService`, `CourseService`, `LessonService`, `PurchaseService`, `ProgressService`).
  * **Валидация** (`app/Http/Requests/`) — 16 классов `FormRequest` для всех входящих запросов.
  * **Аутентификация** (`app/Http/Middleware/AuthenticateApiToken.php`) — собственная Bearer-аутентификация (`api_tokens`, хранение SHA-256 хеша токена, режимы `auth.api` и `auth.api:optional`, мгновенная блокировка доступа при `is_blocked = true`).
  * **Авторизация** (`app/Policies/`) — реляционная RBAC (`roles`, `permissions`, `role_user`, `permission_role`) + `UserPolicy`, `CategoryPolicy`, `CoursePolicy`, `LessonPolicy`.
  * **Логирование** — глобальное логирование необработанных исключений в `bootstrap/app.php` и доменных операций в `storage/logs/laravel.log`.

---

## 👥 Роли пользователей

1. **Администратор (`admin`)**:
   * Полное управление категориями курсов (CRUD).
   * Управление пользователями (просмотр с фильтрацией по роли и статусу блокировки, смена ролей, блокировка/разблокировка с отзывом активных токенов).
   * Полный доступ к просмотру (включая черновики), созданию, редактированию и удалению любых курсов и уроков.
2. **Преподаватель (`teacher`)**:
   * Создание, редактирование, публикация и удаление собственных курсов.
   * Просмотр своих черновиков и фильтрация курсов по статусу (`draft` / `published`).
   * Управление уроками своих курсов с загрузкой файлов дополнительных материалов.
3. **Студент (`student`)**:
   * Просмотр каталога опубликованных курсов, регистронезависимый поиск (`ILIKE`), фильтрация и сортировка.
   * Покупка опубликованных курсов с получением платёжной ссылки и подтверждением через подписанный webhook.
   * Прохождение уроков оплаченного курса с расчётом процента прогресса.
   * Автоматическое получение и скачивание PDF-сертификата после завершения всех уроков курса.

---

## ⚡ Доменные события и фоновые очереди

### 4 Доменных события (`app/Events/` → `app/Listeners/`):
* `UserRegistered` → `LogUserRegistration` (регистрация нового пользователя).
* `CoursePublished` → `LogCoursePublished` (публикация курса при создании или обновлении статуса).
* `CoursePurchased` → `LogCoursePurchased` (подтверждение оплаты курса через webhook).
* `CourseCompleted` → `LogCourseCompleted` (завершение студентом всех уроков курса).

### 2 Фоновые задачи в очереди Redis (`app/Jobs/`):
* `SendPaymentNotification` (`ShouldQueue`) — отправка уведомления `PaymentSuccessfulNotification` студенту после успешной оплаты.
* `GenerateCertificate` (`ShouldQueue`) — генерация валидного PDF-документа (`%PDF-1.4`) в `storage/app/private/certificates/` и сохранение записи в таблице `certificates`.

---

## 🐳 Запуск проекта (Docker + PostgreSQL + Redis)

Конфигурация `docker-compose.yml` поднимает 5 сервисов в единой сети `course_network`:

| Сервис | Контейнер | Образ | Назначение и порты |
|---|---|---|---|
| `web` | `online_course_web` | `nginx:1.25-alpine` | HTTP веб-сервер (`http://localhost:8000`) |
| `app` | `online_course_app` | `online-course-app:latest` | PHP 8.5-FPM (`pdo_pgsql`, `redis`, `gd`, `zip`, `mbstring`, `bcmath`, `pcntl`) |
| `queue` | `online_course_queue` | `online-course-app:latest` | Фоновый воркер Redis-очереди (`php artisan queue:work`, пользователь `www-data`) |
| `db` | `online_course_db` | `postgres:16-alpine` | PostgreSQL 16 (порт `5432`, БД `online_course` + тестовая `online_course_test`) |
| `redis` | `online_course_redis` | `redis:7-alpine` | Redis 7 для очередей, кэша и сессий (порт `6379`) |

### Пошаговый запуск с нуля

1. Скопируйте файл конфигурации окружения:
   ```bash
   cp .env.example .env
   ```
2. Соберите образ и запустите все контейнеры:
   ```bash
   docker compose up -d --build
   ```
3. Сгенерируйте ключ приложения, перезапустите воркер очереди (чтобы он подхватил новый `APP_KEY`) и выполните миграции с сидерами:
   ```bash
   docker compose exec app php artisan key:generate
   docker compose restart queue
   docker compose exec app php artisan migrate:fresh --seed
   ```
4. Создайте симлинк для публичного хранилища материалов уроков (опционально):
   ```bash
   docker compose exec app php artisan storage:link
   ```

Базовый URL API: `http://localhost:8000/api` (проверка healthcheck: `http://localhost:8000/up`).

---

## 🧪 Тестирование и проверка кода

Проект имеет **~80% покрытие автотестами** и содержит **100 автоматических тестов** (44 `Unit` + 56 `Feature`, 319 проверок, 0 ошибок). Конфигурация `phpunit.xml` настроена на запуск тестов напрямую в **PostgreSQL** с использованием изолированной базы `online_course_test` (создаётся автоматически скриптом `docker/postgres/init-test-db.sql`), поэтому запуск тестов не затрагивает рабочую базу `online_course`:

```bash
# Запуск всех Unit и Feature тестов на PostgreSQL
docker compose exec app php artisan test

# Проверка код-стайла (Laravel Pint)
docker compose exec app ./vendor/bin/pint --test

# Просмотр логов фонового воркера очередей
docker compose logs -f queue

# Остановка контейнеров
docker compose down
```

---

## 🔑 Демонстрационные данные (Сидеры)

Команда `php artisan migrate:fresh --seed` создаёт роли и права RBAC, 3 категории (`Веб-разработка`, `Мобильная разработка`, `DevOps и Архитектура`), 3 курса (2 опубликованных и 1 черновик), 3 урока и 3 тестовых пользователей:

| Роль | Email | Пароль |
|---|---|---|
| Администратор (`admin`) | `admin@example.com` | `password123` |
| Преподаватель (`teacher`) | `teacher@example.com` | `password123` |
| Студент (`student`) | `student@example.com` | `password123` |

---

## 📬 Импорт коллекции в Postman

В корне проекта находится готовая коллекция **`postman_collection.json`** (36 запросов в 6 папках):
1. Откройте **Postman** → нажмите **Import** → перетащите файл `postman_collection.json`.
2. Все запросы входа (`Login as Admin`, `Login as Teacher`, `Login as Student`) и регистрации автоматически сохраняют полученный Bearer-токен в переменную коллекции `{{token}}`.
3. Запрос `1. Initiate Course Purchase` автоматически сохраняет `{{payment_id}}`, а `2. Payment Webhook` автоматически вычисляет `HMAC-SHA256` подпись `X-Webhook-Signature` через Pre-request Script (при смене `APP_KEY` в `.env` обновите переменную `app_key` во вкладке **Variables** коллекции).

---

## 📡 Справочник REST API

Для защищённых маршрутов передавайте токен, полученный при `/api/login` или `/api/register`, в заголовке:
```http
Authorization: Bearer <ваш_токен>
Accept: application/json
```

### Аутентификация и Профиль
| Метод | URL | Описание | Доступ |
|---|---|---|---|
| `POST` | `/api/register` | Регистрация нового студента и выдача токена | Публичный |
| `POST` | `/api/login` | Аутентификация и выдача API-токена | Публичный |
| `POST` | `/api/logout` | Выход и отзыв текущего токена | Авторизованный |
| `GET` | `/api/profile` | Просмотр собственного профиля | Авторизованный |
| `PUT` | `/api/profile` | Обновление имени и email профиля | Авторизованный |
| `PUT` | `/api/profile/password` | Смена пароля (`current_password`, `password`, `password_confirmation`) | Авторизованный |

### Категории
| Метод | URL | Описание | Доступ |
|---|---|---|---|
| `GET` | `/api/categories` | Список категорий с пагинацией и числом курсов | Публичный |
| `GET` | `/api/categories/{category}` | Детальная информация о категории и её опубликованных курсах | Публичный |
| `POST` | `/api/categories` | Создание категории | Администратор |
| `PUT` / `PATCH` | `/api/categories/{category}` | Обновление категории | Администратор |
| `DELETE` | `/api/categories/{category}` | Удаление категории | Администратор |

### Курсы
Поддерживаемые query-параметры для `GET /api/courses`:
* `search` — регистронезависимый поиск по названию курса (`ILIKE`);
* `category_id` — фильтр по ID категории;
* `author_id` — фильтр по ID преподавателя;
* `status` — фильтр по статусу (`draft` | `published`, доступен преподавателям и администраторам);
* `sort` — поле сортировки (`price` | `created_at`), `direction` — направление (`asc` | `desc`);
* `per_page` — количество элементов на странице (`1..100`).

| Метод | URL | Описание | Доступ |
|---|---|---|---|
| `GET` | `/api/courses` | Каталог курсов с поиском, фильтрацией и сортировкой | Публичный / Привилегированный |
| `GET` | `/api/courses/{course}` | Детали курса и упорядоченный список его уроков | Публичный (опубл.) / Автор / Админ |
| `POST` | `/api/courses` | Создание курса (`draft` или `published`) | Преподаватель / Администратор |
| `PUT` / `PATCH` | `/api/courses/{course}` | Редактирование и публикация курса | Автор курса / Администратор |
| `DELETE` | `/api/courses/{course}` | Удаление курса | Автор курса / Администратор |

### Уроки
| Метод | URL | Описание | Доступ |
|---|---|---|---|
| `GET` | `/api/courses/{course}/lessons` | Список уроков курса (по `position`) | Публичный (опубл.) / Автор / Админ |
| `GET` | `/api/lessons/{lesson}` | Детальная информация об уроке | Публичный (опубл.) / Автор / Админ |
| `GET` | `/api/lessons/{lesson}/download` | Скачивание прикреплённого файла материалов | Публичный |
| `POST` | `/api/courses/{course}/lessons` | Создание урока (`multipart/form-data`, поле `file` до 10 МБ) | Автор курса / Администратор |
| `PUT` / `PATCH` | `/api/lessons/{lesson}` | Обновление урока и замена файла материалов | Автор курса / Администратор |
| `DELETE` | `/api/lessons/{lesson}` | Удаление урока и его файла | Автор курса / Администратор |

### Покупка, Прогресс и Сертификаты
Для проверки платёжного вебхука `POST /api/payments/webhook` используется заголовок `X-Webhook-Signature`, вычисляемый как `HMAC-SHA256` от сырого JSON-тела запроса с ключом `APP_KEY`:
```php
$signature = hash_hmac('sha256', $rawBody, config('app.key'));
```
Тело вебхука: `{"payment_id": "pay_...", "status": "paid"}` (или `"failed"`). Обработка идемпотентна.

| Метод | URL | Описание | Доступ |
|---|---|---|---|
| `POST` | `/api/courses/{course}/purchase` | Инициация покупки опубликованного курса (возвращает `payment_id` и `payment_url`) | Студент / Администратор |
| `POST` | `/api/payments/webhook` | Приём уведомления от платёжной системы (проверка `X-Webhook-Signature`) | Публичный (подписанный) |
| `GET` | `/api/purchases` | Список покупок текущего пользователя | Авторизованный |
| `POST` | `/api/lessons/{lesson}/complete` | Отметка урока завершённым (при 100% запускает генерацию сертификата в очереди) | Оплативший студент / Автор / Админ |
| `GET` | `/api/courses/{course}/progress` | Текущий прогресс прохождения курса (`percentage`, `completed_lesson_ids`) | Авторизованный |
| `GET` | `/api/courses/{course}/certificate` | Скачивание сгенерированного PDF-сертификата (`application/pdf`) | Авторизованный (владелец сертификата) |

### Администрирование пользователей
Поддерживаемые query-параметры для `GET /api/admin/users`: `role` (`admin` | `teacher` | `student`), `is_blocked` (`true` | `false`), `per_page`.

| Метод | URL | Описание | Доступ |
|---|---|---|---|
| `GET` | `/api/admin/users` | Список пользователей с пагинацией и фильтрами | Администратор |
| `PATCH` | `/api/admin/users/{user}/role` | Изменение роли пользователя (`{"role": "teacher"}`) | Администратор |
| `PATCH` | `/api/admin/users/{user}/block` | Блокировка/разблокировка пользователя (`{"is_blocked": true}`) | Администратор |
