---
name: conectasolidaria-frontend
description: Evoluir a interface server-side do projeto ConectaSolidaria. Use quando houver mudancas em `views/`, `assets/css/style.css`, navegacao, formularios, layout, responsividade ou experiencia de uso das paginas publicas, autenticadas e administrativas sem migrar a stack para framework frontend.
---

# Conectasolidaria Frontend

## Overview

Preservar a renderizacao PHP no servidor e a linguagem visual existente, reduzindo duplicacao e mantendo formularios coerentes com os controllers.

## Workflow

1. Identificar a view dona da tela e o controller que monta os dados.
2. Confirmar quais variaveis a view recebe e evitar recalcular regra de negocio no template.
3. Fazer ajustes visuais preferencialmente em `assets/css/style.css`.
4. Em formularios, manter nomes de campo e tokens CSRF coerentes com o controller.
5. Validar estados de vazio, erro, sucesso e permissao.

## Guardrails

- Nao quebrar a estrutura de navegacao baseada em `index.php?page=...`.
- Nao inventar dependencia de build frontend.
- Reduzir CSS inline quando virar padrao reutilizavel.
- Preservar labels e mensagens coerentes com status reais do backend.

## Hotspots

- `views/public/*`
- `views/auth/*`
- `views/user/*`
- `views/admin/*`
- `views/layouts/*`
- `assets/css/style.css`

## Resultado Esperado

Tela clara, consistente e integrada ao fluxo PHP atual, sem desalinhar nomes de campos, mensagens ou estados.
