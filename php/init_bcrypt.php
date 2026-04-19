#!/usr/bin/env php
<?php
// =============================================================================
// init_bcrypt.php – popola users_ex4 con hash bcrypt
// Eseguito come comando di entrypoint dal container php prima di apache
// =============================================================================

$host = getenv('db_host');
$user = getenv('db_user');
$pass = getenv('db_pass');
$name = getenv('db_name');

// Aspetta che MariaDB sia raggiungibile (max 60 secondi)
$attempts = 0;
do {
    $db = @new mysqli($host, $user, $pass, $name);
    if ($db->connect_error) {
        $attempts++;
        if ($attempts >= 60) {
            fwrite(STDERR, "[init_bcrypt] DB non raggiungibile dopo 60s: " . $db->connect_error . PHP_EOL);
            exit(1);
        }
        sleep(1);
    }
} while ($db->connect_error);

// Controlla se users_ex4 è già popolata
$result = $db->query("SELECT COUNT(*) AS n FROM users_ex4");
$row = $result->fetch_assoc();
if ((int)$row['n'] > 0) {
    echo "[init_bcrypt] users_ex4 già popolata, skip." . PHP_EOL;
    $db->close();
    exit(0);
}

// Inserisce gli utenti con bcrypt
$users = [
    ['Bob Smith',       'bob',    'sunshine'],
    ['Elon Musk',       'elon',   'merlin'],
    ['Steven Thornton', 'steven', '123456'],
];

$stmt = $db->prepare("INSERT INTO users_ex4 (nome, username, password) VALUES (?, ?, ?)");
foreach ($users as [$nome, $username, $plain]) {
    $hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 13]);
    $stmt->bind_param("sss", $nome, $username, $hash);
    $stmt->execute();
}
$stmt->close();
$db->close();

echo "[init_bcrypt] users_ex4 popolata con bcrypt." . PHP_EOL;
