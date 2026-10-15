# AL Commerce
<img width="1254" height="1254" alt="image" src="https://github.com/user-attachments/assets/b9ebc584-9659-46a9-a4c2-be913d8c0d66" />

> Encontre produtos disponíveis no comércio local e fale diretamente com a loja.

O AL Commerce é uma plataforma de vitrines virtuais para o comércio local, desenvolvida com PHP e Laravel. A proposta é permitir que lojistas divulguem suas lojas e produtos em páginas públicas e que os clientes entrem em contato pelo WhatsApp para negociar diretamente com a loja.

Este README é provisório e acompanha o desenvolvimento do projeto em etapas.

## Estado atual

As Etapas 1 e 2 estão implementadas. A base de dados inclui:

- Models `User`, `Store` e `Product`, com relacionamentos Eloquent.
- Migrations com chaves estrangeiras e restrições de unicidade.
- Enum `ProductAvailabilityStatus` para disponibilidade dos produtos.
- Factories e testes de persistência, relacionamentos e integridade dos dados.
- MySQL para desenvolvimento local e SQLite em memória para os testes.

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
- MySQL 8.4 para desenvolvimento.
- SQLite em memória para testes automatizados.
- PHPUnit para testes.
- Laravel Pint para formatação.
- Laravel Boost como ferramenta de apoio ao desenvolvimento.

## Configuração local

É necessário ter PHP, Composer, MySQL e as extensões `pdo_mysql` e `pdo_sqlite` disponíveis. A primeira atende à aplicação; a segunda, aos testes. Para instalar os drivers no Ubuntu com PHP 8.3:

```bash
sudo apt install php8.3-mysql php8.3-sqlite3
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

Disponibilize um banco MySQL chamado `al_commerce`, com charset `utf8mb4` e collation `utf8mb4_unicode_ci`. Use uma conta exclusiva, como `al_commerce_app`, com privilégios somente nesse banco, suficientes para executar as migrations. O usuário `root` fica reservado à administração do servidor.

A configuração de desenvolvimento no `.env` é:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=al_commerce
DB_USERNAME=al_commerce_app
DB_PASSWORD=
```

Preencha `DB_PASSWORD` diretamente no `.env` com a senha real da conta. O arquivo é ignorado pelo Git; `.env.example` contém apenas valores de exemplo, sem senha. Mantenha `DB_URL` ausente, a menos que esteja usando intencionalmente uma URL de conexão, pois ela pode substituir os campos individuais.

Depois de configurar a conexão, aplique as migrations:

```bash
php artisan config:clear --no-interaction
php artisan migrate --no-interaction
php artisan migrate:status --no-interaction
```

**Instalações que já possuem dados em SQLite:** trocar o `.env` e executar migrations não transfere registros. Faça um backup consistente e transfira os dados preservando IDs, relacionamentos, hashes de senhas e timestamps antes de concluir a troca. Compare o conteúdo dos bancos e mantenha o SQLite original como backup. A aplicação passa a usar somente MySQL; SQLite permanece no fluxo automatizado de testes.

Para iniciar o servidor de desenvolvimento:

```bash
php artisan serve
```

O comando informa o endereço local de acesso. Para o frontend, use Node.js 24 e execute `npm ci --ignore-scripts`, seguido de `npm run dev` em outro terminal, ou `npm run build` para gerar os assets estáticos.

Para atualizar uma instalação da Etapa 1, aplique as migrations com `php artisan migrate`. A nova migration torna `stores.category` opcional e preserva os registros, as chaves estrangeiras e os índices existentes.

## Desenvolvimento com Docker (opcional)

O ambiente local acima continua disponível. O Compose oferece uma alternativa com PHP 8.3-FPM (`app`), Nginx (`web`), MySQL 8.4 (`mysql`), Node.js 24 com HMR (`vite`) e o agendador Laravel (`scheduler`). Não há worker de filas. O scheduler executa as tarefas já existentes, incluindo a recuperação de exclusões de conta.

É necessário Docker Engine com Docker Compose v2 ou superior. O MySQL do Docker usa um volume próprio e não publica a porta 3306. Dados, uploads privados, backups de exclusão, cache, dependências e assets do Docker ficam em volumes separados do ambiente local. Os dois ambientes não compartilham os mesmos registros ou imagens; o código-fonte é compartilhado.

Na primeira execução, mantenha seu `.env` atual ou copie `.env.example` para `.env` se ele ainda não existir. O Compose substitui a conexão de banco somente dentro dos containers e preserva as configurações SMTP do `.env`, inclusive Gmail.

```bash
docker compose build app vite
docker compose run --rm --no-deps app composer install --no-interaction --prefer-dist
```

Somente se `APP_KEY` estiver vazio, execute `docker compose run --rm --no-deps app php artisan key:generate --no-interaction`. Esse comando grava a chave no `.env` compartilhado; preserve uma chave já existente.

Prepare o banco isolado e inicie os serviços:

```bash
docker compose run --rm app php artisan migrate --no-interaction
docker compose up -d --wait
```

A aplicação usa a porta **8080** e o Vite a **5175**, ambas acessíveis apenas pela máquina local. As portas foram separadas das usadas no desenvolvimento sem containers. Para personalizar portas, credenciais exclusivas do MySQL Docker ou UID/GID no Linux, copie `.env.docker.example` para `.env.docker` e acrescente `--env-file .env.docker` a **todos** os comandos Compose. O arquivo é ignorado pelo Git e não substitui o `.env` lido pelo Laravel. Ajuste `DOCKER_UID` e `DOCKER_GID` para os valores de `id -u` e `id -g`, depois reconstrua as imagens antes da primeira inicialização dos volumes.

As credenciais padrão do banco são destinadas apenas ao desenvolvimento isolado. Trocar essas variáveis depois de criar o volume não altera senhas já gravadas no MySQL.

Comandos comuns:

```bash
docker compose exec app php artisan test --compact
docker compose exec app php artisan migrate:status --no-interaction
docker compose exec app php artisan schedule:list --no-interaction
docker compose logs --tail=100 app web vite scheduler
docker compose stop
docker compose up -d --wait
docker compose down
```

Os testes continuam usando SQLite em memória e transporte de e-mail em memória, mesmo quando o container recebe variáveis do MySQL ou do Mailpit. A suíte não utiliza o banco de desenvolvimento Docker nem envia e-mails reais. Se tiver criado um cache de configuração, execute `docker compose exec app php artisan config:clear --no-interaction` antes dos testes.

`down` preserva os volumes; `down -v` apaga definitivamente o banco, uploads e demais dados do Docker. Não use a segunda opção para uma instalação com dados que deseja manter. Após mudanças no `composer.lock`, execute `docker compose exec app composer install --no-interaction`; após mudanças no `package-lock.json`, reinicie `vite`. O Vite usa um arquivo de HMR dentro do storage Docker, sem alterar `public/hot` ou o manifest de fontes do ambiente local. Antes de desligar apenas o Vite, gere os assets com `docker compose exec vite npm run build`, depois execute `docker compose stop vite`; o Laravel passa a usar o build estático.

### Mailpit opcional

O Mailpit não é dependência de nenhum serviço. O fluxo padrão continua usando o SMTP do `.env`. Para capturar e-mails de desenvolvimento sem enviar mensagens pelo Gmail, ative explicitamente o perfil e a configuração alternativa:

```bash
docker compose -f compose.yaml -f compose.mailpit.yaml --profile mailpit up -d --wait
```

As mensagens ficam na interface do Mailpit, porta **8025**. O SMTP interno usa `mailpit:1025`, sem publicar essa porta no host. A configuração alternativa afeta somente os containers `app` e `scheduler`; não edite o SMTP do `.env` para isso. Caso tenha executado `config:cache` dentro do Docker, limpe com `php artisan config:clear` no serviço `app` ao trocar o transporte.

Para voltar ao SMTP real:

```bash
docker compose --profile mailpit stop mailpit
docker compose up -d --wait
```

### Imagens para produção

O Dockerfile também oferece os targets `production` (PHP com dependências sem desenvolvimento) e `web-production` (Nginx com assets compilados). Eles não contêm `.env`, uploads locais, dependências Node ou ferramentas de teste. Podem ser construídos com:

```bash
docker build --target production -t al-commerce-app:production .
docker build --target web-production -t al-commerce-web:production .
```

O Compose padrão é de desenvolvimento. Para usar as imagens em produção, forneça configuração e segredos em runtime, `APP_ENV=production`, `APP_DEBUG=false`, domínio HTTPS, persistência para todo `storage`, cache gravável e backup do MySQL; use a mesma imagem PHP no scheduler. Migrations são uma etapa explícita de implantação. Não execute `config:cache` durante o build, pois ele depende da configuração real de runtime. Nenhum deploy é realizado por estes comandos de build.

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

Os testes utilizam SQLite em memória, configurado em `phpunit.xml`. As variáveis `DB_CONNECTION`, `DB_DATABASE`, `DB_URL` e `DB_FOREIGN_KEYS` têm valores forçados para isolar a suíte do MySQL de desenvolvimento. `composer test` limpa o cache de configuração antes de executar os testes. A cobertura inclui autenticação, sessões, limite de tentativas, validação, autorização, slugs, ativação/desativação, dashboard, relacionamentos e preservação dos dados durante a migration de categoria e sua reversão.

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
