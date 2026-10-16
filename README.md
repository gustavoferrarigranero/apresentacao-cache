# Projeto inicial

API REST de tarefas pronta para receber os exemplos das aulas de cache e filas. O projeto contém Laravel 13, SQLite, Redis via Docker, Predis, Laravel Horizon, CRUD completo e testes. A conexão de filas já aponta para o Redis, mas o projeto não possui jobs, endpoints de disparo ou chamadas `dispatch` implementadas.

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

Para iniciar o worker quando a aula chegar à criação do primeiro job:

```bash
php artisan queue:work redis
```

O worker ficará aguardando jobs na fila. O projeto inicial não despacha nenhum job por conta própria.

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
