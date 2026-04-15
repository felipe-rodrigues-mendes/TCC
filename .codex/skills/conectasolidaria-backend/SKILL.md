---
name: conectasolidaria-backend
description: Evoluir o backend PHP MVC do projeto ConectaSolidaria. Use quando houver mudancas em `index.php`, `controllers/`, `models/dao/`, `models/dto/`, `SessionManager` ou em qualquer regra de negocio, autenticacao, validacao, transacao e fluxo de navegacao server-side.
---

# Conectasolidaria Backend

## Overview

Manter controllers enxutos e DAOs como unica camada de SQL. Toda mudanca deve respeitar a cadeia rota -> controller -> DAO -> view.

## Workflow

1. Localizar a rota em `index.php` ou alias relacionado.
2. Revisar o controller dono do fluxo e definir a regra de validacao, permissao e redirecionamento.
3. Ajustar ou criar metodos no DAO usando `prepare` e `bind_param`.
4. Se a operacao tocar varias tabelas, envolver em transacao com `Database::getInstance()`.
5. Confirmar que a view recebe apenas dados prontos, sem carregar regra de negocio extra.

## Guardrails

- Exigir `SessionManager::requireLogin()` ou `requireRole()` em acoes protegidas.
- Exigir CSRF em POST sensivel.
- Nao acessar `$_POST` ou `$_GET` diretamente na view para regras de negocio.
- Nao espalhar SQL em controller.
- Reutilizar nomes de status e convencoes existentes.

## Hotspots

- `controllers/AuthController.php`
- `controllers/DonationController.php`
- `controllers/AdminController.php`
- `controllers/DistributionController.php`
- `controllers/SessionManager.php`
- `models/dao/*.php`

## Resultado Esperado

Fluxo consistente, seguro e alinhado com o schema atual, sem regressao em autenticacao, permissao ou persistencia.
