# Arquitetura API — Laravel

API REST desenvolvida com PHP e Laravel para gerenciamento de produtos, utilizando arquitetura em camadas, orientação a objetos, injeção de dependências e testes automatizados.

Projeto educacional voltado ao aprendizado de boas práticas de desenvolvimento backend.

## Tecnologias utilizadas

- PHP 8.3
- Laravel 9
- MySQL 8.4
- Docker e Laravel Sail
- Eloquent ORM
- PHPUnit e Mockery
- SQLite em memória para testes
- Git e GitHub

## Arquitetura do projeto

A aplicação separa responsabilidades para facilitar a manutenção e os testes.

```text
Requisição HTTP
      |
      v
Form Request (validação)
      |
      v
ProductController
      |
      v
ProductService
      |
      v
ProductRepositoryInterface
      |
      v
ProductRepository
      |
      v
Product (Eloquent ORM)
      |
      v
MySQL
```

Na resposta, o `ProductResource` transforma os dados em JSON padronizado.

### Responsabilidades

- Controller: recebe requisições HTTP e delega operações ao Service.
- Service: coordena operações da aplicação.
- Repository: concentra operações de acesso aos dados com Eloquent.
- Interface: define o contrato do Repository.
- Form Requests: validam os dados recebidos.
- API Resource: define os campos das respostas JSON.
- Exception Handler: personaliza respostas de erro, incluindo HTTP 404.

## Funcionalidades

- Cadastro, listagem, consulta, atualização e exclusão de produtos
- Validação de dados
- Tratamento de erros HTTP
- Respostas JSON padronizadas
- Testes automatizados

## Endpoints da API

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/products` | Lista produtos |
| POST | `/api/products` | Cadastra produto |
| GET | `/api/products/{id}` | Consulta produto |
| PUT | `/api/products/{id}` | Atualiza produto |
| DELETE | `/api/products/{id}` | Exclui produto |

### Exemplo de cadastro

Requisição:

```http
POST /api/products
Content-Type: application/json
Accept: application/json
```

```json
{
  "name": "Mouse Gamer",
  "price": 150.00
}
```

Resposta de exemplo — HTTP 201:

```json
{
  "data": {
    "id": 1,
    "name": "Mouse Gamer",
    "price": "150.00"
  }
}
```

### Exemplo de produto inexistente

Requisição:

```http
GET /api/products/99999
Accept: application/json
```

Resposta — HTTP 404:

```json
{
  "message": "Produto não encontrado."
}
```

## Validação de dados

O cadastro e a atualização utilizam Form Requests.

| Campo | Regras |
|---|---|
| `name` | Obrigatório, texto, máximo de 255 caracteres |
| `price` | Obrigatório, numérico, maior ou igual a zero |

Dados inválidos retornam HTTP `422 Unprocessable Entity`.

## Testes automatizados

Foram executados com sucesso 16 testes automatizados.

### Feature Tests — 11 testes

- Cadastro e listagem de produtos
- Consulta por ID e consulta de produto inexistente
- Atualização e exclusão de produtos
- Validação de preço negativo e nome obrigatório
- Validação de atualização inválida
- Atualização e exclusão de produtos inexistentes

### Unit Tests — 5 testes

Testes do `ProductService` com mocks do `ProductRepositoryInterface`:

- `getAll()`
- `findById()`
- `create()`
- `update()`
- `delete()`

Os testes unitários não dependem de banco de dados real.

### Executando os testes

Com as dependências instaladas e os containers em funcionamento:

```bash
docker compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: laravel.test php artisan test --filter='ProductTest|ProductServiceTest'
```

Resultado registrado durante o desenvolvimento:

```text
Tests: 16 passed
```

Os Feature Tests usam SQLite em memória para isolar os dados do MySQL de desenvolvimento.

## Executando o projeto

### Pré-requisitos

- Docker e Docker Compose
- Git
- PHP e Composer compatíveis com o projeto para instalação inicial

### Configuração

Clone o repositório após sua publicação:

```bash
git clone https://github.com/jota12344/arquitetura-api.git
cd arquitetura-api
```

Instale as dependências:

```bash
composer install
```

Crie o arquivo de ambiente e gere a chave:

```bash
cp .env.example .env
php artisan key:generate
```

Configure as credenciais do banco no `.env` de acordo com o Docker Compose. Em seguida:

```bash
docker compose up -d --build
docker compose exec laravel.test php artisan migrate
```

A API estará disponível em `http://localhost:8082/api/products`, conforme a configuração das portas.

Observação: a instalação limpa do MySQL ainda precisa ser validada antes da publicação. As instruções acima pressupõem credenciais de banco corretamente configuradas.

## Organização dos arquivos

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── ProductController.php
│   ├── Requests/
│   │   ├── StoreProductRequest.php
│   │   └── UpdateProductRequest.php
│   └── Resources/
│       └── ProductResource.php
├── Interfaces/
│   └── ProductRepositoryInterface.php
├── Models/
│   └── Product.php
├── Repositories/
│   └── ProductRepository.php
└── Services/
    └── ProductService.php

tests/
├── Feature/
│   └── ProductTest.php
└── Unit/
    └── ProductServiceTest.php
```

## Objetivo do projeto

Praticar arquitetura em camadas, separação de responsabilidades, orientação a objetos, interfaces, injeção de dependências, APIs REST, validação, tratamento de exceções e testes automatizados.

## Autor

Jonathan Figueiredo de Brito

Estudante de Análise e Desenvolvimento de Sistemas, com foco em desenvolvimento backend utilizando PHP e Laravel.

GitHub: https://github.com/jota12344

## Observações

Este projeto possui finalidade educacional e de portfólio. Os endpoints de produtos ainda não possuem autenticação e autorização. Antes de utilizar a aplicação em produção, será necessário implementar controles de acesso e revisar as configurações de segurança.
