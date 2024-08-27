# Come memorizzare password sicure sul database

Nel primo esempio vediamo come può essere una versione **naive** di un sistema di autenticazione tramite password.

In questo esempio creiamo una tabella che memorizza le credenziali in chiaro sulla base di dati. Le query di creazione e di selezione che possiamo utilizzare saranno le seguenti:

```sql
CREATE TABLE users_ex1 (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome CHAR(255) NOT NULL,
  username CHAR(255) NOT NULL,
  password CHAR(255) NOT NULL
);
```
```sql
INSERT INTO users_ex1 VALUES ('Bob Smith', 'bob', 'sunshine');
INSERT INTO users_ex1 VALUES ('Elon Musk', 'elon', 'merlin');
INSERT INTO users_ex1 VALUES ('Steven Thornton', 'steven', '123456');
```
```sql
SELECT nome
FROM users_ex1
WHERE username = 'userXX' AND password = 'passXX';
```

Questo codice sembra funzionante. Vediamo tuttavia un attacco di SQL injection che può portare al login.<br>Usiamo la seguente password:

```
x' OR 'x'='x
```

Questo ci permette di accedere come uno degli utenti.<br>
Possiamo anche scegliere un utente a piacere:

```
' OR TRUE LIMIT 1,1; -- x
```

Possiamo anche scoprire la password utilizzata dall'utente:

```
' UNION SELECT password FROM users_ex1; -- x
```

Certo il problema è conoscere il nome della colonna **password** e soprattutto della tabella **users_ex1**. Ma come vedremo è un problema superabile.