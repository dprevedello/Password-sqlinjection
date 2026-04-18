-- =============================================================================
-- SQL Injection Demo – inizializzazione database
-- =============================================================================
-- Nota: users_ex4 (bcrypt) viene popolata da reset.php perché
-- password_hash() genera un salt casuale ad ogni esecuzione e non
-- esiste una funzione bcrypt nativa in MySQL.
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

DROP FUNCTION IF EXISTS saltFunction;
DELIMITER $$
CREATE FUNCTION saltFunction() RETURNS VARCHAR(16) NOT DETERMINISTIC
BEGIN
    DECLARE salt       VARCHAR(16)  DEFAULT '';
    DECLARE saltCharset VARCHAR(100) DEFAULT 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    DECLARE i INT DEFAULT 1;
    DECLARE c INT;
    WHILE i <= 16 DO
        SET c    = CONVERT(RAND() * LENGTH(saltCharset) + 1, INT);
        SET salt = CONCAT(salt, SUBSTRING(saltCharset, c, 1));
        SET i    = i + 1;
    END WHILE;
    RETURN salt;
END$$
DELIMITER ;

SET @salt = saltFunction();
INSERT INTO users_ex3 (nome, username, password, salt)
    VALUES ('Bob Smith', 'bob', SHA2(CONCAT('sunshine', @salt), 256), @salt);

SET @salt = saltFunction();
INSERT INTO users_ex3 (nome, username, password, salt)
    VALUES ('Elon Musk', 'elon', SHA2(CONCAT('merlin', @salt), 256), @salt);

SET @salt = saltFunction();
INSERT INTO users_ex3 (nome, username, password, salt)
    VALUES ('Steven Thornton', 'steven', SHA2(CONCAT('123456', @salt), 256), @salt);

-- -----------------------------------------------------------------------------
-- Esempio 4: prepared statements + SHA2/salt (SQLi neutralizzata)
-- -----------------------------------------------------------------------------
-- Riusa users_ex3, nessuna tabella aggiuntiva necessaria.

-- -----------------------------------------------------------------------------
-- Esempio 5: prepared statements + bcrypt → popolato da reset.php
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS users_ex4;
CREATE TABLE users_ex4 (
    id       INT       PRIMARY KEY AUTO_INCREMENT,
    nome     CHAR(255) NOT NULL,
    username CHAR(255) NOT NULL,
    password CHAR(60)  NOT NULL
) ENGINE=InnoDB;
