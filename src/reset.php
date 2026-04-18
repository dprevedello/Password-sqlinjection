<!doctype html>
<html lang="it">
  <head>
    <title>Reset Database</title>
    <meta charset="utf-8">
    <meta name="author" content="Daniele Prevedello">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet"/>
    <!-- MDB -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <header>
      <nav class="navbar navbar-expand-sm navbar-light bg-light fixed-top">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Visualizza menu">
            <i class="fas fa-bars"></i>
          </button>
          <div class="collapse navbar-collapse" id="topNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <li class="nav-item"><a class="nav-link" href="index.php">Esempio 1</a></li>
              <li class="nav-item"><a class="nav-link" href="example2.php">Esempio 2</a></li>
              <li class="nav-item"><a class="nav-link" href="example3.php">Esempio 3</a></li>
              <li class="nav-item"><a class="nav-link" href="example4.php">Esempio 4</a></li>
              <li class="nav-item"><a class="nav-link" href="example5.php">Esempio 5</a></li>
            </ul>
            <div class="d-flex align-items-center">
              <a class="nav-link" href="phpmyadmin/" target="_blank">
                <button type="button" class="btn btn-info me-3">phpMyAdmin</button>
              </a>
              <a class="nav-link active" aria-current="page" href="reset.php">
                <button type="button" class="btn btn-primary me-3">Reset DB</button>
              </a>
            </div>
          </div>
        </div>
      </nav>
    </header>

    <section class="h-100" style="padding-top: 80px;">
      <div class="container py-5">
        <div class="row justify-content-center">
          <div class="col-md-8 col-lg-6">
            <div class="card rounded-3 shadow">
              <div class="card-body p-md-5">
                <div class="text-center mb-4">
                  <i class="fas fa-database fa-3x text-primary mb-3"></i>
                  <h4>Reset del database</h4>
                  <p class="text-muted">Questa operazione elimina e ricrea tutte le tabelle,
                    riportando il database allo stato iniziale.</p>
                </div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {

    include 'db.php';
    global $db;

    $sqlFile = __DIR__ . '/init.sql';
    $errors  = [];

    // -------------------------------------------------------------------
    // 1. Leggi ed esegui init.sql
    // -------------------------------------------------------------------
    if (!file_exists($sqlFile)) {
        $errors[] = "File init.sql non trovato in: $sqlFile";
    } else {
        $sql = file_get_contents($sqlFile);

        // mysqli::multi_query non gestisce DELIMITER, che è un costrutto
        // del client MySQL, non del protocollo. Lo sostituiamo prima di
        // eseguire, raccogliendo le stored function separatamente.
        //
        // Strategia: splittiamo il file sui blocchi DELIMITER $$ … DELIMITER ;
        // ed eseguiamo ogni blocco con la query appropriata.

        // Separa i blocchi delimitati da DELIMITER $$ dal resto
        $parts = preg_split('/DELIMITER\s+\$\$/', $sql);

        $plainSql    = $parts[0];          // tutto prima del primo DELIMITER $$
        $afterDelim  = isset($parts[1]) ? $parts[1] : '';

        // Estrai il corpo della funzione (fino a DELIMITER ;) e ciò che segue
        $delimParts  = preg_split('/DELIMITER\s+;/', $afterDelim, 2);
        $functionSql = isset($delimParts[0]) ? trim($delimParts[0]) : '';
        $restSql     = isset($delimParts[1]) ? $delimParts[1] : '';

        // Esegui le query "normali" (plain + rest) con multi_query
        foreach ([$plainSql, $restSql] as $block) {
            $block = trim($block);
            if ($block === '') continue;

            $db->multi_query($block);
            do {
                if ($db->errno) {
                    $errors[] = "Errore SQL: " . $db->error;
                }
            } while ($db->more_results() && $db->next_result());
        }

        // Esegui la stored function separatamente (senza DELIMITER)
        if ($functionSql !== '') {
            if ($db->query($functionSql) === FALSE) {
                $errors[] = "Errore creazione stored function: " . $db->error;
            }
        }
    }

    // -------------------------------------------------------------------
    // 2. Popola users_ex4 con bcrypt (non gestibile in SQL puro)
    // -------------------------------------------------------------------
    if (empty($errors)) {
        $users = [
            ['Bob Smith',       'bob',    'sunshine'],
            ['Elon Musk',       'elon',   'merlin'],
            ['Steven Thornton', 'steven', '123456'],
        ];

        $stmt = $db->prepare("INSERT INTO users_ex4 (nome, username, password) VALUES (?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Errore prepare: " . $db->error;
        } else {
            foreach ($users as [$nome, $username, $plainPassword]) {
                $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 13]);
                $stmt->bind_param("sss", $nome, $username, $hash);
                if (!$stmt->execute()) {
                    $errors[] = "Errore inserimento bcrypt per $username: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }

    close_db();

    // -------------------------------------------------------------------
    // 3. Mostra risultato
    // -------------------------------------------------------------------
    if (empty($errors)) {
        echo "
        <div class='alert alert-success text-center' role='alert'>
          <i class='fas fa-check-circle fa-2x mb-2'></i><br>
          <strong>Database reinizializzato correttamente.</strong>
        </div>
        <div class='text-center mt-3'>
          <a href='index.php' class='btn btn-primary'>
            <i class='fas fa-arrow-left me-2'></i>Torna agli esempi
          </a>
        </div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>";
        echo "<i class='fas fa-times-circle me-2'></i><strong>Si sono verificati degli errori:</strong><ul class='mt-2 mb-0'>";
        foreach ($errors as $e) {
            echo "<li>" . htmlspecialchars($e) . "</li>";
        }
        echo "</ul></div>";
        echo "<div class='text-center mt-3'>
                <a href='reset.php' class='btn btn-secondary'>
                  <i class='fas fa-redo me-2'></i>Riprova
                </a>
              </div>";
    }

} else {
    // Mostra il form di conferma
    echo "
    <form method='POST' action='reset.php'>
      <div class='alert alert-warning text-center mb-4' role='alert'>
        <i class='fas fa-exclamation-triangle me-2'></i>
        Tutti i dati esistenti verranno <strong>eliminati</strong> e ricreati da zero.
      </div>
      <div class='d-grid gap-2'>
        <button type='submit' name='confirm_reset' value='1' class='btn btn-danger btn-lg'>
          <i class='fas fa-trash-alt me-2'></i>Sì, resetta il database
        </button>
        <a href='index.php' class='btn btn-outline-secondary btn-lg'>
          <i class='fas fa-times me-2'></i>Annulla
        </a>
      </div>
    </form>";
}
?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- MDB -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.js"></script>
  </body>
</html>
