<?php
require_once __DIR__ . '/../interfaces/PaymentInterface.php';
require_once __DIR__ . '/../interfaces/UserInterface.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/MembershipManager.php';
require_once __DIR__ . '/PaymentProcessor.php';

class User implements PaymentInterface, UserInterface {
    protected $id;
    protected $name;
    protected $email;
    protected $password;
    protected $membership;
    protected $saldo;
    protected $membershipManager;
    protected $paymentProcessor;

    public function __construct($id, $name, $email, $password, $membership = "Regular", $saldo = 0) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->membership = $membership;
        $this->saldo = $saldo;
        $this->membershipManager = new MembershipManager($membership);
        $this->paymentProcessor = new PaymentProcessor();
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getEmail() { return $this->email; }
    public function getPassword() { return $this->password; }
    public function getMembership() { return $this->membership; }
    public function getSaldo() { return $this->saldo; }
    public function setSaldo($saldo) { 
        $this->saldo = $saldo; 
        $this->updateSaldoInDatabase();
    }

    private function updateSaldoInDatabase() {
        $this->paymentProcessor->updateSaldo($this->id, $this->saldo);
    }

    public function getDiscount() { 
        $benefits = $this->membershipManager->getMembershipBenefits();
        return $benefits['discount'] / 100;
    }
    
    public function getCashback() { 
        $benefits = $this->membershipManager->getMembershipBenefits();
        return $benefits['cashback'] / 100;
    }
    
    public function canAccessStudio($studioId) { 
        $benefits = $this->membershipManager->getMembershipBenefits();
        return in_array($studioId, $benefits['studios']);
    }
    
    public function getUpgradeCost() { 
        return $this->membershipManager->getUpgradeCost();
    }
    
    public function getNextMembership() { 
        return $this->membershipManager->getNextMembership();
    }

    public function upgradeMembership() {
        if (!$this->membershipManager->canUpgrade()) {
            throw new Exception("Membership sudah maksimal!");
        }

        $cost = $this->getUpgradeCost();
        $newSaldo = $this->paymentProcessor->processUpgrade($this->id, $cost);
        
        $this->membership = $this->getNextMembership();
        $this->saldo = $newSaldo;
        $this->membershipManager = new MembershipManager($this->membership);

        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE users SET membership = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        return $stmt->execute([$this->membership, $this->id]);
    }
}

class RegularUser extends User {
    public function __construct($id, $name, $email, $password, $membership = "Regular", $saldo = 0) {
        parent::__construct($id, $name, $email, $password, "Regular", $saldo);
    }
}

class VIPUser extends User {
    public function __construct($id, $name, $email, $password, $membership = "VIP", $saldo = 0) {
        parent::__construct($id, $name, $email, $password, "VIP", $saldo);
    }
}

class VVIPUser extends User {
    public function __construct($id, $name, $email, $password, $membership = "VVIP", $saldo = 0) {
        parent::__construct($id, $name, $email, $password, "VVIP", $saldo);
    }
}
?>