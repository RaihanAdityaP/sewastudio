<?php
require_once "User.php";

class Auth {
    private $file;

    public function __construct() {
        $this->file = __DIR__ . '/../data/users.json';
        if (!file_exists($this->file)) {
            file_put_contents($this->file, json_encode([]));
        }
    }

    private function loadUsers() {
        $json = file_get_contents($this->file);
        return json_decode($json, true);
    }

    private function saveUsers($users) {
        file_put_contents($this->file, json_encode($users, JSON_PRETTY_PRINT));
    }

    public function register($name, $email, $password, $membership = "Regular", $saldo = 0) {
        $users = $this->loadUsers();

        foreach ($users as $user) {
            if ($user['email'] === $email) {
                return false;
            }
        }

        $id = uniqid();
        $users[] = [
            "id" => $id,
            "name" => $name,
            "email" => $email,
            "password" => password_hash($password, PASSWORD_DEFAULT),
            "membership" => $membership,
            "saldo" => $saldo
        ];

        $this->saveUsers($users);
        return true;
    }

    public function login($email, $password) {
        $users = $this->loadUsers();

        foreach ($users as $user) {
            if ($user['email'] === $email && password_verify($password, $user['password'])) {
                return new User(
                    $user['id'],
                    $user['name'],
                    $user['email'],
                    $user['password'],
                    $user['membership'],
                    $user['saldo'] ?? 0
                );
            }
        }
        return null;
    }
}
?>