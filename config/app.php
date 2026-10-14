<?php
/**
 * App Configuration
 */
define('APP_NAME', 'Sistem Informasi Gudang Bantuan Bencana');
define('APP_SHORT', 'SIGudang');
define('APP_VERSION', '1.0.0');

// Deteksi BASE_URL secara cerdas:
// 1. Jika diakses via http://localhost/RPL-Pertemuan4/ -> BASE_URL = "/RPL-Pertemuan4"
// 2. Jika diakses via virtualhost http://rpl-pertemuan4.test/ -> BASE_URL = ""
if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $folderName = basename(dirname(__DIR__)); // "RPL-Pertemuan4"

    if (preg_match('#^/(' . preg_quote($folderName, '#') . ')#i', $scriptName, $matches)) {
        define('BASE_URL', '/' . $matches[1]);
    } else {
        define('BASE_URL', '');
    }
}

define('SESSION_NAME', 'sigudang_sess');
define('ITEMS_PER_PAGE', 10);
define('CSRF_TOKEN_NAME', '_csrf_token');

// Error reporting (set false in production)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

date_default_timezone_set('Asia/Jakarta');
