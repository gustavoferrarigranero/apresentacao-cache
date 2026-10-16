# Roteiro de verificação da aula

Este roteiro parte da API sem cache e deve ser repetido conforme cada conteúdo da apresentação for implementado. Ao final da aula, os resultados devem coincidir com o `testing.md` do projeto final.

## 1. Preparar o ambiente

Em um terminal:

```bash
docker compose up -d redis
php artisan migrate:fresh
php artisan serve
```

Em outro terminal, defina o endereço da API:

```bash
API_URL=http://127.0.0.1:8000/api
```

## 2. Criar dados para os testes

```bash
curl -i -X POST "$API_URL/tasks" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer aula-cache' \
  -d '{"title":"Estudar cache","description":"Executar os exemplos da aula","status":"open"}'

curl -i -X POST "$API_URL/tasks" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer aula-cache' \
  -d '{"title":"Revisar Redis","description":null,"status":"done"}'
```

Confirme respostas `201 Created` e anote o `id` da primeira tarefa.

## 3. API inicial sem cache

Execute a mesma leitura duas vezes:

```bash
curl -i "$API_URL/tasks?status=open" -H 'Accept: application/json'
curl -i "$API_URL/tasks?status=open" -H 'Accept: application/json'
```

Antes dos exercícios, ambas as requisições consultam o banco. Não deve existir o cabeçalho `X-Cache`.

## 4. Cache da lista

Depois de implementar `Cache::remember` com TTL de 30 segundos, repita:

```bash
curl -i "$API_URL/tasks?status=open" -H 'Accept: application/json'
curl -i "$API_URL/tasks?status=open" -H 'Accept: application/json'
```

Resultado esperado após a implementação:

- primeira leitura com `X-Cache: MISS`;
- segunda leitura com `X-Cache: HIT`;
- cabeçalho `Cache-Control: public, max-age=30`;
- somente tarefas com status `open`;
- chave lógica `tasks:list:open` com TTL de 30 segundos.

## 5. Cache por recurso

Substitua `{id}` pelo identificador anotado:

```bash
curl -i "$API_URL/tasks/{id}" -H 'Accept: application/json'
curl -i "$API_URL/tasks/{id}" -H 'Accept: application/json'
```

Resultado esperado após a implementação:

- primeira leitura com `X-Cache: MISS`;
- segunda leitura com `X-Cache: HIT`;
- cabeçalho `Cache-Control: public, max-age=300`;
- chave lógica `tasks:{id}` independente da chave da lista.

## 6. Tags e invalidação

Preencha o cache da lista e do detalhe, atualize a tarefa e consulte novamente:

```bash
curl -s "$API_URL/tasks?status=open" -H 'Accept: application/json'
curl -s "$API_URL/tasks/{id}" -H 'Accept: application/json'

curl -i -X PUT "$API_URL/tasks/{id}" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"title":"Estudar cache","description":"Conteúdo concluído","status":"done"}'

curl -i "$API_URL/tasks?status=open" -H 'Accept: application/json'
curl -i "$API_URL/tasks/{id}" -H 'Accept: application/json'
```

Resultado esperado após a implementação:

- a escrita limpa todas as chaves da tag `tasks`;
- as duas leituras posteriores retornam `X-Cache: MISS`;
- a tarefa deixa de aparecer na lista `status=open`;
- o detalhe retorna o status `done`.

Repita o cenário com `POST` e `DELETE` para confirmar que todas as escritas invalidam o cache.

## 7. Redis compartilhado

Abra o monitor antes de repetir leituras e escritas:

```bash
docker compose exec redis redis-cli MONITOR
```

Resultado esperado após a implementação:

- a primeira leitura grava uma entrada com expiração;
- a segunda leitura recupera a entrada do Redis;
- a escrita altera o namespace usado pela tag `tasks`.

## 8. Eventos Pub/Sub

Abra um assinante:

```bash
docker compose exec redis redis-cli SUBSCRIBE task.created task.updated task.deleted
```

Em outro terminal, execute um `POST`, um `PUT` e um `DELETE`.

Resultado esperado após a implementação: o assinante recebe um evento correspondente para cada operação, com o identificador da tarefa no payload.

## 9. Rate limiting

Depois de configurar o limite de 60 criações por minuto por token, execute:

```bash
for attempt in $(seq 1 61); do
  curl -s -o /dev/null -w "${attempt}: %{http_code}\n" \
    -X POST "$API_URL/tasks" \
    -H 'Accept: application/json' \
    -H 'Content-Type: application/json' \
    -H 'Authorization: Bearer teste-limite-inicial' \
    -d '{"title":"Teste de limite","description":null,"status":"open"}'
done
```

Resultado esperado após a implementação: as 60 primeiras respostas retornam `201` e a 61ª retorna `429`.

## 10. Verificação automatizada

Durante a implementação, crie ou adapte testes para cada comportamento e execute:

```bash
php artisan test
```

O objetivo final é que a suíte também valide HIT/MISS, invalidação depois de escrita e rate limiting.

