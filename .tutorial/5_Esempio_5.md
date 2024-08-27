# Questione di tempo

Ora che l'accesso al database è stato reso più sicuro, potremmo pensare che le tecniche di protezione che abbaimo applicato alle password memorizzate possano essere sufficienti per garantire la tutela dei nostri clienti.
Tuttavia dobbiamo ragionare come se fossimo nello scenario peggiore e cioè che un ipotetico attaccante sia comunque riuscito ad accedere ai dati memorizzati nel database e ne abbai fatto una copia.

L'attaccante ha tutto il tempo per effettuare atacchi sugli hash memorizzati, magari sfruttando la potenza di calcolo delle moderne **GPU** che riescono tramite attacchi a dizionario o a forza bruta a recuperare il testo in chiaro della password originale.
La soluzione a questo scenario è l'utilizzo di una funziona di hash che sia **computazionalmente onerosa**.

**Bcrypt** è un algoritmo di hash di password ampiamente utilizzato nei linguaggi di programmazione. 
Bcrypt fornisce una protezione più forte rispetto ad altri algoritmi di hash come MD5 e SHA1, perché è più lento e richiede più risorse computazionali per generare un hash della password. 
Questo lo rende più resistente agli attacchi di forza bruta in cui un attaccante cerca di indovinare la password attraverso l'utilizzo di tecniche di guessing automatizzate.

Inoltre, Bcrypt utilizza un meccanismo di "salt" casuale per ogni password, che aggiunge un ulteriore livello di sicurezza. 
Il salt è una stringa casuale aggiunta alla password prima di essere codificata, questo rende gli attacchi di tipo "rainbow table" molto più difficili da eseguire. 
Un attacco di tipo rainbow table prevede la precomputazione di tutti gli hash possibili in modo da poterli confrontare con gli hash delle password, ma l'utilizzo di salt rende questa tecnica molto meno efficace.

In sintesi, l'utilizzo di Bcrypt per proteggere le password memorizzate su un database fornisce una protezione più forte contro gli attacchi di forza bruta e di tipo rainbow table, due delle tecniche più comuni utilizzate dagli attaccanti per violare le password.

Vediamo come cambia il codice del form di login per usare questa tecnica.
Per prima cosa creaiamo la tabella e popoliamola:

```sql
CREATE TABLE users_ex4 (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome CHAR(255) NOT NULL,
  username CHAR(255) NOT NULL,
  password CHAR(60) NOT NULL,
);
```

```php
<?php
  $sql = "INSERT INTO users_ex4 (nome, username, password) VALUES (?, ?, ?);";
  $stmt = $db->prepare($sql);

  $pwd = password_hash('sunshine', PASSWORD_BCRYPT, ['cost' => 13,]);
  $name = 'Bob Smith';
  $user = 'bob';
  $stmt->bind_param("sss", $name, $user, $pwd);
  $stmt->execute();

  $pwd = password_hash('merlin', PASSWORD_BCRYPT, ['cost' => 13,]);
  $name = 'Elon Musk';
  $user = 'elon';
  $stmt->bind_param("sss", $name, $user, $pwd);
  $stmt->execute();

  $pwd = password_hash('123456', PASSWORD_BCRYPT, ['cost' => 13,]);
  $name = 'Steven Thornton';
  $user = 'steven';
  $stmt->bind_param("sss", $name, $user, $pwd);
  $stmt->execute();

  $stmt->close();
?>
```

Si noti l'utilizzo della funzione **password_hash** a cui forniamo oltre alla password, la costante **PASSWORD_BCRYPT** che va a specificare che l'algoritmo che desideriamo usare è il **bcrypt**.
Inoltre nella funzione **password_hash** notiamo anche l'utilizzo di un parametro chiamato **cost**. Questo parametro va a settare la difficoltà di calcolo per la nostra funzione bcrypt. In particolare più è alto il suo valore, più sarà il tempo che dovrà essere usato per calcolarla. Il valore di default è 10.

Vediamo ora il codice per verificare la correttezza della password:

```php
<?php
  $username = mysqli_real_escape_string($db, $_POST["username"]);
  $pass = mysqli_real_escape_string($db, $_POST["password"]);

  $sql = "SELECT nome, password
          FROM users_ex4
          WHERE username = ?;";
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
