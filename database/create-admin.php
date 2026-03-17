<?php
/**
 * Mason Construction Services Inc.
 * Admin User Creation Script
 *
 * Run this script ONCE from the command line or browser to create the admin user:
 *   php create-admin.php
 *
 * After running, DELETE or RENAME this file to prevent unauthorized access.
 */

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/classes/Database.php';

// -------------------------------------------------------
// Admin credentials – prefer environment variables or CLI arguments.
// CLI usage: php create-admin.php <username> <password> <full_name> <email>
// Environment:  ADMIN_USERNAME, ADMIN_PASSWORD, ADMIN_FULL_NAME, ADMIN_EMAIL
$admin_username  = getenv('ADMIN_USERNAME') ?: (isset($argv[1]) ? $argv[1] : 'admin');
$admin_password  = getenv('ADMIN_PASSWORD') ?: (isset($argv[2]) ? $argv[2] : null);
$admin_full_name = getenv('ADMIN_FULL_NAME') ?: (isset($argv[3]) ? $argv[3] : 'Jitesh Admin');
$admin_email     = getenv('ADMIN_EMAIL')    ?: (isset($argv[4]) ? $argv[4] : 'mason@themasonconstruction.com');

if (!$admin_password) {
    // Prompt securely when running in CLI
    if (PHP_SAPI === 'cli') {
        echo 'Enter admin password: ';
        system('stty -echo');
        $admin_password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        die("Error: ADMIN_PASSWORD environment variable is required.\n");
    }
}
// -------------------------------------------------------

$hash = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    $db = Database::getInstance();
    $db->query(
        'INSERT INTO admin_users (username, password_hash, full_name, email, is_active, created_at)
         VALUES (?, ?, ?, ?, 1, NOW())
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), full_name = VALUES(full_name)',
        [$admin_username, $hash, $admin_full_name, $admin_email]
    );
    echo "Admin user '{$admin_username}' created/updated successfully.\n";
    echo "Password hash: {$hash}\n";
    echo "\nIMPORTANT: Delete this file now!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
