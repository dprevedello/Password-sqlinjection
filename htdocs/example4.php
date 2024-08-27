<!doctype html>
<html lang="it">
  <head>
  	<title>Esempio 4</title>
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
    <!-- https://mdbootstrap.com/docs/standard/extended/login/#docsTabsOverview -->
    <header>
      <!-- Navbar -->
      <nav class="navbar navbar-expand-sm navbar-light bg-light fixed-top">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Visualizza menu">
            <i class="fas fa-bars"></i>
          </button>
          <div class="collapse navbar-collapse" id="topNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <li class="nav-item">
                <a class="nav-link" href="index.php">Esempio 1</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="example2.php">Esempio 2</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="example3.php">Esempio 3</a>
              </li>
              <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="example4.php">Esempio 4</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="example5.php">Esempio 5</a>
              </li>
            </ul>

            <div class="d-flex align-items-center">
              <a class="nav-link" href="adminer.php" target="_blank">
                <button type="button" class="btn btn-info me-3">Adminer</button>
              </a>
              <a class="nav-link" href="init.php">
                <button type="button" class="btn btn-primary me-3">Reset DB</button>
              </a>
            </div>
          </div>
        </div>
      </nav>
    </header>
    <section class="h-100 gradient-form">
      <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
          <div class="col-xl-10">
            <div class="card rounded-3 text-black">
              <div class="row g-0">
                <div class="col-lg-6">
                  <div class="card-body p-md-5 mx-md-4">
    
                    <div class="text-center">
                      <img src="https://mdbcdn.b-cdn.net/img/Photos/new-templates/bootstrap-login-form/lotus.webp"
                        style="width: 185px;" alt="logo">
                      <h4 class="mt-1 mb-5 pb-1">Esempio 4 SQL injection</h4>
                    </div>
    
                    <form method="POST" action="example4.php" class="novalidate">
                      <p>Effettua il Login</p>

                      <div class="form-outline mb-4">
                        <input type="text" id="username" name="username" class="form-control" placeholder="Inserisci lo username" required/>
                        <label class="form-label" for="username">Nome utente</label>
                      </div>
    
                      <div class="form-outline mb-4">
                        <input type="password" id="password" name="password" class="form-control" />
                        <label class="form-label" for="password">Password</label>
                      </div>
    
                      <div class="text-center pt-1 mb-5 pb-1">
                        <button class="btn btn-primary btn-block fa-lg gradient-custom-2 mb-3" type="submit">Accedi</button>
                        <a class="text-muted" href="#!">Password dimenticata?</a>
                      </div>
    
                      <div class="d-flex align-items-center justify-content-center pb-4">
                        <p class="mb-0 me-2">Non hai un account?</p>
                        <button type="button" class="btn btn-outline-danger">crealo</button>
                      </div>
                    </form>
                  </div>
                </div>
                <div class="col-lg-6 d-flex align-items-center gradient-custom-2">
                  <div class="text-white px-3 py-4 p-md-5 mx-md-4 table-responsive">
                    <h4 class="mb-4">Inserisci le credenziali di uno degli utenti e prova l'attacco</h4>
                    <table class="table table-light table-striped table-sm table-bordered">
                      <thead class="table-dark">
                        <tr>
                          <th scope="col">#</th>
                          <th scope="col">Nome</th>
                          <th scope="col">Username</th>
                          <th scope="col">Password</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <th scope="row">1</th>
                          <td>Bob Smith</td>
                          <td>bob</td>
                          <td>sunshine</td>
                        </tr>
                        <tr>
                          <th scope="row">2</th>
                          <td>Elon Musk</td>
                          <td>elon</td>
                          <td>merlin</td>
                        </tr>
                        <tr>
                          <th scope="row">3</th>
                          <td>Steven Thornton</td>
                          <td>steven</td>
                          <td>123456</td>
                        </tr>
                      </tbody>
                    </table>
<?php
  include 'db.php';
  global $db;

  if( isset($_POST['username']) && isset($_POST['password']) ){
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
      echo "<p class='small mb-0 alert alert-success' role='alert' data-mdb-color='success'><i class='fas fa-check-circle me-3'></i><b>Bentornato:</b> {$nome}</p>";
    else
      echo "<p class='small mb-0 alert alert-danger' role='alert' data-mdb-color='danger'><i class='fas fa-times-circle me-3'></i><b>LOGIN FALLITO</b></p>";

    $stmt->close();
  }
  close_db();
?>
                  </div>
                </div>
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
