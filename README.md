# SQL Injection Demo

Ambiente didattico per la dimostrazione pratica degli attacchi SQL injection e delle relative contromisure, sviluppato per uso in classe.

## Struttura del repository

```
.
├── docker-compose.yml       # Orchestrazione dei container
├── .env.example             # Template variabili d'ambiente
├── .gitignore
├── php/
│   └── Dockerfile           # Immagine PHP 8.2 + Apache + mysqli
└── src/                     # Sorgenti PHP del sito
    ├── init.sql             # Crea e popola tutte le tabelle del demo
    ├── db.php               # Funzioni di accesso al database
    ├── reset.php            # Pagina web per reinizializzare il database
    ├── style.css
    ├── index.php            # Esempio 1 – SQL injection base
    ├── example2.php         # Esempio 2 – Password MD5, ancora vulnerabile
    ├── example3.php         # Esempio 3 – SHA2 + salt, ancora vulnerabile
    ├── example4.php         # Esempio 4 – Prepared statements + SHA2/salt
    └── example5.php         # Esempio 5 – Prepared statements + bcrypt
```

## Requisiti

- [Docker](https://www.docker.com/) >= 20.x
- [Docker Compose](https://docs.docker.com/compose/) >= 2.x

## Avvio rapido

### 1. Clona il repository

```bash
git clone <url-del-repository>
cd <nome-repository>
```

### 2. Configura le variabili d'ambiente

```bash
cp .env.example .env
# I valori di default vanno bene per uso locale
```

### 3. Avvia i container

```bash
docker compose up -d --build
```

### 4. Inizializza il database

Apri [http://localhost:8080/reset.php](http://localhost:8080/reset.php) e clicca su **"Sì, resetta il database"**.

> ⚠️ Puoi reinizializzare il database in qualsiasi momento cliccando **Reset DB** nella navbar.

### 5. Accedi all'applicazione

| Servizio    | URL                                              |
|-------------|--------------------------------------------------|
| Sito demo   | [http://localhost:8080](http://localhost:8080)   |
| phpMyAdmin  | [http://localhost:8081](http://localhost:8081)   |

---

## Tutorial

<details>
<summary><strong>Esempio 1 — Password in chiaro e SQL injection base</strong> (<code>index.php</code>)</summary>
<br>

### Come memorizzare password sicure sul database

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

Questo codice sembra funzionante. Vediamo tuttavia un attacco di SQL injection che può portare al login.
Usiamo la seguente password:

```
x' OR 'x'='x
```

Questo ci permette di accedere come uno degli utenti.
Possiamo anche scegliere un utente a piacere:

```
' OR TRUE LIMIT 1,1; -- x
```

Possiamo anche scoprire la password utilizzata dall'utente:

```
' UNION SELECT password FROM users_ex1; -- x
```

Certo il problema è conoscere il nome della colonna **password** e soprattutto della tabella **users_ex1**. Ma come vedremo è un problema superabile.

</details>

<details>
<summary><strong>Approfondimento — Recuperare informazioni su tabelle e colonne</strong></summary>
<br>

### Recuperiamo informazioni sulle tabelle e colonne del database

Nel primo esempio abbiamo visto come forzare l'accesso al sito web con un attacco di SQL injection. Ma affinché possa funzionare, bisogna avere una certa conoscenza su come sono fatte le tabelle e sui loro nomi.

In questo esempio proveremo ad estrarre queste informazioni:

```
' UNION SELECT SCHEMA_NAME FROM information_schema.SCHEMATA LIMIT 1,1; -- x
```

```
' UNION SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'my_db' LIMIT 0,1; -- x
```

```
' UNION SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'my_db' AND TABLE_NAME = 'users_ex1' LIMIT 3,1; -- x
```

Utilizzando questi tre frammenti di query all'interno del campo password, riusciamo con molta pazienza a recuperare tutte le informazioni che caratterizzano il nostro form di login:
1. Nome del database
2. Nome della tabella che contiene le credenziali
3. Nome della colonna che contiene la password (o gli altri campi della tabella)

</details>

<details>
<summary><strong>Esempio 2 — Hash MD5, ancora vulnerabile</strong> (<code>example2.php</code>)</summary>
<br>

### Mai usare password in chiaro

Nel secondo esempio usiamo una funzione di **HASH** per memorizzare il **fingerprint della password** invece che la password in chiaro.

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

Ora le password sul database sono codificate, ma non abbiamo risolto molto. Tutti gli attacchi visti precedentemente funzionano ancora con piccole modifiche:

```
x') OR 'x'=('x
```

Questo ci permette di accedere come uno degli utenti. Possiamo anche scegliere un utente a piacere:

```
') OR TRUE LIMIT 1,1; -- x
```

Possiamo anche scoprire la password utilizzata dall'utente:

```
') UNION SELECT password FROM users_ex2; -- x
```

La password è ovviamente codificata con MD5 (lo intuiamo grazie alla lunghezza dell'hash di 32 caratteri).
Non abbiamo bisogno di conoscere la password in chiaro, ma se volessimo, basta usare [un sito web come questo](https://md5.gromweb.com/?md5=0571749e2ac330a7455809c6b0e7af90).

</details>

<details>
<summary><strong>Esempio 3 — SHA2 con salt, ancora vulnerabile</strong> (<code>example3.php</code>)</summary>
<br>

### Aggiungiamo un po' di sale

Per memorizzare le password in modo sicuro nel database l'utilizzo dell'**HASH** non è sufficiente. In particolare **MD5** è noto per essere un algoritmo che presenta un alto numero di collisioni, ma soprattutto è vulnerabile ad attacchi a dizionario come mostrato nel precedente esempio.

La prima ottimizzazione da fare è utilizzare una funzione di HASH moderna come **SHA-2** che limita le collisioni. Tuttavia non si è ancora protetti dagli attacchi a dizionario, soprattutto se gli utenti usano password "semplici da ricordare". Viene in nostro aiuto il concetto di **"salt"**.

L'utilizzo di un "salt" durante il calcolo dell'hash delle password è una pratica comune per aumentare la sicurezza delle password. Un salt è un **valore casuale che viene combinato con la password** prima di essere inviata alla funzione di HASH. L'obiettivo del salt è quindi di rendere più difficile per gli attaccanti l'utilizzo di dizionari di attacchi o di attacchi basati su tabelle precalcolate.

Se due utenti hanno la stessa password, l'utilizzo di salt diversi rende la loro rappresentazione diversa. Questo significa che anche se gli attaccanti ottengono l'accesso ai dati, non saranno in grado di utilizzare tecniche come i dizionari di attacchi o le tabelle precalcolate per scoprire le password originali.

Inoltre, l'utilizzo di un salt univoco per ogni password rende più difficile effettuare attacchi di tipo **"Rainbow Table"**, che sfruttano la ripetizione delle stesse password da parte di molte persone.

Creiamo la tabella e la stored function per la generazione del salt:

```sql
CREATE TABLE users_ex3 (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome CHAR(255) NOT NULL,
  username CHAR(255) NOT NULL,
  password CHAR(64) NOT NULL,
  salt CHAR(16) NOT NULL
);
```

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

```sql
SET @salt = saltFunction();
INSERT INTO users_ex3 (nome, username, password, salt)
VALUES ('Bob Smith', 'bob', SHA2(CONCAT('sunshine', @salt), 256), @salt);
-- (ripetere per ogni utente)
```

```sql
SELECT nome
FROM users_ex3
WHERE username = 'steven'
  AND password = SHA2(CONCAT('123456', (SELECT salt FROM users_ex3 WHERE username = 'steven')), 256);
```

Le password ora sono più sicure, ma la SQL injection è ancora possibile perché l'input non viene sanificato:

```
'), 128) OR TRUE LIMIT 1,1; -- x
```

```
'), 128) UNION SELECT password FROM users_ex3; -- x
```

È anche possibile recuperare il salt (anche se non particolarmente utile):

```
'), 128) UNION SELECT salt FROM users_ex3; -- x
```

</details>

<details>
<summary><strong>Esempio 4 — Prepared statements, SQL injection neutralizzata</strong> (<code>example4.php</code>)</summary>
<br>

### Proteggiamoci da SQL Injection

Come abbiamo visto nei precedenti esempi, gli attacchi di **SQL injection** vanno ad esporre in maniera incontrollata i nostri dati presenti nel database. È quindi giunto il momento di proteggere il codice PHP andando a **sanificare** gli input che arrivano dai form e soprattutto usando le **prepared statements**.

Le **prepared statements** sono una tecnica per eseguire query SQL in modo sicuro ed efficiente: la query SQL viene precompilata e inviata al database separatamente dai parametri, senza che questi ultimi vengano mai concatenati alla stringa SQL. Questo approccio offre diversi vantaggi:

- **Prevenzione delle SQL injection:** i parametri vengono passati separatamente dalla stringa SQL, quindi anche se un malintenzionato cerca di inserire codice dannoso, il database lo tratterà come testo normale.
- **Maggiore efficienza:** il database non deve analizzare e compilare la query ogni volta che viene eseguita.
- **Maggiore leggibilità del codice:** separa la logica della query SQL dalla logica dell'applicazione.

Ecco il codice che possiamo usare per proteggere la query che controlla le credenziali sul database:

```php
<?php
  $username = mysqli_real_escape_string($db, $_POST["username"]);
  $pass = mysqli_real_escape_string($db, $_POST["password"]);

  $sql = "SELECT nome
          FROM users_ex3
          WHERE username = ? AND password = SHA2(CONCAT(?, (SELECT salt FROM users_ex3 WHERE username = ?)), 256);";
  $stmt = $db->prepare($sql);

  $stmt->bind_param("sss", $username, $pass, $username);
  $stmt->bind_result($nome);
  $stmt->execute();

  if ($stmt->fetch())
    echo "<p>Bentornato: {$nome}</p>";
  else
    echo "<p>LOGIN FALLITO</p>";

  $stmt->close();
?>
```

</details>

<details>
<summary><strong>Esempio 5 — Bcrypt, la soluzione completa</strong> (<code>example5.php</code>)</summary>
<br>

### Questione di tempo

Ora che l'accesso al database è stato reso più sicuro, potremmo pensare che le tecniche di protezione applicate alle password memorizzate siano sufficienti. Tuttavia dobbiamo ragionare come se fossimo nello scenario peggiore: un ipotetico attaccante è comunque riuscito ad accedere ai dati memorizzati nel database e ne ha fatto una copia.

L'attaccante ha tutto il tempo per effettuare attacchi sugli hash memorizzati, magari sfruttando la potenza di calcolo delle moderne **GPU** che riescono tramite attacchi a dizionario o a forza bruta a recuperare il testo in chiaro della password originale. La soluzione è l'utilizzo di una funzione di hash **computazionalmente onerosa**.

**Bcrypt** è un algoritmo di hash di password ampiamente utilizzato nei linguaggi di programmazione. Fornisce una protezione più forte rispetto ad altri algoritmi come MD5 e SHA1 perché è più lento e richiede più risorse computazionali. Inoltre utilizza un meccanismo di **salt casuale** per ogni password, rendendo gli attacchi di tipo rainbow table molto più difficili da eseguire.

Per prima cosa creiamo la tabella e popoliamola tramite PHP (bcrypt non è disponibile nativamente in MySQL):

```sql
CREATE TABLE users_ex4 (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome CHAR(255) NOT NULL,
  username CHAR(255) NOT NULL,
  password CHAR(60) NOT NULL
);
```

```php
<?php
  $sql = "INSERT INTO users_ex4 (nome, username, password) VALUES (?, ?, ?);";
  $stmt = $db->prepare($sql);

  $pwd = password_hash('sunshine', PASSWORD_BCRYPT, ['cost' => 13]);
  $name = 'Bob Smith'; $user = 'bob';
  $stmt->bind_param("sss", $name, $user, $pwd);
  $stmt->execute();

  // (ripetere per ogni utente)
  $stmt->close();
?>
```

Si noti l'utilizzo di **`password_hash`** con la costante `PASSWORD_BCRYPT` e il parametro **`cost`**, che definisce la difficoltà computazionale: più è alto, più tempo sarà necessario per calcolare l'hash (valore di default: 10).

Il codice per la verifica della password al login:

```php
<?php
  $username = mysqli_real_escape_string($db, $_POST["username"]);
  $pass = mysqli_real_escape_string($db, $_POST["password"]);

  $sql = "SELECT nome, password FROM users_ex4 WHERE username = ?;";
  $stmt = $db->prepare($sql);

  $stmt->bind_param("s", $username);
  $stmt->bind_result($nome, $password);
  $stmt->execute();

  if ($stmt->fetch() && password_verify($pass, $password))
    echo "<p>Bentornato: {$nome}</p>";
  else
    echo "<p>LOGIN FALLITO</p>";

  $stmt->close();
?>
```

La funzione **`password_verify`** confronta la password fornita con l'hash memorizzato, gestendo automaticamente il salt incluso nell'hash bcrypt.

</details>

---

## Note su `init.sql` e `reset.php`

`init.sql` gestisce la creazione di tutte le tabelle e la popolazione dei dati per gli esempi 1–4 (inclusa la stored function per la generazione del salt).  
`users_ex4` (esempio 5) viene popolata da `reset.php` in PHP perché `password_hash()` con bcrypt genera un salt casuale ad ogni esecuzione e non esiste una funzione bcrypt nativa in MySQL.

---

## Fermare l'ambiente

```bash
docker compose down
```

Per rimuovere anche i dati del database:

```bash
docker compose down -v
```

---

> ⚠️ Questo ambiente è volutamente vulnerabile. **Non esporlo mai su una rete pubblica o di produzione.**  
> Usarlo esclusivamente in locale o in una rete isolata durante le lezioni.
