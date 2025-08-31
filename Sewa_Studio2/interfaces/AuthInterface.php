<?php
interface AuthInterface {
    public function register($name, $email, $password, $membership = "Regular", $saldo = 0);
    public function login($email, $password);
    public function getUserById($id);
}
?>