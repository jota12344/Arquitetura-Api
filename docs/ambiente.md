# Ambiente de desenvolvimento e CI

Guia básico da etapa 4. A documentação completa da API será preparada na etapa 5.

## Instalação em um clone novo

Requisitos: Git, Docker Engine/Desktop com Compose v2 e PHP 8.3 com Composer 2.
O PHP local precisa das extensões exigidas pelo Composer; use `composer check-platform-reqs` para conferir.
No Windows, execute no WSL2. O runtime PHP 8.3 vem do Sail já fixado no `composer.lock`.

```bash
git clone https://github.com/jota12344/arquitetura-api.git
cd arquitetura-api
composer install
cp .env.example .env
```

O comando `cp` é para um clone novo: preserve o `.env` se ele já existir.
Preencha `DB_PASSWORD` e `MYSQL_ROOT_PASSWORD` no `.env` com duas senhas locais distintas.
Você pode gerar cada uma com `openssl rand -hex 24`. Não publique esses valores.
No Linux/WSL, ajuste `WWWUSER` e `WWWGROUP` com os resultados de `id -u` e `id -g`.

```bash
docker compose config --quiet
docker compose up -d --build --wait
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed --class=CategorySeeder
```

O Compose aguarda o MySQL aceitar uma consulta autenticada antes de iniciar a aplicação.
As migrations são incrementais. O seeder de categorias é opcional e não duplica nomes existentes.
O volume `sail-mysql` mantém os dados entre reinicializações.
Senhas do `.env` inicializam somente volumes novos: alterar o arquivo não redefine usuários de um volume existente.
Em ambientes existentes, preserve as credenciais válidas e faça backup antes de aplicar migrations.

## Portas e permissões

- API: `http://localhost:8082/api/products`; categorias: `/api/categories`.
- MySQL para clientes no host: `127.0.0.1:3307`.
- Entre containers: `DB_HOST=mysql`, `DB_PORT=3306`.
- Ajuste `APP_PORT` e `FORWARD_DB_PORT` se houver conflitos; ajuste também `APP_URL`.
- Não é necessário Node/npm ou Vite para executar a API. A porta Vite foi removida do Compose.
- Redis, Memcached, SMTP, S3 e Pusher não são necessários na configuração atual.
- Execute comandos pelo Sail, que utiliza o usuário `sail`. Evite permissões `777`.
- `storage` e `bootstrap/cache` precisam ser graváveis pelo usuário da aplicação.
- Sail é o ambiente de desenvolvimento, não uma configuração de deploy.

## Testes isolados

```bash
./vendor/bin/sail test
# Ou, diretamente no container:
docker compose exec -T laravel.test php vendor/bin/phpunit --do-not-cache-result
```

O `phpunit.xml` força `APP_ENV=testing`, conexão SQLite, banco `:memory:`, integridade referencial e uma `DATABASE_URL` vazia.
Assim, variáveis MySQL herdadas não selecionam o banco de desenvolvimento. Não execute testes com configuração Laravel previamente cacheada; em um ambiente local com cache, remova-o com `sail artisan config:clear` antes de testar.
As migrations dos testes são executadas pelo `RefreshDatabase` na mesma conexão em memória.
Não é necessário rodar `artisan migrate` separadamente para os testes: outra execução teria um banco em memória diferente.
A suíte não exige MySQL, Redis, filas externas ou credenciais pessoais.

Para executar fora do Docker, use PHP 8.3 com `pdo_sqlite`, `sqlite3`, `mbstring`, XML e demais extensões verificadas por `composer check-platform-reqs`.

## GitHub Actions

`.github/workflows/tests.yml` executa em pushes e pull requests, com PHP 8.3, Composer 2 e SQLite em memória.
Instala as versões do `composer.lock`, confere os requisitos de plataforma, gera uma chave efêmera e executa todos os testes sem copiar um `.env` pessoal.
Não usa serviço MySQL, segredos configurados no repositório, deploy ou permissões de escrita.
As dependências de desenvolvimento incluem PHPUnit e são necessárias no CI.
Não adicionamos novas dependências Composer nem alteramos versões da aplicação.

O workflow só poderá ser confirmado no GitHub após publicação autorizada; validação local não equivale a uma execução remota.
