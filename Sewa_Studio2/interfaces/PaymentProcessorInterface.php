<?php
interface PaymentProcessorInterface {
    public function processTopUp($userId, $amount);
    public function processUpgrade($userId, $cost);
    public function validateSaldo($userId, $requiredAmount);
    public function updateSaldo($userId, $newSaldo);
}
?>