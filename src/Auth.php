<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

class Auth {
    public static function login(string $username, string $password): bool {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    }

    public static function logout(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    public static function currentUser(): ?array {
        global $pdo;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['user_id'])) return null;
        $stmt = $pdo->prepare('SELECT id,name,username,role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $u = $stmt->fetch();
        return $u ?: null;
    }

    public static function requireLogin(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['user_id'])) {
            // During PHPUnit runs we avoid redirects/exits so tests can include pages
            if (defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING) return;
            header('Location: login.php');
            exit;
        }
    }

    public static function isAdmin(): bool {
        $u = self::currentUser();
        return $u && ($u['role'] === 'admin');
    }
}
