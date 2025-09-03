<?php
interface SessionInterface {
    public function startSession();
    public function setUser($user);
    public function getUser();
    public function updateUser($userData);
    public function destroySession();
    public function isLoggedIn();
}
?>