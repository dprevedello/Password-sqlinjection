<?php
  header("Location: index.php");
?>
<!DOCTYPE html>
<html>
  <head>
  	<title>Inizializzazione esempi</title>
  </head>
  <body>
<?php
  include 'db.php';
  drop_table('users_ex1');
  create_table("CREATE TABLE users_ex1 (
                  id INT PRIMARY KEY AUTO_INCREMENT,
                  nome CHAR(255) NOT NULL,
                  username CHAR(255) NOT NULL,
                  password CHAR(255) NOT NULL
                ) ENGINE=InnoDB;"
              );
  insert("INSERT INTO users_ex1 (nome, username, password) 
          VALUES ('Bob Smith', 'bob', 'sunshine'),
                 ('Elon Musk', 'elon', 'merlin'),
                 ('Steven Thornton', 'steven', '123456');"
        );
  
  drop_table('users_ex2');
  create_table("CREATE TABLE users_ex2 (
                  id INT PRIMARY KEY AUTO_INCREMENT,
                  nome CHAR(255) NOT NULL,
                  username CHAR(255) NOT NULL,
                  password CHAR(32) NOT NULL
                ) ENGINE=InnoDB;"
              );
  insert("INSERT INTO users_ex2 (nome, username, password) 
          VALUES ('Bob Smith', 'bob', MD5('sunshine')),
                 ('Elon Musk', 'elon', MD5('merlin')),
                 ('Steven Thornton', 'steven', MD5('123456'));"
        );

  drop_table('users_ex3');
  create_table("CREATE TABLE users_ex3 (
                  id INT PRIMARY KEY AUTO_INCREMENT,
                  nome CHAR(255) NOT NULL,
                  username CHAR(255) NOT NULL,
                  password CHAR(64) NOT NULL,
                  salt CHAR(16) NOT NULL
                ) ENGINE=InnoDB;"
              );
  drop_function('saltFunction');
  create_function("CREATE FUNCTION saltFunction() RETURNS VARCHAR(16) NOT DETERMINISTIC
                   BEGIN
                     DECLARE salt VARCHAR(16) DEFAULT '';
                     DECLARE saltCharset VARCHAR(100) DEFAULT 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
                    
                     DECLARE i INT DEFAULT 1;
                     DECLARE c INT;
                     WHILE i <= 16 DO
                       SET c = CONVERT(RAND() * LENGTH(saltCharset) + 1, INT);
                       SET salt = CONCAT(salt, SUBSTRING(saltCharset, c, 1));
                       SET i = i + 1;
                     END WHILE;
                    
                     RETURN (salt);
                    END;"
                  );
  setVar("SET @salt = saltFunction();");
  insert("INSERT INTO users_ex3 (nome, username, password, salt) 
          VALUES ('Bob Smith', 'bob', SHA2(CONCAT('sunshine', @salt), 256), @salt);"
         );
  setVar("SET @salt = saltFunction();");
  insert("INSERT INTO users_ex3 (nome, username, password, salt) 
          VALUES ('Elon Musk', 'elon', SHA2(CONCAT('merlin', @salt), 256), @salt);"
         );
  setVar("SET @salt = saltFunction();");
  insert("INSERT INTO users_ex3 (nome, username, password, salt) 
          VALUES ('Steven Thornton', 'steven', SHA2(CONCAT('123456', @salt), 256), @salt);"
         );

  drop_table('users_ex4');
  create_table("CREATE TABLE users_ex4 (
                  id INT PRIMARY KEY AUTO_INCREMENT,
                  nome CHAR(255) NOT NULL,
                  username CHAR(255) NOT NULL,
                  password CHAR(60) NOT NULL
                ) ENGINE=InnoDB;"
              );
  $pwd = password_hash('sunshine', PASSWORD_BCRYPT, ['cost' => 13,]);
  insert("INSERT INTO users_ex4 (nome, username, password) 
          VALUES ('Bob Smith', 'bob', '$pwd');"
         );
  $pwd = password_hash('merlin', PASSWORD_BCRYPT, ['cost' => 13,]);
  insert("INSERT INTO users_ex4 (nome, username, password) 
          VALUES ('Elon Musk', 'elon', '$pwd');"
         );
  $pwd = password_hash('123456', PASSWORD_BCRYPT, ['cost' => 13,]);
  insert("INSERT INTO users_ex4 (nome, username, password) 
          VALUES ('Steven Thornton', 'steven', '$pwd');"
         );

  close_db();
  print("<p>Database reinizializzato correttamente</p>");
?>
  </body>
</html>