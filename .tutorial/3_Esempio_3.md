# Aggiungiamo un po' di sale

Per memorizzare le password in modo sicuro nel database l'utilizzo dell'**HASH** non è sufficiente. In particolare **MD5** è noto per essere un algoritmo che presenta un alto numero di collisioni, ma soprattutto è vulnerabile ad attacchi a dizionario come mostrato nel precedente esempio.

La prima ottimizzazione da fare è utilizzare una funzione di HASH moderna come **SHA-2** che limita le collisioni. Tuttavia non si è ancora protetti dagli attacchi a dizionario, soprattutto se gli utenti usano password "semplici da ricordare".
Viene in nostro aiuto il concetto di **"salt"**.

L'utilizzo di un "salt" durante il calcolo dell'hash delle password è una pratica comune per aumentare la sicurezza delle password.

Un salt è un **valore casuale che viene combinato con la password** prima di essere inviata alla funzione di HASH. L'obiettivo del salt è quindi di rendere più difficile per gli attaccanti l'utilizzo di dizionari di attacchi o di attacchi basati su tabelle precalcolate per effettuare attacchi a forza bruta alle password.

Se due utenti hanno la stessa password, l'utilizzo di salt diversi rende la loro rappresentazione diversa. Questo significa che anche se gli attaccanti ottengono l'accesso ai dati, non saranno in grado di utilizzare tecniche come i dizionari di attacchi o le tabelle precalcolate per scoprire le password originali.

Inoltre, l'utilizzo di un salt univoco per ogni password rende più difficile per gli attaccanti effettuare attacchi di tipo "Rainbow Table", che sfruttano la ripetizione delle stesse password da parte di molte persone.

In sintesi, l'utilizzo di un salt aumenta la sicurezza delle password archiviate nei database rendendo più difficile la scoperta delle password originali, anche in caso di accesso non autorizzato ai dati.

Creiamo la tabella che memorizza le credenziali sulla base di dati. Le query di creazione e di selezione che possiamo utilizzare saranno le seguenti:

```sql
CREATE TABLE users_ex3 (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome CHAR(255) NOT NULL,
  username CHAR(255) NOT NULL,
  password CHAR(64) NOT NULL,
  salt CHAR(16) NOT NULL
);
```

Per la generazione del salt si possono usare diverse tecniche:

1. Generare il salt lato server tramite linguaggio di programmazione o scripting
2. Farlo generare direttamente al database

In entrambi i casi si consiglia di generare almeno 16 valori alfanumerici contenenti caratteri speciali (come le migliori prassi per la generazione di password consigliano).

Creiamo una stored function che permetta la generazione del salt:

```sql
CREATE FUNCTION saltFunction() RETURNS VARCHAR(16) NOT DETERMINISTIC
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
END;
```

Andiamo ora a inseire alcuni valori utilizzando la stored function per la generazione del salt, per ciascun valore da inserire:

```sql
SET @salt = saltFunction();
INSERT INTO users_ex3 (nome, username, password, salt) 
VALUES ('Bob Smith', 'bob', SHA2(CONCAT('sunshine', @salt), 256), @salt);

SET @salt = saltFunction();
INSERT INTO users_ex3 (nome, username, password, salt) 
VALUES ('Elon Musk', 'elon', SHA2(CONCAT('merlin', @salt), 256), @salt);

SET @salt = saltFunction();
INSERT INTO users_ex3 (nome, username, password, salt) 
VALUES ('Steven Thornton', 'steven', SHA2(CONCAT('123456', @salt), 256), @salt);
```

```sql
SELECT nome
FROM users_ex3
WHERE username = 'steven' AND password = SHA2(CONCAT('123456', (SELECT salt FROM users_ex3 WHERE username = 'steven')), 256)
```

Le password ora sono più sicure, anche se è possibile sempre eseguire il login e recuperarle da database:

```
'), 128) OR TRUE LIMIT 1,1; -- x
```

```
'), 128) UNION SELECT password FROM users_ex3; -- x
```

E' anche possibile recuperare il salt, anche se non particolarmente utile:

```
'), 128) UNION SELECT salt FROM users_ex3; -- x
```