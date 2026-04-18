<?php
/**
 * PrifyPay - Sample Configuration
 * 
 * Copy this file to config.php and fill in your actual credentials.
 *   cp config.sample.php config.php
 */

// ── Base URL (no trailing slash) ──
define('BASE_URL', 'https://yourdomain.com');

// ── Callback & Redirect URLs ──
define('PAYIN_CALLBACK_URL',  BASE_URL . '/callbacks/payin.php');
define('PAYIN_REDIRECT_URL',  BASE_URL . '/retailer/index.php');
define('PAYOUT_CALLBACK_URL', BASE_URL . '/callbacks/payout.php');

// ── Database Credentials ──
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'prifypay');

// ── SLPE API Credentials ──
define('API_BASE_URL', 'https://api.slpe.in/api/v2/');
define('API_MODE',     'live');
define('API_SECRET',   'your_api_secret_here');
define('API_KEY',      'your_api_key_here');
define('ACCESS_TOKEN', 'your_access_token_here');

// ── Gateway IDs ──
define('PAYIN_GATEWAY_ID',  '8');
define('PAYOUT_GATEWAY_ID', '10');
