<?php
require_once __DIR__ . '/../interfaces/SessionInterface.php';

class SessionManager implements SessionInterface {
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            return session_start();
        }
        return true;
    }

    public function setUser($user) {
        $_SESSION['user'] = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'membership' => $user->getMembership(),
            'saldo' => $user->getSaldo()
        ];
    }

    public function getUser() {
        return $_SESSION['user'] ?? null;
    }

    public function updateUser($userData) {
        if (isset($_SESSION['user'])) {
            $_SESSION['user'] = array_merge($_SESSION['user'], $userData);
        }
    }

    public function destroySession() {
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user']);
    }
}
?>