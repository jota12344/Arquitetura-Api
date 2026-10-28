# Documentação da API

A fonte do contrato é [openapi.yaml](openapi.yaml), em OpenAPI 3.0.3. A especificação descreve as dez operações registradas em `routes/api.php`; os campos foram conferidos nos Controllers, FormRequests, Models, Resources e testes. Os exemplos usam dados fictícios, não credenciais ou registros do banco de desenvolvimento.

Para conferir rotas:

```bash
docker compose exec -T laravel.test php artisan route:list --path=api
```

## Visualização local opcional

Uma instalação separada do Swagger UI evita dependências Composer e endpoints adicionais no Laravel. A imagem Docker oficial permite montar o arquivo somente para leitura. Execute da raiz do repositório, se desejar baixar e iniciar essa ferramenta:

```bash
docker run --rm \
  -p 127.0.0.1:8083:8080 \
  --mount "type=bind,source=$(pwd)/docs/openapi.yaml,target=/docs/openapi.yaml,readonly" \
  -e SWAGGER_JSON=/docs/openapi.yaml \
  -e VALIDATOR_URL=none \
  -e SUPPORTED_SUBMIT_METHODS='[]' \
  -e PERSIST_AUTHORIZATION=false \
  swaggerapi/swagger-ui:v5.34.0
```

Acesse `http://127.0.0.1:8083`. O container é separado do Compose e não monta `.env`, banco, tokens ou o restante do repositório. A porta está vinculada apenas ao loopback. O validador externo está desativado, assim como o botão de execução de requisições. Não informe tokens reais para apenas consultar a documentação. O comando não foi executado como parte da alteração da aplicação.

A imagem é uma ferramenta opcional de documentação, não uma dependência de execução da API. Sua versão está explícita para evitar alteração silenciosa do visualizador. Consulte atualizações antes de utilizá-la em outro contexto.

Alternativa: importe `docs/openapi.yaml` no Postman. Configure a URL local e obtenha seu token por login com um usuário já provisionado. Os IDs de exemplo devem ser substituídos por recursos existentes. Requisições de escrita alteram dados: use recursos próprios de teste.

Referências oficiais: [instalação do Swagger UI](https://swagger.io/docs/open-source-tools/swagger-ui/usage/installation/) e [configuração de validação e métodos](https://swagger.io/docs/open-source-tools/swagger-ui/usage/configuration/).

## Validação da especificação

A validação pode ser feita com Swagger Parser, instalado em uma pasta temporária, sem modificar o `package.json` ou o Composer do projeto. Requer Node.js e npm apenas para essa ferramenta opcional:

```bash
npm install --prefix /tmp/arquitetura-openapi-tools \
  --no-save --package-lock=false --ignore-scripts --no-audit --no-fund \
  @apidevtools/swagger-parser@10.1.0
node -e 'require("/tmp/arquitetura-openapi-tools/node_modules/@apidevtools/swagger-parser").validate("docs/openapi.yaml").then(() => console.log("OpenAPI válido")).catch(error => { console.error(error.message); process.exit(1); })'
```

Essa verificação valida a estrutura OpenAPI e referências. Não prova por si só que a implementação cumpre o contrato. A conferência com rotas e respostas dos testes complementa essa análise. O CI atual executa PHPUnit; não instala ferramentas Node nem valida o OpenAPI automaticamente.

## Particularidades do contrato

- Preços de saída podem ser strings ou números, pois Product não define cast; a entrada aceita número ou string decimal validada.
- Os filtros de preço exigem números não negativos, mas não têm a limitação de duas casas imposta na escrita.
- Exemplos de paginação representam o Laravel Resource: `data`, `links` e `meta`, incluindo links de navegação.
- Tokens Sanctum são opacos. Não há cadastro público de usuários nem escrita pública de categorias.
- Categoria inexistente no filtro/corpo retorna 422; na consulta individual, 404.
- PUT exige nome e preço; categoria omitida preserva a associação e `null` remove.
- Mensagens de validação variam com o idioma; o contrato estável identifica campos em `errors`.
- Os schemas de erro 500 descrevem `APP_DEBUG=false`. Em desenvolvimento, debug pode mostrar mais informações.
- HEAD é suportado implicitamente nas rotas GET; métodos inexistentes podem retornar 405 e rotas desconhecidas, 404.

A base normativa é a [especificação OpenAPI 3.0.3](https://spec.openapis.org/oas/v3.0.3.html).
