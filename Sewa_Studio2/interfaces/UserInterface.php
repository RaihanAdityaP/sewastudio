<?php
interface UserInterface {
    public function getId();
    public function getName();
    public function getEmail();
    public function getPassword();
    public function getMembership();
    public function getSaldo();
    public function setSaldo($saldo);
    public function upgradeMembership();
}
?>