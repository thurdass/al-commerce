# AL Commerce

> Encontre produtos disponíveis no comércio local e fale diretamente com a loja.

O AL Commerce é uma plataforma de vitrines virtuais para o comércio local, desenvolvida com PHP e Laravel. A proposta é permitir que lojistas divulguem suas lojas e produtos em páginas públicas e que os clientes entrem em contato pelo WhatsApp para negociar diretamente com a loja.

Este README é provisório e acompanha o desenvolvimento do projeto em etapas.

## Estado atual

As Etapas 1 e 2 estão implementadas. A base de dados inclui:

- Models `User`, `Store` e `Product`, com relacionamentos Eloquent.
- Migrations com chaves estrangeiras e restrições de unicidade.
- Enum `ProductAvailabilityStatus` para disponibilidade dos produtos.
- Factories e testes de persistência, relacionamentos e integridade dos dados.
- SQLite para desenvolvimento local e banco em memória para os testes.

A área do lojista já permite:

- Criar uma conta, entrar e sair, com autenticação por sessão.
- Criar uma única loja, com slug gerado automaticamente a partir do nome.
- Editar os dados da própria loja e ativá-la ou desativá-la.
- Consultar um dashboard com nome, slug, status, cidade e quantidade de produtos existentes.

As páginas usam Blade e CSS básico, sem dependências novas de frontend. O gerenciamento de produtos, categorias, imagens, métricas, integração com WhatsApp e páginas públicas continuam reservados para etapas posteriores.

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

Para atualizar uma instalação da Etapa 1, aplique as migrations com `php artisan migrate`. A nova migration torna `stores.category` opcional e preserva os registros, as chaves estrangeiras e os índices existentes.

## Fluxo do lojista

1. Acesse `/cadastro` e crie sua conta. O cadastro já inicia a sessão e redireciona para o dashboard.
2. No dashboard, use **Criar minha loja** para cadastrar nome, descrição, WhatsApp, cidade, UF, endereço opcional e status.
3. Após criar a loja, use **Editar loja** para alterar seus dados ou ativá-la/desativá-la.
4. Use **Sair** para encerrar a sessão.

O campo WhatsApp armazena apenas o número, com 10 a 15 dígitos, incluindo código do país e DDD. Ainda não há botão de contato ou integração.

### Rotas disponíveis

| Método | Caminho | Acesso / finalidade |
| --- | --- | --- |
| GET | `/` | Entrada da aplicação, com links de acesso. |
| GET / POST | `/cadastro` | Formulário e cadastro de visitantes. |
| GET / POST | `/login` | Formulário e autenticação de visitantes. |
| POST | `/logout` | Encerramento da sessão autenticada. |
| GET | `/dashboard` | Dashboard do lojista autenticado. |
| GET | `/minha-loja/criar` | Formulário para usuário autenticado sem loja. |
| POST | `/minha-loja` | Criação da loja do usuário autenticado. |
| GET | `/minha-loja/{store}/editar` | Edição acessível apenas ao proprietário. |
| PUT / PATCH | `/minha-loja/{store}` | Atualização acessível apenas ao proprietário. |

`{store}` é o ID interno da loja. Essas rotas pertencem à área autenticada e não são as URLs públicas planejadas para o catálogo.

### Autenticação e autorização

- A autenticação usa o guard `web` do Laravel, com renovação da sessão ao entrar e invalidação ao sair.
- As senhas são armazenadas como hash. O cadastro exige confirmação, pelo menos 8 caracteres e respeito ao limite de 72 bytes do bcrypt; caracteres multibyte podem ocupar mais de um byte.
- Após cinco tentativas de login com credenciais inválidas, a combinação de e-mail e IP fica bloqueada por um minuto. Um login bem-sucedido limpa o contador.
- Os formulários usam proteção CSRF e Form Requests para validação, com mensagens em português.
- A `StorePolicy` controla criação e edição. Acesso à loja de outro usuário retorna 404; tentativa de criar uma segunda loja retorna 403, inclusive quando a primeira está inativa.
- `user_id` vem exclusivamente da sessão autenticada. Campos enviados fora da validação, incluindo `user_id` e `slug`, são ignorados.

## Modelagem inicial

| Entidade | Relações e regras |
| --- | --- |
| `User` | Pode existir sem loja e possuir no máximo uma `Store`. |
| `Store` | Pertence a um `User`, possui vários produtos e tem slug único na plataforma. |
| `Product` | Pertence a uma `Store` e tem slug único dentro dessa loja. |

Os slugs de loja são gerados no servidor. Por exemplo, `Tech Bahia` produz `tech-bahia`; colisões recebem sufixos como `tech-bahia-2`. Nomes sem uma representação utilizável usam `loja` como base. Ao editar, o slug só é recalculado se o nome mudar, desconsiderando o próprio registro na busca de conflitos. A gravação trata colisões concorrentes e mantém a unicidade garantida pelo banco.

O preço usa `decimal(10,2)` e cast `decimal:2`. O estoque é opcional: `null` representa uma quantidade não informada, enquanto `0` representa estoque zerado.

Os valores de disponibilidade são `AVAILABLE`, `LOW_STOCK`, `OUT_OF_STOCK` e `INACTIVE`. O campo `active` permite registrar a ativação ou desativação de lojas e produtos. Nesta etapa, não há cálculo automático de disponibilidade a partir do estoque nem sincronização automática entre `active` e o status.

A exclusão de um usuário remove sua loja e os produtos vinculados; a exclusão de uma loja remove seus produtos. Desativar um registro preserva seus dados.

A categoria da loja é um campo de texto opcional e não aparece nos formulários desta etapa. A entidade `Category` e o vínculo `category_id` do produto ainda não existem. Logo e capa permanecem apenas como campos na estrutura de dados, sem upload implementado.

Ao reverter a migration que torna a categoria opcional, valores `null` são convertidos para texto vazio para atender à restrição anterior. Categorias já preenchidas e registros vinculados são preservados.

## Testes

Execute a suíte completa:

```bash
composer test
```

Para executar apenas os testes dos models:

```bash
php artisan test --compact tests/Feature/Models
```

Para executar os testes dos fluxos HTTP:

```bash
php artisan test --compact tests/Feature/Http
```

Os testes utilizam SQLite em memória, configurado em `phpunit.xml`. A cobertura inclui autenticação, sessões, limite de tentativas, validação, autorização, slugs, ativação/desativação, dashboard, relacionamentos e preservação dos dados durante a migration de categoria e sua reversão.

## Organização

```text
app/Models/                        Models User, Store e Product
app/ProductAvailabilityStatus.php  Enum de disponibilidade
app/Http/Controllers/              Autenticação, loja e dashboard
app/Http/Requests/                 Validação e autorização das entradas
app/Actions/                       Geração de slug e gravação da loja
app/Policies/                      Permissões da loja
app/Rules/                         Validação compatível com bcrypt
database/migrations/               Estrutura do banco
database/factories/                Dados para testes
resources/views/                   Páginas Blade e formulário compartilhado
routes/web.php                     Rotas da aplicação
tests/Feature/                     Testes de models e fluxos da aplicação
config/database.php                Configuração das conexões
```

As próximas etapas serão definidas e implementadas mediante autorização, mantendo o foco no MVP.
