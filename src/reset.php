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
              <a class="nav-link" href="/pma/" target="_blank">
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

    $errors = [];

    // -------------------------------------------------------------------
    // 1. Ricrea tabelle (schema)
    // -------------------------------------------------------------------
    $schemaSql = "
        DROP TABLE IF EXISTS users_ex1;
        CREATE TABLE users_ex1 (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nome CHAR(255) NOT NULL,
            username CHAR(255) NOT NULL,
            password CHAR(255) NOT NULL
        ) ENGINE=InnoDB;
        INSERT INTO users_ex1 (nome, username, password) VALUES
            ('Bob Smith','bob','sunshine'),
            ('Elon Musk','elon','merlin'),
            ('Steven Thornton','steven','123456');

        DROP TABLE IF EXISTS users_ex2;
        CREATE TABLE users_ex2 (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nome CHAR(255) NOT NULL,
            username CHAR(255) NOT NULL,
            password CHAR(32) NOT NULL
        ) ENGINE=InnoDB;
        INSERT INTO users_ex2 (nome, username, password) VALUES
            ('Bob Smith','bob',MD5('sunshine')),
            ('Elon Musk','elon',MD5('merlin')),
            ('Steven Thornton','steven',MD5('123456'));

        DROP TABLE IF EXISTS users_ex3;
        CREATE TABLE users_ex3 (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nome CHAR(255) NOT NULL,
            username CHAR(255) NOT NULL,
            password CHAR(64) NOT NULL,
            salt CHAR(16) NOT NULL
        ) ENGINE=InnoDB;

        DROP TABLE IF EXISTS users_ex4;
        CREATE TABLE users_ex4 (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nome CHAR(255) NOT NULL,
            username CHAR(255) NOT NULL,
            password CHAR(60) NOT NULL
        ) ENGINE=InnoDB;
    ";

    $db->multi_query($schemaSql);
    do {
        if ($db->errno) {
            $errors[] = "Errore schema: " . $db->error;
        }
    } while ($db->more_results() && $db->next_result());

    // -------------------------------------------------------------------
    // 2. Ricrea stored function saltFunction e popola users_ex3
    // -------------------------------------------------------------------
    if (empty($errors)) {
        $queries = [
            "DROP FUNCTION IF EXISTS saltFunction",
            "CREATE FUNCTION saltFunction() RETURNS VARCHAR(16) NOT DETERMINISTIC
             BEGIN
                 DECLARE salt VARCHAR(16) DEFAULT '';
                 DECLARE saltCharset VARCHAR(100) DEFAULT 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#\$%^&*()_+-=';
                 DECLARE i INT DEFAULT 1;
                 DECLARE c INT;
                 WHILE i <= 16 DO
                     SET c = CONVERT(RAND() * LENGTH(saltCharset) + 1, INT);
                     SET salt = CONCAT(salt, SUBSTRING(saltCharset, c, 1));
                     SET i = i + 1;
                 END WHILE;
                 RETURN salt;
             END",
        ];

        foreach ($queries as $q) {
            if ($db->query($q) === FALSE) {
                $errors[] = "Errore: " . $db->error;
            }
        }

        // Inserisci utenti in users_ex3 con salt generato da saltFunction
        $saltUsers = [
            ['Bob Smith',       'bob',    'sunshine'],
            ['Elon Musk',       'elon',   'merlin'],
            ['Steven Thornton', 'steven', '123456'],
        ];
        foreach ($saltUsers as [$nome, $username, $plain]) {
            $db->query("SET @salt = saltFunction()");
            $stmt = $db->prepare(
                "INSERT INTO users_ex3 (nome, username, password, salt)
                 VALUES (?, ?, SHA2(CONCAT(?, @salt), 256), @salt)"
            );
            $stmt->bind_param("sss", $nome, $username, $plain);
            if (!$stmt->execute()) {
                $errors[] = "Errore users_ex3 per $username: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // -------------------------------------------------------------------
    // 3. Popola users_ex4 con bcrypt
    // -------------------------------------------------------------------
    if (empty($errors)) {
        $users = [
            ['Bob Smith',       'bob',    'sunshine'],
            ['Elon Musk',       'elon',   'merlin'],
            ['Steven Thornton', 'steven', '123456'],
        ];
        $stmt = $db->prepare("INSERT INTO users_ex4 (nome, username, password) VALUES (?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Errore prepare bcrypt: " . $db->error;
        } else {
            foreach ($users as [$nome, $username, $plain]) {
                $hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 13]);
                $stmt->bind_param("sss", $nome, $username, $hash);
                if (!$stmt->execute()) {
                    $errors[] = "Errore users_ex4 per $username: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }

    close_db();

    // -------------------------------------------------------------------
    // 4. Mostra risultato
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
