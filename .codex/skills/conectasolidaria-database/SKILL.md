---
name: conectasolidaria-database
description: Evoluir o schema, seeds e contratos de persistencia do projeto ConectaSolidaria. Use quando houver mudancas em `bd.sql`, tabelas, chaves, enums, seeds, nomes de colunas, relacionamento entre entidades ou qualquer alteracao que precise permanecer coerente com os DAOs e DTOs do sistema.
---

# Conectasolidaria Database

## Overview

Tratar `bd.sql` como contrato do dominio. Fazer mudancas aditivas e idempotentes sempre que possivel, e refletir cada alteracao no PHP que consome esse schema.

## Workflow

1. Ler a parte relevante de `bd.sql` e identificar tabelas, constraints e seeds afetadas.
2. Mapear quais arquivos de `models/dao/`, `models/dto/` e `controllers/` dependem dessas colunas ou status.
3. Aplicar a mudanca de schema preservando compatibilidade e evitando quebrar dados existentes.
4. Atualizar SQL, hydration de DTO e validacoes ligadas ao campo alterado.
5. Revisar telas e formularios que exibem ou enviam esse dado.

## Guardrails

- Preferir `ALTER` idempotente, checks por `information_schema` e seeds com `NOT EXISTS`.
- Nao introduzir coluna nova sem revisar inserts, selects e updates dos DAOs.
- Nao alterar nomes de status sem revisar comparacoes em controller, DAO e view.
- Nao remover relacionamento sem avaliar estoque, doacao, campanha e distribuicao.

## Arquivos Alvo

- `bd.sql`
- `models/dao/*.php`
- `models/dto/*.php`
- `controllers/*.php`

## Resultado Esperado

Schema e codigo PHP permanecem sincronizados, sem criar campos "fantasma" presentes na UI mas ausentes no banco.
