# InternetLab — Contact Feedback API

Backend API для приёма обратной связи из формы контактов: сохранение в PostgreSQL, асинхронная отправка писем, AI-ревью тональности и комментария, rate limiting и логирование запросов.

---

## 1. Как запустить проект

### Требования

- Docker и Docker Compose (`docker compose`)
- Git
- (опционально) запись в `/etc/hosts` для локального хоста

### Быстрый старт

Из корня репозитория:

```bash
chmod +x start   # один раз, если скрипт ещё не исполняемый
./start
```

Скрипт:

1. Создаёт `docker/.env` и `back/.env` из `*.example`, если их ещё нет
2. При возможности подтягивает изменения из git
3. Собирает и поднимает стек: `docker compose -f docker/docker-compose.local.yml up -d --build`
4. Применяет Doctrine-миграции в контейнере `internet-lab-back`

Сервисы после старта:

| Сервис | Назначение | Порт / доступ |
|--------|------------|---------------|
| `nginx` | HTTP-вход к API | `http://internet-lab.local` (порт 80) |
| `back` | PHP-FPM (Symfony) | внутри сети Docker |
| `messenger` | `messenger:consume async` | фоновый воркер |
| `db` | PostgreSQL 17 | `5432` |
| `mailer` | Mailpit (SMTP + UI) | SMTP `1025`, UI `http://localhost:8025` |

Добавьте в `/etc/hosts` (если ещё нет):

```text
127.0.0.1 internet-lab.local
```

### Настройка переменных окружения

Основные файлы:

- `docker/.env` — переменные для Compose и контейнеров (создаётся из `docker/.env.example`)
- `back/.env` — переменные приложения Symfony (создаётся из `back/.env.example`)

Ключевые переменные:

| Переменная | Назначение | Пример |
|------------|------------|--------|
| `APP_ENV` / `APP_DEBUG` / `APP_SECRET` | Режим Symfony | `dev` / `true` / свой секрет |
| `DATABASE_URL` | PostgreSQL | задаётся в `docker/.env` для контейнеров |
| `MAILER_DSN` | SMTP | в Docker: `smtp://mailer:1025` |
| `ADMIN_EMAIL` | Получатель уведомления админу | `admin@example.com` |
| `MAILER_FROM` | From исходящих писем | `noreply@example.com` |
| `MESSENGER_TRANSPORT_DSN` | Очередь Messenger | `doctrine://default?auto_setup=0` |
| `GENERIC_BASE_URL` | OpenAI-compatible API base (`/v1` или `/api/v1`) | `https://api.example.com/v1` |
| `GENERIC_API_KEY` | API-ключ провайдера | *(секрет, не коммитить)* |
| `GENERIC_MODEL` | Идентификатор модели | `openai/gpt-4o-mini` |

Для AI-ревью обязательно заполните `GENERIC_*` в `docker/.env` (или в окружении контейнеров). Без ключа HTTP-эндпоинт контакта всё равно отвечает `201`, но асинхронный AI-воркер не сможет сохранить ревью.

### Команды для установки зависимостей

Зависимости PHP ставятся при сборке образа (`docker/back/Dockerfile` → `composer install`).

Вручную внутри контейнера:

```bash
docker exec -it internet-lab-back composer install
```

Локально (без Docker), из каталога `back/`:

```bash
cd back
composer install
```

Полезные команды после старта:

```bash
# Миграции
docker exec -t internet-lab-back php bin/console doctrine:migrations:migrate --no-interaction

# Логи / failed Messenger
docker exec -it internet-lab-back php bin/console messenger:failed:show
docker logs internet-lab-messenger
```

Остановка стека:

```bash
docker compose -f docker/docker-compose.local.yml down
```

---

## 2. Стек технологий

### Backend

| Слой | Технологии |
|------|------------|
| Язык | PHP ≥ 8.4 |
| Фреймворк | Symfony 8.1 |
| ORM / БД | Doctrine ORM 3, Doctrine Migrations, PostgreSQL 17 |
| Очереди | Symfony Messenger (Doctrine transport) |
| Почта | Symfony Mailer + Twig-шаблоны; локально Mailpit |
| Валидация / сериализация | Symfony Validator, Serializer, PropertyInfo |
| Логи | Monolog (`symfony/monolog-bundle`) |
| Rate limiting | Symfony RateLimiter |
| Инфраструктура | Docker Compose, nginx Alpine, PHP-FPM Alpine |

### AI

| Инструмент | Роль |
|------------|------|
| `symfony/ai-bundle`, `symfony/ai-agent` | Агенты и конфигурация AI в Symfony |
| `symfony/ai-generic-platform` | OpenAI-compatible провайдер (generic bridge) |
| Scoped HttpClient (`ai.http_client.generic`) | Таймауты к AI API (`timeout: 15`, `max_duration: 30`) |
| Агент `ai.agent.contact_feedback_review` | Классификация тона + краткий отзыв на русском |

Провайдер задаётся через `GENERIC_BASE_URL` / `GENERIC_API_KEY` / `GENERIC_MODEL` — любой совместимый с OpenAI Chat Completions API.

---

## 3. Архитектура

### Структура проекта

```text
InternetLab/
├── start                          # Точка входа: env → compose → миграции
├── docker/
│   ├── docker-compose.local.yml   # db, back, messenger, nginx, mailer
│   ├── .env.example
│   ├── back/Dockerfile
│   └── nginx/nginx.conf
└── back/                          # Symfony-приложение
    ├── config/                    # routes, ai, messenger, rate_limiter, monolog, …
    ├── migrations/
    ├── public/
    ├── templates/emails/
    └── src/
        ├── Controller/API/V1/…    # HTTP-слой
        ├── DTO/                   # Request/Response, Error, AI
        ├── Entity/ / Repository/
        ├── Message/               # Messenger messages + handlers
        ├── Service/               # AI, Mailer, RateLimit, Logging
        ├── Exception/             # HTTP + Messenger exception pipelines
        └── EventSubscriber/       # exceptions, rate limit, logging, failed messages
```

### Паттерны проектирования

| Паттерн | Где используется |
|---------|------------------|
| **Thin controller + handler** | `ContactController` → `ContactFeedbackHandler` |
| **DTO / MapRequestPayload** | Валидация входа через `ContactFeedbackRequest` |
| **Command / Message Bus** | `SendContactFeedbackEmailsMessage`, `ReviewContactFeedbackMessage` |
| **Chain of Responsibility** | Tagged handlers: HTTP (`ExceptionMapper`), Messenger (`MessageExceptionMapper`), failed messages (`FailedMessageMapper`) |
| **Factory** | `ErrorResponseFactory` → Problem Details JSON |
| **Subscriber** | Rate limit по IP, логирование API, failed Messenger events |
| **Graceful degradation** | Успех HTTP не зависит от доступности AI/почты (они в async) |

### Почему такой стек

- **Symfony 8 + PHP 8.4** — привычный экосистемный стек для API, DI, валидации и Messenger «из коробки».
- **Doctrine + PostgreSQL** — надёжное хранение обратной связи и ревью; Doctrine transport для очереди без отдельного брокера в локальной среде.
- **Messenger async** — письма и AI не блокируют ответ клиенту.
- **Symfony AI generic platform** — один код для любого OpenAI-compatible провайдера, без жёсткой привязки к бренду модели.
- **RFC 7807 Problem Details** — единый машиночитаемый формат ошибок для API-клиентов.
- **Docker Compose** — воспроизводимый локальный стенд (nginx + FPM + DB + Mailpit + consumer).

---

## 4. Реализация API

Префикс всех контроллеров: `/api` (`back/config/routes.yaml`).

### `POST /api/contact`

Принимает обратную связь, сохраняет `UserFeedback`, ставит в очередь письма и AI-ревью.

**Запрос:**

```http
POST /api/contact HTTP/1.1
Host: internet-lab.local
Content-Type: application/json

{
  "name": "Иван Иванов",
  "phone": "+79001234567",
  "email": "user@example.com",
  "comment": "Здравствуйте, хочу уточнить…"
}
```

| Поле | Валидация |
|------|-----------|
| `name` | Not blank, min length 3 |
| `phone` | Not blank; `+7` или `8` + 10 цифр |
| `email` | Not blank, валидный email |
| `comment` | Not blank, min length 3 |

**Успех — `201 Created`:**

```json
{
  "id": 1,
  "name": "Иван Иванов",
  "phone": "+79001234567",
  "email": "user@example.com",
  "comment": "Здравствуйте, хочу уточнить…"
}
```

Пример через curl:

```bash
curl -sS -X POST 'http://internet-lab.local/api/contact' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Иван Иванов",
    "phone": "+79001234567",
    "email": "user@example.com",
    "comment": "Здравствуйте, хочу уточнить…"
  }'
```

### Валидация и обработка ошибок

Все необработанные исключения на путях `/api*` превращаются в **Problem Details** (`Content-Type: application/problem+json`) через:

`ExceptionSubscriber` → `ExceptionMapper` (tagged handlers) → `ErrorResponseFactory`.

| Ситуация | HTTP | `type` |
|----------|------|--------|
| Ошибка валидации | `422` | `errors.validation` |
| HTTP-исключение (404, 429, …) | соответствующий статус | `errors.request` |
| Непредвиденная ошибка | `500` | `errors.internal` |

**Пример 422:**

```json
{
  "type": "errors.validation",
  "title": "Ошибка валидации",
  "status": 422,
  "message": "Ошибка валидации",
  "instance": "/api/contact",
  "details": {
    "email": ["This value is not a valid email address."],
    "name": ["This value should not be blank."]
  }
}
```

**Пример 429 (rate limit):**

```json
{
  "type": "errors.request",
  "title": "Ошибка запроса",
  "status": 429,
  "message": "Превышен лимит отправки обращений. Повторите попытку через минуту.",
  "instance": "/api/contact"
}
```

Заголовок `Retry-After` копируется из `TooManyRequestsHttpException`.

Валидация выполняется до потребления bucket rate limit по email: невалидный запрос не тратит лимит отправки.

---

## 5. AI-интеграция

### Какие инструменты и зачем

После успешного `POST /api/contact`:

1. Сохраняется `UserFeedback`
2. В очередь уходит `ReviewContactFeedbackMessage`
3. Воркер вызывает агент `contact_feedback_review` (Symfony AI + generic OpenAI-compatible platform)
4. Результат пишется в `UserFeedbackReview` (OneToOne): `tone` + `comment`

Тональность (`UserFeedbackToneEnum`): `positive` | `neutral` | `negative` | `mixed`.  
Комментарий ревью — краткое резюме на русском (1–3 предложения).

### Как реализован fallback / устойчивость

Отдельного «подстановочного» ревью (например, принудительный `neutral`) **нет** — такой fallback был сознательно убран.

Вместо этого:

| Слой | Поведение |
|------|-----------|
| HTTP | Всегда `201` при успешном persist; AI не блокирует ответ клиенту |
| Невалидный/пустой ответ модели | Сообщение ACK, строка ревью **не** создаётся, retry нет |
| Постоянные ошибки провайдера (auth, bad request, model not found) | `UnrecoverableMessageHandlingException` — без дальнейших retry |
| Временные ошибки (timeout, 5xx, сеть, rate limit провайдера) | Исключение пробрасывается → Messenger retries (`max_retries: 3`) |
| Исчерпаны retry | `FailedMessageSubscriber` → лог; сообщение в transport `failed` |

Повтор failed-сообщений:

```bash
docker exec -it internet-lab-back php bin/console messenger:failed:show
docker exec -it internet-lab-back php bin/console messenger:failed:retry
```

### Промпты

**System prompt** — `back/config/packages/ai.yaml` (агент `contact_feedback_review`):

```text
Вы — рецензент обратной связи из формы контактов для службы поддержки продукта.

Проанализируйте сообщение пользователя и:
1. Определите общий тон, используя ровно одно из значений: positive, neutral, negative, mixed.
2. Напишите краткий отзыв на русском (1–3 предложения), суммирующий обратную связь.

Ответьте только JSON — без markdown-блоков и без лишнего текста. Точный формат:
{"tone":"negative","comment":"..."}

Поле "tone" ОБЯЗАТЕЛЬНО должно быть одним из: positive, neutral, negative, mixed.
```

**User prompt template** — `back/config/services.yaml` (`app.ai.contact_review.user_prompt_template`):

```text
Проанализируй следующий отзыв пользователя:

{comment}
```

Ожидаемый ответ модели (только JSON):

```json
{"tone":"negative","comment":"Краткое резюме на русском…"}
```

Парсер также снимает обёртку markdown code fence (```json … ```), если модель её вернула.

---

## 6. Что сделано с помощью AI

Проект разрабатывался с помощью **Cursor Agent** и оркестрации задач (planner → worker → reviewer → documenter). Основные фичи реализованы AI-агентами по планам в `.cursor/ai_docs/`.

### Какие части кода генерировались

| Область | Примеры артефактов |
|---------|-------------------|
| Contact API | Controller, Request/Response DTO, Handler, Entity `UserFeedback` |
| Async emails | Messenger message/handler, Twig-письма, Mailer-сервис |
| Rate limiting | `ContactFeedbackRateLimiter`, `ApiIpRateLimiter`, subscribers, `rate_limiter.yaml` |
| Error handling | Problem Details pipeline, tagged exception handlers |
| API logging | `ApiRequestLoggingSubscriber`, канал Monolog `api_request` |
| AI review | `ContactFeedbackReviewer`, Messenger AI flow, entity/migration `UserFeedbackReview`, `ai.yaml` |
| Инфраструктура | `start`, Docker Compose, nginx, Dockerfile |
| Документация | планы, feature-docs, ADR в `.cursor/ai_docs/` |

### Какие промпты / задачи использовали (по смыслу)

Типичные постановки для оркестрации (сокращённо):

- «После сохранения контакта асинхронно отправить письма админу и пользователю через Messenger»
- «Ограничить `POST /api/contact` до 1 запроса на email в минуту; 429 + Retry-After в Problem Details»
- «Логировать каждый `/api` запрос (method, path, ip, status, duration), без body и Authorization»
- «Асинхронно через Symfony AI generic platform классифицировать тон отзыва и сохранить ревью; HTTP не зависит от AI»
- «Убрать Neutral-fallback при битом JSON; вынести обработку ошибок Messenger в tagged handlers; промпты и логи на русском; переименовать `POLZA_*` → `GENERIC_*`»

### Что пришлось исправлять вручную / по ревью

По итогам code review и follow-up оркестраций:

1. **Убран fallback на `neutral`** при неизвестном/битом `tone` — теперь `UnparseableAIReviewResponseException` и отсутствие строки ревью.
2. **Отвязка от бренда провайдера** — `POLZA_AI_*` / `polza` → нейтральные `GENERIC_*` и `ai.platform.generic.default`.
3. **Вынос политики ошибок AI** из толстого Messenger-handler в mapper + tagged handlers (позже — единый стиль с HTTP pipeline).
4. **Локализация** — операционные логи, тексты исключений и AI-промпты на русском; JSON-протокол (`tone` values) остался на английском.
5. **Доработка failed-message pipeline** — отдельный subscriber/mapper для исчерпанных retry, без жёсткой связки с одной фичей.
6. **Ручная проверка на стенде** — curl к `internet-lab.local`, Mailpit UI, логи `api_request`, поведение 422/429/201.

Итоговая политика: AI ускоряет реализацию и документацию; архитектурные замечания (SRP, fail-hard, naming, graceful degradation) закрываются ревью и точечными правками.

---

## 7. Хранение данных

### Логи запросов

Канал Monolog: **`api_request`**.

- Подписчик `ApiRequestLoggingSubscriber` засекает время на `REQUEST` и пишет лог на `RESPONSE`
- Сервис `ApiRequestLogger` пишет сообщение `API-запрос` (level `info`)

Поля контекста: `method`, `path`, `query` (если есть), `ip`, `status`, `route`, `duration_ms`.

**Не пишутся:** тело запроса/ответа, `Authorization`, cookies, файлы.

Куда пишется:

| Окружение | Куда |
|-----------|------|
| `dev` | `back/var/log/dev_api_request.log` |
| `test` | `back/var/log/test_api_request.log` |
| `prod` | `back/var/log/prod_api_request.log` (JSON formatter) |

Канал исключён из `fingers_crossed` буфера основного handler’а, чтобы успешные запросы тоже попадали в лог.

### Rate limiting

Конфиг: `back/config/packages/rate_limiter.yaml` (политика `fixed_window`, storage — cache Symfony).

| Лимитер | Ключ | Лимит | Где |
|---------|------|-------|-----|
| `api_ip` | IP клиента | **100** запросов / **1 минуту** | все пути `/api*` (`ApiIpRateLimitSubscriber`, priority 100) |
| `contact_feedback` | `strtolower(trim(email))` | **1** запрос / **1 минуту** | до persist в `ContactFeedbackHandler` |

При отказе — `TooManyRequestsHttpException` → Problem Details `429` + `Retry-After`.  
Ограниченный по email запрос **не** создаёт запись в БД и **не** ставит сообщения в очередь.

> На нескольких инстансах приложения нужен **общий** cache backend для глобального лимита; локально достаточно cache по умолчанию.

Сущности в PostgreSQL:

- `user_feedback` — исходное обращение
- `user_feedback_review` — результат AI (tone + comment), OneToOne
- таблицы Messenger (`messenger_messages` и failed) — очередь Doctrine transport

---

## Лицензия

Proprietary.
