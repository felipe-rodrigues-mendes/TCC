-- Schema oficial do ConectaSolidaria
-- Executavel em MariaDB/MySQL (XAMPP)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS conecta_solidaria
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE conecta_solidaria;

CREATE TABLE IF NOT EXISTS perfil (
  id_perfil INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuario (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  telefone VARCHAR(20) DEFAULT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  id_perfil INT NOT NULL,
  aceite_termos_lgpd TINYINT(1) NOT NULL DEFAULT 0,
  aceite_termos_data DATETIME NULL,
  aceite_termos_versao VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_perfil
    FOREIGN KEY (id_perfil) REFERENCES perfil(id_perfil)
) ENGINE=InnoDB;

SET @sql_add_aceite_termos_lgpd = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'usuario'
        AND COLUMN_NAME = 'aceite_termos_lgpd'
    ),
    'SELECT 1',
    'ALTER TABLE usuario ADD COLUMN aceite_termos_lgpd TINYINT(1) NOT NULL DEFAULT 0 AFTER id_perfil'
  )
);
PREPARE stmt_add_aceite_termos_lgpd FROM @sql_add_aceite_termos_lgpd;
EXECUTE stmt_add_aceite_termos_lgpd;
DEALLOCATE PREPARE stmt_add_aceite_termos_lgpd;

SET @sql_add_aceite_termos_data = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'usuario'
        AND COLUMN_NAME = 'aceite_termos_data'
    ),
    'SELECT 1',
    'ALTER TABLE usuario ADD COLUMN aceite_termos_data DATETIME NULL AFTER aceite_termos_lgpd'
  )
);
PREPARE stmt_add_aceite_termos_data FROM @sql_add_aceite_termos_data;
EXECUTE stmt_add_aceite_termos_data;
DEALLOCATE PREPARE stmt_add_aceite_termos_data;

SET @sql_add_aceite_termos_versao = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'usuario'
        AND COLUMN_NAME = 'aceite_termos_versao'
    ),
    'SELECT 1',
    'ALTER TABLE usuario ADD COLUMN aceite_termos_versao VARCHAR(20) DEFAULT NULL AFTER aceite_termos_data'
  )
);
PREPARE stmt_add_aceite_termos_versao FROM @sql_add_aceite_termos_versao;
EXECUTE stmt_add_aceite_termos_versao;
DEALLOCATE PREPARE stmt_add_aceite_termos_versao;

CREATE TABLE IF NOT EXISTS login (
  id_login INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL UNIQUE,
  username VARCHAR(100) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  ultimo_acesso TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_login_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
      ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS endereco (
  id_endereco INT AUTO_INCREMENT PRIMARY KEY,
  logradouro VARCHAR(150) NOT NULL,
  cidade VARCHAR(100) NOT NULL,
  estado VARCHAR(50) NOT NULL,
  cep VARCHAR(10) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campanha (
  id_campanha INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  descricao TEXT,
  data_inicio DATE NOT NULL,
  data_fim DATE DEFAULT NULL,
  status ENUM('ATIVA', 'ENCERRADA') NOT NULL DEFAULT 'ATIVA',
  id_usuario INT NOT NULL,
  CONSTRAINT fk_campanha_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categoria_item (
  id_categoria INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS necessidade (
  id_necessidade INT AUTO_INCREMENT PRIMARY KEY,
  id_campanha INT NOT NULL,
  id_categoria INT NOT NULL,
  descricao VARCHAR(150) DEFAULT NULL,
  quantidade_necessaria INT NOT NULL,
  CONSTRAINT fk_necessidade_campanha
    FOREIGN KEY (id_campanha) REFERENCES campanha(id_campanha)
      ON DELETE CASCADE,
  CONSTRAINT fk_necessidade_categoria
    FOREIGN KEY (id_categoria) REFERENCES categoria_item(id_categoria)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS doacao (
  id_doacao INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  id_campanha INT NOT NULL,
  id_ponto INT NULL,
  data_doacao DATE NOT NULL,
  status ENUM('PENDENTE', 'RECEBIDA', 'EXCLUIDA') NOT NULL DEFAULT 'PENDENTE',
  CONSTRAINT fk_doacao_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_doacao_campanha
    FOREIGN KEY (id_campanha) REFERENCES campanha(id_campanha)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS item_doacao (
  id_item INT AUTO_INCREMENT PRIMARY KEY,
  id_doacao INT NOT NULL,
  id_categoria INT NOT NULL,
  quantidade INT NOT NULL,
  CONSTRAINT fk_item_doacao_doacao
    FOREIGN KEY (id_doacao) REFERENCES doacao(id_doacao)
      ON DELETE CASCADE,
  CONSTRAINT fk_item_doacao_categoria
    FOREIGN KEY (id_categoria) REFERENCES categoria_item(id_categoria)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ponto_coleta (
  id_ponto INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  id_endereco INT NOT NULL,
  CONSTRAINT fk_ponto_endereco
    FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estoque (
  id_estoque INT AUTO_INCREMENT PRIMARY KEY,
  id_ponto INT NOT NULL,
  CONSTRAINT fk_estoque_ponto
    FOREIGN KEY (id_ponto) REFERENCES ponto_coleta(id_ponto)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS item_estoque (
  id_item INT AUTO_INCREMENT PRIMARY KEY,
  id_estoque INT NOT NULL,
  id_categoria INT NOT NULL,
  quantidade INT NOT NULL,
  CONSTRAINT fk_item_estoque_estoque
    FOREIGN KEY (id_estoque) REFERENCES estoque(id_estoque)
      ON DELETE CASCADE,
  CONSTRAINT fk_item_estoque_categoria
    FOREIGN KEY (id_categoria) REFERENCES categoria_item(id_categoria)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS destino (
  id_destino INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  id_endereco INT NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_destino_endereco
    FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
) ENGINE=InnoDB;

SET @destino_ativo_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'destino'
    AND COLUMN_NAME = 'ativo'
);
SET @destino_ativo_sql := IF(
  @destino_ativo_exists = 0,
  'ALTER TABLE destino ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER id_endereco',
  'SELECT 1'
);
PREPARE stmt_destino_ativo FROM @destino_ativo_sql;
EXECUTE stmt_destino_ativo;
DEALLOCATE PREPARE stmt_destino_ativo;

CREATE TABLE IF NOT EXISTS distribuicao (
  id_distribuicao INT AUTO_INCREMENT PRIMARY KEY,
  id_destino INT NOT NULL,
  id_campanha INT NOT NULL,
  data_envio DATE NOT NULL,
  status ENUM('ENVIADO', 'ENTREGUE') NOT NULL DEFAULT 'ENVIADO',
  CONSTRAINT fk_distribuicao_destino
    FOREIGN KEY (id_destino) REFERENCES destino(id_destino),
  CONSTRAINT fk_distribuicao_campanha
    FOREIGN KEY (id_campanha) REFERENCES campanha(id_campanha)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS item_distribuicao (
  id_item INT AUTO_INCREMENT PRIMARY KEY,
  id_distribuicao INT NOT NULL,
  id_categoria INT NOT NULL,
  quantidade INT NOT NULL,
  CONSTRAINT fk_item_distribuicao_distribuicao
    FOREIGN KEY (id_distribuicao) REFERENCES distribuicao(id_distribuicao)
      ON DELETE CASCADE,
  CONSTRAINT fk_item_distribuicao_categoria
    FOREIGN KEY (id_categoria) REFERENCES categoria_item(id_categoria)
) ENGINE=InnoDB;

INSERT INTO perfil (nome)
SELECT 'admin'
WHERE NOT EXISTS (SELECT 1 FROM perfil WHERE nome = 'admin');

INSERT INTO perfil (nome)
SELECT 'doador'
WHERE NOT EXISTS (SELECT 1 FROM perfil WHERE nome = 'doador');

INSERT INTO categoria_item (nome)
SELECT 'Água potável'
WHERE NOT EXISTS (SELECT 1 FROM categoria_item WHERE nome = 'Água potável');

INSERT INTO categoria_item (nome)
SELECT 'Alimentos não perecíveis'
WHERE NOT EXISTS (SELECT 1 FROM categoria_item WHERE nome = 'Alimentos não perecíveis');

INSERT INTO categoria_item (nome)
SELECT 'Roupas'
WHERE NOT EXISTS (SELECT 1 FROM categoria_item WHERE nome = 'Roupas');

INSERT INTO categoria_item (nome)
SELECT 'Cobertores'
WHERE NOT EXISTS (SELECT 1 FROM categoria_item WHERE nome = 'Cobertores');

INSERT INTO categoria_item (nome)
SELECT 'Produtos de higiene'
WHERE NOT EXISTS (SELECT 1 FROM categoria_item WHERE nome = 'Produtos de higiene');

INSERT INTO categoria_item (nome)
SELECT 'Remédios'
WHERE NOT EXISTS (SELECT 1 FROM categoria_item WHERE nome = 'Remédios');

INSERT INTO categoria_item (nome)
SELECT 'Materiais de construção'
WHERE NOT EXISTS (SELECT 1 FROM categoria_item WHERE nome = 'Materiais de construção');

INSERT INTO endereco (logradouro, cidade, estado, cep)
SELECT 'SCS Quadra 1 Bloco A', 'Brasília', 'DF', '70300-500'
WHERE NOT EXISTS (
  SELECT 1 FROM endereco
  WHERE logradouro = 'SCS Quadra 1 Bloco A'
    AND cidade = 'Brasília'
    AND estado = 'DF'
    AND cep = '70300-500'
);

INSERT INTO ponto_coleta (nome, id_endereco)
SELECT 'Centro de Coleta Plano Piloto', e.id_endereco
FROM endereco e
WHERE e.logradouro = 'SCS Quadra 1 Bloco A'
  AND e.cidade = 'Brasília'
  AND e.estado = 'DF'
  AND e.cep = '70300-500'
  AND NOT EXISTS (
    SELECT 1 FROM ponto_coleta pc WHERE pc.nome = 'Centro de Coleta Plano Piloto'
  )
LIMIT 1;

INSERT INTO endereco (logradouro, cidade, estado, cep)
SELECT 'QNL 11 Área Especial', 'Taguatinga', 'DF', '72150-115'
WHERE NOT EXISTS (
  SELECT 1 FROM endereco
  WHERE logradouro = 'QNL 11 Área Especial'
    AND cidade = 'Taguatinga'
    AND estado = 'DF'
    AND cep = '72150-115'
);

INSERT INTO ponto_coleta (nome, id_endereco)
SELECT 'Ponto de Coleta Taguatinga', e.id_endereco
FROM endereco e
WHERE e.logradouro = 'QNL 11 Área Especial'
  AND e.cidade = 'Taguatinga'
  AND e.estado = 'DF'
  AND e.cep = '72150-115'
  AND NOT EXISTS (
    SELECT 1 FROM ponto_coleta pc WHERE pc.nome = 'Ponto de Coleta Taguatinga'
  )
LIMIT 1;

INSERT INTO endereco (logradouro, cidade, estado, cep)
SELECT 'QNM 18 Conjunto B', 'Ceilândia', 'DF', '72210-182'
WHERE NOT EXISTS (
  SELECT 1 FROM endereco
  WHERE logradouro = 'QNM 18 Conjunto B'
    AND cidade = 'Ceilândia'
    AND estado = 'DF'
    AND cep = '72210-182'
);

INSERT INTO ponto_coleta (nome, id_endereco)
SELECT 'Ponto de Coleta Ceilândia', e.id_endereco
FROM endereco e
WHERE e.logradouro = 'QNM 18 Conjunto B'
  AND e.cidade = 'Ceilândia'
  AND e.estado = 'DF'
  AND e.cep = '72210-182'
  AND NOT EXISTS (
    SELECT 1 FROM ponto_coleta pc WHERE pc.nome = 'Ponto de Coleta Ceilândia'
  )
LIMIT 1;

INSERT INTO endereco (logradouro, cidade, estado, cep)
SELECT 'Escola Técnica de Ceilândia', 'Brasília', 'DF', '72220-140'
WHERE NOT EXISTS (
  SELECT 1 FROM endereco
  WHERE cep = '72220-140'
);

INSERT INTO endereco (logradouro, cidade, estado, cep)
SELECT 'Ginásio de Taguatinga', 'Brasília', 'DF', '72125-190'
WHERE NOT EXISTS (
  SELECT 1 FROM endereco
  WHERE cep = '72125-190'
);

UPDATE ponto_coleta pc
SET pc.nome = 'Escola Técnica de Ceilândia'
WHERE pc.nome = 'Ponto de Coleta Ceilândia'
  AND NOT EXISTS (
    SELECT 1 FROM ponto_coleta px WHERE px.nome = 'Escola Técnica de Ceilândia'
  );

UPDATE ponto_coleta pc
SET pc.nome = 'Ginásio de Taguatinga'
WHERE pc.nome = 'Ponto de Coleta Taguatinga'
  AND NOT EXISTS (
    SELECT 1 FROM ponto_coleta px WHERE px.nome = 'Ginásio de Taguatinga'
  );

INSERT INTO ponto_coleta (nome, id_endereco)
SELECT 'Escola Técnica de Ceilândia', e.id_endereco
FROM endereco e
WHERE e.cep = '72220-140'
  AND NOT EXISTS (
    SELECT 1 FROM ponto_coleta pc WHERE pc.nome = 'Escola Técnica de Ceilândia'
  )
LIMIT 1;

INSERT INTO ponto_coleta (nome, id_endereco)
SELECT 'Ginásio de Taguatinga', e.id_endereco
FROM endereco e
WHERE e.cep = '72125-190'
  AND NOT EXISTS (
    SELECT 1 FROM ponto_coleta pc WHERE pc.nome = 'Ginásio de Taguatinga'
  )
LIMIT 1;

UPDATE ponto_coleta pc
INNER JOIN endereco e ON e.id_endereco = pc.id_endereco
SET e.logradouro = 'Escola Técnica de Ceilândia',
    e.cidade = 'Brasília',
    e.estado = 'DF',
    e.cep = '72220-140'
WHERE pc.nome = 'Escola Técnica de Ceilândia';

UPDATE ponto_coleta pc
INNER JOIN endereco e ON e.id_endereco = pc.id_endereco
SET e.logradouro = 'Ginásio de Taguatinga',
    e.cidade = 'Brasília',
    e.estado = 'DF',
    e.cep = '72125-190'
WHERE pc.nome = 'Ginásio de Taguatinga';

INSERT INTO endereco (logradouro, cidade, estado, cep)
SELECT 'Centro Comunitário Sol Nascente', 'Ceilândia', 'DF', '72236-800'
WHERE NOT EXISTS (
  SELECT 1 FROM endereco
  WHERE logradouro = 'Centro Comunitário Sol Nascente'
    AND cidade = 'Ceilândia'
    AND estado = 'DF'
    AND cep = '72236-800'
);

INSERT INTO destino (nome, id_endereco)
SELECT 'Abrigo Sol Nascente', e.id_endereco
FROM endereco e
WHERE e.logradouro = 'Centro Comunitário Sol Nascente'
  AND e.cidade = 'Ceilândia'
  AND e.estado = 'DF'
  AND e.cep = '72236-800'
  AND NOT EXISTS (
    SELECT 1 FROM destino d WHERE d.nome = 'Abrigo Sol Nascente'
  )
LIMIT 1;

INSERT INTO endereco (logradouro, cidade, estado, cep)
SELECT 'Escola Classe Vila Esperança', 'Taguatinga', 'DF', '72145-220'
WHERE NOT EXISTS (
  SELECT 1 FROM endereco
  WHERE logradouro = 'Escola Classe Vila Esperança'
    AND cidade = 'Taguatinga'
    AND estado = 'DF'
    AND cep = '72145-220'
);

INSERT INTO destino (nome, id_endereco)
SELECT 'Comunidade Vila Esperança', e.id_endereco
FROM endereco e
WHERE e.logradouro = 'Escola Classe Vila Esperança'
  AND e.cidade = 'Taguatinga'
  AND e.estado = 'DF'
  AND e.cep = '72145-220'
  AND NOT EXISTS (
    SELECT 1 FROM destino d WHERE d.nome = 'Comunidade Vila Esperança'
  )
LIMIT 1;

INSERT INTO usuario (nome, email, telefone, ativo, id_perfil, aceite_termos_lgpd, aceite_termos_data, aceite_termos_versao)
SELECT 'Administrador', 'admin@conectasolidaria.local', '', 1, p.id_perfil, 1, NOW(), 'v1.0'
FROM perfil p
WHERE p.nome = 'admin'
  AND NOT EXISTS (
    SELECT 1 FROM usuario u WHERE u.email = 'admin@conectasolidaria.local'
  )
LIMIT 1;

INSERT INTO login (id_usuario, username, senha_hash, ultimo_acesso)
SELECT u.id_usuario, 'admin', '$2y$10$eF2S1nbegQ/Cy6SX40eQqe.YRa76.U.xG1AHogOYO.A0OI39/gjBC', NULL
FROM usuario u
WHERE u.email = 'admin@conectasolidaria.local'
  AND NOT EXISTS (
    SELECT 1 FROM login l WHERE l.id_usuario = u.id_usuario OR l.username = 'admin'
  )
LIMIT 1;

UPDATE login l
INNER JOIN usuario u ON u.id_usuario = l.id_usuario
SET l.username = 'admin',
    l.senha_hash = '$2y$10$9.0yKwuBWAGXfgJbru5s4Oc34.NgvWDfJAoOubuNcVJg1HaXHzPhW'
WHERE u.email = 'admin@conectasolidaria.local';

INSERT INTO campanha (titulo, descricao, data_inicio, data_fim, status, id_usuario)
SELECT 'Brasília', 'Campanha emergencial para apoio a famílias afetadas por chuvas intensas.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'ATIVA', u.id_usuario
FROM usuario u
WHERE u.email = 'admin@conectasolidaria.local'
  AND NOT EXISTS (
    SELECT 1 FROM campanha c WHERE c.titulo = 'Brasília' AND c.status = 'ATIVA'
  )
LIMIT 1;

INSERT INTO campanha (titulo, descricao, data_inicio, data_fim, status, id_usuario)
SELECT 'Taguatinga', 'Campanha para arrecadação de mantimentos destinados às famílias de Taguatinga.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'ATIVA', u.id_usuario
FROM usuario u
WHERE u.email = 'admin@conectasolidaria.local'
  AND NOT EXISTS (
    SELECT 1 FROM campanha c WHERE c.titulo = 'Taguatinga' AND c.status = 'ATIVA'
  )
LIMIT 1;

INSERT INTO campanha (titulo, descricao, data_inicio, data_fim, status, id_usuario)
SELECT 'Ceilândia', 'Campanha para arrecadação de itens essenciais para atendimento emergencial em Ceilândia.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'ATIVA', u.id_usuario
FROM usuario u
WHERE u.email = 'admin@conectasolidaria.local'
  AND NOT EXISTS (
    SELECT 1 FROM campanha c WHERE c.titulo = 'Ceilândia' AND c.status = 'ATIVA'
  )
LIMIT 1;

UPDATE campanha SET titulo = 'São Paulo' WHERE titulo IN ('Sao Paulo', 'São paulo');
UPDATE campanha SET titulo = 'Paraná' WHERE titulo IN ('Parana', 'Parána');

INSERT INTO campanha (titulo, descricao, data_inicio, data_fim, status, id_usuario)
SELECT 'Bahia', 'Campanha emergencial para atendimento às famílias afetadas na Bahia.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'ATIVA', u.id_usuario
FROM usuario u
WHERE u.email = 'admin@conectasolidaria.local'
  AND NOT EXISTS (SELECT 1 FROM campanha c WHERE c.titulo = 'Bahia')
LIMIT 1;

INSERT INTO campanha (titulo, descricao, data_inicio, data_fim, status, id_usuario)
SELECT 'Rio Grande do Sul', 'Campanha de apoio para comunidades afetadas no Rio Grande do Sul.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'ATIVA', u.id_usuario
FROM usuario u
WHERE u.email = 'admin@conectasolidaria.local'
  AND NOT EXISTS (SELECT 1 FROM campanha c WHERE c.titulo = 'Rio Grande do Sul')
LIMIT 1;

INSERT INTO campanha (titulo, descricao, data_inicio, data_fim, status, id_usuario)
SELECT 'São Paulo', 'Campanha de arrecadação para apoio humanitário no estado de São Paulo.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'ATIVA', u.id_usuario
FROM usuario u
WHERE u.email = 'admin@conectasolidaria.local'
  AND NOT EXISTS (SELECT 1 FROM campanha c WHERE c.titulo = 'São Paulo')
LIMIT 1;

INSERT INTO campanha (titulo, descricao, data_inicio, data_fim, status, id_usuario)
SELECT 'Minas Gerais', 'Campanha para distribuição de mantimentos em Minas Gerais.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'ATIVA', u.id_usuario
FROM usuario u
WHERE u.email = 'admin@conectasolidaria.local'
  AND NOT EXISTS (SELECT 1 FROM campanha c WHERE c.titulo = 'Minas Gerais')
LIMIT 1;

INSERT INTO campanha (titulo, descricao, data_inicio, data_fim, status, id_usuario)
SELECT 'Paraná', 'Campanha de arrecadação para famílias impactadas no Paraná.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'ATIVA', u.id_usuario
FROM usuario u
WHERE u.email = 'admin@conectasolidaria.local'
  AND NOT EXISTS (SELECT 1 FROM campanha c WHERE c.titulo = 'Paraná')
LIMIT 1;

INSERT INTO campanha (titulo, descricao, data_inicio, data_fim, status, id_usuario)
SELECT 'Santa Catarina', 'Campanha de ajuda para municípios atingidos em Santa Catarina.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'ATIVA', u.id_usuario
FROM usuario u
WHERE u.email = 'admin@conectasolidaria.local'
  AND NOT EXISTS (SELECT 1 FROM campanha c WHERE c.titulo = 'Santa Catarina')
LIMIT 1;

UPDATE campanha
SET status = 'ATIVA',
    data_fim = DATE_ADD(CURDATE(), INTERVAL 60 DAY)
WHERE titulo IN ('Bahia', 'Rio Grande do Sul', 'São Paulo', 'Minas Gerais', 'Paraná', 'Santa Catarina');

UPDATE campanha
SET status = 'ENCERRADA'
WHERE status = 'ATIVA'
  AND titulo NOT IN ('Bahia', 'Rio Grande do Sul', 'São Paulo', 'Minas Gerais', 'Paraná', 'Santa Catarina');

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Água potável para atendimento emergencial na Bahia.', 220
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Água potável'
WHERE c.titulo = 'Bahia'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Alimentos não perecíveis para famílias do Rio Grande do Sul.', 260
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Alimentos não perecíveis'
WHERE c.titulo = 'Rio Grande do Sul'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Cobertores para pessoas desalojadas em São Paulo.', 180
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Cobertores'
WHERE c.titulo = 'São Paulo'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Roupas para comunidades impactadas em Minas Gerais.', 200
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Roupas'
WHERE c.titulo = 'Minas Gerais'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Produtos de higiene para atendimento no Paraná.', 170
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Produtos de higiene'
WHERE c.titulo = 'Paraná'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Água potável para operações em Santa Catarina.', 210
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Água potável'
WHERE c.titulo = 'Santa Catarina'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Doação prioritária para atendimento imediato.', 200
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Água potável'
WHERE c.titulo = 'Brasília'
  AND c.status = 'ATIVA'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Itens essenciais para montagem de cestas básicas.', 150
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Alimentos não perecíveis'
WHERE c.titulo = 'Brasília'
  AND c.status = 'ATIVA'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Apoio para famílias desalojadas em abrigos temporários.', 120
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Cobertores'
WHERE c.titulo = 'Brasília'
  AND c.status = 'ATIVA'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Prioridade para famílias em situação de vulnerabilidade em Taguatinga.', 180
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Alimentos não perecíveis'
WHERE c.titulo = 'Taguatinga'
  AND c.status = 'ATIVA'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Roupas e cobertores para distribuição em abrigos de Taguatinga.', 140
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Roupas'
WHERE c.titulo = 'Taguatinga'
  AND c.status = 'ATIVA'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Itens de higiene para atendimento imediato em Ceilândia.', 160
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Produtos de higiene'
WHERE c.titulo = 'Ceilândia'
  AND c.status = 'ATIVA'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, 'Água potável para famílias afetadas em Ceilândia.', 220
FROM campanha c
INNER JOIN categoria_item ci ON ci.nome = 'Água potável'
WHERE c.titulo = 'Ceilândia'
  AND c.status = 'ATIVA'
  AND NOT EXISTS (
    SELECT 1 FROM necessidade n
    WHERE n.id_campanha = c.id_campanha
      AND n.id_categoria = ci.id_categoria
  )
LIMIT 1;

-- Padroniza as 6 campanhas ativas com 4 tipos por campanha, variando por cidade.
UPDATE campanha
SET status = 'ATIVA',
    data_fim = DATE_ADD(CURDATE(), INTERVAL 60 DAY)
WHERE titulo IN ('Bahia', 'Rio Grande do Sul', 'São Paulo', 'Minas Gerais', 'Paraná', 'Santa Catarina');

DROP TEMPORARY TABLE IF EXISTS tmp_necessidades_ativas;
CREATE TEMPORARY TABLE tmp_necessidades_ativas (
  campanha_titulo VARCHAR(150) NOT NULL,
  categoria_nome VARCHAR(100) NOT NULL,
  descricao VARCHAR(150) NOT NULL,
  quantidade_necessaria INT NOT NULL,
  PRIMARY KEY (campanha_titulo, categoria_nome)
) ENGINE=InnoDB;

INSERT INTO tmp_necessidades_ativas (campanha_titulo, categoria_nome, descricao, quantidade_necessaria) VALUES
('Bahia', 'Alimentos não perecíveis', 'Somente alimentos lacrados, dentro da validade e sem avarias na embalagem.', 250),
('Bahia', 'Água potável', 'Apenas garrafas lacradas e em bom estado de conservação.', 220),
('Bahia', 'Produtos de higiene', 'Apenas itens de higiene pessoal lacrados e em embalagem original.', 180),
('Bahia', 'Remédios', 'Somente medicamentos lacrados, com bula e dentro do prazo de validade.', 90),

('Rio Grande do Sul', 'Alimentos não perecíveis', 'Somente alimentos lacrados, dentro da validade e sem avarias na embalagem.', 260),
('Rio Grande do Sul', 'Cobertores', 'Cobertores limpos e em bom estado para famílias desalojadas.', 200),
('Rio Grande do Sul', 'Materiais de construção', 'Aceitamos materiais novos ou em bom estado, sem ferrugem ou danos estruturais.', 130),
('Rio Grande do Sul', 'Remédios', 'Somente medicamentos lacrados, com bula e dentro do prazo de validade.', 100),

('São Paulo', 'Água potável', 'Apenas garrafas lacradas e em bom estado de conservação.', 210),
('São Paulo', 'Materiais de construção', 'Aceitamos materiais novos ou em bom estado, sem ferrugem ou danos estruturais.', 140),
('São Paulo', 'Produtos de higiene', 'Apenas itens de higiene pessoal lacrados e em embalagem original.', 190),
('São Paulo', 'Cobertores', 'Cobertores limpos e em bom estado para abrigos temporários.', 170),

('Minas Gerais', 'Alimentos não perecíveis', 'Somente alimentos lacrados, dentro da validade e sem avarias na embalagem.', 240),
('Minas Gerais', 'Roupas', 'Roupas limpas, sem rasgos e adequadas para uso imediato.', 210),
('Minas Gerais', 'Produtos de higiene', 'Apenas itens de higiene pessoal lacrados e em embalagem original.', 180),
('Minas Gerais', 'Remédios', 'Somente medicamentos lacrados, com bula e dentro do prazo de validade.', 95),

('Paraná', 'Água potável', 'Apenas garrafas lacradas e em bom estado de conservação.', 230),
('Paraná', 'Materiais de construção', 'Aceitamos materiais novos ou em bom estado, sem ferrugem ou danos estruturais.', 120),
('Paraná', 'Roupas', 'Roupas limpas, sem rasgos e adequadas para uso imediato.', 180),
('Paraná', 'Alimentos não perecíveis', 'Somente alimentos lacrados, dentro da validade e sem avarias na embalagem.', 240),

('Santa Catarina', 'Cobertores', 'Cobertores limpos e em bom estado para regiões mais frias.', 220),
('Santa Catarina', 'Materiais de construção', 'Aceitamos materiais novos ou em bom estado, sem ferrugem ou danos estruturais.', 150),
('Santa Catarina', 'Produtos de higiene', 'Apenas itens de higiene pessoal lacrados e em embalagem original.', 170),
('Santa Catarina', 'Remédios', 'Somente medicamentos lacrados, com bula e dentro do prazo de validade.', 90);

DELETE n
FROM necessidade n
INNER JOIN campanha c ON c.id_campanha = n.id_campanha
INNER JOIN categoria_item ci ON ci.id_categoria = n.id_categoria
LEFT JOIN tmp_necessidades_ativas t
  ON t.campanha_titulo = c.titulo
 AND t.categoria_nome = ci.nome
WHERE c.titulo IN ('Bahia', 'Rio Grande do Sul', 'São Paulo', 'Minas Gerais', 'Paraná', 'Santa Catarina')
  AND t.categoria_nome IS NULL;

UPDATE necessidade n
INNER JOIN campanha c ON c.id_campanha = n.id_campanha
INNER JOIN categoria_item ci ON ci.id_categoria = n.id_categoria
INNER JOIN tmp_necessidades_ativas t
  ON t.campanha_titulo = c.titulo
 AND t.categoria_nome = ci.nome
SET n.descricao = t.descricao,
    n.quantidade_necessaria = t.quantidade_necessaria
WHERE c.titulo IN ('Bahia', 'Rio Grande do Sul', 'São Paulo', 'Minas Gerais', 'Paraná', 'Santa Catarina');

INSERT INTO necessidade (id_campanha, id_categoria, descricao, quantidade_necessaria)
SELECT c.id_campanha, ci.id_categoria, t.descricao, t.quantidade_necessaria
FROM tmp_necessidades_ativas t
INNER JOIN campanha c ON c.titulo = t.campanha_titulo
INNER JOIN categoria_item ci ON ci.nome = t.categoria_nome
LEFT JOIN necessidade n
  ON n.id_campanha = c.id_campanha
 AND n.id_categoria = ci.id_categoria
WHERE n.id_necessidade IS NULL;

DROP TEMPORARY TABLE IF EXISTS tmp_necessidades_ativas;

SET @add_doacao_ponto_col = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'doacao'
    AND COLUMN_NAME = 'id_ponto'
);
SET @sql_doacao_col = IF(
  @add_doacao_ponto_col = 0,
  'ALTER TABLE doacao ADD COLUMN id_ponto INT NULL AFTER id_campanha',
  'SELECT 1'
);
PREPARE stmt_doacao_col FROM @sql_doacao_col;
EXECUTE stmt_doacao_col;
DEALLOCATE PREPARE stmt_doacao_col;

UPDATE doacao d
INNER JOIN ponto_coleta pc ON pc.nome = 'Ginásio de Taguatinga'
SET d.id_ponto = pc.id_ponto
WHERE d.id_ponto IS NULL;

SET @doacao_fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'doacao'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_doacao_ponto'
);
SET @sql_doacao_fk = IF(
  @doacao_fk_exists = 0,
  'ALTER TABLE doacao ADD CONSTRAINT fk_doacao_ponto FOREIGN KEY (id_ponto) REFERENCES ponto_coleta(id_ponto)',
  'SELECT 1'
);
PREPARE stmt_doacao_fk FROM @sql_doacao_fk;
EXECUTE stmt_doacao_fk;
DEALLOCATE PREPARE stmt_doacao_fk;

SET @make_doacao_ponto_not_null = (
  SELECT IS_NULLABLE = 'YES'
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'doacao'
    AND COLUMN_NAME = 'id_ponto'
  LIMIT 1
);
SET @sql_doacao_nn = IF(
  @make_doacao_ponto_not_null = 1,
  'ALTER TABLE doacao MODIFY id_ponto INT NOT NULL',
  'SELECT 1'
);
PREPARE stmt_doacao_nn FROM @sql_doacao_nn;
EXECUTE stmt_doacao_nn;
DEALLOCATE PREPARE stmt_doacao_nn;

SET @add_distribuicao_campanha_col = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'distribuicao'
    AND COLUMN_NAME = 'id_campanha'
);
SET @sql_distribuicao_col = IF(
  @add_distribuicao_campanha_col = 0,
  'ALTER TABLE distribuicao ADD COLUMN id_campanha INT NULL AFTER id_destino',
  'SELECT 1'
);
PREPARE stmt_distribuicao_col FROM @sql_distribuicao_col;
EXECUTE stmt_distribuicao_col;
DEALLOCATE PREPARE stmt_distribuicao_col;

UPDATE distribuicao d
INNER JOIN destino de ON de.id_destino = d.id_destino
INNER JOIN endereco e ON e.id_endereco = de.id_endereco
INNER JOIN campanha c ON c.titulo = e.cidade
SET d.id_campanha = c.id_campanha
WHERE d.id_campanha IS NULL;

SET @distribuicao_fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'distribuicao'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'fk_distribuicao_campanha'
);
SET @sql_distribuicao_fk = IF(
  @distribuicao_fk_exists = 0,
  'ALTER TABLE distribuicao ADD CONSTRAINT fk_distribuicao_campanha FOREIGN KEY (id_campanha) REFERENCES campanha(id_campanha)',
  'SELECT 1'
);
PREPARE stmt_distribuicao_fk FROM @sql_distribuicao_fk;
EXECUTE stmt_distribuicao_fk;
DEALLOCATE PREPARE stmt_distribuicao_fk;

SET FOREIGN_KEY_CHECKS = 1;
