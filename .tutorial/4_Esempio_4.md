# Proteggiamoci da SQL Injection

Come abbaimo visto nei precedenti esempi, gli attacchi di **SQL injection** vanno ad esporre in maniera incontrollata i nostri dati presenti nel database.
E' quindi giunto il momento di protegegre il codice php andando a **sanificare** gli input che arrivano dai form, ma soprattutto andando ad usare le **prepared statements**.

Il primo passaggio è andare ad utilizzare la funzione **mysqli_real_escape_string** sui dati passati tramite **POST** così da usare i caratteri di escape sull'input fornito dall'utente.
Il secondo passaggio è quello delle prepared statements:

Le **prepared statements** sono una tecnica per eseguire query SQL in modo sicuro ed efficiente.

In pratica, un prepared statement è una query SQL precompilata che viene inviata al database insieme ai parametri della query, senza che questi ultimi siano direttamente concatenati alla stringa SQL. Questo rende la query SQL "preparata" per l'esecuzione, ma senza che venga eseguita immediatamente.

Quando il database riceve il prepared statement, lo memorizza in una sorta di cache e lo analizza per identificare le parti della query che devono essere parametrizzate. Successivamente, quando viene richiesto di eseguire la query, i parametri vengono inviati al database separatamente dalla query SQL precompilata.

Questo approccio offre diversi vantaggi:
- Prevenzione delle SQL injection: l'utilizzo dei prepared statement impedisce l'inserimento di codice SQL dannoso nel parametro della query, poiché i parametri vengono passati separatamente dalla stringa SQL. In questo modo, anche se un malintenzionato cerca di inserire codice dannoso come parte della query, il database lo tratterà come testo normale e non come un comando SQL.
- Maggiore efficienza: l'utilizzo dei prepared statement riduce il tempo di esecuzione delle query SQL perché il database non deve analizzare e compilare la query ogni volta che viene eseguita. Inoltre, l'utilizzo dei prepared statement consente di eseguire la stessa query SQL più volte con parametri diversi, senza dover ripetere l'intera query ogni volta.
- Maggiore leggibilità del codice: l'utilizzo dei prepared statement rende il codice più leggibile perché separa la logica della query SQL dalla logica dell'applicazione. In questo modo, è più facile modificare e mantenere la logica della query SQL e dell'applicazione separatamente.

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
