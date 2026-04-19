# 🔐 SQL Injection & Password Security — Demo Didattica

> **Repository:** [github.com/dprevedello/Password-sqlinjection](https://github.com/dprevedello/Password-sqlinjection)

Questo progetto è un ambiente didattico per esplorare in modo pratico gli attacchi di **SQL injection** e le tecniche di **memorizzazione sicura delle password**. Attraverso cinque esempi progressivi, si parte da un'implementazione volutamente vulnerabile fino ad arrivare a una soluzione sicura e moderna.

> ⚠️ **Attenzione:** questo ambiente è **volutamente vulnerabile**. Non esporlo mai su una rete pubblica o di produzione. Usarlo esclusivamente in locale o in una rete isolata durante le lezioni.

---

## 📁 Struttura del repository


```
.
├── docker-compose.yml            # Orchestrazione dei 4 container
├── setup.sh                      # Script di installazione e avvio automatico
├── .env.example                  # Template variabili d'ambiente
├── .gitignore
│
├── mariadb/                      # Script di inizializzazione database
│   ├── 01_schema.sql             # Crea tabelle e popola users_ex1, users_ex2
│   └── 02_seed.sh                # Crea saltFunction e popola users_ex3
│
├── php/                          # Container PHP + Apache
│   ├── Dockerfile                # PHP 8.2 + Apache + mysqli + PDO
│   ├── entrypoint.sh             # Esegue init_bcrypt.php poi avvia Apache
│   └── init_bcrypt.php           # Popola users_ex4 con bcrypt al primo avvio
│
├── nginx/                        # Container reverse proxy
│   ├── Dockerfile                # nginx:alpine + openssl
│   ├── nginx.conf                # HTTP→HTTPS redirect, proxy app e /pma/
│   └── entrypoint.sh             # Genera certificato self-signed al primo avvio
│
└── src/                          # Sorgenti PHP del sito
    ├── db.php                    # Funzioni di accesso al database
    ├── reset.php                 # Pagina web per reinizializzare il database
    ├── style.css
    ├── index.php                 # Esempio 1 – Password in chiaro, SQLi base
    ├── example2.php              # Esempio 2 – Hash MD5, ancora vulnerabile
    ├── example3.php              # Esempio 3 – SHA2 + salt, ancora vulnerabile
    ├── example4.php              # Esempio 4 – Prepared statements + SHA2/salt
    └── example5.php              # Esempio 5 – Prepared statements + bcrypt ✅
```

---

## 🚀 Avvio dell'ambiente

### Requisiti

- [Docker](https://www.docker.com/) >= 20.x
- [Docker Compose](https://docs.docker.com/compose/) >= 2.x

### 1 — Clona il repository

```bash
git clone https://github.com/dprevedello/Password-sqlinjection.git
cd Password-sqlinjection
```

### 2 — Configura le variabili d'ambiente

```bash
cp .env.example .env
# I valori di default vanno bene per uso locale
```

### 3 — Avvia i container

```bash
docker compose up -d --build
```

### 4 — Inizializza il database 🗄️

Apri il browser e vai su:

```
https://[server_URL]/reset.php
```

Clicca su **"Sì, resetta il database"** per creare le tabelle e popolarle con i dati di esempio.

> 💡 Puoi reinizializzare il database in qualsiasi momento cliccando **Reset DB** nella navbar — utile dopo che un attacco ha modificato i dati.

### 5 — Accedi all'applicazione

| Servizio    | URL                                    | Descrizione                        |
|-------------|----------------------------------------|------------------------------------|
| 🌐 Sito demo  | `https://[server_URL]`           | I cinque esempi interattivi        |
| 🛠️ phpMyAdmin | `https://[server_URL]/pma/`           | Ispezione diretta del database     |

> 🔒 Il certificato è **self-signed**: il browser mostrerà un avviso di sicurezza alla prima apertura. Accetta l'eccezione per procedere (comportamento normale in locale).

### 6 — Fermare l'ambiente

```bash
docker compose down          # ferma i container
docker compose down -v       # ferma i container e cancella i dati del DB
```

---

## 📚 Tutorial

Gli esempi seguono una progressione: si parte da un codice completamente insicuro e, passo dopo passo, si introducono le contromisure fino ad arrivare all'implementazione corretta. Espandi ogni scheda per leggere la spiegazione e i payload di attacco da provare.

---

<details>
<summary>🔴 <strong>Esempio 1 — Password in chiaro e SQL injection base</strong> &nbsp;(<code>index.php</code>)</summary>
<br>

### Come memorizzare password sicure sul database

Nel primo esempio vediamo come può essere una versione **naive** di un sistema di autenticazione tramite password.

In questo esempio creiamo una tabella che memorizza le credenziali **in chiaro** sulla base di dati. Le query di creazione e di selezione che possiamo utilizzare saranno le seguenti:

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

Questo codice sembra funzionante. Vediamo tuttavia un attacco di SQL injection che può portare al login. Usiamo la seguente stringa nel campo **password**:

```
x' OR 'x'='x
```

Questo ci permette di accedere come uno degli utenti. Possiamo anche scegliere un utente specifico usando LIMIT:

```
' OR TRUE LIMIT 1,1; -- x
```

Possiamo addirittura scoprire la password degli utenti direttamente dalla risposta del sito:

```
' UNION SELECT password FROM users_ex1; -- x
```

> 💬 Il problema è conoscere il nome della tabella e della colonna. Ma come vedremo nell'approfondimento successivo, è un ostacolo facilmente superabile.

</details>

---

<details>
<summary>🟠 <strong>Approfondimento — Recuperare informazioni su tabelle e colonne</strong></summary>
<br>

### Recuperiamo informazioni sulle tabelle e colonne del database

Nel primo esempio abbiamo visto come forzare l'accesso. Ma affinché gli attacchi più avanzati funzionino, bisogna conoscere la struttura del database: nomi di tabelle e colonne.

MySQL espone queste informazioni tramite il database di sistema `information_schema`. Inserendo i seguenti payload nel campo **password** dell'Esempio 1, possiamo estrarle una alla volta:

**1. Nome del database:**
```
' UNION SELECT SCHEMA_NAME FROM information_schema.SCHEMATA LIMIT 1,1; -- x
```

**2. Nomi delle tabelle:**
```
' UNION SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'my_db' LIMIT 0,1; -- x
```

**3. Nomi delle colonne di una tabella:**
```
' UNION SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'my_db' AND TABLE_NAME = 'users_ex1' LIMIT 3,1; -- x
```

> 💡 Cambiando il valore di `LIMIT` (es. `LIMIT 0,1`, `LIMIT 1,1`, `LIMIT 2,1`...) si scorrono tutti i risultati uno alla volta, permettendo di mappare l'intera struttura del database con pazienza.

Con queste tre tecniche è possibile ricavare:
1. Il nome del database
2. Il nome della tabella che contiene le credenziali
3. I nomi di tutte le colonne (inclusa quella delle password)

</details>

---

<details>
<summary>🟠 <strong>Esempio 2 — Hash MD5, ancora vulnerabile</strong> &nbsp;(<code>example2.php</code>)</summary>
<br>

### Mai usare password in chiaro

Nel secondo esempio usiamo una funzione di **HASH** per memorizzare il **fingerprint della password** invece che la password in chiaro. L'hash è una funzione a senso unico: dato un input produce sempre lo stesso output, ma non è (facilmente) invertibile.

```sql
CREATE TABLE users_ex2 (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome CHAR(255) NOT NULL,
  username CHAR(255) NOT NULL,
  password CHAR(32) NOT NULL   -- MD5 produce sempre 32 caratteri
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

Ora le password sul database sono codificate, ma **non abbiamo risolto molto**. Tutti gli attacchi visti precedentemente funzionano ancora con piccole modifiche alla sintassi, perché l'input viene ancora concatenato direttamente alla query:

```
x') OR 'x'=('x
```

```
') OR TRUE LIMIT 1,1; -- x
```

```
') UNION SELECT password FROM users_ex2; -- x
```

L'hash restituito è di 32 caratteri esadecimali — questo ci rivela subito che si tratta di **MD5**. Non abbiamo bisogno della password in chiaro per autenticarci tramite SQL injection, ma se volessimo decifrarla esistono database di hash precomputati online, come [md5.gromweb.com](https://md5.gromweb.com/?md5=0571749e2ac330a7455809c6b0e7af90).

> ⚠️ **Problema:** MD5 è veloce, non prevede un salt, ed è soggetto ad attacchi a dizionario e rainbow table.

</details>

---

<details>
<summary>🟡 <strong>Esempio 3 — SHA2 con salt, ancora vulnerabile</strong> &nbsp;(<code>example3.php</code>)</summary>
<br>

### Aggiungiamo un po' di sale

Per memorizzare le password in modo più sicuro, l'utilizzo dell'hash da solo non è sufficiente. **MD5** è vulnerabile agli attacchi a dizionario, e anche algoritmi più moderni come **SHA-2** soffrono dello stesso problema se le password degli utenti sono prevedibili.

Viene in nostro aiuto il concetto di **salt**: un valore casuale che viene combinato con la password prima di calcolare l'hash. I vantaggi principali sono:

- Due utenti con la stessa password avranno hash diversi nel database
- Rende inutilizzabili le rainbow table precomputate
- Ogni password deve essere attaccata individualmente

```sql
CREATE TABLE users_ex3 (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome CHAR(255) NOT NULL,
  username CHAR(255) NOT NULL,
  password CHAR(64) NOT NULL,   -- SHA2-256 produce 64 caratteri
  salt CHAR(16) NOT NULL
);
```

Per generare il salt direttamente nel database, creiamo una stored function:

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
-- (ripetere per ogni utente con un nuovo @salt)
```

```sql
-- Query di login: recupera il salt dell'utente e ricalcola l'hash
SELECT nome FROM users_ex3
WHERE username = 'steven'
  AND password = SHA2(CONCAT('123456', (SELECT salt FROM users_ex3 WHERE username = 'steven')), 256);
```

Le password ora sono più difficili da craccare offline, ma **la SQL injection è ancora possibile** perché l'input viene ancora concatenato alla query. I payload si adattano alla nuova sintassi con `SHA2(CONCAT(...), 256)`:

```
'), 128) OR TRUE LIMIT 1,1; -- x
```

```
'), 128) UNION SELECT password FROM users_ex3; -- x
```

È anche possibile recuperare il salt (anche se di scarsa utilità per l'attaccante):

```
'), 128) UNION SELECT salt FROM users_ex3; -- x
```

> ⚠️ **Problema:** il hashing delle password è migliorato, ma la vulnerabilità alla SQL injection rimane intatta finché l'input non viene gestito correttamente nel codice PHP.

</details>

---

<details>
<summary>🟢 <strong>Esempio 4 — Prepared statements, SQL injection neutralizzata</strong> &nbsp;(<code>example4.php</code>)</summary>
<br>

### Proteggiamoci da SQL Injection

Come abbiamo visto, gli attacchi di SQL injection espongono in maniera incontrollata i dati del database. È quindi giunto il momento di proteggere il codice PHP usando le **prepared statements**.

#### Come funzionano le prepared statements

In una query normale l'input dell'utente viene **concatenato** alla stringa SQL — ed è proprio questo il problema. Con le prepared statements invece:

1. La query viene inviata al database **con dei segnaposto (`?`)** al posto dei valori
2. Il database la compila e la memorizza
3. Solo dopo vengono inviati i valori, **separatamente** dalla query

In questo modo, anche se l'utente inserisce del codice SQL, il database lo tratterà come puro testo e non come un comando da eseguire.

```php
<?php
  $username = mysqli_real_escape_string($db, $_POST["username"]);
  $pass     = mysqli_real_escape_string($db, $_POST["password"]);

  $sql = "SELECT nome
          FROM users_ex3
          WHERE username = ?
            AND password = SHA2(CONCAT(?, (SELECT salt FROM users_ex3 WHERE username = ?)), 256);";

  $stmt = $db->prepare($sql);           // 1. Prepara la query con i segnaposto
  $stmt->bind_param("sss", $username, $pass, $username);  // 2. Lega i valori
  $stmt->bind_result($nome);
  $stmt->execute();                      // 3. Esegue in modo sicuro

  if ($stmt->fetch())
    echo "<p>Bentornato: {$nome}</p>";
  else
    echo "<p>LOGIN FALLITO</p>";

  $stmt->close();
?>
```

Prova ora gli stessi payload degli esempi precedenti: **non funzionerà più nessuno di essi**. ✅

> ⚠️ **Problema residuo:** SHA2 è un algoritmo generico e veloce, non progettato per l'hashing delle password. Con una GPU moderna è possibile tentare miliardi di hash al secondo contro il database copiato.

</details>

---

<details>
<summary>✅ <strong>Esempio 5 — Bcrypt, la soluzione completa</strong> &nbsp;(<code>example5.php</code>)</summary>
<br>

### Questione di tempo

Anche con SQL injection neutralizzata, dobbiamo ragionare come se fossimo nello **scenario peggiore**: l'attaccante ha ottenuto una copia del database e può lavorare offline sugli hash, senza limiti di tempo e sfruttando la potenza delle GPU moderne.

La soluzione è usare una funzione di hash **computazionalmente onerosa**: più tempo ci vuole per calcolare un singolo hash, meno tentativi al secondo può fare un attaccante.

#### Perché Bcrypt?

- È **lento per design**: il parametro `cost` controlla quante iterazioni vengono eseguite
- Include un **salt casuale automaticamente** per ogni password
- Aumentando il `cost` nel tempo si mantiene la sicurezza al crescere della potenza hardware
- È resistente agli attacchi rainbow table

```sql
CREATE TABLE users_ex4 (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome CHAR(255) NOT NULL,
  username CHAR(255) NOT NULL,
  password CHAR(60) NOT NULL   -- bcrypt produce sempre 60 caratteri
);
```

La tabella viene popolata in PHP perché `password_hash()` genera un salt casuale ad ogni chiamata e non esiste un equivalente nativo in MySQL:

```php
<?php
  $sql  = "INSERT INTO users_ex4 (nome, username, password) VALUES (?, ?, ?);";
  $stmt = $db->prepare($sql);

  $pwd = password_hash('sunshine', PASSWORD_BCRYPT, ['cost' => 13]);
  $name = 'Bob Smith'; $user = 'bob';
  $stmt->bind_param("sss", $name, $user, $pwd);
  $stmt->execute();
  // (ripetere per ogni utente)

  $stmt->close();
?>
```

Il parametro **`cost`** definisce la difficoltà di calcolo: il valore di default è 10, ma 13 o superiore è raccomandato per sistemi moderni. Raddoppiare il cost significa raddoppiare il tempo di calcolo.

Il codice per la verifica della password al login:

```php
<?php
  $username = mysqli_real_escape_string($db, $_POST["username"]);
  $pass     = mysqli_real_escape_string($db, $_POST["password"]);

  // Recupera solo username e hash — nessun confronto in SQL
  $sql = "SELECT nome, password FROM users_ex4 WHERE username = ?;";
  $stmt = $db->prepare($sql);

  $stmt->bind_param("s", $username);
  $stmt->bind_result($nome, $password);
  $stmt->execute();

  // password_verify() gestisce automaticamente il salt incluso nell'hash
  if ($stmt->fetch() && password_verify($pass, $password))
    echo "<p>Bentornato: {$nome}</p>";
  else
    echo "<p>LOGIN FALLITO</p>";

  $stmt->close();
?>
```

> ✅ **Questo è l'approccio corretto:** prepared statements contro la SQL injection + bcrypt con cost factor adeguato per la protezione degli hash.

</details>

---

## 🗄️ Note tecniche su `init.sql` e `reset.php`

`init.sql` gestisce la creazione di tutte le tabelle e la popolazione dei dati per gli esempi 1–4, inclusa la stored function per la generazione del salt per l'Esempio 3.

`users_ex4` (Esempio 5) viene popolata direttamente da `reset.php` in PHP perché `password_hash()` con bcrypt genera un salt casuale ad ogni esecuzione e non esiste una funzione bcrypt nativa in MySQL.
