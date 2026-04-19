#!/bin/bash
# =============================================================================
# 02_seed.sh – crea la stored function saltFunction e popola users_ex3
# Eseguito da docker-entrypoint-initdb.d dopo 01_schema.sql
# Nota: users_ex4 (bcrypt) viene popolata dal container php al primo avvio
# =============================================================================

set -e

DB="${MARIADB_DATABASE}"
USER="root"
PASS="${MARIADB_ROOT_PASSWORD}"

# Stored function (DELIMITER non supportato nei file .sql di initdb)
mariadb -u"$USER" -p"$PASS" "$DB" << 'SQL'
DROP FUNCTION IF EXISTS saltFunction;
CREATE FUNCTION saltFunction() RETURNS VARCHAR(16) NOT DETERMINISTIC
BEGIN
    DECLARE salt        VARCHAR(16)  DEFAULT '';
    DECLARE saltCharset VARCHAR(100) DEFAULT 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    DECLARE i INT DEFAULT 1;
    DECLARE c INT;
    WHILE i <= 16 DO
        SET c    = CONVERT(RAND() * LENGTH(saltCharset) + 1, INT);
        SET salt = CONCAT(salt, SUBSTRING(saltCharset, c, 1));
        SET i    = i + 1;
    END WHILE;
    RETURN salt;
END
SQL

# Popola users_ex3 con SHA2 + salt
mariadb -u"$USER" -p"$PASS" "$DB" << 'SQL'
SET @salt = saltFunction();
INSERT INTO users_ex3 (nome, username, password, salt)
    VALUES ('Bob Smith', 'bob', SHA2(CONCAT('sunshine', @salt), 256), @salt);

SET @salt = saltFunction();
INSERT INTO users_ex3 (nome, username, password, salt)
    VALUES ('Elon Musk', 'elon', SHA2(CONCAT('merlin', @salt), 256), @salt);

SET @salt = saltFunction();
INSERT INTO users_ex3 (nome, username, password, salt)
    VALUES ('Steven Thornton', 'steven', SHA2(CONCAT('123456', @salt), 256), @salt);
SQL

echo "[02_seed.sh] users_ex3 popolata con SHA2 + salt."
