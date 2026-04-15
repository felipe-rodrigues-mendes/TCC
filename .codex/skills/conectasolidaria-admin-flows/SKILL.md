---
name: conectasolidaria-admin-flows
description: Evoluir fluxos administrativos do ConectaSolidaria. Use quando houver mudancas nas telas e regras de campanhas, recebimento de doacoes, estoque, distribuicoes, gestao de usuarios e operacoes restritas a administradores.
---

# Conectasolidaria Admin Flows

## Overview

Tratar o modulo admin como operacao critica. Priorizar permissao, consistencia de status, transacao e rastreabilidade do fluxo.

## Workflow

1. Confirmar que a rota e a acao exigem role `admin`.
2. Mapear impacto em campanha, doacao, estoque, distribuicao ou usuario.
3. Aplicar validacoes de negocio antes de escrever no banco.
4. Usar transacao em operacoes que atualizam status e estoque juntos.
5. Revisar mensagens de retorno e estado exibido na view admin.

## Guardrails

- Nao permitir acao admin sem `SessionManager::requireRole('admin')`.
- Nao atualizar estoque sem sincronizar status da doacao.
- Nao encerrar ou reabrir campanha sem revisar reflexo nas telas publicas e de doacao.
- Nao mudar filtros de ponto ou campanha hardcoded sem localizar todos os usos.

## Hotspots

- `controllers/AdminController.php`
- `controllers/DistributionController.php`
- `models/dao/InventoryDAO.php`
- `models/dao/DistributionDAO.php`
- `models/dao/CampaignDAO.php`
- `views/admin/*`

## Resultado Esperado

Fluxos administrativos permanecem seguros e coerentes com estoque, campanhas e historico de doacoes.
