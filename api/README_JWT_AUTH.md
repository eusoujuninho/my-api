# Autenticação JWT para API Platform

Este documento contém instruções para completar a configuração de autenticação JWT na sua API Symfony.

## Instalação do Pacote JWT

Instale o pacote JWT:

```bash
composer require lexik/jwt-authentication-bundle
```

## Gerar Chaves JWT

Execute o seguinte comando para gerar as chaves JWT:

```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

Quando solicitado, use a mesma senha definida na variável `JWT_PASSPHRASE` no seu arquivo `.env`.

## Atualizar o Arquivo de Rotas

Adicione estas linhas em `config/routes.yaml`:

```yaml
auth:
    path: /api/login
    methods: ['POST']
    controller: App\Controller\AuthController::login

api_me:
    path: /api/me
    methods: ['GET']
    controller: App\Controller\AuthController::me
```

## Executar a Migração

Execute a migração para criar a tabela de usuários:

```bash
php bin/console doctrine:migrations:migrate
```

## Criar um Usuário de Teste

Você pode criar um usuário de teste usando o console:

```bash
php bin/console doctrine:query:sql "INSERT INTO user (email, roles, password, name) VALUES ('admin@example.com', '[\"ROLE_ADMIN\"]', '\$2y\$13\$yBJTQvDnbSf5NWwX.OXP.uFkiC7G3u7M91MGbXn9iR0lGGIc2sZZe', 'Admin')"
```

Esta senha é "password" hashada. Use apenas para testes.

## Testar a Autenticação

Você pode testar a autenticação com uma requisição POST para `/api/login`:

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

A resposta deverá conter um token JWT que poderá ser usado nas solicitações subsequentes no header de autenticação:

```
Authorization: Bearer {token}
``` 