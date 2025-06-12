# 🔄 Laravel Reverb – Event Dispatcher (GraphQL)

Este projeto é um **serviço de broadcast via WebSocket**, implementado com **Laravel 12** e **Laravel Reverb**, com suporte a **GraphQL via Lighthouse**.  
Ele funciona como um **dispatcher de eventos**, permitindo que aplicações legadas façam chamadas GraphQL para disparar mensagens em canais WebSocket, recebidas em tempo real por clientes conectados.

---

## ⚙️ Requisitos

- PHP 8.2 ou superior
- Composer
- MariaDB
- Laravel 12
- Laravel Reverb
- Laravel Lighthouse (GraphQL)

---

## 🚀 Como rodar localmente

### 1. Clone o repositório

```bash
git clone git@github.com:Leo-Fernandes-sh3/laravel-reverb.git
cd laravel-reverb
```

### 2. Instale as dependências

```bash
composer install
```

### 3. Configure o ambiente

```bash
cp .env.example .env
php artisan key:generate
```
No arquivo .env, configure o banco de dados e os dados do Reverb:

```bash
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_reverb
DB_USERNAME=root
DB_PASSWORD=sua_senha_aqui

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=756580
REVERB_APP_KEY=bcpo0zdaoqapn3jfeyxn
REVERB_APP_SECRET=ittrkdd0jnfhmugq6jzo
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 4. Execute as migrations

```bash
php artisan migrate
```

### 5. Inicie os servidores

Servidor HTTP:
```bash
php artisan serve
```

Servidor WebSocket (Reverb):
```bash
php artisan reverb:start
```

### 🧠 GraphQL com Lighthouse
Este projeto usa o pacote `nuwave/lighthouse` para definir e consumir APIs via GraphQL.
Você encontrará os arquivos de schema em:

```bash
/graphql/schema.graphql
```

Para testar suas queries, recomenda-se o uso de ferramentas como:
- Altair GraphQL
- GraphiQL

### 🧩 (Opcional) Lighthouse IDE Helper
Caso deseje autocomplete para **GraphQL** no **PHPStorm** ou **VS Code**, você pode instalar o pacote abaixo:

```bash
php artisan lighthouse:ide-helper
```
Isso irá gerar um `arquivo .graphql.schema` com sugestões automáticas para queries, mutations e tipos.

