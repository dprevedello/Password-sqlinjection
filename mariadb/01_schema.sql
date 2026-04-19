-- =============================================================================
-- SQL Injection Demo – inizializzazione database
-- Eseguito automaticamente da MariaDB al primo avvio del volume.
-- Nota: users_ex4 (bcrypt) viene popolata da 02_bcrypt.sh
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Esempio 1: password in chiaro, nessuna protezione
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS users_ex1;
CREATE TABLE users_ex1 (
    id       INT          PRIMARY KEY AUTO_INCREMENT,
    nome     CHAR(255)    NOT NULL,
    username CHAR(255)    NOT NULL,
    password CHAR(255)    NOT NULL
) ENGINE=InnoDB;

INSERT INTO users_ex1 (nome, username, password) VALUES
    ('Bob Smith',        'bob',    'sunshine'),
    ('Elon Musk',        'elon',   'merlin'),
    ('Steven Thornton',  'steven', '123456');

-- -----------------------------------------------------------------------------
-- Esempio 2: password hashata con MD5 (no salt), ancora vulnerabile a SQLi
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS users_ex2;
CREATE TABLE users_ex2 (
    id       INT       PRIMARY KEY AUTO_INCREMENT,
    nome     CHAR(255) NOT NULL,
    username CHAR(255) NOT NULL,
    password CHAR(32)  NOT NULL
) ENGINE=InnoDB;

INSERT INTO users_ex2 (nome, username, password) VALUES
    ('Bob Smith',        'bob',    MD5('sunshine')),
    ('Elon Musk',        'elon',   MD5('merlin')),
    ('Steven Thornton',  'steven', MD5('123456'));

-- -----------------------------------------------------------------------------
-- Esempio 3: SHA2-256 con salt per utente, ancora vulnerabile a SQLi
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS users_ex3;
CREATE TABLE users_ex3 (
    id       INT       PRIMARY KEY AUTO_INCREMENT,
    nome     CHAR(255) NOT NULL,
    username CHAR(255) NOT NULL,
    password CHAR(64)  NOT NULL,
    salt     CHAR(16)  NOT NULL
) ENGINE=InnoDB;

-- La stored function viene creata da 01_saltfunction.sql tramite mariadb -e
-- per evitare problemi con DELIMITER in questo contesto

-- -----------------------------------------------------------------------------
-- Esempio 5: prepared statements + bcrypt → popolato da 02_bcrypt.sh
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS users_ex4;
CREATE TABLE users_ex4 (
    id       INT       PRIMARY KEY AUTO_INCREMENT,
    nome     CHAR(255) NOT NULL,
    username CHAR(255) NOT NULL,
    password CHAR(60)  NOT NULL
) ENGINE=InnoDB;
