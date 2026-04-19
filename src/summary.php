<!doctype html>
<html lang="it">
  <head>
    <title>Riepilogo & Hash</title>
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
                <a class="nav-link" href="example4.php">Esempio 4</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="example5.php">Esempio 5</a>
              </li>
              <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="summary.php">Riepilogo</a>
              </li>
            </ul>
            <div class="d-flex align-items-center">
              <button id="toggle-docente" type="button" onclick="toggleDocente()" class="btn btn-outline-warning btn-sm me-3"><i class="fas fa-chalkboard-teacher me-1"></i>Mostra soluzioni</button>
              <a class="nav-link" href="/pma/" target="_blank">
                <button type="button" class="btn btn-info me-3">phpMyAdmin</button>
              </a>
              <a class="nav-link" href="reset.php">
                <button type="button" class="btn btn-primary me-3">Reset DB</button>
              </a>
            </div>
          </div>
        </div>
      </nav>
    </header>

    <div class="container" style="padding-top: 80px; padding-bottom: 60px;">

      <!-- ================================================================
           SEZIONE 1: Tabella comparativa
           ================================================================ -->
      <div class="card rounded-3 shadow mb-5">
        <div class="card-body p-4">
          <h4 class="mb-1"><i class="fas fa-table me-2 text-primary"></i>Confronto tra gli esempi</h4>
          <p class="text-muted mb-4">Clicca su qualsiasi cella per leggere la spiegazione.</p>

          <div class="table-responsive">
            <table class="table table-bordered text-center align-middle summary-table">
              <thead class="table-dark">
                <tr>
                  <th class="text-start" style="min-width:220px">Caratteristica</th>
                  <th>Es. 1</th>
                  <th>Es. 2</th>
                  <th>Es. 3</th>
                  <th>Es. 4</th>
                  <th>Es. 5</th>
                </tr>
              </thead>
              <tbody id="summary-tbody"></tbody>
            </table>
          </div>

          <!-- Legenda -->
          <div class="d-flex gap-4 mt-3 flex-wrap">
            <span><span class="badge-legend badge-red"></span> Vulnerabile / Assente</span>
            <span><span class="badge-legend badge-yellow"></span> Parziale / Migliorabile</span>
            <span><span class="badge-legend badge-green"></span> Sicuro / Presente</span>
          </div>
        </div>
      </div>

      <!-- Popover dettaglio (nascosto, posizionato via JS) -->
      <div id="summary-popover" class="summary-popover" style="display:none">
        <button class="summary-popover-close" onclick="closePopover()"><i class="fas fa-times"></i></button>
        <div id="summary-popover-body"></div>
      </div>

      <!-- ================================================================
           SEZIONE 2: Utility hash
           ================================================================ -->
      <div class="card rounded-3 shadow">
        <div class="card-body p-4">
          <h4 class="mb-1"><i class="fas fa-fingerprint me-2 text-primary"></i>Calcolatore di hash</h4>
          <p class="text-muted mb-4">Inserisci una parola e confronta come viene rappresentata dai diversi algoritmi.</p>

          <div class="row g-3 mb-4">
            <div class="col-md-5">
              <label class="form-label fw-semibold">Utente A</label>
              <input type="text" id="hash-input-a" class="form-control" placeholder="es. sunshine" oninput="computeAll()">
            </div>
            <div class="col-md-5">
              <label class="form-label fw-semibold">Utente B <span class="text-muted fw-normal">(stessa o diversa password)</span></label>
              <input type="text" id="hash-input-b" class="form-control" placeholder="es. sunshine" oninput="computeAll()">
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button class="btn btn-outline-secondary w-100" onclick="resetHasher()">
                <i class="fas fa-undo me-1"></i>Reset
              </button>
            </div>
          </div>

          <!-- Tabella risultati hash -->
          <div class="table-responsive">
            <table class="table table-bordered hash-table">
              <thead class="table-dark">
                <tr>
                  <th style="min-width:120px">Algoritmo</th>
                  <th>Hash utente A</th>
                  <th>Hash utente B</th>
                  <th style="min-width:120px">Uguali?</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><span class="hash-algo-badge badge-md5">MD5</span></td>
                  <td><code id="md5-a" class="hash-value text-muted">—</code></td>
                  <td><code id="md5-b" class="hash-value text-muted">—</code></td>
                  <td id="md5-eq" class="text-center">—</td>
                </tr>
                <tr>
                  <td><span class="hash-algo-badge badge-sha256">SHA-256</span></td>
                  <td><code id="sha256-a" class="hash-value text-muted">—</code></td>
                  <td><code id="sha256-b" class="hash-value text-muted">—</code></td>
                  <td id="sha256-eq" class="text-center">—</td>
                </tr>
                <tr>
                  <td><span class="hash-algo-badge badge-sha256salt">SHA-256<br><small>+ salt</small></span></td>
                  <td><code id="sha256s-a" class="hash-value text-muted">—</code></td>
                  <td><code id="sha256s-b" class="hash-value text-muted">—</code></td>
                  <td id="sha256s-eq" class="text-center">—</td>
                </tr>
                <tr>
                  <td><span class="hash-algo-badge badge-bcrypt">bcrypt</span></td>
                  <td><code id="bcrypt-a" class="hash-value text-muted">—</code></td>
                  <td><code id="bcrypt-b" class="hash-value text-muted">—</code></td>
                  <td id="bcrypt-eq" class="text-center">—</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Box osservazioni dinamiche -->
          <div id="hash-observations" class="hash-observations" style="display:none"></div>

          <!-- Info tempo bcrypt -->
          <div id="bcrypt-timing" class="text-muted small mt-2" style="display:none">
            <i class="fas fa-clock me-1"></i><span id="bcrypt-timing-text"></span>
          </div>
        </div>
      </div>

    </div><!-- /container -->

    <!-- Modalità Docente -->
    <script src="payloads.js"></script>
    <!-- MDB -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.1.0/mdb.min.js"></script>
    <!-- bcrypt.js via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bcryptjs/2.4.3/bcrypt.min.js"></script>
    <script src="summary.js"></script>
  </body>
</html>
