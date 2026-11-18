# Deploying GreenLoop to InfinityFree (PHP + MySQL)

This app needs **PHP 8.1+** (it uses the `never` return type) with the **mbstring**
and **pdo_mysql** extensions, plus **MySQL**. InfinityFree supports all of these.

## 1. Create hosting
1. Sign up at https://infinityfree.com and create a free hosting account.
2. Note your assigned subdomain, e.g. `yourname.infinityfreeapp.com`.
3. In the control panel, go to **MySQL Databases** and create a database.
4. Note the **Database name**, **Username**, **Password** and **Hostname**
   (e.g. `sql123.infinityfree.com`).

## 2. Import the database
1. Open **phpMyAdmin** from the InfinityFree control panel.
2. Select your database.
3. Import `database/schema-infinityfree.sql`.
   - Use this file, **not** `schema.sql`: the regular file contains
     `CREATE DATABASE` / `USE` statements that shared hosting will reject.
   - The tables are created with the same structure and prefixes are not needed.

## 3. Configure the backend
1. In the InfinityFree **Online File Manager** (or via FTP), open
   `config/database.php`.
2. Replace the default local values with your hosting credentials:

   ```php
   $host = 'sql123.infinityfree.com'; // your MySQL hostname
   $name = 'if0_12345678_wastewise_db'; // your database name
   $user = 'if0_12345678';              // your database username
   $pass = 'your-database-password';    // your database password
   ```

3. In the control panel, set the **PHP version to 8.1 or newer**.

## 4. Upload the files
Upload the **contents of this folder** (not the folder itself) into your
hosting `htdocs/` root so that `index.php`, `api.php`, `assets/`,
`config/`, `database/`, `frontend/` and `includes/` are all at the web root.

## 5. Enable HTTPS (required for GPS tracking)
In the control panel, open **SSL Certificates** and install the free
Let's Encrypt certificate for your subdomain. GPS/location APIs only work
over HTTPS (or localhost).

## 6. Create the first administrator
1. Visit `https://your-subdomain/setup-admin.php`.
2. Create the admin account.
3. **Delete `setup-admin.php` from the server afterwards** (security).

## 7. Use the app
- Public site: `https://your-subdomain/`
- Admin login: `https://your-subdomain/admin-login.php`
- Driver login: `https://your-subdomain/driver-login.php`

## Notes
- The frontend calls `api.php` with relative URLs, so no code changes are
  needed for the domain.
- `.htaccess` blocks direct access to `schema.sql`, `.env` and `README.md`.
- Keep your database password out of GitHub: this repo uses local defaults,
  and you should fill in real credentials **only on the server**.
