<?php
require_once __DIR__ . '/../interfaces/PaymentProcessorInterface.php';
require_once __DIR__ . '/../config/database.php';

class PaymentProcessor implements PaymentProcessorInterface {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function processTopUp($userId, $amount) {
        if ($amount < 50000) {
            throw new Exception("Minimal top up Rp 50.000");
        }

        $query = "UPDATE users SET saldo = saldo + :amount WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            return $this->getCurrentSaldo($userId);
        }
        throw new Exception("Gagal melakukan top up");
    }

    public function processUpgrade($userId, $cost) {
        $currentSaldo = $this->getCurrentSaldo($userId);
        
        if ($currentSaldo < $cost) {
            throw new Exception("Saldo tidak cukup untuk upgrade membership!");
        }

        $newSaldo = $currentSaldo - $cost;
        return $this->updateSaldo($userId, $newSaldo);
    }

    public function validateSaldo($userId, $requiredAmount) {
        $currentSaldo = $this->getCurrentSaldo($userId);
        return $currentSaldo >= $requiredAmount;
    }

    public function updateSaldo($userId, $newSaldo) {
        $query = "UPDATE users SET saldo = :saldo WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':saldo', $newSaldo);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            return $newSaldo;
        }
        throw new Exception("Gagal mengupdate saldo");
    }

    private function getCurrentSaldo($userId) {
        $query = "SELECT saldo FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['saldo'];
        }
        throw new Exception("User tidak ditemukan");
    }
}
?>