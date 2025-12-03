# Installation Guide

## Quick Setup (No Composer Required!)

This project uses cURL to communicate with Groq API directly, so you don't need Composer installed.

## Step-by-Step Installation

### 1. Start XAMPP Services
- Open XAMPP Control Panel
- Start **Apache** and **MySQL** services

### 2. Configure API Key
- Open `config/config.php`
- Replace `'your_groq_api_key_here'` with your actual Groq API key
- Get your API key from: https://console.groq.com/keys

### 3. Create Database
- Open your browser
- Go to: `http://localhost/prog_project/setup.php`
- You should see success messages for each database table created
- **Important**: After setup, delete `setup.php` for security

### 4. Access the Application
- Open: `http://localhost/prog_project/index.php`
- Start chatting!

## Alternative: Using Composer (Optional)

If you prefer to use the Groq PHP library instead of direct cURL:

1. **Install Composer** (if not already installed):
   - Download from: https://getcomposer.org/download/
   - Or use: `php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"`
   - Run: `php composer-setup.php`

2. **Install Dependencies**:
   ```bash
   composer install
   ```

3. **Update api/chat.php** to use the Groq PHP library (see original version in git history)

## Troubleshooting

### Database Connection Error
- Make sure MySQL is running in XAMPP
- Check that `DB_USER` and `DB_PASS` in `config/config.php` match your XAMPP MySQL settings (default: root, no password)

### API Key Error
- Make sure you've set your Groq API key in `config/config.php`
- Verify the API key is correct at https://console.groq.com/keys

### cURL Not Available
- cURL should be enabled by default in XAMPP
- If not, uncomment `extension=curl` in `php.ini`

### 404 Error
- Make sure the project is in: `C:\xampp_lite_8_4\www\prog_project`
- Check that Apache is running

## Project Structure

```
prog_project/
├── api/
│   └── chat.php              # Backend API endpoint
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── chat.js
├── config/
│   ├── config.php            # Configuration
│   └── db.php                 # Database class
├── index.php                  # Main interface
├── setup.php                  # Database setup (delete after use)
└── README.md
```

## Next Steps

1. ✅ Set your Groq API key
2. ✅ Run setup.php to create database
3. ✅ Delete setup.php
4. ✅ Start using the chatbot!

Enjoy your Smart Study Assistant! 🎓

