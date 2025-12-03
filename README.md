# Smart Study Assistant Chatbot

A web-based study assistant chatbot application built with PHP, MySQL, and Groq API integration.

## Features

- 🤖 AI-powered chatbot using Groq API
- 💬 Real-time chat interface
- 📚 Subject categorization (Mathematics, Science, History, Programming, General)
- 💾 Chat history storage in MySQL database
- 🎨 Modern, responsive UI design
- 🔄 Session management

## Requirements

- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 or higher
- Composer (for PHP dependencies)
- Groq API key (get one at https://console.groq.com/keys)

## Installation

1. **Clone or download this project** to your XAMPP `htdocs` folder:
   ```
   C:\xampp_lite_8_4\www\prog_project
   ```

2. **Install PHP dependencies** using Composer:
   ```bash
   composer install
   ```
   If you don't have Composer, download it from https://getcomposer.org/

3. **Configure the application**:
   - Open `config/config.php`
   - Replace `'your_groq_api_key_here'` with your actual Groq API key

4. **Set up the database**:
   - Open your browser and go to: `http://localhost/prog_project/setup.php`
   - This will automatically create the database and tables
   - After setup completes, you can delete `setup.php` for security

5. **Access the application**:
   - Open: `http://localhost/prog_project/index.php`

## Project Structure

```
prog_project/
├── api/
│   └── chat.php              # Backend API endpoint for Groq integration
├── assets/
│   ├── css/
│   │   └── style.css         # Styling
│   └── js/
│       └── chat.js           # Frontend JavaScript
├── config/
│   ├── config.php            # Configuration (API keys, DB settings)
│   └── db.php                # Database connection class
├── vendor/                   # Composer dependencies
├── index.php                 # Main chat interface
├── setup.php                 # Database setup script
├── composer.json             # PHP dependencies
└── README.md                 # This file
```

## Database Schema

The application uses the following tables:

- **users**: User accounts (for future authentication)
- **chat_sessions**: Chat session tracking
- **chat_messages**: Stores all chat messages
- **subjects**: Predefined subject categories

## Usage

1. Select a subject from the dropdown (optional)
2. Type your question in the input field
3. Click "Send" or press Enter
4. The AI assistant will respond based on your question
5. Use "Clear Chat" to remove messages from the current view
6. Use "New Session" to start a fresh conversation

## Configuration

Edit `config/config.php` to customize:
- Database credentials (if different from XAMPP defaults)
- Groq API key
- Application settings

## Security Notes

- **Delete `setup.php`** after initial database setup
- **Never commit** your `config/config.php` with real API keys to version control
- Consider adding authentication for production use

## Troubleshooting

- **API Key Error**: Make sure you've set your Groq API key in `config/config.php`
- **Database Connection Error**: Check that MySQL is running in XAMPP
- **Composer Error**: Make sure Composer is installed and run `composer install` in the project directory

## License

This project is for educational purposes.

## Credits

- Groq API: https://groq.com/
- Groq PHP Library: https://github.com/lucianotonet/groq-php

