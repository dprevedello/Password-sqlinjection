<?php
if( !isset($db) ){
  $db_server = getenv('db_host');
  $db_user = getenv('db_user');
  $db_pass = getenv('db_pass');
  $db_name = getenv('db_name');

  $db = new mysqli($db_server, $db_user, $db_pass);
  if ($db->connect_error)
    die("Connection failed: " . $db->connect_error . "<br>");
  
  $db->select_db($db_name);
}

function drop_table($name) {
  global $db;
  $name = $db->real_escape_string($name);
  $sql = "DROP TABLE IF EXISTS $name;";
  if ($db->query($sql) === FALSE)
    die("Errore cancellazione tabella: " . $db->error . "<br>");
}

function create_table($sql) {
  global $db;
  if ($db->query($sql) === FALSE)
    die("Errore creazione tabella: " . $db->error . "<br>");
}

function drop_function($name) {
  global $db;
  $name = $db->real_escape_string($name);
  $sql = "DROP FUNCTION IF EXISTS $name;";
  if ($db->query($sql) === FALSE)
    die("Errore cancellazione stored function: " . $db->error . "<br>");
}

function create_function($sql) {
  global $db;
  if ($db->query($sql) === FALSE)
    die("Errore creazione stored function: " . $db->error . "<br>");
}

function setVar($sql) {
  global $db;
  if ($db->query($sql) === FALSE)
    die("Errore creazione variabile: " . $db->error . "<br>");
}

function insert($sql) {
  global $db;
  if ($db->query($sql) === FALSE)
    die("Errore inserimento nel database: " . $db->error . "<br>");
}

function close_db() {
  global $db;
  $db->close();
  unset($db);
}
?>