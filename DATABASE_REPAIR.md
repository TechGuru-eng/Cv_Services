# Database connection and repair

The previous connection file used root with an empty password even on hosting.
It now reads the root .env file. Preserve its other settings and add:

```dotenv
DB_HOST=localhost
DB_PORT=3306
DB_NAME=YOUR_FULL_HOSTING_DATABASE_NAME
DB_USER=YOUR_HOSTING_DATABASE_USER
DB_PASSWORD='YOUR_DATABASE_PASSWORD'
```

Use the database credentials from the hosting panel, not your hosting login.
Process environment variables take precedence over .env.

Upload admin/config/db.php, includes/database-connect.php,
includes/environment.php, tools/repair-database.php and admin/sql/.
Configure .env on the server; do not overwrite it with your local configuration.

In the hosting terminal, change to the website root and preview:

```sh
php tools/repair-database.php
```

Export a database backup in the hosting panel before applying schema changes:

```sh
php tools/repair-database.php --apply
```

The script creates missing tables and adds explicitly declared missing columns
from admin/sql/*.sql. Re-running skips existing objects. It does not drop,
rename, overwrite records, change existing column types, or create database users.
It lists migration files containing unsupported statements for manual review.
MySQL schema changes cannot be rolled back as a transaction.

This does not fix every possible database problem. Invalid credentials, missing
user permissions or a stopped database server require changes in the hosting
panel. If your database with customer data is missing, restore a backup first.
Existing CREATE TABLE definitions are not used to infer missing columns, and PHP
runtime migrations are outside this script's coverage.

The script is terminal-only (404 in a browser). If your hosting plan has no
terminal, uploading the connection files and setting .env addresses the connection
configuration; use the database panel for reviewed SQL schema changes.
