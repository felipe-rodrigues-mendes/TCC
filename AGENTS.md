# ConectaSolidaria Agent Rule

## Contexto

Este projeto e um sistema web MVC simples em PHP puro, com `index.php` como front controller, controllers em `controllers/`, acesso a dados via DAOs em `models/dao/`, DTOs em `models/dto/`, views server-side em `views/`, estilo central em `assets/css/style.css` e schema/seed centralizado em `bd.sql`.

## Regra Principal

Antes de alterar qualquer fluxo, localizar e alinhar os 4 pontos afetados: rota em `index.php`, regra no controller, persistencia no DAO e reflexo na view ou no schema.

## Regras de Desenvolvimento

1. Preservar o MVC atual. Nao mover regra de negocio para views e nao acessar banco diretamente em views.
2. Manter controllers como orquestradores. Validacao, permissao e fluxo ficam no controller; SQL fica no DAO.
3. Usar `prepare` e `bind_param` em consultas com entrada do usuario. Seguir o padrao ja adotado nos DAOs.
4. Toda operacao que escreve em mais de uma tabela deve usar transacao via `Database::getInstance()`.
5. Toda alteracao de formulario ou acao sensivel deve manter validacao de sessao e CSRF via `SessionManager`.
6. Toda mudanca de schema ou seed em `bd.sql` deve ser refletida no codigo PHP correspondente no mesmo trabalho.
7. Preservar os nomes e ciclos de status usados hoje: campanha `ATIVA|ENCERRADA`, doacao `PENDENTE|RECEBIDA|EXCLUIDA`, distribuicao `ENVIADO|ENTREGUE`.
8. Evitar criar novas dependencias de framework. A base do projeto e PHP server-rendered com CSS proprio.
9. Reaproveitar `assets/css/style.css` para estilos compartilhados. CSS inline so quando for estritamente local e temporario.
10. Em fluxos admin, validar role `admin` antes de qualquer leitura ou escrita sensivel.

## Sequencia Recomendada de Trabalho

1. `conectasolidaria-database`
2. `conectasolidaria-backend`
3. `conectasolidaria-frontend`
4. `conectasolidaria-admin-flows`
5. `conectasolidaria-qa`

## Checklist Antes de Fechar Uma Tarefa

- A rota ou acao afetada ainda esta registrada em `index.php`.
- O controller valida autenticacao/permissao e CSRF quando necessario.
- O DAO cobre a persistencia com SQL consistente com `bd.sql`.
- A view reflete o estado real do fluxo sem duplicar regra de negocio.
- Os textos e labels continuam coerentes com os status reais do sistema.
