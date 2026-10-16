# Projeto inicial

API REST de tarefas pronta para receber, durante a aula, os exemplos de cache da apresentação. O projeto já contém Laravel 13, SQLite, Redis via Docker, Predis, CRUD completo e testes. Nenhuma estratégia de cache, invalidação, Pub/Sub ou rate limiting está implementada.

## Preparação

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
docker compose up -d redis
php artisan serve
```

Se o arquivo `.env` já existir, não é necessário copiá-lo novamente.

## Endpoints

```text
GET    /api/tasks?status=open
POST   /api/tasks
GET    /api/tasks/{id}
PUT    /api/tasks/{id}
DELETE /api/tasks/{id}
```

## Testes

O roteiro manual dos conteúdos da aula está em [`testing.md`](testing.md).

```bash
php artisan test
```
