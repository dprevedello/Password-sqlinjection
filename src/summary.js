// =============================================================================
// summary.js – Tabella comparativa + Calcolatore hash
// =============================================================================

// =============================================================================
// 1. TABELLA COMPARATIVA
// =============================================================================

const GREEN  = "green";
const YELLOW = "yellow";
const RED    = "red";

const ROWS = [
  {
    label: "Vulnerabile a SQL injection",
    icon: "fa-shield-alt",
    values: [RED, RED, RED, GREEN, GREEN],
    notes: [
      { color: RED,   text: "L'input utente viene concatenato direttamente nella query. Qualsiasi payload SQL viene eseguito dal database." },
      { color: RED,   text: "Uguale all'Esempio 1: hashare la password non protegge dalla SQL injection nello username o nella stessa password." },
      { color: RED,   text: "L'aggiunta del salt non cambia nulla sulla vulnerabilità SQL injection: l'input non è ancora sanificato." },
      { color: GREEN, text: "I prepared statements separano il codice SQL dai dati: il database tratta l'input sempre come stringa, mai come comando." },
      { color: GREEN, text: "Prepared statements presenti come nell'Esempio 4. La SQL injection è completamente neutralizzata." },
    ]
  },
  {
    label: "Password in chiaro nel DB",
    icon: "fa-eye",
    values: [RED, GREEN, GREEN, GREEN, GREEN],
    notes: [
      { color: RED,   text: "Le password sono memorizzate esattamente come le ha inserite l'utente. Chi accede al DB le legge immediatamente." },
      { color: GREEN, text: "Le password sono hashate con MD5. Un attaccante che ottiene il DB non vede le password in chiaro… ma può ricavarle." },
      { color: GREEN, text: "SHA-256 con salt: le password non sono in chiaro. Serve comunque un attacco per ricavarle." },
      { color: GREEN, text: "Come l'Esempio 3: SHA-256 con salt, le password non sono visibili direttamente." },
      { color: GREEN, text: "bcrypt: le password non sono in chiaro. Hash lungo e complesso, con salt automatico incorporato." },
    ]
  },
  {
    label: "Resistente agli attacchi a dizionario",
    icon: "fa-book-open",
    values: [RED, RED, GREEN, GREEN, GREEN],
    notes: [
      { color: RED,   text: "Password in chiaro: non serve nemmeno un attacco a dizionario, si leggono direttamente." },
      { color: RED,   text: "MD5 senza salt: esistono enormi database di hash precalcolati (rainbow table). La password 'sunshine' ha un hash MD5 noto e cercarla online richiede secondi." },
      { color: GREEN, text: "Il salt univoco per utente rende inutili le rainbow table: anche se due utenti hanno la stessa password, i loro hash sono diversi e non sono precalcolabili." },
      { color: GREEN, text: "Salt presente come nell'Esempio 3: le rainbow table non funzionano." },
      { color: GREEN, text: "bcrypt include il salt automaticamente. Le rainbow table sono inutilizzabili per definizione." },
    ]
  },
  {
    label: "Resistente al brute force con GPU",
    icon: "fa-microchip",
    values: [RED, RED, RED, YELLOW, GREEN],
    notes: [
      { color: RED,    text: "Password in chiaro: non serve brute force." },
      { color: RED,    text: "MD5 è estremamente veloce: una GPU moderna calcola miliardi di hash MD5 al secondo, rendendo il brute force praticabile su password deboli." },
      { color: RED,    text: "SHA-256 è più sicuro di MD5 ma è comunque un algoritmo general-purpose ad alta velocità. Una GPU può provare centinaia di milioni di combinazioni al secondo." },
      { color: YELLOW, text: "SHA-256 con salt è meglio di MD5, ma rimane un algoritmo veloce. Con hardware dedicato e password deboli, il brute force rimane una minaccia concreta." },
      { color: GREEN,  text: "bcrypt è progettato per essere lento: il parametro 'cost' (qui: 13) definisce quante iterazioni vengono eseguite. Raddoppiare il cost raddoppia il tempo. Una GPU può provare solo poche centinaia di hash al secondo invece di miliardi." },
    ]
  },
  {
    label: "Usa prepared statements",
    icon: "fa-code",
    values: [RED, RED, RED, GREEN, GREEN],
    notes: [
      { color: RED,   text: "Query costruita per concatenazione di stringhe: tecnica insicura e sconsigliata." },
      { color: RED,   text: "Stessa tecnica insicura dell'Esempio 1." },
      { color: RED,   text: "Stessa tecnica insicura: la presenza del salt non aiuta contro la SQL injection." },
      { color: GREEN, text: "Prepared statements con bind_param(): la query e i parametri viaggiano separati verso il database." },
      { color: GREEN, text: "Prepared statements presenti come nell'Esempio 4." },
    ]
  },
  {
    label: "Algoritmo di hash consigliato",
    icon: "fa-key",
    values: [RED, RED, YELLOW, YELLOW, GREEN],
    notes: [
      { color: RED,    text: "Nessun hash: le password sono in chiaro." },
      { color: RED,    text: "MD5 non è mai stato progettato per l'hashing di password. È veloce e presenta collisioni note. Non usarlo." },
      { color: YELLOW, text: "SHA-256 è crittograficamente sicuro ma non è pensato per le password: è troppo veloce. Meglio di MD5, ma non ottimale." },
      { color: YELLOW, text: "Stessa considerazione dell'Esempio 3 per l'algoritmo di hash." },
      { color: GREEN,  text: "bcrypt è specificamente progettato per l'hashing di password. È lento, include il salt, e il costo computazionale è regolabile nel tempo per stare al passo con l'hardware." },
    ]
  },
];

function renderSummaryTable() {
  const tbody = document.getElementById("summary-tbody");
  if (!tbody) return;

  const colors = {
    [GREEN]:  { bg: "#d1f2d1", icon: "fa-check-circle",    color: "#1a7f1a" },
    [YELLOW]: { bg: "#fff3cd", icon: "fa-exclamation-circle", color: "#856404" },
    [RED]:    { bg: "#f8d7da", icon: "fa-times-circle",    color: "#842029" },
  };

  ROWS.forEach((row, rowIdx) => {
    const tr = document.createElement("tr");

    // Colonna etichetta
    const tdLabel = document.createElement("td");
    tdLabel.className = "text-start fw-semibold";
    tdLabel.innerHTML = `<i class="fas ${row.icon} me-2 text-secondary"></i>${row.label}`;
    tr.appendChild(tdLabel);

    // Colonne esempi
    row.values.forEach((val, colIdx) => {
      const td = document.createElement("td");
      const cfg = colors[val];
      td.style.background = cfg.bg;
      td.style.cursor = "pointer";
      td.innerHTML = `<i class="fas ${cfg.icon} fa-lg" style="color:${cfg.color}"></i>`;
      td.title = "Clicca per dettagli";
      td.addEventListener("click", (e) => showPopover(e.currentTarget, row.notes[colIdx], row.label, colIdx + 1));
      tr.appendChild(td);
    });

    tbody.appendChild(tr);
  });
}

function showPopover(cell, note, rowLabel, esNum) {
  const popover = document.getElementById("summary-popover");
  const body    = document.getElementById("summary-popover-body");

  const colorMap = {
    [GREEN]:  { cls: "popover-green",  label: "✅ Sicuro" },
    [YELLOW]: { cls: "popover-yellow", label: "⚠️ Parziale" },
    [RED]:    { cls: "popover-red",    label: "❌ Vulnerabile" },
  };
  const cfg = colorMap[note.color];

  body.innerHTML = `
    <div class="popover-title">${rowLabel} — Esempio ${esNum}</div>
    <span class="popover-badge ${cfg.cls}">${cfg.label}</span>
    <p class="popover-text">${note.text}</p>`;

  // Posizionamento relativo alla cella
  const rect      = cell.getBoundingClientRect();
  const scrollTop = window.scrollY || document.documentElement.scrollTop;

  popover.style.display = "block";
  const popW = popover.offsetWidth;
  let left = rect.left + rect.width / 2 - popW / 2;
  left = Math.max(8, Math.min(left, window.innerWidth - popW - 8));
  popover.style.left = left + "px";
  popover.style.top  = (rect.bottom + scrollTop + 8) + "px";
}

function closePopover() {
  document.getElementById("summary-popover").style.display = "none";
}

document.addEventListener("click", (e) => {
  const pop = document.getElementById("summary-popover");
  if (pop && !pop.contains(e.target) && !e.target.closest("td[title]")) {
    closePopover();
  }
});

// =============================================================================
// 2. CALCOLATORE HASH
// =============================================================================

// Salt fissi per SHA-256+salt (simulano quelli del DB — cambiano ad ogni reset)
const SALT_A = "dEf3!xQzP@k9mLwR";
const SALT_B = "hJn7#vYtU$c2oBsA";

async function md5(str) {
  // MD5 non è disponibile nella Web Crypto API (obsoleto).
  // Implementazione minimale pura JS.
  function safeAdd(x, y) {
    const lsw = (x & 0xFFFF) + (y & 0xFFFF);
    return (((x >> 16) + (y >> 16) + (lsw >> 16)) << 16) | (lsw & 0xFFFF);
  }
  function bitRotateLeft(num, cnt) { return (num << cnt) | (num >>> (32 - cnt)); }
  function md5cmn(q, a, b, x, s, t) { return safeAdd(bitRotateLeft(safeAdd(safeAdd(a, q), safeAdd(x, t)), s), b); }
  function md5ff(a,b,c,d,x,s,t){ return md5cmn((b&c)|((~b)&d),a,b,x,s,t); }
  function md5gg(a,b,c,d,x,s,t){ return md5cmn((b&d)|(c&(~d)),a,b,x,s,t); }
  function md5hh(a,b,c,d,x,s,t){ return md5cmn(b^c^d,a,b,x,s,t); }
  function md5ii(a,b,c,d,x,s,t){ return md5cmn(c^(b|(~d)),a,b,x,s,t); }

  function utf8Encode(str) {
    return unescape(encodeURIComponent(str));
  }

  const s = utf8Encode(str);
  const n = s.length;
  const state = new Array(Math.ceil((n + 9) / 64) * 16).fill(0);
  for (let i = 0; i < n; i++) state[i >> 2] |= s.charCodeAt(i) << ((i % 4) * 8);
  state[n >> 2] |= 0x80 << ((n % 4) * 8);
  state[state.length - 2] = n * 8;

  let [a, b, c, d] = [1732584193, -271733879, -1732584194, 271733878];
  for (let i = 0; i < state.length; i += 16) {
    const [aa,bb,cc,dd] = [a,b,c,d];
    const w = state.slice(i, i+16);
    a=md5ff(a,b,c,d,w[0],7,-680876936);d=md5ff(d,a,b,c,w[1],12,-389564586);c=md5ff(c,d,a,b,w[2],17,606105819);b=md5ff(b,c,d,a,w[3],22,-1044525330);
    a=md5ff(a,b,c,d,w[4],7,-176418897);d=md5ff(d,a,b,c,w[5],12,1200080426);c=md5ff(c,d,a,b,w[6],17,-1473231341);b=md5ff(b,c,d,a,w[7],22,-45705983);
    a=md5ff(a,b,c,d,w[8],7,1770035416);d=md5ff(d,a,b,c,w[9],12,-1958414417);c=md5ff(c,d,a,b,w[10],17,-42063);b=md5ff(b,c,d,a,w[11],22,-1990404162);
    a=md5ff(a,b,c,d,w[12],7,1804603682);d=md5ff(d,a,b,c,w[13],12,-40341101);c=md5ff(c,d,a,b,w[14],17,-1502002290);b=md5ff(b,c,d,a,w[15],22,1236535329);
    a=md5gg(a,b,c,d,w[1],5,-165796510);d=md5gg(d,a,b,c,w[6],9,-1069501632);c=md5gg(c,d,a,b,w[11],14,643717713);b=md5gg(b,c,d,a,w[0],20,-373897302);
    a=md5gg(a,b,c,d,w[5],5,-701558691);d=md5gg(d,a,b,c,w[10],9,38016083);c=md5gg(c,d,a,b,w[15],14,-660478335);b=md5gg(b,c,d,a,w[4],20,-405537848);
    a=md5gg(a,b,c,d,w[9],5,568446438);d=md5gg(d,a,b,c,w[14],9,-1019803690);c=md5gg(c,d,a,b,w[3],14,-187363961);b=md5gg(b,c,d,a,w[8],20,1163531501);
    a=md5gg(a,b,c,d,w[13],5,-1444681467);d=md5gg(d,a,b,c,w[2],9,-51403784);c=md5gg(c,d,a,b,w[7],14,1735328473);b=md5gg(b,c,d,a,w[12],20,-1926607734);
    a=md5hh(a,b,c,d,w[5],4,-378558);d=md5hh(d,a,b,c,w[8],11,-2022574463);c=md5hh(c,d,a,b,w[11],16,1839030562);b=md5hh(b,c,d,a,w[14],23,-35309556);
    a=md5hh(a,b,c,d,w[1],4,-1530992060);d=md5hh(d,a,b,c,w[4],11,1272893353);c=md5hh(c,d,a,b,w[7],16,-155497632);b=md5hh(b,c,d,a,w[10],23,-1094730640);
    a=md5hh(a,b,c,d,w[13],4,681279174);d=md5hh(d,a,b,c,w[0],11,-358537222);c=md5hh(c,d,a,b,w[3],16,-722521979);b=md5hh(b,c,d,a,w[6],23,76029189);
    a=md5hh(a,b,c,d,w[9],4,-640364487);d=md5hh(d,a,b,c,w[12],11,-421815835);c=md5hh(c,d,a,b,w[15],16,530742520);b=md5hh(b,c,d,a,w[2],23,-995338651);
    a=md5ii(a,b,c,d,w[0],6,-198630844);d=md5ii(d,a,b,c,w[7],10,1126891415);c=md5ii(c,d,a,b,w[14],15,-1416354905);b=md5ii(b,c,d,a,w[5],21,-57434055);
    a=md5ii(a,b,c,d,w[12],6,1700485571);d=md5ii(d,a,b,c,w[3],10,-1894986606);c=md5ii(c,d,a,b,w[10],15,-1051523);b=md5ii(b,c,d,a,w[1],21,-2054922799);
    a=md5ii(a,b,c,d,w[8],6,1873313359);d=md5ii(d,a,b,c,w[15],10,-30611744);c=md5ii(c,d,a,b,w[6],15,-1560198380);b=md5ii(b,c,d,a,w[13],21,1309151649);
    a=md5ii(a,b,c,d,w[4],6,-145523070);d=md5ii(d,a,b,c,w[11],10,-1120210379);c=md5ii(c,d,a,b,w[2],15,718787259);b=md5ii(b,c,d,a,w[9],21,-343485551);
    a=safeAdd(a,aa); b=safeAdd(b,bb); c=safeAdd(c,cc); d=safeAdd(d,dd);
  }

  return [a,b,c,d].map(n => {
    let s = "";
    for (let i = 0; i < 4; i++) s += ("0" + ((n >>> (i*8)) & 0xFF).toString(16)).slice(-2);
    return s;
  }).join("");
}

async function sha256(str) {
  const buf  = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(str));
  return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2,"0")).join("");
}

// Cache bcrypt per evitare ricalcoli su ogni keystroke
const bcryptCache = {};
// bcryptTiming tiene il tempo dell'ultimo calcolo reale (non da cache)
let bcryptLastRealMs = null;

async function bcryptHash(str) {
  if (bcryptCache[str] !== undefined) return bcryptCache[str];
  const t0 = performance.now();
  const hash = await new Promise(resolve => {
    // cost 8 per la demo (più veloce di 13 ma ancora dimostrativo)
    dcodeIO.bcrypt.hash(str, 8, (err, h) => resolve(h));
  });
  bcryptLastRealMs = Math.round(performance.now() - t0);
  bcryptCache[str] = hash;
  return hash;
}

function eqIcon(a, b) {
  if (a === "—" || b === "—") return "—";
  if (a === b) return '<span class="hash-eq hash-eq-yes"><i class="fas fa-equals me-1"></i>Uguali</span>';
  return '<span class="hash-eq hash-eq-no"><i class="fas fa-not-equal me-1"></i>Diversi</span>';
}

function truncate(hash) {
  if (!hash || hash === "—") return "—";
  if (hash.length <= 20) return hash;
  return `<span title="${hash}">${hash.slice(0, 10)}…${hash.slice(-8)}<span class="hash-len"> (${hash.length} car.)</span></span>`;
}

let bcryptDebounce = null;

async function computeAll() {
  const a = document.getElementById("hash-input-a").value;
  const b = document.getElementById("hash-input-b").value;

  if (!a && !b) { resetDisplay(); return; }

  // MD5
  const [m5a, m5b] = await Promise.all([a ? md5(a) : "—", b ? md5(b) : "—"]);
  document.getElementById("md5-a").innerHTML       = truncate(m5a);
  document.getElementById("md5-b").innerHTML       = truncate(m5b);
  document.getElementById("md5-eq").innerHTML      = eqIcon(m5a, m5b);

  // SHA-256 puro
  const [s256a, s256b] = await Promise.all([a ? sha256(a) : "—", b ? sha256(b) : "—"]);
  document.getElementById("sha256-a").innerHTML    = truncate(s256a);
  document.getElementById("sha256-b").innerHTML    = truncate(s256b);
  document.getElementById("sha256-eq").innerHTML   = eqIcon(s256a, s256b);

  // SHA-256 + salt diverso per utente
  const [ss256a, ss256b] = await Promise.all([
    a ? sha256(a + SALT_A) : "—",
    b ? sha256(b + SALT_B) : "—",
  ]);
  document.getElementById("sha256s-a").innerHTML   = truncate(ss256a);
  document.getElementById("sha256s-b").innerHTML   = truncate(ss256b);
  document.getElementById("sha256s-eq").innerHTML  = eqIcon(ss256a, ss256b);

  // bcrypt – debounced (lento anche a cost 8)
  document.getElementById("bcrypt-a").innerHTML    = '<span class="text-muted fst-italic">calcolo…</span>';
  document.getElementById("bcrypt-b").innerHTML    = '<span class="text-muted fst-italic">calcolo…</span>';
  document.getElementById("bcrypt-eq").innerHTML   = "—";

  clearTimeout(bcryptDebounce);
  bcryptDebounce = setTimeout(async () => {
    const [bca, bcb] = await Promise.all([a ? bcryptHash(a) : "—", b ? bcryptHash(b) : "—"]);

    document.getElementById("bcrypt-a").innerHTML  = truncate(bca);
    document.getElementById("bcrypt-b").innerHTML  = truncate(bcb);
    // bcrypt con stesso input produce SEMPRE hash diversi (salt interno casuale)
    const bcEq = (bca !== "—" && bcb !== "—")
      ? '<span class="hash-eq hash-eq-no"><i class="fas fa-not-equal me-1"></i>Sempre diversi</span>'
      : "—";
    document.getElementById("bcrypt-eq").innerHTML = bcEq;

    // Mostra il tempo solo se c'è stato almeno un calcolo reale (non da cache)
    if (bcryptLastRealMs !== null) {
      document.getElementById("bcrypt-timing").style.display = "block";
      document.getElementById("bcrypt-timing-text").textContent =
        `bcrypt (cost 8): calcolo completato in ${bcryptLastRealMs} ms — a cost 13 (usato nel DB) il tempo sarebbe ~${Math.round(bcryptLastRealMs * Math.pow(2, 13-8))} ms`;
    }

    updateObservations(a, b, m5a, m5b, ss256a, ss256b);
  }, 600);

  updateObservations(a, b, m5a, m5b, ss256a, ss256b);
}

function updateObservations(a, b, md5a, md5b, sha256sa, sha256sb) {
  const obs = document.getElementById("hash-observations");
  const messages = [];

  if (a && b && a === b) {
    messages.push({ cls: "obs-warning", icon: "fa-exclamation-triangle", text: "<strong>Stessa password, utenti diversi:</strong> MD5 e SHA-256 puro producono lo stesso hash → un attaccante che conosce l'hash di un utente conosce automaticamente la password dell'altro." });
    messages.push({ cls: "obs-ok", icon: "fa-check-circle",          text: "<strong>SHA-256 + salt:</strong> nonostante la password sia identica, i due hash sono diversi grazie al salt univoco per utente." });
    messages.push({ cls: "obs-ok", icon: "fa-check-circle",          text: "<strong>bcrypt:</strong> ogni calcolo produce un hash diverso anche con la stessa password, grazie al salt casuale incorporato automaticamente." });
  } else if (a && b && a !== b) {
    messages.push({ cls: "obs-info", icon: "fa-info-circle", text: "Le due password sono diverse: tutti gli algoritmi produrranno hash diversi. Prova a inserire la <strong>stessa password</strong> per i due utenti per vedere il problema delle rainbow table." });
  }

  if (messages.length === 0) {
    obs.style.display = "none";
    return;
  }

  obs.innerHTML = messages.map(m =>
    `<div class="obs-item ${m.cls}"><i class="fas ${m.icon} me-2"></i>${m.text}</div>`
  ).join("");
  obs.style.display = "block";
}

function resetDisplay() {
  ["md5-a","md5-b","sha256-a","sha256-b","sha256s-a","sha256s-b","bcrypt-a","bcrypt-b"].forEach(id => {
    document.getElementById(id).innerHTML = '<span class="text-muted">—</span>';
  });
  ["md5-eq","sha256-eq","sha256s-eq","bcrypt-eq"].forEach(id => {
    document.getElementById(id).innerHTML = "—";
  });
  document.getElementById("hash-observations").style.display = "none";
  document.getElementById("bcrypt-timing").style.display = "none";
}

function resetHasher() {
  document.getElementById("hash-input-a").value = "";
  document.getElementById("hash-input-b").value = "";
  resetDisplay();
}

// =============================================================================
// Init
// =============================================================================
document.addEventListener("DOMContentLoaded", () => {
  renderSummaryTable();
});
