# Pontua

Sistema de controle de ponto e frequência de colaboradores, construído com Laravel 12 e Filament 5.

## Funcionalidades

- Gestão de colaboradores, turnos e departamentos
- Registro de ponto (entrada/saída manhã e tarde)
- Folha de frequência com exportação em PDF e Excel
- Gestão de feriados (nacionais, recorrentes e parciais)
- Widget de registro de ponto no dashboard
- Controle de acesso por perfis (Admin, Gestor, Colaborador)

## Requisitos

- PHP 8.4+
- Node.js 22+
- Composer 2+
- SQLite (local) ou PostgreSQL (produção)

## Instalação local

```bash
# Clonar o repositório
git clone <url-do-repositorio> pontua
cd pontua

# Instalar dependências e configurar o projeto
composer setup
```

O comando `composer setup` executa automaticamente:
- Instalação das dependências PHP e Node.js
- Criação do `.env` a partir do `.env.example`
- Geração da `APP_KEY`
- Migrations do banco de dados
- Build dos assets (Vite)

### Seed de dados de teste (opcional)

```bash
php artisan db:seed
```

Cria usuários de teste, colaboradores, registros de ponto e feriados.

| Usuário | Email | Senha | Perfil |
|---|---|---|---|
| Administrador | admin@pontua.test | password | Admin |
| Gestor | gestor@pontua.test | password | Gestor |
| Maria Silva | maria@pontua.test | password | Colaborador |
| João Santos | joao@pontua.test | password | Colaborador |

## Executando

```bash
composer run dev
```

Inicia simultaneamente o servidor PHP, queue worker, logs (Pail) e o Vite dev server.

Acesse o painel em: [http://localhost:8000/pontua](http://localhost:8000/pontua)

## Testes

```bash
php artisan test --compact
```

## Deploy (Render)

O projeto está configurado para deploy no [Render](https://render.com) com Docker.

### Via Blueprint (automático)

1. No Render Dashboard, clique **New > Blueprint**
2. Conecte o repositório Git
3. O `render.yaml` configura o serviço automaticamente
4. Preencha as variáveis marcadas como `sync: false`:
   - `APP_KEY` — gere com `php artisan key:generate --show`
   - `APP_URL` — domínio atribuído pelo Render (ex: `https://pontua.onrender.com`)
   - `DATABASE_URL` — connection string do PostgreSQL

### Variáveis de ambiente obrigatórias

| Variável | Valor |
|---|---|
| `APP_KEY` | `base64:...` (gerar com artisan) |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://seu-dominio.onrender.com` |
| `DB_CONNECTION` | `pgsql` |
| `DATABASE_URL` | `postgresql://user:pass@host:port/db?sslmode=require` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `LOG_CHANNEL` | `stderr` |

O primeiro deploy cria automaticamente um usuário admin:
- **Email:** admin@pontua.com
- **Senha:** password (trocar após o primeiro acesso)

## Stack

- [Laravel 12](https://laravel.com)
- [Filament 5](https://filamentphp.com)
- [Livewire 4](https://livewire.laravel.com)
- [Tailwind CSS 4](https://tailwindcss.com)
- [Pest 4](https://pestphp.com)
