<?php
class User {
    protected $id;
    protected $name;
    protected $email;
    protected $password;
    protected $membership;
    protected $saldo;

    public function __construct($id, $name, $email, $password, $membership = "Regular", $saldo = 0) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->membership = $membership;
        $this->saldo = $saldo;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getEmail() { return $this->email; }
    public function getPassword() { return $this->password; }
    public function getMembership() { return $this->membership; }
    public function getSaldo() { return $this->saldo; }
    public function setSaldo($saldo) { $this->saldo = $saldo; }

    public function getDiscount() { return 0; }
    public function getCashback() { return 0; }
}

class RegularUser extends User {
    public function getDiscount() { return 0; }
    public function getCashback() { return 0; }
}

class VIPUser extends User {
    public function getDiscount() { return 0.1; }   // 10%
    public function getCashback() { return 0.05; } // 5%
}

class VVIPUser extends User {
    public function getDiscount() { return 0.2; }   // 20%
    public function getCashback() { return 0.1; }  // 10%
}
?>