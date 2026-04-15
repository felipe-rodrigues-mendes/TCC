---
name: conectasolidaria-qa
description: Validar regressao funcional do projeto ConectaSolidaria. Use quando precisar revisar o impacto de mudancas em rotas, controllers, views, DAOs e `bd.sql`, montando checklist manual focado em autenticacao, doacoes, admin, estoque e consistencia de status.
---

# Conectasolidaria Qa

## Overview

Validar o sistema por fluxo de negocio, nao apenas por arquivo alterado. Sempre checar autenticacao, persistencia e reflexo visual.

## Checklist Base

1. Acesso publico:
   Confirmar home, sobre, contato e pontos de coleta.
2. Autenticacao:
   Confirmar cadastro, login, logout e redefinicao de senha.
3. Doacao:
   Confirmar criacao, edicao, exclusao, comprovante PDF e dashboard.
4. Admin:
   Confirmar recebimento, campanhas, imagem, usuarios, estoque e distribuicao.
5. Consistencia:
   Confirmar mensagens flash, status exibidos e dados persistidos.

## Guardrails

- Se mudar banco, revisar inserts e selects reais do fluxo.
- Se mudar nome de campo, revisar form, controller e DAO.
- Se mudar status, revisar comparacoes em PHP e labels em tela.
- Se mudar permissao, testar acesso logado e nao logado.

## Resultado Esperado

Checklist objetivo para detectar regressao funcional antes de considerar a tarefa pronta.
