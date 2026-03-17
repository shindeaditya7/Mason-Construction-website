<?php
/**
 * Mason Construction Services Inc.
 * Admin Authentication Class
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

class Admin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Attempt to log in an admin user
     */
    public function login($username, $password) {
        $username = sanitize($username);

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required.'];
        }

        try {
            $user = $this->db->fetchOne(
                'SELECT id, username, password_hash, full_name, email, is_active
                 FROM admin_users WHERE username = ? LIMIT 1',
                [$username]
            );

            if (!$user) {
                // Prevent timing attacks with a dummy verify
                password_verify($password, '$2y$12$dummyhashfortimingnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnn');
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }

            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Your account has been deactivated. Contact support.'];
            }

            if (!password_verify($password, $user['password_hash'])) {
                $this->logFailedAttempt($username);
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }

            // Successful login - regenerate session ID to prevent fixation
            session_regenerate_id(true);

            $_SESSION['admin_id']        = $user['id'];
            $_SESSION['admin_username']  = $user['username'];
            $_SESSION['admin_name']      = $user['full_name'];
            $_SESSION['admin_email']     = $user['email'];
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['last_activity']   = time();
            $_SESSION['login_time']      = time();

            // Update last login timestamp
            $this->db->query(
                'UPDATE admin_users SET last_login = NOW() WHERE id = ?',
                [$user['id']]
            );

            return [
                'success'  => true,
                'message'  => 'Login successful.',
                'username' => $user['username'],
                'name'     => $user['full_name'],
            ];
        } catch (PDOException $e) {
            error_log('Admin login error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }

    /**
     * Log out the current admin session
     */
    public function logout() {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }

    /**
     * Check if the current session is authenticated
     */
    public function isAuthenticated() {
        return isset($_SESSION['admin_id']) && isset($_SESSION['admin_logged_in'])
            && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Get current admin info
     */
    public function getCurrentAdmin() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        return [
            'id'       => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'name'     => $_SESSION['admin_name'],
            'email'    => $_SESSION['admin_email'],
        ];
    }

    /**
     * Log a failed login attempt (for auditing)
     */
    private function logFailedAttempt($username) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        error_log("Failed login attempt for username '{$username}' from IP {$ip}");
    }
}
