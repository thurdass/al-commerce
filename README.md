# AL Commerce

> Encontre produtos disponíveis no comércio local e fale diretamente com a loja.

O AL Commerce é uma plataforma de vitrines virtuais para o comércio local, desenvolvida com PHP e Laravel. A proposta é permitir que lojistas divulguem suas lojas e produtos em páginas públicas e que os clientes entrem em contato pelo WhatsApp para negociar diretamente com a loja.

Este README é provisório e acompanha o desenvolvimento do projeto em etapas.

## Estado atual

A primeira etapa implementa a estrutura inicial de dados:

- Models `User`, `Store` e `Product`, com relacionamentos Eloquent.
- Migrations com chaves estrangeiras e restrições de unicidade.
- Enum `ProductAvailabilityStatus` para disponibilidade dos produtos.
- Factories e testes de persistência, relacionamentos e integridade dos dados.
- SQLite para desenvolvimento local e banco em memória para os testes.

Ainda não existem telas do AL Commerce, endpoints de cadastro ou páginas públicas. A rota inicial exibe a página padrão do Laravel. Autenticação, geração automática de slugs, categorias de produtos, imagens múltiplas, métricas e integração com WhatsApp serão desenvolvidas em etapas posteriores.

## Escopo previsto para o MVP

- Cadastro, login e logout de lojistas.
- Criação e edição de uma loja por lojista.
- Cadastro e gerenciamento de produtos, com preço, disponibilidade e imagens.
- Páginas públicas de lojas e produtos.
- Busca por nome e filtro por categoria.
- Botão de contato via WhatsApp com mensagem preenchida.
- Dashboard do lojista com métricas básicas de visualizações e cliques.

O MVP não terá pagamentos, carrinho, checkout ou chat interno. O perfil de administrador e o filtro por cidade ficam para versões futuras.

Rotas públicas planejadas, ainda não implementadas:

```text
/loja/{storeSlug}
/loja/{storeSlug}/produto/{productSlug}
```

## Tecnologias

- PHP 8.3 ou superior, conforme as dependências do Composer.
- Laravel 13.
- SQLite.
- PHPUnit para testes.
- Laravel Pint para formatação.
- Laravel Boost como ferramenta de apoio ao desenvolvimento.

## Configuração local

É necessário ter PHP, Composer e a extensão `pdo_sqlite` disponíveis. Para instalar o driver no Ubuntu com PHP 8.3:

```bash
sudo apt install php8.3-sqlite3
```

Na raiz do projeto, instale as dependências e prepare o ambiente:

```bash
composer install
composer check-platform-reqs
php -r "file_exists('.env') || copy('.env.example', '.env');"
```

Na primeira configuração, caso `APP_KEY` esteja vazio no `.env`, gere a chave:

```bash
php artisan key:generate
```

A configuração padrão do banco no `.env` é:

```dotenv
DB_CONNECTION=sqlite
DB_FOREIGN_KEYS=true
```

Sem definir `DB_DATABASE`, a aplicação utiliza `database/database.sqlite`. Crie o arquivo, caso ainda não exista, e aplique as migrations:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan config:clear
php artisan migrate
```

Para iniciar o servidor de desenvolvimento:

```bash
php artisan serve
```

O comando informa o endereço local de acesso. A etapa atual não exige instalação ou compilação de dependências de frontend.

## Modelagem inicial

| Entidade | Relações e regras |
| --- | --- |
| `User` | Pode existir sem loja e possuir no máximo uma `Store`. |
| `Store` | Pertence a um `User`, possui vários produtos e tem slug único na plataforma. |
| `Product` | Pertence a uma `Store` e tem slug único dentro dessa loja. |

O preço usa `decimal(10,2)` e cast `decimal:2`. O estoque é opcional: `null` representa uma quantidade não informada, enquanto `0` representa estoque zerado.

Os valores de disponibilidade são `AVAILABLE`, `LOW_STOCK`, `OUT_OF_STOCK` e `INACTIVE`. O campo `active` permite registrar a ativação ou desativação de lojas e produtos. Nesta etapa, não há cálculo automático de disponibilidade a partir do estoque nem sincronização automática entre `active` e o status.

A exclusão de um usuário remove sua loja e os produtos vinculados; a exclusão de uma loja remove seus produtos. Desativar um registro preserva seus dados.

A categoria da loja é um campo de texto. A entidade `Category` e o vínculo `category_id` do produto ainda não existem. Os campos de WhatsApp, logo e capa da loja são apenas dados cadastrais, sem integração ou upload implementados.

## Testes

Execute a suíte completa:

```bash
composer test
```

Para executar apenas os testes dos models:

```bash
php artisan test --compact tests/Feature/Models
```

Os testes utilizam SQLite em memória, configurado em `phpunit.xml`. Eles verificam relacionamentos, unicidade dos slugs, limite de uma loja por usuário, status, casts e exclusões em cascata.

## Organização

```text
app/Models/                        Models User, Store e Product
app/ProductAvailabilityStatus.php  Enum de disponibilidade
database/migrations/               Estrutura do banco
database/factories/                 Dados para testes
tests/Feature/Models/               Testes dos models
config/database.php                Configuração das conexões
```

As próximas etapas serão definidas e implementadas mediante autorização, mantendo o foco no MVP.
