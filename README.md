# Arquitetura API

API REST para gerenciamento de produtos e consulta de categorias, desenvolvida com PHP e Laravel. Projeto de portfólio voltado ao estudo de arquitetura em camadas, injeção de dependências, autenticação, autorização e testes automatizados.

Produtos podem ser consultados publicamente. Para criar um produto é necessário autenticar-se; somente seu proprietário pode atualizá-lo ou excluí-lo.

## Tecnologias

- PHP 8.3 no ambiente Docker e no CI; Laravel 9.52 e Sanctum 3.
- MySQL 8.4 no desenvolvimento e SQLite em memória nos testes.
- Docker Compose v2 e Laravel Sail.
- PHPUnit 9, Mockery, Eloquent ORM e GitHub Actions.
- OpenAPI 3.0.3 em arquivo versionado, sem pacote adicional na aplicação.

As versões exatas das dependências PHP estão no `composer.lock`; a instalação usa `composer install`.

## Funcionalidades

- CRUD de produtos com validação de entrada e respostas JSON.
- Busca parcial por nome e filtros combináveis por preço mínimo, preço máximo e categoria.
- Paginação de dez registros; produtos ordenados por ID decrescente e links preservando os filtros.
- Categorias com consultas públicas, paginação e ordenação por ID crescente.
- Categoria opcional em produtos, integridade referencial e eager loading.
- Login com Bearer Token, consulta do usuário autenticado e logout que revoga o token atual.
- Policies para restringir alteração e exclusão ao proprietário.
- Request ID, tempo de resposta e logs de conclusão sem corpo da requisição ou cabeçalho Authorization.
- Limite geral de 60 requisições por minuto por usuário ou IP; login também limitado a cinco por minuto por IP.

## Arquitetura

```text
HTTP → validação (FormRequest ou Controller)
     → Controller → Service → Interface → Repository → Model → Banco
                                                       ↓
HTTP ← JSON ← Resource ←────────────────────────── resultado
```

O Controller recebe a requisição e coordena a resposta HTTP. O Service representa as operações da aplicação e recebe o Repository pela interface. O Repository concentra as consultas Eloquent; o Model representa os dados e relacionamentos. O Resource define os campos de saída. FormRequests validam cadastro, atualização e login; os filtros são validados no Controller. Policies decidem se o usuário pode modificar o produto.

As consultas de categorias seguem as mesmas camadas. O `AuthService` usa diretamente o Model User e o Sanctum; a autenticação não possui um Repository próprio.

```text
app/
├── Http/Controllers/   # Produtos, categorias e autenticação
├── Http/Requests/      # Validação dos corpos de entrada
├── Http/Resources/     # Contrato JSON de produtos e categorias
├── Interfaces/         # Contratos dos repositories
├── Repositories/       # Consultas e persistência
├── Services/           # Operações da aplicação
├── Models/             # User, Product e Category
└── Policies/           # Autorização por proprietário
database/               # Migrations, factories e seeders
tests/                  # Feature e Unit
docs/                   # OpenAPI e guias
.github/workflows/      # Integração contínua
```

## Instalação local

Requisitos: Git, Docker com Compose v2, PHP 8.3 e Composer 2 para a instalação inicial. No Windows, utilize WSL2. Não é necessário compilar frontend para utilizar a API.

```bash
git clone "URL_DO_REPOSITORIO"
cd arquitetura-api
composer install
cp .env.example .env
```

Substitua `URL_DO_REPOSITORIO` pela URL do seu clone. Em um ambiente já configurado, preserve o `.env` existente.

Preencha `DB_PASSWORD` e `MYSQL_ROOT_PASSWORD` somente no `.env`, com senhas locais distintas. `openssl rand -hex 24` pode gerar cada valor. No Linux/WSL, ajuste `WWWUSER` e `WWWGROUP` com `id -u` e `id -g`.

```bash
docker compose config --quiet
docker compose up -d --build --wait
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed --class=CategorySeeder
```

O seeder é opcional: cria Eletrônicos, Informática e Acessórios sem duplicar nomes. Não cria usuários nem produtos. A API não oferece cadastro de usuários: para testar o login, provisione um usuário local pelo Tinker (`./vendor/bin/sail artisan tinker`), usando `App\Models\User::create` com os campos `name`, `email` e `password`; preencha `password` com o resultado de `Illuminate\Support\Facades\Hash::make` para a senha escolhida, ou utilize um usuário já existente. Nunca armazene a senha em texto puro nem publique suas credenciais.

Endereço padrão: `http://localhost:8082/api`. Clientes MySQL no host usam `127.0.0.1:3307`; entre containers, a conexão usa `mysql:3306`. As portas podem ser ajustadas no `.env`.

O volume `sail-mysql` preserva o banco. Alterar senhas no `.env` não redefine usuários de um volume existente. Não use comandos de recriação de banco para atualizar um ambiente com dados; aplique migrations incrementais após backup.

Detalhes de permissões, configuração e CI: [guia de ambiente](docs/ambiente.md).

## Endpoints

| Método | Caminho | Acesso | Sucesso |
|---|---|---|---|
| POST | `/api/login` | Público | 200 |
| POST | `/api/logout` | Autenticado | 200 |
| GET | `/api/user` | Autenticado | 200 |
| GET | `/api/products` | Público | 200 |
| GET | `/api/products/{id}` | Público | 200 |
| POST | `/api/products` | Autenticado | 201 |
| PUT | `/api/products/{id}` | Proprietário | 200 |
| DELETE | `/api/products/{id}` | Proprietário | 204 |
| GET | `/api/categories` | Público | 200 |
| GET | `/api/categories/{id}` | Público | 200 |

As rotas GET também aceitam HEAD. Não há PATCH nem endpoints de escrita de categorias.

## Autenticação com Sanctum

Envie `email` e `password` do usuário local para `POST /api/login`, em JSON. A resposta contém `message`, `token` e `token_type: Bearer`. Não se trata de um JWT.

Nas requisições protegidas, envie:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token-retornado-no-login>
```

O Sanctum guarda o hash do token no banco. `POST /api/logout` revoga apenas o token usado na requisição; outros tokens continuam válidos. A configuração atual não define expiração global. Login com credenciais incorretas retorna 422; token ausente, inválido, expirado ou revogado retorna 401. Não salve tokens em arquivos versionados.

O proprietário de um produto é definido pelo usuário autenticado. Enviar `user_id` não permite criar ou transferir produtos para outra pessoa. A consulta pública de produtos não expõe o proprietário.

## Produtos, filtros e paginação

```bash
curl --get 'http://localhost:8082/api/products' \
  --header 'Accept: application/json' \
  --data-urlencode 'name=Mouse' \
  --data-urlencode 'min_price=100' \
  --data-urlencode 'max_price=500' \
  --data-urlencode 'category_id=1' \
  --data-urlencode 'page=2'
```

Utilize um ID de categoria existente. Os filtros são opcionais e combinados por AND. Preços mínimo e máximo são inclusivos e não negativos; o mínimo não pode exceder o máximo. `page` deve ser um inteiro positivo. Nome informado não pode ser vazio e tem limite de 255 caracteres. A busca usa SQL `LIKE`, incluindo o significado dos curingas `%` e `_`; a comparação depende da collation.

Listas retornam `data`, `links` e `meta`. Siga `links.next` para preservar os filtros. Uma busca sem resultados ou uma página além da última retorna 200 com `data: []`.

Corpo de cadastro ou atualização (exemplo fictício):

```json
{
  "name": "Mouse Gamer",
  "price": 150.00,
  "category_id": 1
}
```

`name` e `price` são obrigatórios nos dois métodos. O preço deve estar entre zero e `99999999.99`, com até duas casas decimais; strings em notação científica não são aceitas. Essa restrição de precisão é para escrita, não para os filtros de preço.

`category_id` é opcional e deve apontar para uma categoria existente. Na atualização, omitir o campo preserva a categoria; enviar `null` remove a associação. Produtos anteriores continuam válidos sem categoria. A exclusão de uma categoria pelo banco mantém seus produtos com `category_id = null`.

Exemplo de resposta de criação, HTTP 201:

```json
{
  "data": {
    "id": 1,
    "name": "Mouse Gamer",
    "price": "150.00",
    "category_id": 1,
    "category": { "id": 1, "name": "Informática" }
  }
}
```

IDs e dados são ilustrativos. O preço não tem cast explícito no Model: o MySQL normalmente retorna uma string decimal, enquanto SQLite pode retornar um número. Categoria ausente é representada por `null`. O DELETE bem-sucedido retorna 204 sem corpo. `/api/user` retorna o objeto do usuário diretamente, sem envelope `data`.

## Erros HTTP

| Código | Situação |
|---|---|
| 401 | Autenticação ausente ou token inválido |
| 403 | Tentativa de alterar ou excluir produto de outro proprietário |
| 404 | Recurso inexistente ou rota não encontrada |
| 422 | Entrada inválida ou credenciais de login incorretas |
| 429 | Limite de requisições excedido; confira `Retry-After` |
| 500 | Erro inesperado; com `APP_DEBUG=false`, sem detalhes internos |

Erros da API são JSON mesmo sem `Accept`. Erros de validação incluem `message` e `errors`, com listas de mensagens por campo. O texto pode variar conforme o idioma. A validação do PUT ocorre antes da busca e da Policy: um corpo inválido pode retornar 422 antes de 403 ou 404.

## Testes e integração contínua

```bash
./vendor/bin/sail test
# Execução direta, sem cache do PHPUnit:
docker compose exec -T laravel.test php vendor/bin/phpunit --do-not-cache-result
```

A suíte possui **111 testes**, com **476 assertions** na última execução verificada durante a documentação. Há testes de CRUD, filtros, paginação, relacionamentos, integridade referencial, autenticação, autorização, tokens e erros HTTP, além de testes de Services com Mockery. Os testes de AuthService usam banco; os testes de ProductService e CategoryService isolam o Repository.

O `phpunit.xml` força SQLite em memória e neutraliza `DATABASE_URL`. Não utilize configuração Laravel cacheada ao testar. `RefreshDatabase` prepara o banco isolado na mesma execução; não é necessário executar migrations de teste separadamente.

O [workflow](.github/workflows/tests.yml) executa em push e pull request com PHP 8.3, instala o lock pelo Composer, confere extensões, gera uma chave temporária e executa a suíte. Não requer banco MySQL ou segredos pessoais. A execução remota depende da publicação autorizada no GitHub; a validação local não substitui o CI remoto.

## OpenAPI e Swagger UI

- [Especificação OpenAPI 3.0.3](docs/openapi.yaml): dez operações, schemas, filtros, paginação, autenticação e erros.
- [Guia de documentação](docs/api.md): visualização local opcional no Swagger UI, importação no Postman e validação.

A especificação é mantida manualmente e deve acompanhar mudanças nas rotas, validações e Resources. Nenhuma rota `/docs` ou dependência Swagger foi adicionada ao Laravel.

## Decisões e próximos passos

A arquitetura permite testar a delegação pelos contratos dos repositories sem refatorar a aplicação inteira. A categoria opcional preserva registros anteriores. Eager loading evita uma consulta adicional por produto. Operações atuais gravam um registro por vez; não foram adicionadas transações sem uma necessidade de múltiplas gravações atômicas.

Cache, jobs e filas não foram adicionados: o CRUD atual não possui processamento demorado ou carga medida que justifique essa complexidade. O projeto usa execução síncrona e não exige workers.

Melhorias futuras: revisar a atualização das versões do framework, definir política de expiração de tokens conforme o uso, ampliar testes específicos do MySQL e medir consultas antes de criar novos índices. Sail atende desenvolvimento; a publicação em produção exige configuração própria e revisão final. Não há deploy automatizado neste projeto.
