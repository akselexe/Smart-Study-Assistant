# Smart Study Assistant Chatbot

A web-based study assistant chatbot application built with PHP, MySQL, and Groq API integration.

## Features

- 🤖 AI-powered chatbot using Groq API
- 💬 Real-time chat interface
- 📚 Subject categorization (Mathematics, Science, History, Programming, General)
- 💾 Chat history storage in MySQL database
- 🎨 Modern, responsive UI design
- � Role-based access (students / professors)

## Quick setup

1. Place the project in your webroot (example for XAMPP):
   `C:\xampp_lite_8_4\www\prog_project`

2. Install PHP dependencies (optional):
   ```powershell
   composer install
   ```

3. Configure the application:
   - Copy `config/config.php.example` (if present) to `config/config.php` and set DB credentials and your Groq API key.

4. Initialize the database (one of the following):
   - Open in browser: `http://localhost/prog_project/setup.php` (recommended; uses the web server's PHP configuration)
   - Or run the setup/migration script via CLI (only if your CLI PHP has mysqli enabled):
     ```powershell
     php .\migrate_create_quiz_tables.php  # if present
     ```
     Note: the project provides `setup.php` for automatic initialization — prefer using the browser-based setup if unsure.

5. Open the app:
   - Students: `http://localhost/prog_project/index.php`
   - Professors: `http://localhost/prog_project/professor/dashboard.php`

## Important behavior: professor access

- Professors do not access the chat UI. If a logged-in user is a professor, `index.php` redirects them to the professor dashboard. This prevents professors from opening the chat interface directly.
- Professors still manage exercises and quizzes from the `professor/` area.

## Project structure (key files)

```
prog_project/
├── api/                     # API endpoints (e.g. chat.php)
├── assets/                  # CSS and JS
├── config/                  # app configuration and DB wrapper
├── includes/                # shared UI fragments
├── professor/               # professor pages (create exercise, dashboard)
├── student/                 # student pages (exercises, view exercise)
├── uploads/                 # uploaded exercise files
├── index.php                # Main chat interface (students)
├── login.php, register.php  # auth pages
└── README.md
```

## Configuration

Edit `config/config.php` to customize:
- Database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME)
- Groq API key

## Common troubleshooting

- API Key problems: ensure GROQ API key is set in `config/config.php`.
- Database connection: verify MySQL is running and credentials are correct.
- Composer: run `composer install` in the project root to install optional dependencies.

CLI PHP and mysqli

- If you run PHP scripts from PowerShell/CLI (for migrations), you may see an error like "Class 'mysqli' not found". This means the CLI `php` binary is using a php.ini where `mysqli` is not enabled.
- To fix:
  1. Run `php --ini` to find the loaded `php.ini` used by the CLI.
 2. Edit that `php.ini` and enable the `mysqli` extension (remove the leading `;` from `extension=php_mysqli.dll` on Windows).
 3. Restart your terminal and run `php -m` — `mysqli` should appear in the list.

If you prefer to avoid CLI issues, run `setup.php` from your browser (it uses the webserver's PHP configuration which is typically already configured with `mysqli`).

## Security notes

- Delete or secure any setup/migration scripts after initial use (e.g., `setup.php`, `migrate_*.php`).
- Do not commit `config/config.php` with real credentials to version control.
- Consider adding CSRF protection and stricter input validation for production.

## Next steps / recommended improvements

- Add a quiz listing and management UI for professors.
- Add per-question feedback and review pages for professors.
- Implement rate-limiting and API usage metrics.

## License

This project is for educational purposes.

## Credits

- Groq API: https://groq.com/
- Groq PHP Library: https://github.com/lucianotonet/groq-php

