# Brooks Construtora - Site Institucional + Painel Admin

Sistema MVC em PHP puro para a Brooks Construtora, com site institucional, área administrativa, sistema de newsletter e geração de revistas digitais com IA (OpenAI).

## Estrutura do Projeto

```
├── app/
│   ├── Config/           # Configurações (banco de dados, app)
│   ├── Controllers/
│   │   ├── Admin/        # Controllers da área admin
│   │   └── Site/         # Controllers do site institucional
│   ├── Core/             # Framework MVC (Router, Database, Auth, etc.)
│   ├── Models/           # Models
│   ├── Services/         # Services (OpenAI, Mail)
│   └── Views/
│       ├── admin/        # Views do painel admin
│       └── site/         # Views do site institucional
├── cron/                 # Scripts de cron (geração automática de revistas)
├── database/
│   └── migrations/       # Arquivos SQL para executar manualmente
├── htmls/                # HTMLs do site WordPress antigo (referência)
├── public/               # Document root do servidor web
│   ├── assets/           # CSS, JS, imagens
│   ├── uploads/          # Uploads de usuário
│   ├── .htaccess         # Rewrite rules
│   └── index.php         # Front controller
└── README.md
```

## Requisitos

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache com mod_rewrite habilitado
- Extensões PHP: pdo, pdo_mysql, curl, mbstring, json

## Instalação

### 1. Configurar o Virtual Host

Aponte o document root para a pasta `/public`.

### 2. Executar as Migrations

Execute os arquivos SQL na ordem dentro de `database/migrations/`:

```
001_create_users_table.sql
002_create_settings_table.sql
003_create_newsletter_table.sql
004_create_magazines_table.sql
005_create_projects_table.sql
```

### 3. Configuração do Banco

As credenciais estão em `app/Config/app.php`:

- **Banco:** brooks_construtora
- **Usuário:** brooks_construtora
- **Senha:** (definida no arquivo de config)

### 4. Acesso Inicial

- **Site:** https://www.brooksconstrutora.com.br
- **Admin:** https://www.brooksconstrutora.com.br/admin
- **Login:** admin@brooksconstrutora.com.br
- **Senha:** Brooks@2026

### 5. Após o primeiro acesso

1. Acesse **Configurações** e preencha SMTP, chave da OpenAI e dados do site
2. Crie os usuários da equipe com as permissões adequadas
3. Configure os e-mails de notificação para aviso de revista gerada

## Sistema de Permissões

| Cargo | Acesso |
|-------|--------|
| Super Admin | Acesso total |
| Admin | Dashboard, configurações, newsletter, usuários, revistas |
| Designer | Dashboard, revistas (edição, upload de capa, revisão) |
| Editor | Dashboard, visualização de revistas |

## Fluxo da Revista Digital

1. **Gerar Temas** → IA sugere temas sobre construção/arquitetura
2. **Gerar Revista** → Seleciona um tema e a IA cria o conteúdo + imagens
3. **Notificação** → E-mail enviado para a equipe informando geração
4. **Revisão** → Designer faz upload da capa, revisa textos
5. **Aprovação** → Primeiro aprova o conteúdo
6. **Preview** → Visualiza como ficará o PDF
7. **Publicação** → Publica e envia para todos os assinantes da newsletter

## Cron Job (Geração Automática)

Adicionar ao crontab do servidor:

```bash
0 9 * * * php /caminho/para/cron/generate_magazine.php >> /var/log/brooks_magazine.log 2>&1
```

## Imagens e Assets

As imagens de logo devem ser colocadas manualmente em:
- `/public/assets/images/logo-brooks.png` (logo escuro, para fundo claro)
- `/public/assets/images/logo-brooks-white.png` (logo branco, para fundo escuro)

Imagens de projetos em:
- `/public/assets/images/projects/`

## Notas Importantes

- **Sem .env**: Todas as configurações dinâmicas ficam no banco de dados, gerenciadas pela tela de Configurações do admin.
- **Sem migrations automáticas**: Os arquivos `.sql` devem ser executados manualmente. Para alterações futuras, crie uma nova migration (nunca edite as existentes).
- **Newsletter**: O campo de inscrição fica no footer de todas as páginas do site.
