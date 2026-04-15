# Analise do Projeto

## Resumo

O projeto `ConectaSolidaria` e uma aplicacao web em PHP puro, com estrutura MVC leve e renderizacao server-side. O fluxo principal cobre campanhas, cadastro/login, registro de doacoes, recebimento administrativo, estoque e distribuicao.

## Arquitetura Atual

- Entrada unica em `index.php` com roteamento por `?page=`.
- Controllers em `controllers/` com regras de sessao, validacao e orquestracao.
- Persistencia em `models/dao/` com `mysqli` e DTOs em `models/dto/`.
- Views PHP em `views/` com layout parcial e HTML renderizado no servidor.
- Banco centralizado em `bd.sql`, incluindo schema e carga inicial.
- Recursos auxiliares em `utils/` para PDF e QR Code.

## Pontos Fortes

- Estrutura de pastas ja separa controller, DAO, DTO, view e utilitarios.
- Existe `SessionManager` centralizando autenticacao, flash message e CSRF.
- Boa parte das consultas ja usa prepared statements.
- Fluxos principais de doacao e recebimento usam transacao.
- O schema em `bd.sql` esta relativamente completo e documenta o dominio.

## Pontos de Atencao

- O projeto mistura camadas modernas com trechos ainda acoplados e herdados.
- `index.php` concentra muitas rotas manualmente, o que aumenta risco de divergencia.
- O banco e idempotente em partes, mas `bd.sql` acumula schema, seed e migracoes no mesmo arquivo.
- Existem regras de negocio hardcoded por nome de ponto de coleta e por titulo de campanha.
- Ha duplicacao visual entre views completas e o layout base.
- O diretorio `assets/uploas` indica convencao herdada e nome possivelmente incorreto.

## Acoplamentos Reais do Sistema

### Frontend

- Views publicas e autenticadas dependem de dados ja prontos vindos dos controllers.
- O CSS principal esta centralizado em `assets/css/style.css`, mas algumas telas admin usam muito estilo inline na propria view.

### Backend

- Controllers fazem validacao de fluxo, mas tambem conhecem detalhes de status e mensagens.
- DAOs refletem diretamente o schema e sao o ponto certo para qualquer mudanca de persistencia.

### Banco

- `bd.sql` e a referencia real de entidades, relacionamentos e seeds.
- Mudancas em colunas e constraints exigem atualizacao imediata de DAOs, DTOs e formularios.

## Riscos Tecnicos

1. Alterar tabela ou status sem ajustar DAO/controller quebra o fluxo silenciosamente.
2. Criar view nova sem CSRF ou sem `SessionManager` reabre falhas em acoes sensiveis.
3. Mudar nomes de pontos/campanhas afeta filtros hardcoded em controllers.
4. Evoluir admin sem rever `bd.sql` pode gerar inconsistencias entre tela e schema.

## Direcao Recomendada

1. Tratar `bd.sql` como contrato de dominio.
2. Evoluir primeiro pelo backend e persistencia, depois refletir na interface.
3. Isolar regras hardcoded em metodos ou configuracoes internas antes de expandir funcionalidade.
4. Padronizar uso de layout e reduzir HTML/CSS duplicado nas views administrativas.
5. Validar sempre o fluxo completo: rota, controller, DAO, view e schema.
