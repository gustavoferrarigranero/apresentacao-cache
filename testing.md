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

## 11. Preparação para a aula de filas

Antes de criar o primeiro job, valide a infraestrutura:

```bash
docker compose up -d redis
php artisan migrate
php artisan test --filter=QueueInfrastructureTest
php artisan queue:work redis
```

Resultados esperados:

- os testes confirmam a conexão de filas `redis`;
- o Redis responde à aplicação;
- as tabelas `jobs`, `job_batches` e `failed_jobs` existem;
- o worker inicia e aguarda jobs;
- não existe nenhuma classe de job ou chamada `dispatch` no projeto inicial.

## 12. Fluxo prático da apresentação 8

Os passos abaixo descrevem o resultado que será construído durante a aula. O projeto inicial contém apenas a infraestrutura necessária.

### Etapa 1: criar o primeiro job

```bash
php artisan make:job GenerateTaskReport
```

O job deverá:

- implementar `ShouldQueue`;
- receber o identificador do usuário no construtor;
- executar a geração do relatório no método `handle()`;
- usar a fila `reports`.

Depois da criação, confirme que o arquivo existe:

```bash
test -f app/Jobs/GenerateTaskReport.php && echo "Job criado"
```

### Etapa 2: disparar o job pela API

O endpoint criado durante a aula será:

```text
POST /api/reports
```

O controller deverá disparar `GenerateTaskReport` e responder sem aguardar a geração do relatório.

Teste o endpoint:

```bash
curl -i -X POST "$API_URL/reports" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"user_id":1}'
```

Resultado esperado:

```http
HTTP/1.1 202 Accepted
Content-Type: application/json
```

```json
{
  "job_id": "identificador-do-job",
  "status": "queued"
}
```

Neste momento, a resposta `202` confirma que a API aceitou o processamento. Ela não confirma que o relatório terminou.

### Etapa 3: verificar o job no Redis

Antes de iniciar o worker, dispare o endpoint e consulte o tamanho da fila:

```bash
docker compose exec redis redis-cli LLEN queues:reports
```

Resultado esperado: o valor aumenta quando o job entra na fila `reports`.

Também é possível acompanhar os comandos executados no Redis:

```bash
docker compose exec redis redis-cli MONITOR
```

### Etapa 4: executar o job com o worker

Em outro terminal:

```bash
php artisan queue:work redis --queue=reports
```

Dispare novamente `POST /api/reports` e verifique:

- o worker encontra `GenerateTaskReport`;
- o método `handle()` executa;
- o terminal mostra o job como concluído;
- `LLEN queues:reports` retorna `0` após o processamento.

Para consumir somente um job e encerrar:

```bash
php artisan queue:work redis --queue=reports --once
```

### Etapa 5: testar sem executar o job

No teste do endpoint, use `Queue::fake()` para impedir a execução e confirmar apenas o disparo.

O teste deverá verificar:

- resposta HTTP `202`;
- conteúdo `status: queued`;
- disparo de `GenerateTaskReport`;
- envio para a fila `reports`;
- identificador de usuário recebido pelo job.

Depois, crie um teste separado para executar `handle()` e validar o efeito produzido pelo serviço de relatórios.

### Etapa 6: encadear a notificação

Após o primeiro job funcionar, adicione `NotifyReportReady` e construa a sequência:

```text
GenerateTaskReport
NotifyReportReady
```

Verifique que a notificação começa somente depois que o relatório termina. Se o primeiro job falhar, o segundo não deve executar.

### Etapa 7: processar tarefas em lote

O endpoint da apresentação será:

```text
POST /api/tasks/reindex
```

Ele deverá criar um `ReindexTask` para cada tarefa aberta e retornar o identificador do lote.

Verifique:

- quantidade total de jobs no lote;
- progresso enquanto o worker processa os jobs;
- quantidade de jobs pendentes e com falha;
- estado final do lote.

### Etapa 8: limitar integrações no worker

Adicione `RateLimitedWithRedis` ao middleware do job que chama uma API externa.

Dispare vários jobs e confirme que o worker respeita o limite configurado, liberando os jobs excedentes para uma nova tentativa.

### Etapa 9: validar tentativas e backoff

Configure três tentativas com intervalos de 10, 30 e 60 segundos. Force uma falha temporária e execute:

```bash
php artisan queue:work redis --queue=reports --tries=3
```

Verifique:

- o job volta para a fila depois da falha;
- o intervalo aumenta conforme o backoff;
- o job entra em `failed_jobs` depois da última tentativa;
- o método `failed()` registra o erro final.

Comandos úteis:

```bash
php artisan queue:failed
php artisan queue:retry all
php artisan queue:flush
```

### Etapa 10: acompanhar com Horizon

Interrompa o `queue:work` antes de iniciar o Horizon:

```bash
php artisan horizon
```

Acesse:

```text
http://127.0.0.1:8000/horizon
```

No painel, confirme:

- fila `reports`;
- execução de `GenerateTaskReport`;
- duração e throughput;
- jobs concluídos e com falha;
- estado dos workers.

### Checklist do fluxo completo

```text
POST /api/reports
        ↓
202 Accepted
        ↓
Redis armazena GenerateTaskReport em queues:reports
        ↓
Worker ou Horizon consome o job
        ↓
handle() gera o relatório
        ↓
NotifyReportReady informa que o relatório está disponível
```

Ao concluir a aula, execute:

```bash
php artisan test
php artisan queue:work redis --queue=reports --once
php artisan horizon:status
```

